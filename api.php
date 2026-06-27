<?php
require_once 'db.php';
require_once 'crawler.php';

// Production error policy: never leak warnings, stack traces, SQL fragments, or paths
// to the HTTP response. Errors are logged server-side; the response stays generic.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
safe_session_start();

// Issue a per-session CSRF token on the very first request that touches the
// session; the SPA picks it up via `status` and echoes it back as X-CSRF-Token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? '';

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);

if (!$isAuthenticated && $action !== 'login' && $action !== 'get_share_audit') {
    echo json_encode(['error' => 'Unauthorized. Please log in.', 'code' => 401]);
    exit;
}

// CSRF guard: every state-changing or session-touching action must present the
// session's CSRF token in the X-CSRF-Token header. Exempt: token-bootstrap
// (status), credential-bootstrap (login), and the public token-gated read view
// (get_share_audit). `logout` is NOT exempt — we don't want CSRF logouts.
$csrf_exempt = ['login', 'status', 'get_share_audit'];
if (!in_array($action, $csrf_exempt, true)) {
    $supplied = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($supplied) || !hash_equals($_SESSION['csrf_token'] ?? '', $supplied)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token. Reload the page and try again.']);
        exit;
    }
}

/**
 * Validate and store an uploaded image, rejecting anything that is not a real image.
 *
 * Trusts the file's real MIME type via finfo, not the client-supplied filename or
 * Content-Type, then forces a random server-side filename with the canonical extension.
 *
 * @param array  $file     A single $_FILES entry, e.g. $_FILES['main_channels_file'].
 * @param string $dest_dir Absolute or relative path to the upload directory.
 * @param string $prefix   Filename prefix, e.g. 'main_channels'.
 * @param int    $owner_id Owner id (audit/page/competitor) used in the generated filename.
 * @return string|false    Relative stored path (uploads/...) on success, false on rejection.
 */
function store_uploaded_image($file, $dest_dir, $prefix, $owner_id) {
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mime || !isset($allowed[$mime])) {
        return false;
    }

    // Confirm it actually decodes as an image, not just a matching magic header.
    if (getimagesize($file['tmp_name']) === false) {
        return false;
    }

    if (!is_dir($dest_dir) && !mkdir($dest_dir, 0777, true) && !is_dir($dest_dir)) {
        return false;
    }

    $ext  = $allowed[$mime];
    $name = sprintf(
        '%s_%d_%d_%s.%s',
        preg_replace('/[^A-Za-z0-9_]/', '', $prefix),
        (int)$owner_id,
        time(),
        bin2hex(random_bytes(8)),
        $ext
    );
    $path = rtrim($dest_dir, '/\\') . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return false;
    }

    return 'uploads/' . $name;
}

// Default lifetime, in days, of a freshly-minted share-link token.
if (!defined('SHARE_TOKEN_DEFAULT_DAYS')) {
    define('SHARE_TOKEN_DEFAULT_DAYS', 90);
}

/**
 * Log the real exception server-side and emit a sanitized JSON error to the client.
 *
 * The exception's full text (which can include the failed SQL statement, file
 * paths, or other implementation detail) goes to the PHP error log only; the
 * HTTP response carries the generic prefix the caller passed in.
 *
 * @param string    $prefix Human-readable label describing the failed step.
 * @param Throwable $e      The thrown exception.
 * @return void
 */
function report_error($prefix, Throwable $e) {
    error_log($prefix . ': ' . $e->getMessage());
    echo json_encode(['error' => $prefix . '.']);
}

/**
 * Validate a client-supplied stored-image path.
 *
 * Accepts only paths that look like one produced by store_uploaded_image() — i.e.
 * inside uploads/, no directory traversal, and ending in an allowed image extension.
 *
 * @param string $path The candidate path.
 * @return bool  True when the path is shaped like a stored upload.
 */
function is_safe_upload_path($path) {
    if (!is_string($path) || $path === '') {
        return false;
    }
    return (bool)preg_match('#^uploads/[A-Za-z0-9_\-]+\.(?:png|jpe?g|webp|gif)$#', $path);
}

// -------------------------------------------------------------
// Core Actions
// -------------------------------------------------------------

