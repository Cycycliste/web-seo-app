<?php
class Crawler {
    public static function fetchAndAnalyze($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid URL format.");
        }

        // SSRF guard: reject loopback, link-local, RFC1918, and other reserved IPs
        // before opening a socket. Caller never sees why beyond a generic message.
        if (!self::isFetchableUrl($url)) {
            throw new Exception("Refusing to fetch non-public address.");
        }

        $res = self::fetchUrl($url);

        if (!empty($res['error'])) {
            throw new Exception("Connection failed: " . $res['error']);
        }

        if ($res['http_code'] === 403) {
            throw new Exception("Access blocked by target server's bot protection (HTTP 403 Forbidden)");
        }

        if ($res['http_code'] !== 200) {
            throw new Exception("Server returned HTTP status code " . $res['http_code']);
        }

        if (empty($res['content'])) {
            throw new Exception("Failed to fetch the webpage content. Make sure the URL is accessible.");
        }

        return self::analyzeHtml($res['content'], $url);
    }

    /**
     * Tell whether a URL is safe to fetch server-side.
     *
     * Only http/https schemes are accepted. The host must resolve to at least one
     * IP, and every resolved IP must be a public (non-private, non-reserved) address.
     * Bare IP literals (v4 or v6) are validated directly with the same rules.
     *
     * Note: this resolves DNS once and cURL resolves again at connect time, so a
     * hostile DNS server could in theory flip the answer between the two. Combined
     * with redirects-disabled and a short timeout, the practical risk is low.
     *
     * @param string $url Candidate URL.
     * @return bool       True when every resolved address is public.
     */
    public static function isFetchableUrl($url) {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        // Normalize the host: lowercase, strip trailing dot, convert IDN/Punycode to
        // ASCII so libcurl and our resolver see the same name.
        $host = strtolower(rtrim($parts['host'], '.'));
        if ($host === '') {
            return false;
        }
        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (is_string($ascii) && $ascii !== '') {
                $host = $ascii;
            }
        }

        $ips = [];

        // Direct IP literal (handles v4 and bracketed v6).
        $literal = trim($host, '[]');
        if (filter_var($literal, FILTER_VALIDATE_IP)) {
            $ips[] = $literal;
        } else {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $rec) {
                if (!empty($rec['ip']))   { $ips[] = $rec['ip']; }
                if (!empty($rec['ipv6'])) { $ips[] = $rec['ipv6']; }
            }
            // Fail closed: if DNS returns nothing, refuse rather than guessing via
            // gethostbynamel, which can yield IPv4 results for a host whose AAAA
            // record points at loopback and would be preferred by libcurl.
        }

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                return false;
            }
        }
        return true;
    }

    /**
     * True only for IPs that should be reachable from the public internet.
     *
     * Layers PHP's FILTER_FLAG_NO_PRIV_RANGE / NO_RES_RANGE checks with extra
     * rejects for ranges PHP doesn't class as reserved: CGNAT, IPv4-mapped IPv6
     * (e.g. ::ffff:127.0.0.1 — would otherwise tunnel to localhost), and the
     * NAT64 / 6to4 well-known prefixes.
     *
     * @param string $ip
     * @return bool
     */
    private static function isPublicIp($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // IPv4-mapped IPv6: ::ffff:1.2.3.4 — re-validate the embedded v4 portion.
        if (stripos($ip, '::ffff:') === 0) {
            $v4 = substr($ip, 7);
            return self::isPublicIp($v4);
        }

        // 6to4 (2002::/16): the v4 sits in the next 32 bits.
        if (strncasecmp($ip, '2002:', 5) === 0) {
            $hex = inet_pton($ip);
            if ($hex !== false && strlen($hex) === 16) {
                $v4 = long2ip(unpack('N', substr($hex, 2, 4))[1]);
                if (!self::isPublicIp($v4)) {
                    return false;
                }
            }
        }

        // Standard reserved/private check.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // Extra rejects PHP's flags don't cover.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // 100.64.0.0/10 — CGNAT (RFC 6598).
            if (self::ipv4InCidr($ip, '100.64.0.0', 10)) {
                return false;
            }
            // 169.254.169.254 (cloud metadata) is technically link-local and caught by
            // NO_RES_RANGE, but be explicit so a future PHP change can't quietly drop it.
            if ($ip === '169.254.169.254') {
                return false;
            }
        }

        return true;
    }

    /**
     * Tell whether an IPv4 address falls inside a CIDR block.
     *
     * @param string $ip       Dotted-quad IPv4.
     * @param string $network  Network address.
     * @param int    $maskBits 0..32.
     * @return bool
     */
    private static function ipv4InCidr($ip, $network, $maskBits) {
        $ipLong  = ip2long($ip);
        $netLong = ip2long($network);
        if ($ipLong === false || $netLong === false) {
            return false;
        }
        $mask = $maskBits === 0 ? 0 : (~0 << (32 - $maskBits)) & 0xFFFFFFFF;
        return ($ipLong & $mask) === ($netLong & $mask);
    }

    private static function fetchUrl($url) {
        $ch = curl_init();
        $maxBytes = 5 * 1024 * 1024; // 5 MiB response cap.
        $body     = '';

        $protocols = defined('CURLPROTO_HTTP') ? (CURLPROTO_HTTP | CURLPROTO_HTTPS) : 0;

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            // Redirects disabled: a 3xx Location could otherwise re-introduce SSRF
            // by pointing the second hop at a private address after the first hop
            // passed isFetchableUrl().
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false, // local cert path issues
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0',
            // Hard cap response size by aborting the write callback when over limit.
            CURLOPT_WRITEFUNCTION  => function ($curl, $data) use (&$body, $maxBytes) {
                $body .= $data;
                if (strlen($body) > $maxBytes) {
                    return 0; // signals abort to libcurl
                }
                return strlen($data);
            },
        ];
        if ($protocols !== 0) {
            $opts[CURLOPT_PROTOCOLS] = $protocols;
        }
        curl_setopt_array($ch, $opts);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return [
            'content'   => $body,
            'http_code' => $httpCode,
            'error'     => $error,
        ];
    }

    private static function analyzeHtml($html, $url) {
        $dom = new DOMDocument();
        // Prevent warnings for invalid HTML
        libxml_use_internal_errors(true);
        
        // Convert encoding to UTF-8 if necessary
        // A common trick to prevent DOMDocument from messing up character sets:
        // Prefix with UTF-8 declaration if not present.
        if (strpos($html, '<?xml') === false && strpos($html, '<meta http-equiv="content-type"') === false && strpos($html, '<meta charset') === false) {
            $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html;
        }
        
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 1. Meta Title
        $titleNode = $xpath->query('//title');
        $title = '';
        if ($titleNode->length > 0) {
            $title = trim($titleNode->item(0)->textContent);
        }
        $titleLength = mb_strlen($title, 'UTF-8');

        // 2. Meta Description
        $descNodes = $xpath->query('//meta[@name="description" or @name="Description"]/@content');
        $description = '';
        if ($descNodes->length > 0) {
            $description = trim($descNodes->item(0)->nodeValue);
        }
        $descriptionLength = mb_strlen($description, 'UTF-8');

        // 3. Headers count (H1 - H6) & Structure
        $headersCount = [
            'h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0
        ];
        $headersStructure = [];
        
        // Find all headings
        $headingNodes = $xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');
        foreach ($headingNodes as $node) {
            $tag = strtolower($node->nodeName);
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if (!empty($text)) {
                $headersCount[$tag]++;
                $headersStructure[] = [
                    'tag' => strtoupper($tag),
                    'text' => $text
                ];
            }
        }

        // H1 Main text
        $h1Text = '';
        $h1Nodes = $xpath->query('//h1');
        if ($h1Nodes->length > 0) {
            $h1Text = trim($h1Nodes->item(0)->textContent);
        }
        $h1Length = mb_strlen($h1Text, 'UTF-8');

        // 4. Links analysis (internal vs external)
        $parsedUrl = parse_url($url);
        $baseHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
        
        $linkNodes = $xpath->query('//a[@href]');
        $internalCount = 0;
        $externalCount = 0;
        $internalUrls = [];

        foreach ($linkNodes as $link) {
            $href = trim($link->getAttribute('href'));
            
            // Ignore anchor fragments, mailto, tel, javascript links
            if (empty($href) || strpos($href, '#') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0 || strpos($href, 'javascript:') === 0) {
                continue;
            }

            $parsedHref = parse_url($href);
            if (!isset($parsedHref['host']) || empty($parsedHref['host'])) {
                // Relative URL is internal
                $internalCount++;
                $resolved = self::resolveUrl($url, $href);
                if (filter_var($resolved, FILTER_VALIDATE_URL)) {
                    $internalUrls[] = $resolved;
                }
            } else {
                $hrefHost = strtolower($parsedHref['host']);
                // Normalize www
                $normBase = preg_replace('/^www\./', '', $baseHost);
                $normHref = preg_replace('/^www\./', '', $hrefHost);
                
                if ($normBase === $normHref) {
                    $internalCount++;
                    $internalUrls[] = $href;
                } else {
                    $externalCount++;
                }
            }
        }

        // 5. Images analysis (alt text)
        $imgNodes = $xpath->query('//img');
        $totalImages = $imgNodes->length;
        $missingAltCount = 0;

        foreach ($imgNodes as $img) {
            $alt = $img->getAttribute('alt');
            // If alt attribute doesn't exist or is empty (whitespace only or completely absent)
            // Note: sometimes a decorative image has alt="", which is technically an empty attribute.
            // But for SEO audits, we usually check for absent alt or empty string. Let's count missing alt attributes.
            if (!$img->hasAttribute('alt') || trim($alt) === '') {
                $missingAltCount++;
            }
        }

        return [
            'meta_title' => $title,
            'meta_title_len' => $titleLength,
            'meta_description' => $description,
            'meta_description_len' => $descriptionLength,
            'h1' => $h1Text,
            'h1_len' => $h1Length,
            'h1_count' => $headersCount['h1'],
            'h2_count' => $headersCount['h2'],
            'h3_count' => $headersCount['h3'],
            'h4_count' => $headersCount['h4'],
            'h5_count' => $headersCount['h5'],
            'h6_count' => $headersCount['h6'],
            'headers_structure' => $headersStructure,
            'internal_links' => $internalCount,
            'external_links' => $externalCount,
            'missing_alt_images' => $missingAltCount,
            'internal_urls' => array_values(array_unique($internalUrls))
        ];
    }

    public static function resolveUrl($base, $relative) {
        if (empty($relative)) {
            return $base;
        }
        if (parse_url($relative, PHP_URL_SCHEME) != '') {
            return $relative;
        }
        
        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $path = $baseParts['path'] ?? '/';

        if (substr($relative, 0, 2) === '//') {
            return $scheme . ':' . $relative;
        }

        if ($relative[0] === '/') {
            return $scheme . '://' . $host . $port . $relative;
        }

        if ($relative[0] === '?') {
            return $scheme . '://' . $host . $port . preg_replace('/\?.*$/', '', $path) . $relative;
        }

        // Relative path resolution
        if (substr($path, -1) === '/') {
            $dir = $path;
        } else {
            $lastSlash = strrpos($path, '/');
            if ($lastSlash !== false) {
                $dir = substr($path, 0, $lastSlash + 1);
            } else {
                $dir = '/';
            }
        }
        
        // Remove ./
        if (substr($relative, 0, 2) === './') {
            $relative = substr($relative, 2);
        }

        // Resolve ../
        while (substr($relative, 0, 3) === '../') {
            $relative = substr($relative, 3);
            $dir = rtrim($dir, '/');
            $lastSlash = strrpos($dir, '/');
            if ($lastSlash !== false) {
                $dir = substr($dir, 0, $lastSlash + 1);
            } else {
                $dir = '/';
            }
        }

        return $scheme . '://' . $host . $port . '/' . ltrim($dir . $relative, '/');
    }
}
