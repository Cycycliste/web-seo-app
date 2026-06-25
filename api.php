<?php
require_once 'db.php';
require_once 'crawler.php';

header('Content-Type: application/json');
safe_session_start();

$action = $_GET['action'] ?? '';

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);

if (!$isAuthenticated && $action !== 'login' && $action !== 'get_share_audit') {
    echo json_encode(['error' => 'Unauthorized. Please log in.', 'code' => 401]);
    exit;
}

// -------------------------------------------------------------
// Core Actions
// -------------------------------------------------------------

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

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true, 'message' => 'Logged in successfully.']);
        } else {
            echo json_encode(['error' => 'Invalid username or password.']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'status':
        echo json_encode(['logged_in' => $isAuthenticated, 'username' => $_SESSION['username'] ?? null]);
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
            echo json_encode(['error' => 'Failed to create client: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to update client: ' . $e->getMessage()]);
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

        try {
            $stmt = $pdo->prepare("INSERT INTO audits (client_id, name, share_token) VALUES (?, ?, ?)");
            $stmt->execute([$client_id, $name, $share_token]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to create audit: ' . $e->getMessage()]);
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

        $main_channels = $_POST['main_channels'] ?? '';
        $traffic_trends = $_POST['traffic_trends'] ?? '';

        // Handle file uploads
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        if (isset($_FILES['main_channels_file']) && $_FILES['main_channels_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['main_channels_file']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/main_channels_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['main_channels_file']['tmp_name'], $filename)) {
                $main_channels = $filename;
                // Delete old file if it exists and is different
                if (!empty($oldChannels) && file_exists($oldChannels) && $oldChannels !== $filename) {
                    @unlink($oldChannels);
                }
            }
        } else if (empty($main_channels) && !empty($oldChannels)) {
            // Deleted old file
            if (file_exists($oldChannels)) {
                @unlink($oldChannels);
            }
        }

        if (isset($_FILES['traffic_trends_file']) && $_FILES['traffic_trends_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['traffic_trends_file']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/traffic_trends_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['traffic_trends_file']['tmp_name'], $filename)) {
                $traffic_trends = $filename;
                // Delete old file if it exists and is different
                if (!empty($oldTrends) && file_exists($oldTrends) && $oldTrends !== $filename) {
                    @unlink($oldTrends);
                }
            }
        } else if (empty($traffic_trends) && !empty($oldTrends)) {
            // Deleted old file
            if (file_exists($oldTrends)) {
                @unlink($oldTrends);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE audits SET bounce_rate = ?, pages_per_visit = ?, avg_monthly_visits = ?, avg_visit_duration = ?, breakdown_by_country = ?, main_channels = ?, traffic_trends = ?, sitemap_details = ?, additional_notes = ?, global_analysis = ?, global_strategy = ?, global_ranking = ?, country_ranking = ?, target_country = ? WHERE id = ?");
            $stmt->execute([$bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration, $breakdown_by_country, $main_channels, $traffic_trends, $sitemap_details, $additional_notes, $global_analysis, $global_strategy, $global_ranking, $country_ranking, $target_country, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to save audit metrics: ' . $e->getMessage()]);
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
                echo json_encode(['error' => 'Website crawl failed: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to update page fields: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to refresh page audit: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to save headers: ' . $e->getMessage()]);
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

        $file = $_FILES['screenshot'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'Invalid file extension. Only PNG, JPG, JPEG, WEBP, and GIF are allowed.']);
            exit;
        }

        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $filename = 'uploads/headers_screenshot_' . $type . '_' . $id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $filename)) {
            try {
                if ($type === 'page') {
                    $stmt = $pdo->prepare("UPDATE audit_pages SET headers_screenshot = ? WHERE id = ?");
                    $stmt->execute([$filename, $id]);
                    
                    // Re-fetch
                    $stmt = $pdo->prepare("SELECT * FROM audit_pages WHERE id = ?");
                    $stmt->execute([$id]);
                    echo json_encode(['success' => true, 'filepath' => $filename, 'page' => $stmt->fetch()]);
                } else {
                    $stmt = $pdo->prepare("UPDATE competitor_analyses SET headers_screenshot = ? WHERE id = ?");
                    $stmt->execute([$filename, $id]);
                    
                    // Re-fetch
                    $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                    $stmt->execute([$id]);
                    echo json_encode(['success' => true, 'filepath' => $filename, 'competitor' => $stmt->fetch()]);
                }
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database update failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'Failed to move uploaded file.']);
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

        $file = $_FILES['screenshot'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'Invalid file extension. Only PNG, JPG, JPEG, WEBP, and GIF are allowed.']);
            exit;
        }

        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $filename = 'uploads/breakdown_screenshot_' . $type . '_' . $id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $filename)) {
            try {
                if ($type === 'audit') {
                    // Fetch old screenshot to delete
                    $stmt = $pdo->prepare("SELECT breakdown_by_country FROM audits WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetchColumn();
                    if ($old && file_exists($old) && is_file($old)) {
                        @unlink($old);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE audits SET breakdown_by_country = ? WHERE id = ?");
                    $stmt->execute([$filename, $id]);
                    
                    echo json_encode(['success' => true, 'filepath' => $filename]);
                } else {
                    // Fetch old screenshot to delete
                    $stmt = $pdo->prepare("SELECT breakdown_by_country FROM competitor_analyses WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetchColumn();
                    if ($old && file_exists($old) && is_file($old)) {
                        @unlink($old);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE competitor_analyses SET breakdown_by_country = ? WHERE id = ?");
                    $stmt->execute([$filename, $id]);
                    
                    // Re-fetch competitor
                    $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
                    $stmt->execute([$id]);
                    echo json_encode(['success' => true, 'filepath' => $filename, 'competitor' => $stmt->fetch()]);
                }
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database update failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'Failed to move uploaded file.']);
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
            'competitor_analysis' => ['meta_title', 'meta_description', 'h1', 'monthly_visits', 'avg_time_per_visit', 'audience_country_proportion', 'global_ranking', 'country_ranking', 'search_terms', 'notes', 'bounce_rate', 'pages_per_visit', 'avg_monthly_visits', 'avg_visit_duration', 'breakdown_by_country', 'internal_links', 'external_links', 'missing_alt_images'],
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
            echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
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
                    CURLOPT_SSL_VERIFYPEER => false,
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
            echo json_encode(['error' => 'Failed to save speed data: ' . $e->getMessage()]);
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

        try {
            $stmt = $pdo->prepare("INSERT INTO search_terms (audit_id, term) VALUES (?, ?)");
            $stmt->execute([$audit_id, $term]);
            $term_id = $pdo->lastInsertId();

            echo json_encode(['success' => true, 'term' => [
                'id' => $term_id,
                'audit_id' => $audit_id,
                'term' => $term
            ]]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to add search term: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to add competitor: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to update competitor: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to crawl and add competitor: ' . $e->getMessage()]);
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

        $stmt = $pdo->prepare("SELECT breakdown_by_country FROM competitor_analyses WHERE id = ?");
        $stmt->execute([$id]);
        $oldBreakdown = $stmt->fetchColumn() ?? '';

        $breakdown_by_country = $_POST['breakdown_by_country'] ?? null;

        if (isset($_FILES['breakdown_by_country_file']) && $_FILES['breakdown_by_country_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['breakdown_by_country_file']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/comp_breakdown_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['breakdown_by_country_file']['tmp_name'], $filename)) {
                $breakdown_by_country = $filename;
                if (!empty($oldBreakdown) && file_exists($oldBreakdown) && is_file($oldBreakdown) && $oldBreakdown !== $filename) {
                    @unlink($oldBreakdown);
                }
            }
        } else if (($breakdown_by_country === '' || $breakdown_by_country === null) && !empty($oldBreakdown) && file_exists($oldBreakdown) && is_file($oldBreakdown)) {
            @unlink($oldBreakdown);
        }

        try {
            $stmt = $pdo->prepare("UPDATE competitor_analyses SET 
                meta_title = ?, meta_description = ?, h1 = ?, 
                monthly_visits = ?, avg_time_per_visit = ?, audience_country_proportion = ?, 
                global_ranking = ?, country_ranking = ?, search_terms = ?, notes = ?,
                bounce_rate = ?, pages_per_visit = ?, avg_monthly_visits = ?, avg_visit_duration = ?, breakdown_by_country = ?
                WHERE id = ?");
            $stmt->execute([
                $meta_title, $meta_description, $h1,
                $monthly_visits, $avg_time_per_visit, $audience_country_proportion,
                $global_ranking, $country_ranking, $search_terms, $notes,
                $bounce_rate, $pages_per_visit, $avg_monthly_visits, $avg_visit_duration, $breakdown_by_country,
                $id
            ]);

            $stmt = $pdo->prepare("SELECT * FROM competitor_analyses WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'competitor' => $stmt->fetch()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to update competitor: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Failed to refresh competitor audit: ' . $e->getMessage()]);
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

        $stmt = $pdo->prepare("SELECT a.*, c.name as client_name, c.homepage_url as client_homepage_url, c.industry as client_industry FROM audits a JOIN clients c ON a.client_id = c.id WHERE a.share_token = ?");
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