try {
switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['error' => 'Please enter username and password.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Always run password_verify against a real bcrypt hash so the response
        // time doesn't reveal whether the username exists (timing-oracle defense).
        // The dummy hash never verifies against any password.
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuO0Mn1mFqsCFkS0Mz5x.aBdCEfGhIjKlM';
        $hashToCheck = $user ? $user['password_hash'] : $dummyHash;
        $verified = password_verify($password, $hashToCheck);

        if ($user && $verified) {
            // Regenerate the session ID on successful auth to defeat session fixation:
            // a pre-login session id can't survive into the authenticated state.
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            // Rotate the CSRF token too so a pre-auth token can't be re-used.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            echo json_encode([
                'success'    => true,
                'message'    => 'Logged in successfully.',
                'csrf_token' => $_SESSION['csrf_token'],
            ]);
        } else {
            echo json_encode(['error' => 'Invalid username or password.']);
        }
        break;

    case 'logout':
        // Tear down session state in-process AND delete the cookie so the same
        // PHPSESSID can't be reused by setting it back from the browser.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'status':
        echo json_encode([
            'logged_in'  => $isAuthenticated,
            'username'   => $_SESSION['username'] ?? null,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
        break;

    // -------------------------------------------------------------
    // Clients
    // -------------------------------------------------------------
    case 'clients_list':
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE name LIKE ? OR homepage_url LIKE ? OR industry LIKE ? ORDER BY name ASC");
            $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'client_create':
        $name = trim($_POST['name'] ?? '');
        $homepage_url = trim($_POST['homepage_url'] ?? '');
        $industry = trim($_POST['industry'] ?? '');

        if (empty($name) || empty($homepage_url)) {
            echo json_encode(['error' => 'Client name and homepage URL are required.']);
            exit;
        }

        // Validate homepage URL format
        if (!filter_var($homepage_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid homepage URL format. Make sure to include http:// or https://.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO clients (name, homepage_url, industry) VALUES (?, ?, ?)");
            $stmt->execute([$name, $homepage_url, $industry]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            report_error('Failed to create client', $e);
        }
        break;

    case 'client_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid client ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'client_update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $homepage_url = trim($_POST['homepage_url'] ?? '');
        $industry = trim($_POST['industry'] ?? '');

        if ($id <= 0 || empty($name) || empty($homepage_url)) {
            echo json_encode(['error' => 'Client ID, name, and homepage URL are required.']);
            exit;
        }

        // Validate homepage URL format
        if (!filter_var($homepage_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid homepage URL format. Make sure to include http:// or https://.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, homepage_url = ?, industry = ? WHERE id = ?");
            $stmt->execute([$name, $homepage_url, $industry, $id]);
            
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $client = $stmt->fetch();
            
            echo json_encode(['success' => true, 'client' => $client]);
        } catch (PDOException $e) {
            report_error('Failed to update client', $e);
        }
        break;

    // -------------------------------------------------------------
    // Audits
    // -------------------------------------------------------------
    case 'audits_list':
        $client_id = (int)($_GET['client_id'] ?? 0);
        if ($client_id <= 0) {
            echo json_encode(['error' => 'Invalid client ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM audits WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$client_id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'audit_create':
        $client_id = (int)($_POST['client_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($client_id <= 0 || empty($name)) {
            echo json_encode(['error' => 'Client and audit name are required.']);
            exit;
        }

        $share_token = bin2hex(random_bytes(16));
        $expires_at  = (new DateTimeImmutable('+' . SHARE_TOKEN_DEFAULT_DAYS . ' days'))->format('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare("INSERT INTO audits (client_id, name, share_token, share_token_expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$client_id, $name, $share_token, $expires_at]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            report_error('Failed to create audit', $e);
        }
        break;

    case 'audit_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM audits WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'audit_get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT a.*, c.name as client_name, c.homepage_url as client_homepage_url FROM audits a JOIN clients c ON a.client_id = c.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $audit = $stmt->fetch();

        if (!$audit) {
            echo json_encode(['error' => 'Audit not found.']);
            exit;
        }

        // Fetch pages
        $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $pages = $stmt->fetchAll();

        // Fetch search terms
        $stmt = $pdo->prepare("SELECT * FROM search_terms WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $search_terms = $stmt->fetchAll();

        // Fetch competitors
        $stmt = $pdo->prepare("SELECT * FROM competitors WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $competitors = $stmt->fetchAll();

        // Fetch competitor analyses
        $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $competitor_analyses = $stmt->fetchAll();

        // Fetch core web vitals
        $stmt = $pdo->prepare("SELECT * FROM core_web_vitals WHERE audit_id = ?");
        $stmt->execute([$id]);
        $cwt = $stmt->fetch();

        echo json_encode([
            'audit' => $audit,
            'pages' => $pages,
            'search_terms' => $search_terms,
            'competitors' => $competitors,
            'competitor_analyses' => $competitor_analyses,
            'core_web_vitals' => $cwt ? $cwt : null
        ]);
        break;

    case 'audit_save_metrics':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        $bounce_rate = $_POST['bounce_rate'] === '' ? null : (float)$_POST['bounce_rate'];
        $pages_per_visit = $_POST['pages_per_visit'] === '' ? null : (float)$_POST['pages_per_visit'];
        $avg_monthly_visits = $_POST['avg_monthly_visits'] === '' ? null : (int)$_POST['avg_monthly_visits'];
        $avg_visit_duration = $_POST['avg_visit_duration'] === '' ? null : (int)$_POST['avg_visit_duration'];
        $breakdown_by_country = $_POST['breakdown_by_country'] ?? '';
        $sitemap_details = $_POST['sitemap_details'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        $global_analysis = $_POST['global_analysis'] ?? null;
        $global_strategy = $_POST['global_strategy'] ?? null;
        $global_ranking = $_POST['global_ranking'] === '' ? null : (int)$_POST['global_ranking'];
        $country_ranking = $_POST['country_ranking'] === '' ? null : (int)$_POST['country_ranking'];
        $target_country = trim($_POST['target_country'] ?? "Website's Country");
        if ($target_country === '') {
            $target_country = "Website's Country";
        }

        // Fetch current audit paths first to clean up old files if they are replaced or deleted
        $stmt = $pdo->prepare("SELECT main_channels, traffic_trends FROM audits WHERE id = ?");
        $stmt->execute([$id]);
        $currentAudit = $stmt->fetch();
        $oldChannels = $currentAudit['main_channels'] ?? '';
        $oldTrends = $currentAudit['traffic_trends'] ?? '';

        // Client-supplied paths are only accepted when they match the shape of a stored
        // upload — anything else is coerced to empty so an attacker can't smuggle
        // arbitrary URLs (e.g. javascript:, data:, https://evil/) into the share view.
        $main_channels = $_POST['main_channels'] ?? '';
        if ($main_channels !== '' && !is_safe_upload_path($main_channels)) {
            $main_channels = '';
        }
        $traffic_trends = $_POST['traffic_trends'] ?? '';
        if ($traffic_trends !== '' && !is_safe_upload_path($traffic_trends)) {
            $traffic_trends = '';
        }

        if (isset($_FILES['main_channels_file']) && $_FILES['main_channels_file']['error'] === UPLOAD_ERR_OK) {
            $stored = store_uploaded_image($_FILES['main_channels_file'], 'uploads', 'main_channels', $id);
            if ($stored === false) {
                echo json_encode(['error' => 'Main channels file rejected. Only PNG, JPG, WEBP, or GIF images are allowed.']);
                exit;
            }
            $main_channels = $stored;
            if (!empty($oldChannels) && is_safe_upload_path($oldChannels) && file_exists($oldChannels) && $oldChannels !== $stored) {
                @unlink($oldChannels);
            }
        } else if (empty($main_channels) && !empty($oldChannels)) {
            if (is_safe_upload_path($oldChannels) && file_exists($oldChannels)) {
                @unlink($oldChannels);
            }
        }

        if (isset($_FILES['traffic_trends_file']) && $_FILES['traffic_trends_file']['error'] === UPLOAD_ERR_OK) {
            $stored = store_uploaded_image($_FILES['traffic_trends_file'], 'uploads', 'traffic_trends', $id);
            if ($stored === false) {
                echo json_encode(['error' => 'Traffic trends file rejected. Only PNG, JPG, WEBP, or GIF images are allowed.']);
                exit;
            }
            $traffic_trends = $stored;
            if (!empty($oldTrends) && is_safe_upload_path($oldTrends) && file_exists($oldTrends) && $oldTrends !== $stored) {
                @unlink($oldTrends);
            }
        } else if (empty($traffic_trends) && !empty($oldTrends)) {
            if (is_safe_upload_path($oldTrends) && file_exists($oldTrends)) {
                @unlink($oldTrends);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE audits SET bounce_rate = ?, pages_per_visit = ?, avg_monthly_visits = ?, avg_visit_duration = ?, breakdown_by_country = ?, main_channels = ?, traffic_trends = ?, sitemap_details = ?, additional_notes = ?, global_analysis = ?, global_strategy = ?, global_ranking = ?, country_ranking = ?, target_country = ? WHERE id = ?");
            $stmt->execute([$bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration, $breakdown_by_country, $main_channels, $traffic_trends, $sitemap_details, $additional_notes, $global_analysis, $global_strategy, $global_ranking, $country_ranking, $target_country, $id]);
            echo json_encode([
                'success' => true,
                'main_channels' => $main_channels,
                'traffic_trends' => $traffic_trends
            ]);
        } catch (PDOException $e) {
            report_error('Failed to save audit metrics', $e);
        }
        break;

    // -------------------------------------------------------------
    // Website Audit Pages
    // -------------------------------------------------------------
    case 'page_add':
        @set_time_limit(120); // Prevent PHP execution timeout
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $mode = $_POST['mode'] ?? 'single'; // 'single' (meaning pasted URLs) or 'website'

        if ($audit_id <= 0) {
            echo json_encode(['error' => 'Audit ID is required.']);
            exit;
        }

        if ($mode === 'website') {
            $seed_url = trim($_POST['url'] ?? '');
            if (empty($seed_url)) {
                echo json_encode(['error' => 'Starting page URL is required.']);
                exit;
            }
            if (!filter_var($seed_url, FILTER_VALIDATE_URL)) {
                echo json_encode(['error' => 'Invalid starting URL format. Make sure to include http:// or https://.']);
                exit;
            }

            $max_pages = (int)($_POST['max_pages'] ?? 10);
            if ($max_pages < 1) $max_pages = 1;
            if ($max_pages > 50) $max_pages = 50;

            try {
                $parsedSeed = parse_url($seed_url);
                $seedHost = isset($parsedSeed['host']) ? strtolower($parsedSeed['host']) : '';
                $normSeedHost = preg_replace('/^www\./', '', $seedHost);

                $queue = [$seed_url];
                $visited = [$seed_url => true];
                $addedPages = [];
                $errors = [];

                while (!empty($queue) && count($addedPages) < $max_pages) {
                    $currentUrl = array_shift($queue);

                    // Skip duplicates in database for this audit
                    $stmt = $pdo->prepare("SELECT id FROM audit_pages WHERE audit_id = ? AND url = ?");
                    $stmt->execute([$audit_id, $currentUrl]);
                    if ($stmt->fetch()) {
                        continue;
                    }

                    try {
                        $meta = Crawler::fetchAndAnalyze($currentUrl);

                        $stmt = $pdo->prepare("INSERT INTO audit_pages (
                            audit_id, url, meta_title, meta_description, h1, 
                            h1_count, h2_count, h3_count, h4_count, h5_count, h6_count, 
                            headers_structure, internal_links, external_links, missing_alt_images
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                        $stmt->execute([
                            $audit_id, $currentUrl, $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                            $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                            json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images']
                        ]);

                        $page_id = $pdo->lastInsertId();
                        $stmtGet = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
                        $stmtGet->execute([$page_id]);
                        $addedPages[] = $stmtGet->fetch();

                        if (isset($meta['internal_urls']) && is_array($meta['internal_urls'])) {
                            foreach ($meta['internal_urls'] as $foundUrl) {
                                $cleanUrl = preg_replace('/#.*$/', '', $foundUrl);
                                $foundParts = parse_url($cleanUrl);
                                $foundHost = isset($foundParts['host']) ? strtolower($foundParts['host']) : '';
                                $normFoundHost = preg_replace('/^www\./', '', $foundHost);

                                if ($normFoundHost === $normSeedHost) {
                                    if (!isset($visited[$cleanUrl])) {
                                        $visited[$cleanUrl] = true;
                                        $queue[] = $cleanUrl;
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Crawl failed for {$currentUrl}: " . $e->getMessage();
                    }
                }

                echo json_encode([
                    'success' => true,
                    'pages' => $addedPages,
                    'errors' => $errors
                ]);
            } catch (Exception $e) {
                report_error('Website crawl failed', $e);
            }
        } else {
            // mode === 'single' (Bulk or single pasted URLs)
            $urlInput = trim($_POST['url'] ?? '');
            if (empty($urlInput)) {
                echo json_encode(['error' => 'Webpage URL is required.']);
                exit;
            }

            // Split URLs by newlines, commas, or multiple whitespaces
            $urls = preg_split('/[\n\r,]+/', $urlInput);
            $urls = array_map('trim', $urls);
            $urls = array_filter($urls, function($u) {
                return !empty($u);
            });

            if (empty($urls)) {
                echo json_encode(['error' => 'Please enter at least one URL.']);
                exit;
            }

            $addedPages = [];
            $errors = [];

            foreach ($urls as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = "Invalid URL: {$url}";
                    continue;
                }

                // Skip duplicates in database for this audit
                $stmt = $pdo->prepare("SELECT id FROM audit_pages WHERE audit_id = ? AND url = ?");
                $stmt->execute([$audit_id, $url]);
                if ($stmt->fetch()) {
                    continue; // Skip duplicate silently
                }

                try {
                    $meta = Crawler::fetchAndAnalyze($url);

                    $stmt = $pdo->prepare("INSERT INTO audit_pages (
                        audit_id, url, meta_title, meta_description, h1, 
                        h1_count, h2_count, h3_count, h4_count, h5_count, h6_count, 
                        headers_structure, internal_links, external_links, missing_alt_images
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->execute([
                        $audit_id, $url, $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                        $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                        json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images']
                    ]);

                    $page_id = $pdo->lastInsertId();
                    $stmtGet = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
                    $stmtGet->execute([$page_id]);
                    $addedPages[] = $stmtGet->fetch();
                } catch (Exception $e) {
                    $errors[] = "Crawl failed for {$url}: " . $e->getMessage();
                }
            }

            echo json_encode([
                'success' => true,
                'pages' => $addedPages,
                'errors' => $errors
            ]);
        }
        break;

    case 'page_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid page ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM audit_pages WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'page_update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid page ID.']);
            exit;
        }

        $meta_title = $_POST['meta_title'] ?? null;
        $meta_description = $_POST['meta_description'] ?? null;
        $h1 = $_POST['h1'] ?? null;
        $monthly_visits = $_POST['monthly_visits'] === '' ? null : (int)$_POST['monthly_visits'];
        $avg_time_per_visit = $_POST['avg_time_per_visit'] === '' ? null : (int)$_POST['avg_time_per_visit'];
        $audience_country_proportion = $_POST['audience_country_proportion'] ?? '';
        $global_ranking = $_POST['global_ranking'] === '' ? null : (int)$_POST['global_ranking'];
        $country_ranking = $_POST['country_ranking'] === '' ? null : (int)$_POST['country_ranking'];
        $search_terms = $_POST['search_terms'] ?? '';
        $notes = $_POST['notes'] ?? null;
        $indexing_gsc = (isset($_POST['indexing_gsc']) && $_POST['indexing_gsc'] !== '') ? $_POST['indexing_gsc'] : null;
        $crawl_errors = (isset($_POST['crawl_errors']) && $_POST['crawl_errors'] !== '') ? $_POST['crawl_errors'] : null;

        try {
            $stmt = $pdo->prepare("UPDATE audit_pages SET 
                meta_title = ?, meta_description = ?, h1 = ?, 
                monthly_visits = ?, avg_time_per_visit = ?, audience_country_proportion = ?, 
                global_ranking = ?, country_ranking = ?, search_terms = ?, notes = ?, 
                indexing_gsc = ?, crawl_errors = ? 
                WHERE id = ?");
            $stmt->execute([
                $meta_title, $meta_description, $h1,
                $monthly_visits, $avg_time_per_visit, $audience_country_proportion,
                $global_ranking, $country_ranking, $search_terms, $notes,
                $indexing_gsc, $crawl_errors, $id
            ]);
            
            // Re-fetch to return latest data
            $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'page' => $stmt->fetch()]);
        } catch (PDOException $e) {
            report_error('Failed to update page fields', $e);
        }
        break;

    case 'page_refresh':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid page ID.']);
            exit;
        }

        // Get the url
        $stmt = $pdo->prepare("SELECT url FROM audit_pages WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['error' => 'Page not found.']);
            exit;
        }

        try {
            $meta = Crawler::fetchAndAnalyze($row['url']);

            $stmt = $pdo->prepare("UPDATE audit_pages SET 
                meta_title = ?, meta_description = ?, h1 = ?, 
                h1_count = ?, h2_count = ?, h3_count = ?, h4_count = ?, h5_count = ?, h6_count = ?, 
                headers_structure = ?, internal_links = ?, external_links = ?, missing_alt_images = ?
                WHERE id = ?");

            $stmt->execute([
                $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images'],
                $id
            ]);

            $stmtGet = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
            $stmtGet->execute([$id]);
            echo json_encode(['success' => true, 'page' => $stmtGet->fetch()]);
        } catch (Exception $e) {
            report_error('Failed to refresh page audit', $e);
        }
        break;

    case 'save_headers_structure':
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'page'; // 'page' or 'competitor'

        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid ID.']);
            exit;
        }

        $h1_count = (int)($_POST['h1_count'] ?? 0);
        $h2_count = (int)($_POST['h2_count'] ?? 0);
        $h3_count = (int)($_POST['h3_count'] ?? 0);
        $h4_count = (int)($_POST['h4_count'] ?? 0);
        $h5_count = (int)($_POST['h5_count'] ?? 0);
        $h6_count = (int)($_POST['h6_count'] ?? 0);
        $headers_structure = $_POST['headers_structure'] ?? '[]';
        $headers_screenshot = isset($_POST['headers_screenshot']) && $_POST['headers_screenshot'] !== '' ? $_POST['headers_screenshot'] : null;

        try {
            if ($type === 'page') {
                $stmt = $pdo->prepare("UPDATE audit_pages SET 
                    h1_count = ?, h2_count = ?, h3_count = ?, h4_count = ?, h5_count = ?, h6_count = ?, 
                    headers_structure = ?, headers_screenshot = ? WHERE id = ?");
                $stmt->execute([$h1_count, $h2_count, $h3_count, $h4_count, $h5_count, $h6_count, $headers_structure, $headers_screenshot, $id]);

                // Re-fetch
                $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'page' => $stmt->fetch()]);
            } else {
                $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                    h1_count = ?, h2_count = ?, h3_count = ?, h4_count = ?, h5_count = ?, h6_count = ?, 
                    headers_structure = ?, headers_screenshot = ? WHERE id = ?");
                $stmt->execute([$h1_count, $h2_count, $h3_count, $h4_count, $h5_count, $h6_count, $headers_structure, $headers_screenshot, $id]);

                // Re-fetch
                $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
            }
        } catch (PDOException $e) {
            report_error('Failed to save headers', $e);
        }
        break;

    case 'upload_headers_screenshot':
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'page'; // 'page' or 'competitor'

        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid ID.']);
            exit;
        }

        if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded or upload error occurred.']);
            exit;
        }

        $filename = store_uploaded_image($_FILES['screenshot'], 'uploads', 'headers_' . ($type === 'competitor' ? 'comp' : 'page'), $id);
        if ($filename === false) {
            echo json_encode(['error' => 'File rejected. Only real PNG, JPG, WEBP, or GIF images are allowed.']);
            exit;
        }

        try {
            if ($type === 'page') {
                $stmt = $pdo->prepare("UPDATE audit_pages SET headers_screenshot = ? WHERE id = ?");
                $stmt->execute([$filename, $id]);

                $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'filepath' => $filename, 'page' => $stmt->fetch()]);
            } else {
                $stmt = $pdo->prepare("UPDATE competitor_analyses SET headers_screenshot = ? WHERE id = ?");
                $stmt->execute([$filename, $id]);

                $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'filepath' => $filename, 'competitor' => $stmt->fetch()]);
            }
        } catch (PDOException $e) {
            error_log('headers_screenshot db update failed: ' . $e->getMessage());
            echo json_encode(['error' => 'Database update failed.']);
        }
        break;

    case 'upload_breakdown_screenshot':
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'audit'; // 'audit' or 'competitor'

        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid ID.']);
            exit;
        }

        if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded or upload error occurred.']);
            exit;
        }

        $filename = store_uploaded_image($_FILES['screenshot'], 'uploads', 'breakdown_' . ($type === 'competitor' ? 'comp' : 'audit'), $id);
        if ($filename === false) {
            echo json_encode(['error' => 'File rejected. Only real PNG, JPG, WEBP, or GIF images are allowed.']);
            exit;
        }

        try {
            if ($type === 'audit') {
                $stmt = $pdo->prepare("SELECT breakdown_by_country FROM audits WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetchColumn();
                if ($old && is_safe_upload_path($old) && file_exists($old) && is_file($old)) {
                    @unlink($old);
                }

                $stmt = $pdo->prepare("UPDATE audits SET breakdown_by_country = ? WHERE id = ?");
                $stmt->execute([$filename, $id]);

                echo json_encode(['success' => true, 'filepath' => $filename]);
            } else {
                $stmt = $pdo->prepare("SELECT breakdown_by_country FROM competitor_analyses WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetchColumn();
                if ($old && is_safe_upload_path($old) && file_exists($old) && is_file($old)) {
                    @unlink($old);
                }

                $stmt = $pdo->prepare("UPDATE competitor_analyses SET breakdown_by_country = ? WHERE id = ?");
                $stmt->execute([$filename, $id]);

                $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'filepath' => $filename, 'competitor' => $stmt->fetch()]);
            }
        } catch (PDOException $e) {
            error_log('breakdown_screenshot db update failed: ' . $e->getMessage());
            echo json_encode(['error' => 'Database update failed.']);
        }
        break;

    case 'update_field':
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? ''; // 'page', 'competitor_analysis', 'competitor_card', or 'audit'
        $field = trim($_POST['field'] ?? '');
        $value = $_POST['value'] ?? null;

        if ($id <= 0 || empty($type) || empty($field)) {
            echo json_encode(['error' => 'Required parameters missing.']);
            exit;
        }

        // Validate fields to prevent sql injection
        $allowedFields = [
            'page' => ['meta_title', 'meta_description', 'h1', 'monthly_visits', 'avg_time_per_visit', 'audience_country_proportion', 'global_ranking', 'country_ranking', 'search_terms', 'notes', 'indexing_gsc', 'crawl_errors', 'internal_links', 'external_links', 'missing_alt_images'],
            'competitor_analysis' => ['meta_title', 'meta_description', 'h1', 'monthly_visits', 'avg_time_per_visit', 'audience_country_proportion', 'global_ranking', 'country_ranking', 'target_country', 'search_terms', 'notes', 'bounce_rate', 'pages_per_visit', 'avg_monthly_visits', 'avg_visit_duration', 'breakdown_by_country', 'internal_links', 'external_links', 'missing_alt_images'],
            'competitor_card' => ['url', 'bounce_rate', 'pages_per_visit', 'avg_monthly_visits', 'avg_visit_duration'],
            'audit' => ['target_country']
        ];

        if (!isset($allowedFields[$type]) || !in_array($field, $allowedFields[$type])) {
            echo json_encode(['error' => 'Field update not allowed.']);
            exit;
        }

        // Normalize empty values
        if ($value === '') {
            $value = null;
        }

        // Screenshot-path fields are rendered as <img src> in the share view. The
        // whitelist above prevents SQL-side abuse, but the value itself must still
        // look like a stored upload so an attacker can't smuggle e.g.
        // "javascript:..." or "https://evil/x.png" into the public report.
        $screenshot_path_fields = ['breakdown_by_country', 'headers_screenshot'];
        if ($value !== null && in_array($field, $screenshot_path_fields, true) && !is_safe_upload_path($value)) {
            echo json_encode(['error' => 'Invalid screenshot path.']);
            exit;
        }

        $table = '';
        if ($type === 'page') {
            $table = 'audit_pages';
        } else if ($type === 'competitor_analysis') {
            $table = 'competitor_analyses';
        } else if ($type === 'competitor_card') {
            $table = 'competitors';
        } else if ($type === 'audit') {
            $table = 'audits';
        }

        try {
            $stmt = $pdo->prepare("UPDATE {$table} SET {$field} = ? WHERE id = ?");
            $stmt->execute([$value, $id]);

            // Re-fetch record
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();

            echo json_encode(['success' => true, 'record' => $record]);
        } catch (PDOException $e) {
            report_error('Update failed', $e);
        }
        break;

    // -------------------------------------------------------------
    // Core Web Vitals & Page Speed API Call
    // -------------------------------------------------------------
    case 'cwt_fetch':
        $type = $_POST['type'] ?? 'page'; // 'page' or 'competitor'
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $competitor_id = (int)($_POST['competitor_id'] ?? 0);
        $url = trim($_POST['url'] ?? '');
        $strategy = trim($_POST['strategy'] ?? ''); // 'desktop' or 'mobile'

        if (($type === 'page' && $audit_id <= 0) || ($type === 'competitor' && $competitor_id <= 0) || empty($url) || !in_array($strategy, ['desktop', 'mobile'])) {
            echo json_encode(['error' => 'Required parameters missing.']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid URL format.']);
            exit;
        }

        if (!function_exists('getPsiData')) {
            // Inner function to query PSI API
            function getPsiData($url, $strategy) {
                $apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($url) . 
                          "&strategy=" . $strategy . 
                          "&category=performance&category=accessibility&category=best-practices&category=seo";
                if (defined('PAGESPEED_API_KEY') && PAGESPEED_API_KEY !== '') {
                    $apiUrl .= "&key=" . urlencode(PAGESPEED_API_KEY);
                }
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 35,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    // Keep TLS verification on — this request carries PAGESPEED_API_KEY
                    // in the URL, so a MITM with verification off would harvest the key.
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ]);
                $response = curl_exec($ch);
                curl_close($ch);
                
                if (!$response) return null;
                $json = json_decode($response, true);
                if (!$json || isset($json['error'])) return null;

                $score = isset($json['lighthouseResult']['categories']['performance']['score'])
                    ? round($json['lighthouseResult']['categories']['performance']['score'] * 100)
                    : null;
                $accessibility = isset($json['lighthouseResult']['categories']['accessibility']['score'])
                    ? round($json['lighthouseResult']['categories']['accessibility']['score'] * 100)
                    : null;
                $best_practices = isset($json['lighthouseResult']['categories']['best-practices']['score'])
                    ? round($json['lighthouseResult']['categories']['best-practices']['score'] * 100)
                    : null;
                $seo = isset($json['lighthouseResult']['categories']['seo']['score'])
                    ? round($json['lighthouseResult']['categories']['seo']['score'] * 100)
                    : null;

                // Extract Lighthouse audits (Lab Data)
                $lhAudits = $json['lighthouseResult']['audits'] ?? [];
                
                $fcp = $lhAudits['first-contentful-paint']['displayValue'] ?? null;
                $lcp = $lhAudits['largest-contentful-paint']['displayValue'] ?? null;
                $tbt = $lhAudits['total-blocking-time']['displayValue'] ?? null;
                $cls = $lhAudits['cumulative-layout-shift']['displayValue'] ?? null;
                $si  = $lhAudits['speed-index']['displayValue'] ?? null;

                if ($score === null && $fcp === null && $lcp === null && $tbt === null && $cls === null && $si === null) {
                    return null;
                }

                return [
                    'score' => $score,
                    'accessibility' => $accessibility,
                    'best_practices' => $best_practices,
                    'seo' => $seo,
                    'fcp' => $fcp,
                    'lcp' => $lcp,
                    'tbt' => $tbt,
                    'cls' => $cls,
                    'si' => $si
                ];
            }
        }

        $data = getPsiData($url, $strategy);
        $is_mocked = false;

        if (!$data) {
            // Fallback mock if completely blocked / offline
            if ($strategy === 'desktop') {
                $data = [
                    'score' => 88, 'fcp' => '1.2 s', 'lcp' => '1.5 s', 'tbt' => '150 ms', 'cls' => '0.02', 'si' => '1.8 s',
                    'accessibility' => 94, 'best_practices' => 90, 'seo' => 92
                ];
            } else {
                $data = [
                    'score' => 31, 'fcp' => '3.5 s', 'lcp' => '9.7 s', 'tbt' => '10,860 ms', 'cls' => '0', 'si' => '7.4 s',
                    'accessibility' => 68, 'best_practices' => 75, 'seo' => 80
                ];
            }
            $is_mocked = true;
        }

        // Calculate Agentic Browsing score dynamically
        $cls_val = 0;
        if (isset($data['cls'])) {
            $cls_val = (float)preg_replace('/[^0-9.]/', '', $data['cls']);
        }
        $cls_ok = ($cls_val <= 0.1);
        $a11y_ok = (($data['accessibility'] ?? 0) >= 80);
        $seo_ok = (($data['seo'] ?? 0) >= 90);
        
        $passed_checks = 0;
        if ($cls_ok) $passed_checks++;
        if ($a11y_ok) $passed_checks++;
        if ($seo_ok) $passed_checks++;
        
        $data['agentic_browsing'] = "{$passed_checks}/3";

        try {
            if ($type === 'competitor') {
                if ($strategy === 'desktop') {
                    $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                        desktop_score = ?, 
                        desktop_fcp = ?, 
                        desktop_lcp = ?, 
                        desktop_tbt = ?, 
                        desktop_cls = ?, 
                        desktop_si = ?,
                        desktop_accessibility = ?,
                        desktop_best_practices = ?,
                        desktop_seo = ?,
                        desktop_agentic_browsing = ?
                        WHERE id = ?");
                    $stmt->execute([
                        $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                        $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing'],
                        $competitor_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                        mobile_score = ?, 
                        mobile_fcp = ?, 
                        mobile_lcp = ?, 
                        mobile_tbt = ?, 
                        mobile_cls = ?, 
                        mobile_si = ?,
                        mobile_accessibility = ?,
                        mobile_best_practices = ?,
                        mobile_seo = ?,
                        mobile_agentic_browsing = ?
                        WHERE id = ?");
                    $stmt->execute([
                        $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                        $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing'],
                        $competitor_id
                    ]);
                }

                // Return the full updated competitor row
                $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                $stmt->execute([$competitor_id]);
                $updatedRow = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'data' => $updatedRow,
                    'mocked' => $is_mocked
                ]);
            } else {
                // Check if record exists
                $stmt = $pdo->prepare("SELECT id FROM core_web_vitals WHERE audit_id = ?");
                $stmt->execute([$audit_id]);
                $exists = $stmt->fetch();

                if ($exists) {
                    // Update strategy fields
                    if ($strategy === 'desktop') {
                        $stmt = $pdo->prepare("UPDATE core_web_vitals SET 
                            desktop_score = ?, 
                            desktop_fcp = ?, 
                            desktop_lcp = ?, 
                            desktop_tbt = ?, 
                            desktop_cls = ?, 
                            desktop_si = ?,
                            desktop_accessibility = ?,
                            desktop_best_practices = ?,
                            desktop_seo = ?,
                            desktop_agentic_browsing = ?
                            WHERE audit_id = ?");
                        $stmt->execute([
                            $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                            $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing'],
                            $audit_id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE core_web_vitals SET 
                            mobile_score = ?, 
                            mobile_fcp = ?, 
                            mobile_lcp = ?, 
                            mobile_tbt = ?, 
                            mobile_cls = ?, 
                            mobile_si = ?,
                            mobile_accessibility = ?,
                            mobile_best_practices = ?,
                            mobile_seo = ?,
                            mobile_agentic_browsing = ?
                            WHERE audit_id = ?");
                        $stmt->execute([
                            $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                            $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing'],
                            $audit_id
                        ]);
                    }
                } else {
                    // Insert new row
                    if ($strategy === 'desktop') {
                        $stmt = $pdo->prepare("INSERT INTO core_web_vitals (
                            audit_id, 
                            desktop_score, desktop_fcp, desktop_lcp, desktop_tbt, desktop_cls, desktop_si,
                            desktop_accessibility, desktop_best_practices, desktop_seo, desktop_agentic_browsing
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $audit_id, 
                            $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                            $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO core_web_vitals (
                            audit_id, 
                            mobile_score, mobile_fcp, mobile_lcp, mobile_tbt, mobile_cls, mobile_si,
                            mobile_accessibility, mobile_best_practices, mobile_seo, mobile_agentic_browsing
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $audit_id, 
                            $data['score'], $data['fcp'], $data['lcp'], $data['tbt'], $data['cls'], $data['si'],
                            $data['accessibility'], $data['best_practices'], $data['seo'], $data['agentic_browsing']
                        ]);
                    }
                }

                // Return the full updated row
                $stmt = $pdo->prepare("SELECT * FROM core_web_vitals WHERE audit_id = ?");
                $stmt->execute([$audit_id]);
                $updatedRow = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'data' => $updatedRow,
                    'mocked' => $is_mocked
                ]);
            }
        } catch (PDOException $e) {
            report_error('Failed to save speed data', $e);
        }
        break;

    // -------------------------------------------------------------
    // Search Terms & Competitors
    // -------------------------------------------------------------
    case 'search_term_add':
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $term = trim($_POST['term'] ?? '');

        if ($audit_id <= 0 || empty($term)) {
            echo json_encode(['error' => 'Audit ID and search term are required.']);
            exit;
        }

        $lines = preg_split('/\r\n|\r|\n/', $term);
        $terms_to_add = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $terms_to_add[] = $trimmed;
            }
        }

        if (empty($terms_to_add)) {
            echo json_encode(['error' => 'No valid search terms provided.']);
            exit;
        }

        try {
            $added_terms = [];
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO search_terms (audit_id, term) VALUES (?, ?)");
            foreach ($terms_to_add as $t) {
                $stmt->execute([$audit_id, $t]);
                $term_id = $pdo->lastInsertId();
                $added_terms[] = [
                    'id' => (int)$term_id,
                    'audit_id' => $audit_id,
                    'term' => $t
                ];
            }
            $pdo->commit();

            echo json_encode(['success' => true, 'terms' => $added_terms]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            report_error('Failed to add search terms', $e);
        }
        break;

    case 'search_term_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid term ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM search_terms WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'competitor_add':
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $search_term_id = (int)($_POST['search_term_id'] ?? 0);
        $url = trim($_POST['url'] ?? '');
        $type = $_POST['type'] ?? 'organic'; // 'organic' or 'sponsored'

        if ($audit_id <= 0 || $search_term_id <= 0 || empty($url)) {
            echo json_encode(['error' => 'Required fields missing.']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid competitor URL format.']);
            exit;
        }

        $bounce_rate = (($_POST['bounce_rate'] ?? '') === '' || $type === 'sponsored') ? null : (float)$_POST['bounce_rate'];
        $pages_per_visit = (($_POST['pages_per_visit'] ?? '') === '' || $type === 'sponsored') ? null : (float)$_POST['pages_per_visit'];
        $avg_monthly_visits = (($_POST['avg_monthly_visits'] ?? '') === '' || $type === 'sponsored') ? null : (int)$_POST['avg_monthly_visits'];
        $avg_visit_duration = (($_POST['avg_visit_duration'] ?? '') === '' || $type === 'sponsored') ? null : (int)$_POST['avg_visit_duration'];


        try {
            $stmt = $pdo->prepare("INSERT INTO competitors (
                search_term_id, audit_id, url, type, bounce_rate, pages_per_visit, avg_monthly_visits, avg_visit_duration
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $search_term_id, $audit_id, $url, $type, $bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration
            ]);

            $comp_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM competitors WHERE id = ?");
            $stmt->execute([$comp_id]);
            echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
        } catch (PDOException $e) {
            report_error('Failed to add competitor', $e);
        }
        break;

    case 'competitor_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid competitor ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM competitors WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'competitor_update':
        $id = (int)($_POST['id'] ?? 0);
        $url = trim($_POST['url'] ?? '');

        if ($id <= 0 || empty($url)) {
            echo json_encode(['error' => 'Required fields missing.']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid URL format. Make sure to include http:// or https://.']);
            exit;
        }

        // Fetch type
        $stmt = $pdo->prepare("SELECT type FROM competitors WHERE id = ?");
        $stmt->execute([$id]);
        $comp = $stmt->fetch();
        if (!$comp) {
            echo json_encode(['error' => 'Competitor not found.']);
            exit;
        }

        $type = $comp['type'];
        $bounce_rate = (($_POST['bounce_rate'] ?? '') === '' || $type === 'sponsored') ? null : (float)$_POST['bounce_rate'];
        $pages_per_visit = (($_POST['pages_per_visit'] ?? '') === '' || $type === 'sponsored') ? null : (float)$_POST['pages_per_visit'];
        $avg_monthly_visits = (($_POST['avg_monthly_visits'] ?? '') === '' || $type === 'sponsored') ? null : (int)$_POST['avg_monthly_visits'];
        $avg_visit_duration = (($_POST['avg_visit_duration'] ?? '') === '' || $type === 'sponsored') ? null : (int)$_POST['avg_visit_duration'];


        try {
            $stmt = $pdo->prepare("UPDATE competitors SET 
                url = ?, bounce_rate = ?, pages_per_visit = ?, avg_monthly_visits = ?, avg_visit_duration = ?
                WHERE id = ?");
            $stmt->execute([
                $url, $bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration, $id
            ]);

            $stmt = $pdo->prepare("SELECT * FROM competitors WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
        } catch (PDOException $e) {
            report_error('Failed to update competitor', $e);
        }
        break;


    // -------------------------------------------------------------
    // Competitor suggestion ranking engine
    // -------------------------------------------------------------
    case 'competitors_suggest':
        $audit_id = (int)($_GET['audit_id'] ?? 0);
        if ($audit_id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        // Fetch all organic competitors for this audit, joining with the search terms
        $stmt = $pdo->prepare("
            SELECT c.*, s.term 
            FROM competitors c 
            JOIN search_terms s ON c.search_term_id = s.id 
            WHERE c.audit_id = ? AND c.type = 'organic'
        ");
        $stmt->execute([$audit_id]);
        $comps = $stmt->fetchAll();

        // Perform aggregation by domain in PHP
        $domains = [];
        foreach ($comps as $c) {
            $parsed = parse_url($c['url']);
            $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
            if (empty($host)) continue;

            $domain = preg_replace('/^www\./', '', $host);

            if (!isset($domains[$domain])) {
                $domains[$domain] = [
                    'domain' => $domain,
                    'count' => 0,
                    'urls' => [],
                    'terms' => []
                ];
            }
            $domains[$domain]['count']++;
            $domains[$domain]['urls'][] = $c['url'];
            $domains[$domain]['terms'][] = $c['term'];
        }

        // Sort by appearance count DESC
        uasort($domains, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        // Compute Ranks (same count shares same rank)
        $ranked = [];
        $rank = 0;
        $prevCount = -1;
        $index = 0;

        foreach ($domains as $domain => $data) {
            $index++;
            if ($data['count'] !== $prevCount) {
                $rank = $index;
                $prevCount = $data['count'];
            }

            // Derive competitor name (Capitalize first letter of domain parts)
            $parts = explode('.', $domain);
            $name = ucfirst($parts[0]);

            // Representative URL: exact URL if it was always the same, otherwise homepage URL
            $uniqueUrls = array_unique($data['urls']);
            $representativeUrl = '';
            if (count($uniqueUrls) === 1) {
                $representativeUrl = $uniqueUrls[0];
            } else {
                // Construct domain homepage URL matching original protocol if possible
                $firstUrlParsed = parse_url($data['urls'][0]);
                $scheme = $firstUrlParsed['scheme'] ?? 'https';
                $representativeUrl = $scheme . '://' . $domain . '/';
            }

            $ranked[] = [
                'rank' => $rank,
                'name' => $name,
                'domain' => $domain,
                'appearances' => $data['count'],
                'representative_url' => $representativeUrl,
                'terms' => array_values(array_unique($data['terms']))
            ];
        }

        echo json_encode($ranked);
        break;

    case 'competitors_send_to_analysis':
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $selected = json_decode($_POST['selected'] ?? '[]', true);

        if ($audit_id <= 0 || empty($selected)) {
            echo json_encode(['error' => 'Audit ID and selection details are required.']);
            exit;
        }

        $totalAdded = 0;
        $errors = [];

        foreach ($selected as $item) {
            $url = trim($item['representative_url'] ?? '');
            $terms = isset($item['terms']) ? implode(', ', $item['terms']) : '';

            if (empty($url)) continue;
            // Skip anything that isn't a syntactically valid http(s) URL — the value
            // is later passed to the crawler AND stored as a clickable link in the
            // public share view, so reject javascript:, data:, file:, garbage, etc.
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                $errors[] = "Invalid URL skipped: {$url}";
                continue;
            }

            // Check if competitor already exists in competitor_analyses
            $stmt = $pdo->prepare("SELECT id FROM competitor_analyses WHERE audit_id = ? AND url = ?");
            $stmt->execute([$audit_id, $url]);
            if ($stmt->fetch()) {
                // Already audited
                continue;
            }

            try {
                // Crawl competitor details
                $meta = Crawler::fetchAndAnalyze($url);

                $stmt = $pdo->prepare("INSERT INTO competitor_analyses (
                    audit_id, url, meta_title, meta_description, h1, 
                    h1_count, h2_count, h3_count, h4_count, h5_count, h6_count, 
                    headers_structure, internal_links, external_links, missing_alt_images, search_terms
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $audit_id, $url, $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                    $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                    json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images'],
                    $terms
                ]);
                $totalAdded++;
            } catch (Exception $e) {
                // Insert with empty details but save the URL so the user can see it failed and manually edit/try again
                $stmt = $pdo->prepare("INSERT INTO competitor_analyses (audit_id, url, search_terms) VALUES (?, ?, ?)");
                $stmt->execute([$audit_id, $url, $terms]);
                $totalAdded++;
                $errors[] = "Crawl failed for {$url}: " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true, 
            'added' => $totalAdded, 
            'errors' => $errors
        ]);
        break;

    // -------------------------------------------------------------
    // Competitor Analysis Management
    // -------------------------------------------------------------
    case 'competitor_analysis_add_manual':
        $audit_id = (int)($_POST['audit_id'] ?? 0);
        $url = trim($_POST['url'] ?? '');
        $search_terms = trim($_POST['search_terms'] ?? '');

        if ($audit_id <= 0 || empty($url)) {
            echo json_encode(['error' => 'Audit ID and URL are required.']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['error' => 'Invalid URL format.']);
            exit;
        }

        try {
            $meta = Crawler::fetchAndAnalyze($url);

            $stmt = $pdo->prepare("INSERT INTO competitor_analyses (
                audit_id, url, meta_title, meta_description, h1, 
                h1_count, h2_count, h3_count, h4_count, h5_count, h6_count, 
                headers_structure, internal_links, external_links, missing_alt_images, search_terms
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $audit_id, $url, $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images'],
                $search_terms
            ]);

            $comp_analysis_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
            $stmt->execute([$comp_analysis_id]);
            echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
        } catch (Exception $e) {
            report_error('Failed to crawl and add competitor', $e);
        }
        break;

    case 'competitor_analysis_delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid competitor analysis ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM competitor_analyses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'competitor_analysis_update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid competitor analysis ID.']);
            exit;
        }

        $meta_title = $_POST['meta_title'] ?? null;
        $meta_description = $_POST['meta_description'] ?? null;
        $h1 = $_POST['h1'] ?? null;
        $monthly_visits = $_POST['monthly_visits'] === '' ? null : (int)$_POST['monthly_visits'];
        $avg_time_per_visit = $_POST['avg_time_per_visit'] === '' ? null : (int)$_POST['avg_time_per_visit'];
        $audience_country_proportion = $_POST['audience_country_proportion'] ?? '';
        $global_ranking = $_POST['global_ranking'] === '' ? null : (int)$_POST['global_ranking'];
        $country_ranking = $_POST['country_ranking'] === '' ? null : (int)$_POST['country_ranking'];
        $search_terms = $_POST['search_terms'] ?? '';
        $notes = $_POST['notes'] ?? null;

        $bounce_rate = isset($_POST['bounce_rate']) && $_POST['bounce_rate'] !== '' ? (float)$_POST['bounce_rate'] : null;
        $pages_per_visit = isset($_POST['pages_per_visit']) && $_POST['pages_per_visit'] !== '' ? (float)$_POST['pages_per_visit'] : null;
        $avg_monthly_visits = isset($_POST['avg_monthly_visits']) && $_POST['avg_monthly_visits'] !== '' ? (int)$_POST['avg_monthly_visits'] : null;
        $avg_visit_duration = isset($_POST['avg_visit_duration']) && $_POST['avg_visit_duration'] !== '' ? (int)$_POST['avg_visit_duration'] : null;

        $stmt = $pdo->prepare("SELECT breakdown_by_country, target_country FROM competitor_analyses WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $oldBreakdown = $row['breakdown_by_country'] ?? '';
        $oldTargetCountry = $row['target_country'] ?? null;

        $target_country = isset($_POST['target_country']) ? ($_POST['target_country'] === '' ? null : $_POST['target_country']) : $oldTargetCountry;

        // Same hardening as audit_save_metrics: client-supplied paths must look like a
        // stored upload, and old paths must be path-validated before unlink().
        $breakdown_by_country = $_POST['breakdown_by_country'] ?? null;
        if ($breakdown_by_country !== null && $breakdown_by_country !== '' && !is_safe_upload_path($breakdown_by_country)) {
            $breakdown_by_country = '';
        }

        if (isset($_FILES['breakdown_by_country_file']) && $_FILES['breakdown_by_country_file']['error'] === UPLOAD_ERR_OK) {
            $stored = store_uploaded_image($_FILES['breakdown_by_country_file'], 'uploads', 'comp_breakdown', $id);
            if ($stored === false) {
                echo json_encode(['error' => 'Breakdown file rejected. Only PNG, JPG, WEBP, or GIF images are allowed.']);
                exit;
            }
            $breakdown_by_country = $stored;
            if (!empty($oldBreakdown) && is_safe_upload_path($oldBreakdown) && file_exists($oldBreakdown) && is_file($oldBreakdown) && $oldBreakdown !== $stored) {
                @unlink($oldBreakdown);
            }
        } else if (($breakdown_by_country === '' || $breakdown_by_country === null) && !empty($oldBreakdown) && is_safe_upload_path($oldBreakdown) && file_exists($oldBreakdown) && is_file($oldBreakdown)) {
            @unlink($oldBreakdown);
        }

        try {
            $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                meta_title = ?, meta_description = ?, h1 = ?, 
                monthly_visits = ?, avg_time_per_visit = ?, audience_country_proportion = ?, 
                global_ranking = ?, country_ranking = ?, target_country = ?, search_terms = ?, notes = ?,
                bounce_rate = ?, pages_per_visit = ?, avg_monthly_visits = ?, avg_visit_duration = ?, breakdown_by_country = ?
                WHERE id = ?");
            $stmt->execute([
                $meta_title, $meta_description, $h1,
                $monthly_visits, $avg_time_per_visit, $audience_country_proportion,
                $global_ranking, $country_ranking, $target_country, $search_terms, $notes,
                $bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration, $breakdown_by_country,
                $id
            ]);

            $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
        } catch (PDOException $e) {
            report_error('Failed to update competitor', $e);
        }
        break;

    case 'competitor_analysis_refresh':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid competitor ID.']);
            exit;
        }

        // Get the url
        $stmt = $pdo->prepare("SELECT url FROM competitor_analyses WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['error' => 'Competitor not found.']);
            exit;
        }

        try {
            $meta = Crawler::fetchAndAnalyze($row['url']);

            $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                meta_title = ?, meta_description = ?, h1 = ?, 
                h1_count = ?, h2_count = ?, h3_count = ?, h4_count = ?, h5_count = ?, h6_count = ?, 
                headers_structure = ?, internal_links = ?, external_links = ?, missing_alt_images = ?
                WHERE id = ?");

            $stmt->execute([
                $meta['meta_title'], $meta['meta_description'], $meta['h1'],
                $meta['h1_count'], $meta['h2_count'], $meta['h3_count'], $meta['h4_count'], $meta['h5_count'], $meta['h6_count'],
                json_encode($meta['headers_structure']), $meta['internal_links'], $meta['external_links'], $meta['missing_alt_images'],
                $id
            ]);

            $stmtGet = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
            $stmtGet->execute([$id]);
            echo json_encode(['success' => true, 'competitor' => $stmtGet->fetch()]);
        } catch (Exception $e) {
            report_error('Failed to refresh competitor audit', $e);
        }
        break;

    // -------------------------------------------------------------
    // Share-token lifecycle (admin only)
    // -------------------------------------------------------------
    case 'share_token_regenerate':
        $id = (int)($_POST['id'] ?? 0);
        $days = isset($_POST['days']) && $_POST['days'] !== '' ? (int)$_POST['days'] : SHARE_TOKEN_DEFAULT_DAYS;
        if ($days < 1)   { $days = 1; }
        if ($days > 365) { $days = 365; }
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        $new_token   = bin2hex(random_bytes(16));
        $expires_at  = (new DateTimeImmutable('+' . $days . ' days'))->format('Y-m-d H:i:s');

        try {
            // Revoking + rotating in one update ensures the previous token is dead
            // before the new one becomes usable.
            $stmt = $pdo->prepare(
                "UPDATE audits
                 SET share_token = ?, share_token_expires_at = ?, share_token_revoked_at = NULL
                 WHERE id = ?"
            );
            $stmt->execute([$new_token, $expires_at, $id]);
            echo json_encode([
                'success' => true,
                'share_token' => $new_token,
                'share_token_expires_at' => $expires_at,
                'share_token_revoked_at' => null,
            ]);
        } catch (PDOException $e) {
            report_error('Failed to regenerate share token', $e);
        }
        break;

    case 'share_token_revoke':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid audit ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE audits SET share_token_revoked_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("SELECT share_token_revoked_at FROM audits WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode([
                'success' => true,
                'share_token_revoked_at' => $stmt->fetchColumn(),
            ]);
        } catch (PDOException $e) {
            report_error('Failed to revoke share token', $e);
        }
        break;

    // -------------------------------------------------------------
    // Shared Audit Retrieval (Read-only View)
    // -------------------------------------------------------------
    case 'get_share_audit':
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            echo json_encode(['error' => 'Token required.']);
            exit;
        }

        // Validity is enforced in the WHERE clause so the lookup itself returns nothing
        // for revoked or expired tokens — there's no separate "this token exists but is
        // dead" leak. NULL expires_at means "no expiration" (legacy rows only).
        $stmt = $pdo->prepare(
            "SELECT a.*, c.name as client_name, c.homepage_url as client_homepage_url, c.industry as client_industry
             FROM audits a JOIN clients c ON a.client_id = c.id
             WHERE a.share_token = ?
               AND a.share_token_revoked_at IS NULL
               AND (a.share_token_expires_at IS NULL OR a.share_token_expires_at > NOW())"
        );
        $stmt->execute([$token]);
        $audit = $stmt->fetch();

        if (!$audit) {
            echo json_encode(['error' => 'Invalid or expired share token.']);
            exit;
        }

        $audit_id = $audit['id'];

        // Fetch pages
        $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$audit_id]);
        $pages = $stmt->fetchAll();

        // Fetch search terms
        $stmt = $pdo->prepare("SELECT * FROM search_terms WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$audit_id]);
        $search_terms = $stmt->fetchAll();

        // Fetch competitors
        $stmt = $pdo->prepare("SELECT * FROM competitors WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$audit_id]);
        $competitors = $stmt->fetchAll();

        // Fetch competitor analyses
        $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE audit_id = ? ORDER BY id ASC");
        $stmt->execute([$audit_id]);
        $competitor_analyses = $stmt->fetchAll();

        // Fetch core web vitals
        $stmt = $pdo->prepare("SELECT * FROM core_web_vitals WHERE audit_id = ?");
        $stmt->execute([$audit_id]);
        $cwt = $stmt->fetch();

        echo json_encode([
            'audit' => $audit,
            'pages' => $pages,
            'search_terms' => $search_terms,
            'competitors' => $competitors,
            'competitor_analyses' => $competitor_analyses,
            'core_web_vitals' => $cwt ? $cwt : null
        ]);
        break;

    default:
        echo json_encode(['error' => 'Invalid API action.']);
        break;
}
} catch (Throwable $e) {
    // Safety net: any exception that bypasses an inner catch (including TypeError,
    // unexpected PDOException from the dispatcher itself, etc.) lands here. The full
    // message is logged; the client sees a generic error so we don't leak SQL or paths.
    error_log('Unhandled API exception in action "' . $action . '": ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(['error' => 'Internal server error.']);
}
