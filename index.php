<?php
require_once 'db.php';
safe_session_start();

// Protect page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SEO Audit Suite</title>
    <link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">
    <style>
        /* Floating Status Selector Menu */
        .floating-selector-menu {
            position: absolute;
            background: rgba(15, 23, 42, 0.98) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6) !important;
            padding: 6px !important;
            z-index: 100000 !important;
            min-width: 140px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 4px !important;
            animation: fadeInScale 0.12s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .floating-selector-item {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 8px 12px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            border-radius: var(--radius-sm) !important;
            color: var(--text-primary) !important;
            cursor: pointer !important;
            transition: var(--transition) !important;
        }

        .floating-selector-item:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }

        .floating-selector-item.active {
            background: rgba(139, 92, 246, 0.15) !important;
            color: var(--primary) !important;
            border: 1px solid rgba(139, 92, 246, 0.3) !important;
        }

        .floating-selector-item .status-dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            margin-left: 8px !important;
        }
    </style>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

    <!-- Fullscreen Loader -->
    <div id="fullscreen-loader" class="fullscreen-loader" style="display: none;">
        <div class="spinner spinner-large"></div>
        <div id="loader-text" style="font-weight: 500; font-size: 1.1rem;">Loading workspace...</div>
    </div>

    <div id="app-layout">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="glass-panel" style="border-radius: 0;">
            <div class="sidebar-header" style="justify-content: space-between; align-items: center; display: flex;">
                <div class="flex-align logo-container expanded-only">
                    <div class="logo-icon">
                        <i data-lucide="activity" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <span class="logo-text">SEO Audit Tool</span>
                </div>
                <button class="btn btn-secondary btn-icon sidebar-toggle-btn" onclick="toggleSidebar()" title="Collapse Sidebar" style="border: none; background: transparent; padding: 6px; color: var(--text-secondary); transition: var(--transition);">
                    <i data-lucide="panel-left-close" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <div class="sidebar-content">
                <!-- Expanded Content View -->
                <div class="expanded-only" style="width: 100%;">
                    <!-- Client selector state -->
                    <div id="client-select-state">
                        <div class="flex-space" style="margin-bottom: 12px;">
                            <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em;">Clients</span>
                            <button class="btn btn-primary btn-icon" style="border-radius: 6px; padding: 4px;" onclick="openModal('new-client-modal')" title="New Client">
                                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>

                        <div style="position: relative; margin-bottom: 16px;">
                            <i data-lucide="search" style="position: absolute; left: 10px; top: 11px; width: 14px; height: 14px; color: var(--text-muted);"></i>
                            <input type="text" id="client-search" class="form-input" style="padding: 8px 12px 8px 32px; font-size: 0.85rem;" placeholder="Search clients..." oninput="loadClients()">
                        </div>

                        <div id="clients-list" class="clients-list-container">
                            <!-- Clients loaded via AJAX -->
                        </div>
                    </div>

                    <!-- Active Client info and its Audits list -->
                    <div id="client-active-state" style="display: none;">
                        <div style="margin-bottom: 24px;">
                            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;" onclick="clearActiveClient()">
                                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                                <span>Switch Client</span>
                            </button>
                        </div>

                        <div class="glass-card" style="padding: 10px 14px; margin-bottom: 16px; background: rgba(139, 92, 246, 0.03); border-color: rgba(139, 92, 246, 0.15); display: flex; align-items: center; justify-content: space-between; gap: 8px; overflow: hidden;">
                            <a id="active-client-url" href="#" target="_blank" class="url-link" style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                <span id="active-client-name"></span>
                                <i data-lucide="external-link" style="width: 12px; height: 12px; color: var(--text-muted); flex-shrink: 0;"></i>
                            </a>
                            <span id="active-client-industry" style="font-size: 0.75rem; color: var(--text-secondary); font-style: italic; margin-left: auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px; display: none; text-align: right;"></span>
                        </div>

                        <div class="flex-space" style="margin-bottom: 12px;">
                            <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em;">Audits</span>
                            <button class="btn btn-primary btn-icon" style="border-radius: 6px; padding: 4px;" onclick="openModal('new-audit-modal')" title="New Audit">
                                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>

                        <div id="audits-list" class="clients-list-container">
                            <!-- Audits loaded via AJAX -->
                        </div>
                    </div>
                </div>

                <!-- Collapsed Content View -->
                <div class="collapsed-only" style="flex-direction: column; align-items: center; gap: 20px; width: 100%;">
                    <!-- If no client is active -->
                    <div id="collapsed-client-select-state" style="display: flex; flex-direction: column; gap: 16px; align-items: center; width: 100%;">
                        <button class="btn btn-secondary btn-icon" onclick="toggleSidebar(); setTimeout(() => document.getElementById('client-search').focus(), 150);" title="Search Clients" style="border: none; background: rgba(255,255,255,0.03); width: 42px; height: 42px; border-radius: var(--radius-sm);">
                            <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                        </button>
                        <button class="btn btn-primary btn-icon" onclick="openModal('new-client-modal')" title="New Client" style="width: 42px; height: 42px; border-radius: var(--radius-sm);">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                    
                    <!-- If client is active -->
                    <div id="collapsed-client-active-state" style="display: none; flex-direction: column; gap: 16px; align-items: center; width: 100%;">
                        <button class="btn btn-secondary btn-icon" onclick="clearActiveClient()" title="Switch Client" style="border: none; background: rgba(255,255,255,0.03); width: 42px; height: 42px; border-radius: var(--radius-sm);">
                            <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
                        </button>
                        <button class="btn btn-primary btn-icon" onclick="openModal('new-audit-modal')" title="New Audit" style="width: 42px; height: 42px; border-radius: var(--radius-sm);">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                        </button>
                        <button class="btn btn-secondary btn-icon" onclick="toggleSidebar()" title="View Audits List" style="border: none; background: rgba(255,255,255,0.03); width: 42px; height: 42px; border-radius: var(--radius-sm);">
                            <i data-lucide="folder-kanban" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Actions (visible only when audit is active) -->
            <div id="sidebar-actions" style="display: none; padding: 0 16px; margin-bottom: 20px; flex-direction: column; gap: 8px; border-bottom: 1px solid var(--border-glass); padding-bottom: 20px;">
                <!-- Expanded View -->
                <button class="btn btn-secondary expanded-only" onclick="copyShareLink()" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; font-size: 0.85rem; font-weight: 500; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: rgba(255, 255, 255, 0.03); color: var(--text-primary); transition: var(--transition);">
                    <i data-lucide="share-2" style="width: 15px; height: 15px; color: var(--primary);"></i>
                    <span>Copy Share Link</span>
                </button>
                <!-- Collapsed View -->
                <div class="collapsed-only" style="display: flex; justify-content: center; width: 100%;">
                    <button class="btn btn-secondary btn-icon" onclick="copyShareLink()" title="Copy Share Link" style="width: 42px; height: 42px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-glass); background: rgba(255, 255, 255, 0.03); color: var(--text-primary); transition: var(--transition);">
                        <i data-lucide="share-2" style="width: 18px; height: 18px; color: var(--primary);"></i>
                    </button>
                </div>
            </div>

            <div class="sidebar-footer">
                <!-- Expanded Footer -->
                <div class="flex-align expanded-only" style="width: 100%; justify-content: space-between;">
                    <div class="flex-align">
                        <i data-lucide="user-check" style="width: 16px; height: 16px; color: var(--success);"></i>
                        <span style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </div>
                    <button class="btn btn-secondary btn-icon" style="padding: 6px;" onclick="logout()" title="Log out">
                        <i data-lucide="log-out" style="width: 16px; height: 16px; color: #fca5a5;"></i>
                    </button>
                </div>
                
                <!-- Collapsed Footer -->
                <div class="collapsed-only" style="flex-direction: column; align-items: center; gap: 12px; width: 100%;">
                    <div class="avatar-initials" title="<?= htmlspecialchars($_SESSION['username']) ?>" style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; box-shadow: 0 0 8px var(--primary-glow);">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <button class="btn btn-secondary btn-icon" style="padding: 6px;" onclick="logout()" title="Log out">
                        <i data-lucide="log-out" style="width: 16px; height: 16px; color: #fca5a5;"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Panel -->
        <main id="main-panel">


            <!-- Workspace content -->
            <div class="workspace-wrapper">
                
                <!-- Welcome/Stats View (No Active Audit) -->
                <div id="welcome-view">
                    <h1 class="dashboard-title">Welcome to SEO Audit Suite</h1>
                    <p class="dashboard-subtitle">Automate website indexing, metadata crawl, PageSpeed metrics, and competitor mapping in one unified interface.</p>
                    
                    <div class="grid-3">
                        <div class="glass-panel metric-card">
                            <div class="metric-icon">
                                <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Total Clients</span>
                                <div id="stat-clients-count" class="metric-val">0</div>
                            </div>
                        </div>
                        <div class="glass-panel metric-card">
                            <div class="metric-icon" style="color: var(--secondary);">
                                <i data-lucide="folder-kanban" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Total Audits</span>
                                <div id="stat-audits-count" class="metric-val">0</div>
                            </div>
                        </div>
                        <div class="glass-panel metric-card">
                            <div class="metric-icon" style="color: var(--success);">
                                <i data-lucide="globe" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Audited Pages</span>
                                <div id="stat-pages-count" class="metric-val">0</div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel" style="padding: 40px; text-align: center; background: rgba(255, 255, 255, 0.01);">
                        <i data-lucide="compass" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                        <h3>Ready to begin?</h3>
                        <p style="color: var(--text-secondary); max-width: 500px; margin: 8px auto 20px;">Select an existing client from the left directory sidebar, or create a new client to start auditing their web pages.</p>
                        <button class="btn btn-primary" onclick="openModal('new-client-modal')">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            <span>Create New Client</span>
                        </button>
                    </div>
                </div>

                <!-- Active Audit Workspace View -->
                <div id="audit-workspace-view" style="display: none;">
                    
                    <!-- Tabs Headers -->
                    <div class="tabs-header">
                        <button class="tab-link active" onclick="switchTab(event, 'tab-website-audit')">
                            <i data-lucide="search-code" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                            <span>Website Audit</span>
                        </button>
                        <button class="tab-link" onclick="switchTab(event, 'tab-search-terms')">
                            <i data-lucide="key-round" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                            <span>Search Terms</span>
                        </button>
                        <button class="tab-link" onclick="switchTab(event, 'tab-competitor-analysis')">
                            <i data-lucide="swords" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                            <span>Competitor Analysis</span>
                        </button>
                        <button class="tab-link" onclick="switchTab(event, 'tab-global-report')">
                            <i data-lucide="file-text" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                            <span>Global Report & Strategy</span>
                        </button>
                    </div>

                    <!-- Tab 1: Website Audit -->
                    <div id="tab-website-audit" class="tab-pane active">
                        <!-- Sub tabs for Website Audit -->
                        <div class="flex-space" style="margin-bottom: 24px;">
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-secondary btn-sm" id="subtab-btn-seo" onclick="switchSubTab('seo')" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);">SEO State</button>
                                <button class="btn btn-secondary btn-sm" id="subtab-btn-tech" onclick="switchSubTab('tech')">Technical State</button>
                                <button class="btn btn-secondary btn-sm" id="subtab-btn-perf" onclick="switchSubTab('perf')">Traffic & Performance</button>
                            </div>
                        </div>

                        <!-- Subtab: SEO State -->
                        <div id="subtab-seo">
                            <div class="glass-panel scraper-box">
                                <h4 style="margin-bottom: 12px; font-weight: 600;">Audit Page URL(s)</h4>
                                <form id="scraper-form" onsubmit="addPage(event)" style="display: flex; flex-direction: column; gap: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label style="font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Scraping Method</label>
                                        <div class="scrape-mode-selector">
                                            <button type="button" class="mode-toggle-btn active" id="btn-mode-single" onclick="setScrapeMode('single')">Pasted URL(s)</button>
                                            <button type="button" class="mode-toggle-btn" id="btn-mode-website" onclick="setScrapeMode('website')">Full Website from 1 Link</button>
                                        </div>
                                    </div>

                                    <!-- Panel for Pasted URL(s) -->
                                    <div id="panel-mode-single" class="mode-panel">
                                        <label for="scrape-urls" style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 8px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Webpage URL(s)</label>
                                        <textarea id="scrape-urls" class="form-input" placeholder="Paste webpage URL(s) to crawl (one URL per line, e.g. https://example.com/about)..." rows="3" style="resize: vertical;" required></textarea>
                                    </div>

                                    <!-- Panel for Full Website -->
                                    <div id="panel-mode-website" class="mode-panel" style="display: none;">
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                                            <div style="flex: 1; min-width: 250px;">
                                                <label for="scrape-website-url" style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 8px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Starting Page URL</label>
                                                <input type="url" id="scrape-website-url" class="form-input" placeholder="Enter homepage or seed URL (e.g. https://example.com)...">
                                            </div>
                                            <div style="width: 150px;">
                                                <label for="scrape-max-pages" style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 8px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Max Pages to Crawl</label>
                                                <input type="number" id="scrape-max-pages" class="form-input" value="10" min="1" max="50">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="submit" class="btn btn-primary" style="white-space: nowrap; margin-top: 4px;">
                                            <i data-lucide="scan" style="width: 16px; height: 16px;"></i>
                                            <span id="submit-btn-text">Crawl & Add URL(s)</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="table-wrapper">
                                <table class="table-custom table-wide" id="seo-pages-table">
                                    <thead>
                                        <tr>
                                            <th>URL</th>
                                            <th>Meta Title</th>
                                            <th>Meta Description</th>
                                            <th>H1</th>
                                            <th>Semantic Headers</th>
                                            <th>Internal Links</th>
                                            <th>External Links</th>
                                            <th>Missing Alt</th>
                                            <th>Search Terms</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="seo-pages-list">
                                        <!-- Pages render via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Subtab: Technical State -->
                        <div id="subtab-tech" style="display: none;">
                            
                            <!-- Website Speed & Core Web Vitals (Homepage) -->
                            <div class="glass-panel" style="padding: 30px; margin-bottom: 30px; width: 100%;">
                                <div class="flex-space" style="margin-bottom: 24px; border-bottom: 1px solid var(--border-glass); padding-bottom: 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <h3 style="font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                                            <span>Core Web Vitals & Speed Scores</span>
                                            <button class="btn btn-secondary btn-icon" id="audit-cwv-toggle-btn" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;" onclick="toggleAuditTechCollapse()" title="Collapse">
                                                <i id="audit-cwv-toggle-icon" data-lucide="chevron-up" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </h3>
                                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px; margin-left: 12px; margin-bottom: 0;">Checked using homepage as representative page</p>
                                    </div>
                                    <button class="btn btn-secondary" onclick="fetchCoreWebVitalsAll()">
                                        <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                                        <span>Fetch Core Web Vitals (All)</span>
                                    </button>
                                </div>

                                <div id="audit-cwv-body">
                                    <div id="cwv-results" class="cwv-container" style="display: none;">
                                        <!-- Desktop Card -->
                                        <div class="glass-card cwv-strategy-card">
                                            <div class="cwv-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                                <div class="flex-align" style="gap: 8px;">
                                                    <i data-lucide="monitor" style="width: 20px; height: 20px; color: var(--secondary);"></i>
                                                    <span style="font-weight: 600;">Desktop Strategy</span>
                                                </div>
                                                <button class="btn btn-secondary btn-sm" id="cwv-desktop-btn" onclick="fetchCoreWebVitals('desktop')" style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                                                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                                    <span>Refresh Desktop</span>
                                                </button>
                                            </div>
                                            
                                            <div id="cwv-desktop-loader" style="display: none; text-align: center; padding: 60px 0;">
                                                <div class="spinner spinner-large" style="margin: 0 auto 12px;"></div>
                                                <p style="font-size: 0.85rem; color: var(--text-secondary);">Querying PageSpeed Insights API...</p>
                                            </div>

                                            <div id="cwv-desktop-content">
                                                <!-- Row of 5 circular scores -->
                                                <div class="cwv-scores-row">
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-desktop-score" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Perf</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Performance</div>
                                                            <div class="cwv-tooltip-text">Measures page load speed, responsiveness, and visual stability.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-desktop-accessibility" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">A11y</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Accessibility</div>
                                                            <div class="cwv-tooltip-text">Measures how easy the website is to use for people with disabilities.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-desktop-best-practices" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Best</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Best Practices</div>
                                                            <div class="cwv-tooltip-text">Checks if the website follows web standards and security best practices.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-desktop-seo" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">SEO</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Search Engine Optimization</div>
                                                            <div class="cwv-tooltip-text">Checks how well search engines can crawl, index, and understand the page.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-desktop-agentic-browsing" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Agentic</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Agentic Browsing</div>
                                                            <div class="cwv-tooltip-text">Measures suitability for AI agents: checks visual stability (CLS &le; 0.1), Accessibility (&ge; 80), and SEO (&ge; 90).</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="cwv-metrics-list">
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">First Contentful Paint</div>
                                                        <div id="cwv-desktop-fcp" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Largest Contentful Paint</div>
                                                        <div id="cwv-desktop-lcp" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Total Blocking Time</div>
                                                        <div id="cwv-desktop-tbt" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Cumulative Layout Shift</div>
                                                        <div id="cwv-desktop-cls" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item" style="grid-column: span 2;">
                                                        <div class="cwv-metric-lbl">Speed Index</div>
                                                        <div id="cwv-desktop-si" class="cwv-metric-val">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mobile Card -->
                                        <div class="glass-card cwv-strategy-card">
                                            <div class="cwv-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                                <div class="flex-align" style="gap: 8px;">
                                                    <i data-lucide="smartphone" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                                    <span style="font-weight: 600;">Mobile Strategy</span>
                                                </div>
                                                <button class="btn btn-secondary btn-sm" id="cwv-mobile-btn" onclick="fetchCoreWebVitals('mobile')" style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                                                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                                    <span>Refresh Mobile</span>
                                                </button>
                                            </div>
                                            
                                            <div id="cwv-mobile-loader" style="display: none; text-align: center; padding: 60px 0;">
                                                <div class="spinner spinner-large" style="margin: 0 auto 12px;"></div>
                                                <p style="font-size: 0.85rem; color: var(--text-secondary);">Querying PageSpeed Insights API...</p>
                                            </div>

                                            <div id="cwv-mobile-content">
                                                <!-- Row of 5 circular scores -->
                                                <div class="cwv-scores-row">
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-mobile-score" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Perf</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Performance</div>
                                                            <div class="cwv-tooltip-text">Measures page load speed, responsiveness, and visual stability.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-mobile-accessibility" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">A11y</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Accessibility</div>
                                                            <div class="cwv-tooltip-text">Measures how easy the website is to use for people with disabilities.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-mobile-best-practices" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Best</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Best Practices</div>
                                                            <div class="cwv-tooltip-text">Checks if the website follows web standards and security best practices.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-mobile-seo" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">SEO</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Search Engine Optimization</div>
                                                            <div class="cwv-tooltip-text">Checks how well search engines can crawl, index, and understand the page.</div>
                                                        </div>
                                                    </div>
                                                    <div class="cwv-score-card">
                                                        <div id="cwv-mobile-agentic-browsing" class="cwv-score-circle poor">-</div>
                                                        <div class="cwv-score-label">Agentic</div>
                                                        <div class="cwv-tooltip">
                                                            <div class="cwv-tooltip-title">Agentic Browsing</div>
                                                            <div class="cwv-tooltip-text">Measures suitability for AI agents: checks visual stability (CLS &le; 0.1), Accessibility (&ge; 80), and SEO (&ge; 90).</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="cwv-metrics-list">
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">First Contentful Paint</div>
                                                        <div id="cwv-mobile-fcp" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Largest Contentful Paint</div>
                                                        <div id="cwv-mobile-lcp" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Total Blocking Time</div>
                                                        <div id="cwv-mobile-tbt" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item">
                                                        <div class="cwv-metric-lbl">Cumulative Layout Shift</div>
                                                        <div id="cwv-mobile-cls" class="cwv-metric-val">-</div>
                                                    </div>
                                                    <div class="cwv-metric-item" style="grid-column: span 2;">
                                                        <div class="cwv-metric-lbl">Speed Index</div>
                                                        <div id="cwv-mobile-si" class="cwv-metric-val">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="cwv-placeholder" style="text-align: center; padding: 40px 0; color: var(--text-muted);">
                                        <i data-lucide="gauge" style="width: 40px; height: 40px; margin-bottom: 12px;"></i>
                                        <p>No Core Web Vitals data pulled yet. Click "Refresh Desktop" or "Refresh Mobile" to run automated analysis.</p>
                                    </div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: minmax(auto, 676px) 350px 350px; gap: 0 24px; width: 100%;">
                                
                                <!-- Row 1, Column 1: Header & Form -->
                                <div style="grid-column: 1; grid-row: 1; display: flex; align-items: center; margin-bottom: 16px; gap: 24px; flex-wrap: wrap;">
                                    <h4 style="font-weight: 600; margin: 0; white-space: nowrap;">Page-level Indexing & Crawl Errors</h4>
                                    <form id="tech-scrape-form" onsubmit="addPageTech(event)" style="display: flex; gap: 8px; align-items: center; max-width: 320px; flex-grow: 1;">
                                        <input type="url" id="tech-scrape-url" class="form-input" style="padding: 6px 12px; font-size: 0.85rem;" placeholder="Paste webpage URL..." required>
                                        <button type="submit" class="btn btn-primary" style="white-space: nowrap; padding: 6px 12px; font-size: 0.85rem; display: flex; align-items: center; gap: 4px;">
                                            <i data-lucide="scan" style="width: 14px; height: 14px;"></i>
                                            <span>Crawl & Add</span>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Row 2, Column 1: Table Wrapper -->
                                <div class="table-wrapper" style="grid-column: 1; grid-row: 2; margin-bottom: 30px;">
                                    <table class="table-custom" id="tech-pages-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 50%;">URL</th>
                                                <th style="width: 25%; text-align: center;">Indexed in GSC</th>
                                                <th style="width: 25%; text-align: center;">Crawl Errors</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tech-pages-list">
                                            <!-- Pages list for tech properties -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Row 2, Column 2: Sitemap Status & Setup Details -->
                                <div class="glass-panel" style="grid-column: 2; grid-row: 2; padding: 24px; margin-bottom: 30px; display: flex; flex-direction: column;">
                                    <h4 style="margin-bottom: 16px; font-weight: 600;">Sitemap Status & Setup Details</h4>
                                    <div class="form-group" style="margin-bottom: 0; flex-grow: 1; display: flex; flex-direction: column;">
                                        <textarea id="sitemap-details" class="form-input" style="flex-grow: 1; min-height: 120px; resize: vertical;" placeholder="Add sitemap submission status, URL, last read dates, and crawl coverage..." onchange="saveAuditMetrics(true)"></textarea>
                                    </div>
                                </div>

                                <!-- Row 2, Column 3: Additional Notes -->
                                <div class="glass-panel" style="grid-column: 3; grid-row: 2; padding: 24px; margin-bottom: 30px; display: flex; flex-direction: column;">
                                    <h4 style="margin-bottom: 16px; font-weight: 600;">Additional Notes</h4>
                                    <div class="form-group" style="margin-bottom: 0; flex-grow: 1; display: flex; flex-direction: column;">
                                        <textarea id="additional-notes" class="form-input" style="flex-grow: 1; min-height: 120px; resize: vertical;" placeholder="Add other observations, technical guidelines, or custom audit notes..." onchange="saveAuditMetrics(true)"></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Subtab: Traffic & Performance -->
                        <div id="subtab-perf" style="display: none;">
                            <div class="glass-panel" style="padding: 30px;">
                                <h3 style="font-weight: 700; margin-bottom: 24px; border-bottom: 1px solid var(--border-glass); padding-bottom: 12px;">Website Traffic & Performance Metrics</h3>
                                
                                <div class="grid-form">
                                    <!-- Left Column: Metrics -->
                                    <div style="display: flex; flex-direction: column; gap: 20px;">
                                        <!-- Row 1: Bounce Rate & Pages Per Visit -->
                                        <div style="display: flex; gap: 15px;">
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label for="perf-bounce-rate">Bounce Rate (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" id="perf-bounce-rate" class="form-input" placeholder="e.g. 45.5" onchange="saveAuditMetrics(true)">
                                            </div>
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label for="perf-pages-per-visit">Pages Per Visit</label>
                                                <input type="number" step="0.01" min="0" id="perf-pages-per-visit" class="form-input" placeholder="e.g. 3.2" onchange="saveAuditMetrics(true)">
                                            </div>
                                        </div>

                                        <!-- Row 2: Average Monthly Visits & Average Visit Duration -->
                                        <div style="display: flex; gap: 15px;">
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label for="perf-avg-monthly-visits">Average Monthly Visits</label>
                                                <input type="number" min="0" id="perf-avg-monthly-visits" class="form-input" placeholder="e.g. 50000" onchange="saveAuditMetrics(true)">
                                            </div>
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label style="margin-bottom: 6px; display: block;">Average Visit Duration</label>
                                                <div style="display: flex; gap: 8px;">
                                                    <div style="flex: 1; display: flex; align-items: center; gap: 4px;">
                                                        <input type="number" min="0" id="perf-avg-visit-duration-min" class="form-input" placeholder="Min" style="width: 100%;" onchange="saveAuditMetrics(true)">
                                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">m</span>
                                                    </div>
                                                    <div style="flex: 1; display: flex; align-items: center; gap: 4px;">
                                                        <input type="number" min="0" max="59" id="perf-avg-visit-duration-sec" class="form-input" placeholder="Sec" style="width: 100%;" onchange="saveAuditMetrics(true)">
                                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">s</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row 3: Global Rank & Country Rank -->
                                        <div style="display: flex; gap: 15px;">
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label for="perf-global-ranking">Global Rank</label>
                                                <input type="number" min="0" id="perf-global-ranking" class="form-input" placeholder="e.g. 150000" onchange="saveAuditMetrics(true)">
                                            </div>
                                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                <label for="perf-country-ranking">Country Rank</label>
                                                <div style="display: flex; gap: 8px; align-items: center;">
                                                    <input type="number" min="0" id="perf-country-ranking" class="form-input" placeholder="e.g. 500" style="flex: 1;" onchange="saveAuditMetrics(true)">
                                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">in</span>
                                                    <input type="text" id="perf-target-country" class="form-input" placeholder="e.g. France" style="flex: 1.5;" onchange="saveAuditMetrics(true)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Breakdown By Country -->
                                    <div style="display: flex; flex-direction: column; height: 100%;">
                                        <div class="form-group" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0;">
                                            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 8px;">
                                                <label style="margin-bottom: 0;">Breakdown By Country</label>
                                                <div class="mode-tabs" style="display: flex; gap: 4px;">
                                                    <button type="button" class="btn btn-secondary btn-sm" id="btn-breakdown-mode-text" onclick="setBreakdownMode('text')" style="font-size: 0.75rem; padding: 2px 8px; background: rgba(139, 92, 246, 0.15); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);">Text</button>
                                                    <button type="button" class="btn btn-secondary btn-sm" id="btn-breakdown-mode-screenshot" onclick="setBreakdownMode('screenshot')" style="font-size: 0.75rem; padding: 2px 8px;">Screenshot</button>
                                                </div>
                                            </div>
                                            
                                            <!-- Mode Text: Textarea -->
                                            <div id="breakdown-text-container" style="flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
                                                <textarea id="perf-breakdown-country" class="form-input" style="flex-grow: 1; min-height: 180px; resize: vertical; height: 100%;" placeholder="e.g. USA: 40%, France: 20%, Germany: 15%..." onchange="saveAuditMetrics(true)"></textarea>
                                            </div>
                                            
                                            <!-- Mode Screenshot: Upload Zone & Preview -->
                                            <div id="breakdown-screenshot-container" style="display: none; flex-grow: 1; flex-direction: column; height: 100%;">
                                                <div class="screenshot-upload-zone" id="zone-breakdown-country" onclick="triggerFileInput('perf-breakdown-country-file')" style="flex-grow: 1; height: 100%; min-height: 180px; padding: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; overflow: hidden; position: relative;">
                                                    <input type="file" id="perf-breakdown-country-file" accept="image/*" style="display: none;" onchange="handleFileChange(event, 'breakdown-country')">
                                                    <div class="upload-placeholder" id="placeholder-breakdown-country" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px;">
                                                        <i data-lucide="image" style="width: 28px; height: 28px; color: var(--text-muted); margin-bottom: 8px;"></i>
                                                        <p style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; margin: 0; text-align: center; line-height: 1.4;">Click, drag & drop, or paste (Ctrl+V) breakdown screenshot</p>
                                                    </div>
                                                    <div class="upload-preview" id="preview-breakdown-country" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                                        <img src="" id="img-breakdown-country" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-sm); cursor: zoom-in;" onclick="event.stopPropagation(); openImageLightbox(this.src)">
                                                        <button type="button" class="btn-delete-screenshot" onclick="removeScreenshot(event, 'breakdown-country')">
                                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                            <span>Remove</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Main Channels</label>
                                        <div class="screenshot-upload-zone" id="zone-main-channels" onclick="triggerFileInput('perf-main-channels-file')" style="min-height: 280px;">
                                            <input type="file" id="perf-main-channels-file" accept="image/*" style="display: none;" onchange="handleFileChange(event, 'main-channels')">
                                            <div class="upload-placeholder" id="placeholder-main-channels">
                                                <i data-lucide="image" style="width: 28px; height: 28px; color: var(--text-muted); margin-bottom: 8px;"></i>
                                                <p style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Click or drag & drop to upload Main Channels screenshot</p>
                                            </div>
                                            <div class="upload-preview" id="preview-main-channels" style="display: none; position: relative;">
                                                <img src="" id="img-main-channels" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--radius-sm);">
                                                <button type="button" class="btn-delete-screenshot" onclick="removeScreenshot(event, 'main-channels')">
                                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Traffic Trends (Last 3-6 Months)</label>
                                        <div class="screenshot-upload-zone" id="zone-traffic-trends" onclick="triggerFileInput('perf-traffic-trends-file')" style="min-height: 280px;">
                                            <input type="file" id="perf-traffic-trends-file" accept="image/*" style="display: none;" onchange="handleFileChange(event, 'traffic-trends')">
                                            <div class="upload-placeholder" id="placeholder-traffic-trends">
                                                <i data-lucide="image" style="width: 28px; height: 28px; color: var(--text-muted); margin-bottom: 8px;"></i>
                                                <p style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Click or drag & drop to upload Traffic Trends screenshot</p>
                                            </div>
                                            <div class="upload-preview" id="preview-traffic-trends" style="display: none; position: relative;">
                                                <img src="" id="img-traffic-trends" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--radius-sm);">
                                                <button type="button" class="btn-delete-screenshot" onclick="removeScreenshot(event, 'traffic-trends')">
                                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Search Terms -->
                    <div id="tab-search-terms" class="tab-pane">
                        <div class="glass-panel scraper-box">
                            <h4 style="margin-bottom: 12px; font-weight: 600;">Add Keyword / Search Term</h4>
                            <form id="search-term-form" class="scraper-form" onsubmit="addSearchTerm(event)">
                                <input type="text" id="new-search-term" class="form-input" placeholder="e.g. best seo agency in paris..." required>
                                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                                    <span>Add Term</span>
                                </button>
                            </form>
                        </div>

                        <div class="flex-space" style="margin-bottom: 24px;">
                            <h3 style="font-weight: 700;">Configured Search Terms</h3>
                            <button class="btn btn-secondary" onclick="suggestCompetitors()">
                                <i data-lucide="sparkles" style="width: 16px; height: 16px; color: var(--secondary);"></i>
                                <span>Generate Competitor Suggestions</span>
                            </button>
                        </div>

                        <div id="search-terms-wrapper" style="display: flex; flex-direction: column; gap: 24px;">
                            <!-- Search terms cards dynamically rendered -->
                        </div>
                    </div>

                    <!-- Tab 3: Competitor Analysis -->
                    <div id="tab-competitor-analysis" class="tab-pane">
                        <!-- Sub-navigation for Competitor Analysis -->
                        <div class="flex-space" style="margin-bottom: 24px;">
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-secondary btn-sm" id="comp-subtab-btn-seo" onclick="switchCompSubTab('seo')" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);">SEO State</button>
                                <button class="btn btn-secondary btn-sm" id="comp-subtab-btn-tech" onclick="switchCompSubTab('tech')">Technical State</button>
                                <button class="btn btn-secondary btn-sm" id="comp-subtab-btn-perf" onclick="switchCompSubTab('perf')">Traffic & Performance</button>
                            </div>
                        </div>

                        <!-- Subtab: SEO State -->
                        <div id="comp-subtab-seo">
                            <div class="glass-panel scraper-box">
                                <h4 style="margin-bottom: 12px; font-weight: 600;">Add Competitor Page Manually</h4>
                                <form id="competitor-manual-form" class="scraper-form" onsubmit="addCompetitorManual(event)">
                                    <input type="url" id="comp-manual-url" class="form-input" style="flex-grow: 2;" placeholder="Paste competitor webpage URL (e.g. https://competitor.com/page)..." required>
                                    <input type="text" id="comp-manual-terms" class="form-input" style="flex-grow: 1;" placeholder="Search term(s)...">
                                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                                        <i data-lucide="scan" style="width: 16px; height: 16px;"></i>
                                        <span>Crawl & Add</span>
                                    </button>
                                </form>
                            </div>

                            <h3 style="font-weight: 700; margin-bottom: 20px;">Audited Competitors</h3>

                            <div class="table-wrapper">
                                <table class="table-custom table-wide" id="competitor-analysis-table">
                                    <thead>
                                        <tr>
                                            <th>Domain name</th>
                                            <th>Meta Title</th>
                                            <th>Meta Description</th>
                                            <th>H1 Tag</th>
                                            <th>Semantic Headers</th>
                                            <th>Internal Links</th>
                                            <th>External Links</th>
                                            <th>Missing Alt</th>
                                            <th>Search Terms</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="competitors-analysis-list">
                                        <!-- Competitor pages render via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Subtab: Technical State -->
                        <div id="comp-subtab-tech" style="display: none;">
                            <div class="glass-panel scraper-box" style="margin-bottom: 24px;">
                                <h4 style="margin-bottom: 12px; font-weight: 600;">Add Competitor Page Manually</h4>
                                <form id="competitor-manual-form-tech" class="scraper-form" onsubmit="addCompetitorManualTech(event)">
                                    <input type="url" id="comp-manual-url-tech" class="form-input" style="flex-grow: 2;" placeholder="Paste competitor webpage URL (e.g. https://competitor.com/page)..." required>
                                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                                        <i data-lucide="scan" style="width: 16px; height: 16px;"></i>
                                        <span>Crawl & Add</span>
                                    </button>
                                </form>
                            </div>
                            <div id="comp-tech-cards-container" style="display: flex; flex-direction: column; gap: 24px;">
                                <!-- Competitor Speed cards populated via JS -->
                            </div>
                        </div>

                        <!-- Subtab: Traffic & Performance -->
                        <div id="comp-subtab-perf" style="display: none;">
                            <div class="glass-panel scraper-box" style="margin-bottom: 24px;">
                                <h4 style="margin-bottom: 12px; font-weight: 600;">Add Competitor Page Manually</h4>
                                <form id="competitor-manual-form-perf" class="scraper-form" onsubmit="addCompetitorManualPerf(event)">
                                    <input type="url" id="comp-manual-url-perf" class="form-input" style="flex-grow: 2;" placeholder="Paste competitor webpage URL (e.g. https://competitor.com/page)..." required>
                                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                                        <i data-lucide="scan" style="width: 16px; height: 16px;"></i>
                                        <span>Crawl & Add</span>
                                    </button>
                                </form>
                            </div>
                            <div id="comp-perf-cards-container" style="display: flex; flex-direction: column; gap: 24px;">
                                <!-- Competitor Traffic forms populated via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: Global Report & Strategy -->
                    <div id="tab-global-report" class="tab-pane">
                        <div class="glass-panel" style="padding: 28px; margin-bottom: 24px;">
                            <h3 style="font-weight: 700; margin-bottom: 16px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="file-text" style="width: 22px; height: 22px; color: var(--primary);"></i>
                                <span>Global Analysis & Strategic Report</span>
                            </h3>
                            <p style="color: var(--text-secondary); margin-bottom: 28px; font-size: 0.95rem; line-height: 1.5;">
                                Use these sections to write custom takeaways, audit summaries, and recommendations for your client. Content will save automatically as you type.
                            </p>
                            
                            <div class="form-group" style="margin-bottom: 28px; display: flex; flex-direction: column; gap: 8px;">
                                <label for="global-report-analysis" style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="compass" style="width: 16px; height: 16px; color: var(--primary);"></i>
                                    <span>Global Analysis & Audit Meaning</span>
                                </label>
                                <textarea id="global-report-analysis" class="form-input" style="min-height: 250px; resize: vertical; width: 100%; font-family: inherit; line-height: 1.5; padding: 14px;" placeholder="Explain the global findings of this audit. What do these metrics mean for the client's website? (e.g. Traffic Trends, Keyword Gaps, Core Web Vitals summary...)" onchange="saveAuditMetrics(true)"></textarea>
                            </div>
                            
                            <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                                <label for="global-report-strategy" style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="trending-up" style="width: 16px; height: 16px; color: var(--secondary);"></i>
                                    <span>Recommendations & Strategy to Adopt</span>
                                </label>
                                <textarea id="global-report-strategy" class="form-input" style="min-height: 250px; resize: vertical; width: 100%; font-family: inherit; line-height: 1.5; padding: 14px;" placeholder="Outline the concrete recommendations and next steps. What strategy should they execute next?" onchange="saveAuditMetrics(true)"></textarea>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- -------------------------------------------------------------
         MODALS
         ------------------------------------------------------------- -->

    <!-- Modal 1: New Client -->
    <div id="new-client-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'new-client-modal')">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Create Client Profile</h3>
                <button class="modal-close" onclick="closeModal('new-client-modal')">&times;</button>
            </div>
            <form id="new-client-form" onsubmit="createClient(event)">
                <div class="form-group">
                    <label for="client-name-input">Client Name</label>
                    <input type="text" id="client-name-input" class="form-input" placeholder="e.g. ACME Corp" required>
                </div>
                <div class="form-group">
                    <label for="client-url-input">Homepage URL</label>
                    <input type="url" id="client-url-input" class="form-input" placeholder="e.g. https://acme.com" required>
                </div>
                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="client-industry-input">Industry</label>
                    <input type="text" id="client-industry-input" class="form-input" placeholder="e.g. E-Commerce / SaaS">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('new-client-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 1.5: Edit Client -->
    <div id="edit-client-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'edit-client-modal')">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Edit Client Profile</h3>
                <button class="modal-close" onclick="closeModal('edit-client-modal')">&times;</button>
            </div>
            <form id="edit-client-form" onsubmit="saveClientEdit(event)">
                <input type="hidden" id="edit-client-id">
                <div class="form-group">
                    <label for="edit-client-name-input">Client Name</label>
                    <input type="text" id="edit-client-name-input" class="form-input" placeholder="e.g. ACME Corp" required>
                </div>
                <div class="form-group">
                    <label for="edit-client-url-input">Homepage URL</label>
                    <input type="url" id="edit-client-url-input" class="form-input" placeholder="e.g. https://acme.com" required>
                </div>
                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="edit-client-industry-input">Industry</label>
                    <input type="text" id="edit-client-industry-input" class="form-input" placeholder="e.g. E-Commerce / SaaS">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('edit-client-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: New Audit -->
    <div id="new-audit-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'new-audit-modal')">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Start New Website Audit</h3>
                <button class="modal-close" onclick="closeModal('new-audit-modal')">&times;</button>
            </div>
            <form id="new-audit-form" onsubmit="createAudit(event)">
                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="audit-name-input">Audit Campaign Name</label>
                    <input type="text" id="audit-name-input" class="form-input" placeholder="e.g. Q2 2026 Core Audit" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('new-audit-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Audit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Edit Page Metrics -->
    <div id="edit-page-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'edit-page-modal')">
        <div class="modal-content glass-panel" style="max-width: 750px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;" id="edit-page-modal-title">Edit Page Metrics</h3>
                <button class="modal-close" onclick="closeModal('edit-page-modal')">&times;</button>
            </div>
            <form id="edit-page-form" onsubmit="savePageUpdate(event)">
                <input type="hidden" id="edit-page-id">
                <input type="hidden" id="edit-page-type"> <!-- 'page' or 'competitor' -->
                
                <div class="grid-form">
                    <div class="form-group full-width">
                        <label>Webpage URL (Read-only)</label>
                        <input type="text" id="edit-page-url" class="form-input" style="opacity: 0.65;" readonly>
                    </div>

                    <!-- Crawled properties override -->
                    <div class="form-group">
                        <label for="edit-page-meta-title">Meta Title Override</label>
                        <input type="text" id="edit-page-meta-title" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-meta-description">Meta Description Override</label>
                        <input type="text" id="edit-page-meta-description" class="form-input">
                    </div>
                    <div class="form-group full-width">
                        <label for="edit-page-h1">H1 Tag Override</label>
                        <input type="text" id="edit-page-h1" class="form-input">
                    </div>

                    <!-- Manual Metrics -->
                    <div class="form-group">
                        <label for="edit-page-visits">Monthly Visits</label>
                        <input type="number" min="0" id="edit-page-visits" class="form-input" placeholder="e.g. 1500">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-duration">Avg. Time per Visit (Seconds)</label>
                        <input type="number" min="0" id="edit-page-duration" class="form-input" placeholder="e.g. 90">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-audience">Audience Country + proportion %</label>
                        <input type="text" id="edit-page-audience" class="form-input" placeholder="e.g. US: 60%, CA: 15%">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-search-terms">Search Terms</label>
                        <input type="text" id="edit-page-search-terms" class="form-input" placeholder="e.g. best seo, agencies">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-global-rank">Global Ranking</label>
                        <input type="number" min="0" id="edit-page-global-rank" class="form-input" placeholder="e.g. 240000">
                    </div>
                    <div class="form-group">
                        <label for="edit-page-country-rank">Ranking in Website's Country</label>
                        <input type="number" min="0" id="edit-page-country-rank" class="form-input" placeholder="e.g. 45000">
                    </div>

                    <!-- Technical fields (Only visible for website pages, not competitor pages) -->
                    <div class="form-group tech-only">
                        <label for="edit-page-gsc">Indexed in GSC</label>
                        <select id="edit-page-gsc" class="form-input form-select">
                            <option value="">-</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group tech-only">
                        <label for="edit-page-errors">Crawl Errors</label>
                        <select id="edit-page-errors" class="form-input form-select">
                            <option value="">-</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('edit-page-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Semantic Headers Editor -->
    <div id="headers-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'headers-modal')">
        <div class="modal-content glass-panel" style="max-width: 980px; width: 90%;">
            <div class="modal-header" style="margin-bottom: 16px;">
                <h3 style="font-weight: 700;">Semantic Headers Structure</h3>
                <button class="modal-close" onclick="closeModal('headers-modal')">&times;</button>
            </div>
            
            <div class="mode-tabs" style="display: flex; gap: 10px; margin-bottom: 16px; align-items: center;">
                <button type="button" class="btn btn-secondary btn-sm" id="btn-header-mode-text" onclick="setHeaderMode('text')" style="background: rgba(139, 92, 246, 0.15); border-color: rgba(139, 92, 246, 0.3); color: var(--primary); font-size: 0.8rem; padding: 6px 14px;">Text Structure</button>
                <button type="button" class="btn btn-secondary btn-sm" id="btn-header-mode-screenshot" onclick="setHeaderMode('screenshot')" style="font-size: 0.8rem; padding: 6px 14px;">Screenshot</button>
                <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-re-fetch-headers" onclick="reFetchHeaders(this)" style="font-size: 0.8rem; padding: 6px 14px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i>
                        <span>Re-fetch</span>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="clearHeadersStructure()" style="font-size: 0.8rem; padding: 6px 14px;">Delete/Clear All</button>
                </div>
            </div>

            <div style="margin-bottom: 20px; height: 420px;">
                <!-- Tab 1: Text Structure -->
                <div id="headers-text-container" style="display: block; height: 100%;">
                    <div id="headers-modal-tree" class="headers-structure-tree" style="height: 100%; max-height: 100%; overflow-y: auto; background: rgba(0, 0, 0, 0.2); padding: 16px; border-radius: var(--radius-sm);">
                        <!-- Tree items rendered via JS -->
                    </div>
                </div>

                <!-- Tab 2: Screenshot Container -->
                <div id="headers-screenshot-container" style="display: none; height: 100%;">
                    <div id="headers-upload-zone" class="screenshot-upload-zone" style="height: 100%; border: 2px dashed rgba(255, 255, 255, 0.15); border-radius: var(--radius-md); background: rgba(0, 0, 0, 0.15); padding: 40px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;" onclick="document.getElementById('headers-screenshot-file').click()">
                        <i data-lucide="image" style="width: 36px; height: 36px; color: var(--text-muted);"></i>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                            Drag & drop, <span style="color: var(--primary); text-decoration: underline;">browse</span>, or paste screenshot (Ctrl+V)
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Supports PNG, JPG, JPEG, WEBP</div>
                        <input type="file" id="headers-screenshot-file" accept="image/*" style="display: none;" onchange="uploadHeadersScreenshotFile(event)">
                    </div>

                    <div id="headers-screenshot-preview-box" style="display: none; height: 100%; position: relative; text-align: center; display: flex; flex-direction: column; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; display: flex; align-items: center; justify-content: center; width: 100%; overflow: hidden;">
                            <img id="headers-screenshot-img" src="" style="max-width: 100%; max-height: 350px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); object-fit: contain;" />
                        </div>
                        <div style="margin-top: 12px; height: 38px;">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeHeadersScreenshot()" style="font-size: 0.8rem; padding: 6px 14px;">Remove Screenshot</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer with table of H1-H6 count inputs and action buttons -->
            <div class="flex-space" style="border-top: 1px solid var(--border-glass); padding-top: 20px; flex-wrap: wrap; gap: 16px;">
                <!-- H1-H6 Counts Editor -->
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H1</div>
                        <input type="number" id="edit-h1-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H2</div>
                        <input type="number" id="edit-h2-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H3</div>
                        <input type="number" id="edit-h3-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H4</div>
                        <input type="number" id="edit-h4-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H5</div>
                        <input type="number" id="edit-h5-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 4px;">H6</div>
                        <input type="number" id="edit-h6-count" class="form-input" style="width: 50px; padding: 6px; text-align: center; font-size: 0.85rem;" min="0">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('headers-modal')">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveHeadersStructure()">Save Headers</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 5: Competitor Suggestions -->
    <div id="suggestions-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'suggestions-modal')">
        <div class="modal-content glass-panel" style="max-width: 800px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Competitor Recommendations Engine</h3>
                <button class="modal-close" onclick="closeModal('suggestions-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 20px;">We analyzed all organic competitor URLs entered across your active search terms, grouped them by domain, and ranked them by frequency. Select which domains to auto-import into Competitor Analysis.</p>
                
                <div id="suggestions-loader" style="text-align: center; padding: 30px 0;">
                    <div class="spinner"></div>
                    <p style="color: var(--text-secondary); margin-top: 8px;">Analyzing keywords data...</p>
                </div>

                <div id="suggestions-empty" style="display: none; text-align: center; padding: 30px 0; color: var(--text-muted);">
                    <i data-lucide="info" style="width: 32px; height: 32px; margin-bottom: 8px;"></i>
                    <p>No organic competitors found. Make sure to add competitors under your search terms first.</p>
                </div>

                <div id="suggestions-list" style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
                    <!-- Suggestions rendered here -->
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('suggestions-modal')">Cancel</button>
                <button type="button" id="suggestions-send-btn" class="btn btn-primary" onclick="sendSelectedSuggestions()" style="display: none;">Send Selected to Competitor Analysis</button>
            </div>
        </div>
    </div>

    <!-- Modal for Import Results / Crawl Summary -->
    <div id="import-results-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'import-results-modal')">
        <div class="modal-content glass-panel" style="max-width: 650px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Import & Crawl Status</h3>
                <button class="modal-close" onclick="closeModal('import-results-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="import-results-summary" style="margin-bottom: 20px; font-weight: 500; font-size: 1.05rem;">
                    <!-- Successfully imported X competitors. -->
                </div>
                
                <div id="import-results-warning" style="display: none; padding: 16px; border-radius: var(--radius-sm); background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i data-lucide="alert-triangle" style="flex-shrink: 0; width: 20px; height: 20px; color: #fbbf24;"></i>
                        <div>
                            <strong style="display: block; margin-bottom: 4px; color: #fbbf24;">Crawling Restricted by Server</strong>
                            <p style="font-size: 0.85rem; line-height: 1.4; color: var(--text-secondary); margin: 0;">
                                Some websites could not be crawled because they block automated scripts (e.g. Cloudflare protection).
                                They have still been added to your Competitor Analysis table with empty details, so you can manually fill in the metrics.
                            </p>
                        </div>
                    </div>
                </div>

                <div id="import-results-errors-list" style="display: none;">
                    <h5 style="margin-bottom: 8px; font-size: 0.9rem; color: var(--text-secondary);">Crawl failures:</h5>
                    <div id="import-results-errors-container" style="max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 12px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8rem; line-height: 1.5; color: var(--text-secondary); display: flex; flex-direction: column; gap: 8px;">
                        <!-- Failures list -->
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-primary" onclick="closeModal('import-results-modal')">Close</button>
            </div>
        </div>
    </div>



    <!-- Modal 7: View Full Text / Edit -->
    <div id="text-viewer-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'text-viewer-modal')">
        <div class="modal-content glass-panel" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;" id="text-viewer-field-name">Field Value</h3>
                <button class="modal-close" onclick="closeModal('text-viewer-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;" id="text-viewer-url">URL: </div>
                <div class="form-group">
                    <textarea id="text-viewer-content" class="form-input" style="height: 160px; resize: vertical; opacity: 0.9; font-family: inherit;"></textarea>
                    <div id="text-viewer-hint" style="display: none; font-size: 0.8rem; color: var(--primary); margin-top: 8px; align-items: center; gap: 6px;">
                        <i data-lucide="info" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                        <span>Enter one search term per line to generate bullet points in the table.</span>
                    </div>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); text-align: right;" id="text-viewer-char-count">0 characters</div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('text-viewer-modal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTextViewerContent()">Save Changes</button>
            </div>
        </div>
    </div>



    <!-- Modal 8: Audience Countries -->
    <div id="audience-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'audience-modal')">
        <div class="modal-content glass-panel" style="max-width: 650px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Audience Location & Proportions</h3>
                <button class="modal-close" onclick="closeModal('audience-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 16px;">Top 5 countries contributing to this page's traffic. Enter country names and proportion percentages below.</p>
                
                <div style="display: flex; gap: 24px; flex-direction: column; margin-bottom: 24px;">
                    <div id="audience-editor-rows" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- JS renders 5 rows of inputs -->
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-glass); padding-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('audience-modal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAudience()">Save Audience</button>
            </div>
        </div>
    </div>


    <!-- -------------------------------------------------------------
         CLIENT SCRIPT LOGIC
         ------------------------------------------------------------- -->
    <script>
        // Global State variables
        let activeClientId = null;
        let activeAuditId = null;
        let activeAuditCountry = 'Website\'s Country';
        let pagesData = [];
        let searchTermsData = [];
        let collapsedSearchTerms = new Set();
        let competitorsData = [];
        let competitorAnalysesData = [];
        let collapsedCompetitorTech = new Set();
        let interactedCompetitorTech = new Set();
        let collapsedCompetitorPerf = new Set();
        let collapsedAuditTech = false;
        let activeCompetitorId = null;
        let cwtData = null;
        let compCwvLoading = {};
        let activeDropdown = null;
        window.activeSavePromise = null;
        window.showSaveToastNext = false;

        // Toggle Sidebar collapsed state
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle-btn');
            
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
            
            // Update button icon and title
            if (toggleBtn) {
                const iconName = isCollapsed ? 'panel-left-open' : 'panel-left-close';
                toggleBtn.setAttribute('title', isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
                toggleBtn.innerHTML = `<i data-lucide="${iconName}" style="width: 18px; height: 18px;"></i>`;
            }
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // Initialize UI on load
        window.addEventListener('DOMContentLoaded', () => {
            // Apply saved sidebar collapsed state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle-btn');
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                if (toggleBtn) {
                    toggleBtn.setAttribute('title', 'Expand Sidebar');
                    toggleBtn.innerHTML = `<i data-lucide="panel-left-open" style="width: 18px; height: 18px;"></i>`;
                }
            }

            loadClients();
            loadWelcomeStats();
            handleUrlRouting();
            initDragAndDrop();
            initHeadersModalHandlers();
            lucide.createIcons();

            // Initialize column resizing on tables
            initTableResizing('seo-pages-table');
            initTableResizing('tech-pages-table');
            initTableResizing('competitor-analysis-table');

            // Listen for Ctrl+S or F5 keyboard shortcuts to save the audit and refresh
            window.addEventListener('keydown', (e) => {
                const isCtrlS = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's';
                const isF5 = e.key === 'F5';

                if (isCtrlS || isF5) {
                    if (activeAuditId) {
                        e.preventDefault();
                        
                        // First, trigger saving of any active editor or modal
                        const textViewerModal = document.getElementById('text-viewer-modal');
                        const audienceModal = document.getElementById('audience-modal');
                        
                        if (textViewerModal && textViewerModal.style.display === 'flex') {
                            saveTextViewerContent();
                        } else if (audienceModal && audienceModal.style.display === 'flex') {
                            saveAudience();
                        } else {
                            const activeForm = document.activeElement ? document.activeElement.closest('form') : null;
                            if (activeForm && (activeForm.id === 'edit-page-form' || activeForm.id === 'edit-client-form' || activeForm.classList.contains('edit-state'))) {
                                activeForm.requestSubmit();
                            } else if (document.activeElement && typeof document.activeElement.blur === 'function') {
                                document.activeElement.blur();
                            }
                        }
                        
                        const proceedSave = () => {
                            saveAuditMetrics(true).then((success) => {
                                if (success) {
                                    sessionStorage.setItem('savedToast', 'true');
                                    window.location.reload();
                                }
                            });
                        };
                        
                        if (window.activeSavePromise) {
                            window.activeSavePromise.then(proceedSave).catch(proceedSave);
                        } else {
                            // Small timeout in case the blur or submit handler takes a microtask to fire fetch
                            setTimeout(() => {
                                if (window.activeSavePromise) {
                                    window.activeSavePromise.then(proceedSave).catch(proceedSave);
                                } else {
                                    proceedSave();
                                }
                            }, 50);
                        }
                    }
                }
            });
        });

        // Handle reloading / deep linking with URL hashes
        let isRouting = false;

        function getActiveTab() {
            const activePane = document.querySelector('.tab-pane.active');
            return activePane ? activePane.id : 'tab-website-audit';
        }

        function getActiveSubTab() {
            if (document.getElementById('subtab-seo').style.display === 'block') return 'seo';
            if (document.getElementById('subtab-tech').style.display === 'block') return 'tech';
            if (document.getElementById('subtab-perf').style.display === 'block') return 'perf';
            return 'seo';
        }

        function handleUrlRouting() {
            const hash = window.location.hash;
            if (hash.startsWith('#')) {
                const params = new URLSearchParams(hash.substring(1));
                const cId = parseInt(params.get('client'));
                const aId = parseInt(params.get('audit'));
                const tab = params.get('tab');
                const subtab = params.get('subtab');

                if (cId) {
                    isRouting = true;
                    selectClient(cId, false).then(() => {
                        if (aId) {
                            return selectAudit(aId);
                        }
                    }).then(() => {
                        if (tab) {
                            switchTab(tab);
                        }
                        if (subtab) {
                            switchSubTab(subtab);
                        }
                        isRouting = false;
                        updateUrlHash();

                        // Show saved toast if page reload was triggered by save
                        if (sessionStorage.getItem('savedToast') === 'true') {
                            sessionStorage.removeItem('savedToast');
                            setTimeout(() => {
                                showToast('Metrics saved', 'success');
                            }, 150);
                        }
                    }).catch(err => {
                        isRouting = false;
                        console.error(err);
                    });
                } else {
                    if (sessionStorage.getItem('savedToast') === 'true') {
                        sessionStorage.removeItem('savedToast');
                        showToast('Metrics saved', 'success');
                    }
                }
            } else {
                if (sessionStorage.getItem('savedToast') === 'true') {
                    sessionStorage.removeItem('savedToast');
                    showToast('Metrics saved', 'success');
                }
            }
        }

        function updateUrlHash() {
            if (isRouting) return;
            if (activeClientId && activeAuditId) {
                const activeTab = getActiveTab();
                const activeSubTab = getActiveSubTab();
                window.location.hash = `client=${activeClientId}&audit=${activeAuditId}&tab=${activeTab}&subtab=${activeSubTab}`;
            } else if (activeClientId) {
                window.location.hash = `client=${activeClientId}`;
            } else {
                window.location.hash = '';
            }
        }

        // Welcome panel statistics loader
        function loadWelcomeStats() {
            fetch('api.php?action=clients_list')
                .then(res => res.json())
                .then(clients => {
                    document.getElementById('stat-clients-count').textContent = clients.length;
                });
        }

        // -------------------------------------------------------------
        // Clients Management
        // -------------------------------------------------------------
        function loadClients() {
            const search = document.getElementById('client-search').value;
            fetch(`api.php?action=clients_list&search=${encodeURIComponent(search)}`)
                .then(res => res.json())
                .then(clients => {
                    const listContainer = document.getElementById('clients-list');
                    listContainer.innerHTML = '';
                    
                    if (clients.length === 0) {
                        listContainer.innerHTML = '<div style="font-size:0.85rem; color:var(--text-muted); text-align:center; padding: 20px 0;">No clients found.</div>';
                        return;
                    }

                    clients.forEach(c => {
                        const item = document.createElement('div');
                        item.className = `client-item glass-card ${activeClientId === parseInt(c.id) ? 'active' : ''}`;
                        item.style.padding = '8px 12px'; // slightly smaller padding
                        item.onclick = () => selectClient(parseInt(c.id));
                        
                        item.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-grow: 1; min-width: 0; padding-right: 4px; overflow: hidden;">
                                <a href="${escapeHtml(c.homepage_url)}" target="_blank" onclick="event.stopPropagation();" class="client-name" style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;" title="Visit ${escapeHtml(c.homepage_url)}">
                                    <span>${escapeHtml(c.name)}</span>
                                    <i data-lucide="external-link" style="width: 11px; height: 11px; color: var(--text-muted); flex-shrink: 0;"></i>
                                </a>
                                ${c.industry ? `
                                    <span class="client-industry-text" title="${escapeHtml(c.industry)}" style="font-size: 0.75rem; color: var(--text-muted); font-style: italic; margin-left: auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px; text-align: right;">
                                        ${escapeHtml(c.industry)}
                                    </span>
                                ` : ''}
                            </div>
                            <div class="client-actions-wrapper">
                                <button class="btn btn-secondary btn-icon" style="padding: 4px; border-radius: 6px; border: 1px solid var(--border-glass);" onclick="openEditClientModal(event, ${c.id}, '${escapeHtml(c.name.replace(/'/g, "\\'"))}', '${escapeHtml(c.homepage_url.replace(/'/g, "\\'"))}', '${escapeHtml((c.industry || '').replace(/'/g, "\\'"))}')" title="Edit Client">
                                    <i data-lucide="pencil" style="width:12px; height:12px;"></i>
                                </button>
                                <button class="btn btn-danger btn-icon" style="padding: 4px; border-radius: 6px;" onclick="deleteClient(event, ${c.id})" title="Delete Client">
                                    <i data-lucide="trash-2" style="width:12px; height:12px;"></i>
                                </button>
                            </div>
                        `;



                        listContainer.appendChild(item);
                    });
                    lucide.createIcons();
                });
        }

        function selectClient(id, resetAudit = true) {
            return fetch(`api.php?action=clients_list`)
                .then(res => res.json())
                .then(clients => {
                    const client = clients.find(c => parseInt(c.id) === id);
                    if (!client) return;

                    activeClientId = id;
                    document.getElementById('client-select-state').style.display = 'none';
                    document.getElementById('client-active-state').style.display = 'block';
                    
                    const collClientSelect = document.getElementById('collapsed-client-select-state');
                    const collClientActive = document.getElementById('collapsed-client-active-state');
                    if (collClientSelect) collClientSelect.style.display = 'none';
                    if (collClientActive) collClientActive.style.display = 'flex';

                    const industryEl = document.getElementById('active-client-industry');
                    if (client.industry) {
                        industryEl.textContent = client.industry;
                        industryEl.setAttribute('title', client.industry);
                        industryEl.style.display = 'inline-block';
                    } else {
                        industryEl.style.display = 'none';
                    }

                    document.getElementById('active-client-name').textContent = client.name;
                    document.getElementById('active-client-url').href = client.homepage_url;

                    if (resetAudit) {
                        closeAuditWorkspace();
                    }

                    updateUrlHash();
                    loadAudits();
                    loadWelcomeStats();
                });
        }

        function clearActiveClient() {
            activeClientId = null;
            document.getElementById('client-active-state').style.display = 'none';
            document.getElementById('client-select-state').style.display = 'block';
            
            const collClientSelect = document.getElementById('collapsed-client-select-state');
            const collClientActive = document.getElementById('collapsed-client-active-state');
            if (collClientSelect) collClientSelect.style.display = 'flex';
            if (collClientActive) collClientActive.style.display = 'none';

            closeAuditWorkspace();
            updateUrlHash();
            loadClients();
        }

        function createClient(e) {
            e.preventDefault();
            const name = document.getElementById('client-name-input').value;
            const homepage_url = document.getElementById('client-url-input').value;
            const industry = document.getElementById('client-industry-input').value;

            const formData = new FormData();
            formData.append('name', name);
            formData.append('homepage_url', homepage_url);
            formData.append('industry', industry);

            fetch('api.php?action=client_create', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('new-client-modal');
                    document.getElementById('new-client-form').reset();
                    loadClients();
                    selectClient(parseInt(data.id));
                } else {
                    alert(data.error || 'Failed to create client.');
                }
            });
        }

        function openEditClientModal(e, id, name, homepage_url, industry) {
            e.stopPropagation(); // prevent selecting the client when clicking edit button
            document.getElementById('edit-client-id').value = id;
            document.getElementById('edit-client-name-input').value = name;
            document.getElementById('edit-client-url-input').value = homepage_url;
            document.getElementById('edit-client-industry-input').value = industry || '';
            openModal('edit-client-modal');
        }

        function saveClientEdit(e) {
            e.preventDefault();
            const id = document.getElementById('edit-client-id').value;
            const name = document.getElementById('edit-client-name-input').value;
            const homepage_url = document.getElementById('edit-client-url-input').value;
            const industry = document.getElementById('edit-client-industry-input').value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', name);
            formData.append('homepage_url', homepage_url);
            formData.append('industry', industry);

            fetch('api.php?action=client_update', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('edit-client-modal');
                    loadClients();
                    if (activeClientId === parseInt(id)) {
                        // Refresh the active client details on the sidebar UI
                        selectClient(activeClientId, false);
                    }
                } else {
                    alert(data.error || 'Failed to update client.');
                }
            });
        }

        function deleteClient(e, id) {
            e.stopPropagation();
            if (!confirm('Are you sure you want to delete this client? All their audits, crawled data, and page history will be permanently deleted.')) {
                return;
            }

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=client_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (activeClientId === id) {
                        clearActiveClient();
                    } else {
                        loadClients();
                    }
                    loadWelcomeStats();
                } else {
                    alert(data.error);
                }
            });
        }

        // -------------------------------------------------------------
        // Audits Management
        // -------------------------------------------------------------
        function loadAudits() {
            if (!activeClientId) return;

            fetch(`api.php?action=audits_list&client_id=${activeClientId}`)
                .then(res => res.json())
                .then(audits => {
                    const listContainer = document.getElementById('audits-list');
                    listContainer.innerHTML = '';

                    // update stats
                    fetch(`api.php?action=clients_list`).then(r => r.json()).then(clients => {
                        let total = audits.length; // rough audit count for client
                        // We will just let global counts update
                    });

                    if (audits.length === 0) {
                        listContainer.innerHTML = '<div style="font-size:0.85rem; color:var(--text-muted); text-align:center; padding: 20px 0;">No audits yet.</div>';
                        return;
                    }

                    audits.forEach(a => {
                        const item = document.createElement('div');
                        item.className = `client-item glass-card ${activeAuditId === parseInt(a.id) ? 'active' : ''}`;
                        item.onclick = () => selectAudit(parseInt(a.id));

                        const date = new Date(a.created_at).toLocaleDateString();

                        item.innerHTML = `
                            <div>
                                <div class="client-name">${escapeHtml(a.name)}</div>
                                <div class="client-meta">Created: ${date}</div>
                            </div>
                            <button class="btn btn-danger btn-icon" style="padding:4px; opacity:0; transition:var(--transition);" onclick="deleteAudit(event, ${a.id})" title="Delete Audit">
                                <i data-lucide="trash-2" style="width:12px; height:12px;"></i>
                            </button>
                        `;

                        item.addEventListener('mouseenter', () => {
                            item.querySelector('.btn-danger').style.opacity = '1';
                        });
                        item.addEventListener('mouseleave', () => {
                            item.querySelector('.btn-danger').style.opacity = '0';
                        });

                        listContainer.appendChild(item);
                    });
                    lucide.createIcons();
                });
        }

        function selectAudit(id, showLoader = true) {
            if (showLoader) {
                showFullscreenLoader("Loading audit database...");
            }
            activeAuditId = id;

            // Update UI list active indicators
            loadAudits();

            return fetch(`api.php?action=audit_get&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (showLoader) {
                        hideFullscreenLoader();
                    }
                    if (data.error) {
                        alert(data.error);
                        closeAuditWorkspace();
                        return;
                    }

                    // Breadcrumbs
                    const breadcrumb = document.getElementById('top-nav-breadcrumbs');
                    if (breadcrumb) {
                        breadcrumb.innerHTML = `
                            <span style="font-weight: 500; color: var(--primary);">${escapeHtml(data.audit.client_name)}</span>
                            <i data-lucide="chevron-right" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin: 0 4px; color: var(--text-muted);"></i>
                            <span style="font-weight: 700; color: white;">${escapeHtml(data.audit.name)}</span>
                        `;
                    }
                    lucide.createIcons();

                    const sidebarActions = document.getElementById('sidebar-actions');
                    if (sidebarActions) {
                        sidebarActions.style.display = 'flex';
                    }
                    document.getElementById('welcome-view').style.display = 'none';
                    document.getElementById('audit-workspace-view').style.display = 'block';

                    // Populate global arrays
                    pagesData = data.pages;
                    searchTermsData = data.search_terms;
                    competitorsData = data.competitors;
                    competitorAnalysesData = data.competitor_analyses;
                    cwtData = data.core_web_vitals;
                    compCwvLoading = {};

                    // Reset collapse states
                    collapsedCompetitorTech.clear();
                    interactedCompetitorTech.clear();
                    collapsedAuditTech = (cwtData === null || (cwtData.desktop_score === null && cwtData.mobile_score === null));
                    updateAuditTechCollapseUI();

                    activeAuditCountry = data.audit.target_country || "Website's Country";

                    // Fill Traffic & Performance forms
                    document.getElementById('perf-bounce-rate').value = data.audit.bounce_rate || '';
                    document.getElementById('perf-pages-per-visit').value = data.audit.pages_per_visit || '';
                    document.getElementById('perf-avg-monthly-visits').value = data.audit.avg_monthly_visits || '';
                    
                    const duration = data.audit.avg_visit_duration || 0;
                    document.getElementById('perf-avg-visit-duration-min').value = duration ? Math.floor(duration / 60) : '';
                    document.getElementById('perf-avg-visit-duration-sec').value = duration ? (duration % 60) : '';

                    const breakdownVal = data.audit.breakdown_by_country || '';
                    currentBreakdownPath = isScreenshotPath(breakdownVal) ? breakdownVal : '';
                    if (isScreenshotPath(breakdownVal)) {
                        setBreakdownMode('screenshot');
                        document.getElementById('img-breakdown-country').src = breakdownVal + '?v=' + new Date().getTime();
                        document.getElementById('preview-breakdown-country').style.display = 'block';
                        document.getElementById('placeholder-breakdown-country').style.display = 'none';
                        document.getElementById('perf-breakdown-country').value = '';
                    } else {
                        setBreakdownMode('text');
                        document.getElementById('perf-breakdown-country').value = breakdownVal;
                        document.getElementById('img-breakdown-country').src = '';
                        document.getElementById('preview-breakdown-country').style.display = 'none';
                        document.getElementById('placeholder-breakdown-country').style.display = 'block';
                    }
                    
                    currentMainChannelsPath = data.audit.main_channels || '';
                    currentTrafficTrendsPath = data.audit.traffic_trends || '';
                    updateScreenshotPreview('main-channels', currentMainChannelsPath);
                    updateScreenshotPreview('traffic-trends', currentTrafficTrendsPath);

                    document.getElementById('sitemap-details').value = data.audit.sitemap_details || '';
                    document.getElementById('additional-notes').value = data.audit.additional_notes || '';
                    document.getElementById('global-report-analysis').value = data.audit.global_analysis || '';
                    document.getElementById('global-report-strategy').value = data.audit.global_strategy || '';
                    document.getElementById('perf-global-ranking').value = data.audit.global_ranking || '';
                    document.getElementById('perf-country-ranking').value = data.audit.country_ranking || '';
                    document.getElementById('perf-target-country').value = data.audit.target_country || '';

                    // Render Sub-tabs
                    renderPages();
                    renderSearchTerms();
                    renderCompetitorAnalyses();
                    renderCoreWebVitals();

                    updateUrlHash();
                })
                .catch(err => {
                    hideFullscreenLoader();
                    console.error(err);
                    alert("An error occurred loading the audit.");
                });
        }

        function createAudit(e) {
            e.preventDefault();
            const name = document.getElementById('audit-name-input').value;

            const formData = new FormData();
            formData.append('client_id', activeClientId);
            formData.append('name', name);

            fetch('api.php?action=audit_create', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('new-audit-modal');
                    document.getElementById('new-audit-form').reset();
                    selectAudit(parseInt(data.id));
                } else {
                    alert(data.error || 'Failed to create audit.');
                }
            });
        }

        function deleteAudit(e, id) {
            e.stopPropagation();
            if (!confirm('Are you sure you want to delete this audit? All crawled pages and rankings will be lost.')) {
                return;
            }

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=audit_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (activeAuditId === id) {
                        closeAuditWorkspace();
                    } else {
                        loadAudits();
                    }
                } else {
                    alert(data.error);
                }
            });
        }

        function closeAuditWorkspace() {
            activeAuditId = null;
            const breadcrumb = document.getElementById('top-nav-breadcrumbs');
            if (breadcrumb) {
                breadcrumb.innerHTML = '<span style="color: var(--text-muted);">Select a client to get started</span>';
            }
            const sidebarActions = document.getElementById('sidebar-actions');
            if (sidebarActions) {
                sidebarActions.style.display = 'none';
            }
            document.getElementById('audit-workspace-view').style.display = 'none';
            document.getElementById('welcome-view').style.display = 'block';
            updateUrlHash();
            loadAudits();
        }

        function copyShareLink() {
            if (!activeAuditId) return;
            
            fetch(`api.php?action=audit_get&id=${activeAuditId}`)
                .then(res => res.json())
                .then(data => {
                    const token = data.audit.share_token;
                    // Construct absolute URL path
                    const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'share.php?token=' + token;
                    
                    navigator.clipboard.writeText(url).then(() => {
                        alert('Read-only client share link copied to clipboard:\n' + url);
                    }, () => {
                        // Fallback
                        prompt('Copy share link manually:', url);
                    });
                });
        }

        function saveAuditMetrics(silent = false, showToastNotification = !silent) {
            if (!activeAuditId) return Promise.resolve();

            const min = parseInt(document.getElementById('perf-avg-visit-duration-min').value) || 0;
            const sec = parseInt(document.getElementById('perf-avg-visit-duration-sec').value) || 0;
            const avg_visit_duration = (min * 60) + sec;

            const formData = new FormData();
            formData.append('id', activeAuditId);
            formData.append('bounce_rate', document.getElementById('perf-bounce-rate').value);
            formData.append('pages_per_visit', document.getElementById('perf-pages-per-visit').value);
            formData.append('avg_monthly_visits', document.getElementById('perf-avg-monthly-visits').value);
            formData.append('avg_visit_duration', avg_visit_duration || '');
            if (currentBreakdownMode === 'screenshot') {
                formData.append('breakdown_by_country', currentBreakdownPath || '');
            } else {
                formData.append('breakdown_by_country', document.getElementById('perf-breakdown-country').value);
            }
            formData.append('sitemap_details', document.getElementById('sitemap-details').value);
            formData.append('additional_notes', document.getElementById('additional-notes').value);
            formData.append('global_analysis', document.getElementById('global-report-analysis').value);
            formData.append('global_strategy', document.getElementById('global-report-strategy').value);
            formData.append('global_ranking', document.getElementById('perf-global-ranking').value);
            formData.append('country_ranking', document.getElementById('perf-country-ranking').value);
            formData.append('target_country', document.getElementById('perf-target-country').value);

            // Handle main channels screenshot
            const channelsInput = document.getElementById('perf-main-channels-file');
            if (channelsInput && channelsInput.files.length > 0) {
                formData.append('main_channels_file', channelsInput.files[0]);
            } else {
                formData.append('main_channels', currentMainChannelsPath || '');
            }

            // Handle traffic trends screenshot
            const trendsInput = document.getElementById('perf-traffic-trends-file');
            if (trendsInput && trendsInput.files.length > 0) {
                formData.append('traffic_trends_file', trendsInput.files[0]);
            } else {
                formData.append('traffic_trends', currentTrafficTrendsPath || '');
            }

            return fetch('api.php?action=audit_save_metrics', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (showToastNotification || window.showSaveToastNext) {
                        showToast('Metrics saved');
                        window.showSaveToastNext = false;
                    }
                    const oldCountry = activeAuditCountry;
                    activeAuditCountry = document.getElementById('perf-target-country').value || "Website's Country";
                    if (oldCountry !== activeAuditCountry) {
                        renderPages();
                        renderCompetitorAnalyses();
                    }
                    if (!silent) {
                        return selectAudit(activeAuditId, false).then(() => true);
                    }
                    return true;
                } else {
                    alert(data.error);
                    return false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to save metrics.');
                return false;
            });
        }

        let currentMainChannelsPath = '';
        let currentTrafficTrendsPath = '';
        let currentBreakdownPath = '';
        let currentBreakdownMode = 'text';

        function setBreakdownMode(mode) {
            currentBreakdownMode = mode;
            const btnText = document.getElementById('btn-breakdown-mode-text');
            const btnImg = document.getElementById('btn-breakdown-mode-screenshot');
            const containerText = document.getElementById('breakdown-text-container');
            const containerImg = document.getElementById('breakdown-screenshot-container');
            
            if (mode === 'text') {
                btnText.style.background = 'rgba(139, 92, 246, 0.15)';
                btnText.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnText.style.color = 'var(--primary)';
                
                btnImg.style.background = 'none';
                btnImg.style.borderColor = 'var(--border-glass)';
                btnImg.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'block';
                containerImg.style.display = 'none';
            } else {
                btnImg.style.background = 'rgba(139, 92, 246, 0.15)';
                btnImg.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnImg.style.color = 'var(--primary)';
                
                btnText.style.background = 'none';
                btnText.style.borderColor = 'var(--border-glass)';
                btnText.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'none';
                containerImg.style.display = 'block';
            }
        }

        function setCompBreakdownMode(compId, mode) {
            const btnText = document.getElementById(`btn-comp-breakdown-mode-text-${compId}`);
            const btnImg = document.getElementById(`btn-comp-breakdown-mode-screenshot-${compId}`);
            const containerText = document.getElementById(`comp-breakdown-text-container-${compId}`);
            const containerImg = document.getElementById(`comp-breakdown-screenshot-container-${compId}`);
            
            if (mode === 'text') {
                btnText.style.background = 'rgba(139, 92, 246, 0.15)';
                btnText.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnText.style.color = 'var(--primary)';
                
                btnImg.style.background = 'none';
                btnImg.style.borderColor = 'var(--border-glass)';
                btnImg.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'block';
                containerImg.style.display = 'none';
            } else {
                btnImg.style.background = 'rgba(139, 92, 246, 0.15)';
                btnImg.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnImg.style.color = 'var(--primary)';
                
                btnText.style.background = 'none';
                btnText.style.borderColor = 'var(--border-glass)';
                btnText.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'none';
                containerImg.style.display = 'block';
            }
        }

        function uploadBreakdownScreenshot(file, type, id) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);
            formData.append('screenshot', file);
            
            showFullscreenLoader("Uploading breakdown screenshot...");
            
            fetch('api.php?action=upload_breakdown_screenshot', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideFullscreenLoader();
                if (data.success) {
                    if (type === 'audit') {
                        currentBreakdownPath = data.filepath;
                        document.getElementById('img-breakdown-country').src = data.filepath + '?v=' + new Date().getTime();
                        document.getElementById('preview-breakdown-country').style.display = 'block';
                        document.getElementById('placeholder-breakdown-country').style.display = 'none';
                        saveAuditMetrics(true);
                    } else {
                        const compId = id;
                        const img = document.getElementById(`img-comp-breakdown-${compId}`);
                        const preview = document.getElementById(`preview-comp-breakdown-${compId}`);
                        const placeholder = document.getElementById(`placeholder-comp-breakdown-${compId}`);
                        if (img) img.src = data.filepath + '?v=' + new Date().getTime();
                        if (preview) preview.style.display = 'block';
                        if (placeholder) placeholder.style.display = 'none';
                        
                        // Update in memory
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id == compId ? data.competitor : c);
                        saveAuditMetrics(true);
                    }
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                hideFullscreenLoader();
                console.error(err);
                alert("Upload failed.");
            });
        }

        function handleCompBreakdownFileChange(event, compId) {
            const file = event.target.files[0];
            if (file) {
                uploadBreakdownScreenshot(file, 'competitor', compId);
            }
        }

        function removeCompBreakdownScreenshot(event, compId) {
            if (event) event.stopPropagation();
            
            const preview = document.getElementById(`preview-comp-breakdown-${compId}`);
            const placeholder = document.getElementById(`placeholder-comp-breakdown-${compId}`);
            const img = document.getElementById(`img-comp-breakdown-${compId}`);
            
            if (preview) preview.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (img) img.src = '';
            
            // Save empty breakdown
            const comp = competitorAnalysesData.find(c => c.id === compId);
            if (comp) {
                const formData = new FormData();
                formData.append('id', compId);
                formData.append('url', comp.url);
                formData.append('breakdown_by_country', '');
                
                // Fetch other fields
                formData.append('meta_title', comp.meta_title || '');
                formData.append('meta_description', comp.meta_description || '');
                formData.append('h1', comp.h1 || '');
                formData.append('search_terms', comp.search_terms || '');
                formData.append('monthly_visits', comp.monthly_visits || '');
                formData.append('avg_time_per_visit', comp.avg_time_per_visit || '');
                formData.append('bounce_rate', comp.bounce_rate !== null ? comp.bounce_rate : '');
                formData.append('pages_per_visit', comp.pages_per_visit !== null ? comp.pages_per_visit : '');
                formData.append('avg_monthly_visits', comp.avg_monthly_visits !== null ? comp.avg_monthly_visits : '');
                formData.append('avg_visit_duration', comp.avg_visit_duration !== null ? comp.avg_visit_duration : '');
                formData.append('global_ranking', comp.global_ranking !== null ? comp.global_ranking : '');
                formData.append('country_ranking', comp.country_ranking !== null ? comp.country_ranking : '');

                fetch('api.php?action=competitor_analysis_update', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === compId ? data.competitor : c);
                        saveAuditMetrics(true);
                    }
                });
            }
        }

        function triggerFileInput(id) {
            document.getElementById(id).click();
        }

        function handleFileChange(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            if (type === 'breakdown-country') {
                uploadBreakdownScreenshot(file, 'audit', activeAuditId);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(`img-${type}`).src = e.target.result;
                document.getElementById(`placeholder-${type}`).style.display = 'none';
                document.getElementById(`preview-${type}`).style.display = 'block';
                saveAuditMetrics(true); // Auto-save after file upload/change
            };
            reader.readAsDataURL(file);
        }

        function removeScreenshot(event, type) {
            if (event) {
                event.stopPropagation(); // prevent triggering browser file browse popup click
            }
            document.getElementById(`perf-${type}-file`).value = '';
            document.getElementById(`img-${type}`).src = '';
            document.getElementById(`placeholder-${type}`).style.display = 'flex';
            document.getElementById(`preview-${type}`).style.display = 'none';

            if (type === 'main-channels') {
                currentMainChannelsPath = '';
            } else if (type === 'traffic-trends') {
                currentTrafficTrendsPath = '';
            } else if (type === 'breakdown-country') {
                currentBreakdownPath = '';
            }
            saveAuditMetrics(true); // Auto-save after screenshot removal
        }

        function updateScreenshotPreview(type, path) {
            const img = document.getElementById(`img-${type}`);
            const placeholder = document.getElementById(`placeholder-${type}`);
            const preview = document.getElementById(`preview-${type}`);

            if (path) {
                img.src = path;
                placeholder.style.display = 'none';
                preview.style.display = 'block';
            } else {
                img.src = '';
                placeholder.style.display = 'flex';
                preview.style.display = 'none';
            }
        }

        function initDragAndDrop() {
            ['main-channels', 'traffic-trends'].forEach(type => {
                const zone = document.getElementById(`zone-${type}`);
                if (!zone) return;

                ['dragenter', 'dragover'].forEach(eventName => {
                    zone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    zone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.classList.remove('dragover');
                    }, false);
                });

                zone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files.length > 0) {
                        const fileInput = document.getElementById(`perf-${type}-file`);
                        fileInput.files = files;
                        
                        // Trigger preview
                        const eventObj = { target: { files: files } };
                        handleFileChange(eventObj, type);
                    }
                }, false);
            });
        }

        function initTableResizing(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const getMinWidthForHeader = (th) => {
                const text = th.textContent.trim().toUpperCase();
                if (text.includes('URL') || text.includes('DOMAIN')) return 120;
                if (text.includes('META TITLE')) return 120;
                if (text.includes('META DESCRIPTION')) return 150;
                if (text.includes('H1') || text.includes('HEADING')) return 100;
                if (text.includes('SEMANTIC')) return 130;
                if (text.includes('INTERNAL')) return 110;
                if (text.includes('EXTERNAL')) return 110;
                if (text.includes('MISSING')) return 100;
                if (text.includes('SEARCH') || text.includes('KEYWORD')) return 110;
                if (text.includes('NOTES')) return 100;
                if (text.includes('INDEXED') || text.includes('GSC')) return 130;
                if (text.includes('CRAWL') || text.includes('ERRORS')) return 120;
                return 80; // default safe fallback
            };

            const headers = table.querySelectorAll('thead th');
            headers.forEach((th) => {
                th.style.position = 'relative';

                const resizer = document.createElement('div');
                resizer.className = 'table-resizer';
                th.appendChild(resizer);

                let startX, startWidth;
                let totalTableWidth = 0;
                let minWidthLimit = getMinWidthForHeader(th);

                resizer.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    startX = e.clientX;
                    startWidth = th.getBoundingClientRect().width;

                    resizer.classList.add('is-resizing');

                    const allHeaders = table.querySelectorAll('thead th');
                    totalTableWidth = 0;
                    allHeaders.forEach((h) => {
                        const w = h.getBoundingClientRect().width;
                        h.style.width = w + 'px';
                        h.style.minWidth = w + 'px';
                        totalTableWidth += w;
                    });

                    table.style.width = totalTableWidth + 'px';
                    table.style.tableLayout = 'fixed';

                    document.body.classList.add('is-resizing-table');

                    const onMouseMove = (moveEvent) => {
                        const deltaX = moveEvent.clientX - startX;
                        const newWidth = Math.max(minWidthLimit, startWidth + deltaX);
                        const widthDiff = newWidth - startWidth;
                        
                        th.style.width = newWidth + 'px';
                        th.style.minWidth = newWidth + 'px';
                        table.style.width = (totalTableWidth + widthDiff) + 'px';
                    };

                    const onMouseUp = () => {
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        resizer.classList.remove('is-resizing');
                        document.body.classList.remove('is-resizing-table');
                    };

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        }

        // -------------------------------------------------------------
        // Webpage Crawl & SEO/Technical list rendering
        // -------------------------------------------------------------
        function renderPages() {
            closeActiveDropdown();
            const seoTable = document.getElementById('seo-pages-list');
            const techTable = document.getElementById('tech-pages-list');
            seoTable.innerHTML = '';
            techTable.innerHTML = '';

            if (pagesData.length === 0) {
                seoTable.innerHTML = '<tr><td colspan="15" style="text-align:center; color:var(--text-muted);">No pages audited yet. Enter a URL above to start crawling.</td></tr>';
                techTable.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No pages configured.</td></tr>';
                return;
            }

            pagesData.forEach(p => {
                // Render SEO list
                const seoRow = document.createElement('tr');
                const titleLen = p.meta_title ? p.meta_title.length : 0;
                const descLen = p.meta_description ? p.meta_description.length : 0;
                const h1Len = p.h1 ? p.h1.length : 0;

                seoRow.innerHTML = `
                    <td style="white-space: nowrap; vertical-align: middle;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button class="btn btn-secondary btn-icon action-trigger-btn" style="padding: 2px; width: 20px; height: 20px; min-width: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;" onclick="showUrlActionsDropdown(event, this, ${p.id}, 'page', '${escapeHtml(p.url)}')">
                                <i data-lucide="more-vertical" style="width: 12px; height: 12px;"></i>
                            </button>
                            <a href="${escapeHtml(p.url)}" target="_blank" class="url-link" title="${escapeHtml(p.url)}">${escapeHtml(getUrlDisplayName(p.url))}</a>
                        </div>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="meta_title" data-value="${escapeHtml(p.meta_title || '')}">
                        <div class="text-truncate-cell" style="font-weight: 500;" title="${escapeHtml(p.meta_title || '')}">${escapeHtml(truncateCellText(p.meta_title))}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                            <span>${titleLen} chars</span>
                        </div>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="meta_description" data-input-type="textarea" data-value="${escapeHtml(p.meta_description || '')}">
                        <div class="text-truncate-cell" style="font-size:0.85rem;" title="${escapeHtml(p.meta_description || '')}">${escapeHtml(truncateCellText(p.meta_description))}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                            <span>${descLen} chars</span>
                        </div>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="h1" data-value="${escapeHtml(p.h1 || '')}">
                        <div class="text-truncate-cell" style="font-weight: 500;" title="${escapeHtml(p.h1 || '')}">${escapeHtml(truncateCellText(p.h1))}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                            <span>${h1Len} chars</span>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;" onclick="viewHeadersStructure(${p.id}, 'page')">
                            <i data-lucide="list-tree" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                            <span>View (${p.h1_count + p.h2_count + p.h3_count + p.h4_count + p.h5_count + p.h6_count} tags)</span>
                        </button>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="internal_links" data-input-type="number" data-value="${p.internal_links}">
                        <span style="font-weight:600; color:var(--secondary);">${p.internal_links}</span>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="external_links" data-input-type="number" data-value="${p.external_links}">
                        <span style="font-weight:600; color:var(--accent);">${p.external_links}</span>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="missing_alt_images" data-input-type="number" data-value="${p.missing_alt_images}">
                        <span class="badge ${p.missing_alt_images > 0 ? 'badge-warning' : 'badge-success'}">
                            ${p.missing_alt_images}
                        </span>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="search_terms" data-value="${escapeHtml(p.search_terms || '')}">
                        <div class="text-truncate-cell" title="${escapeHtml(p.search_terms || '')}">${formatSearchTermsAsBullets(p.search_terms)}</div>
                    </td>
                    <td data-editable="true" data-id="${p.id}" data-type="page" data-field="notes" data-value="${escapeHtml(p.notes || '')}">
                        <div class="text-truncate-cell" title="${escapeHtml(p.notes || '')}">${escapeHtml(p.notes || '')}</div>
                    </td>
                `;
                seoTable.appendChild(seoRow);

                // Render Technical list
                let gscBadgeClass = 'badge-neutral';
                let gscText = '-';
                if (p.indexing_gsc === 'yes') {
                    gscBadgeClass = 'badge-success';
                    gscText = 'YES';
                } else if (p.indexing_gsc === 'no') {
                    gscBadgeClass = 'badge-danger';
                    gscText = 'NO';
                }

                let errorsBadgeClass = 'badge-neutral';
                let errorsText = '-';
                if (p.crawl_errors === 'yes') {
                    errorsBadgeClass = 'badge-danger';
                    errorsText = 'ERRORS';
                } else if (p.crawl_errors === 'no') {
                    errorsBadgeClass = 'badge-success';
                    errorsText = 'NO ERRORS';
                }

                const techRow = document.createElement('tr');
                techRow.innerHTML = `
                    <td style="white-space: nowrap; vertical-align: middle;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button class="btn btn-secondary btn-icon action-trigger-btn" style="padding: 2px; width: 20px; height: 20px; min-width: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;" onclick="showUrlActionsDropdown(event, this, ${p.id}, 'page', '${escapeHtml(p.url)}')">
                                <i data-lucide="more-vertical" style="width: 12px; height: 12px;"></i>
                            </button>
                            <a href="${escapeHtml(p.url)}" target="_blank" class="url-link" title="${escapeHtml(p.url)}">${escapeHtml(getUrlDisplayName(p.url))}</a>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-clickable ${gscBadgeClass}" onclick="showGscSelector(event, this, ${p.id}, '${p.indexing_gsc || ''}')">
                            ${gscText}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-clickable ${errorsBadgeClass}" onclick="showCrawlErrorsSelector(event, this, ${p.id}, '${p.crawl_errors || ''}')">
                            ${errorsText}
                        </span>
                    </td>
                `;
                techTable.appendChild(techRow);
            });
            lucide.createIcons();
        }

        let activeScrapeMode = 'single';

        function setScrapeMode(mode) {
            activeScrapeMode = mode;
            
            const btnSingle = document.getElementById('btn-mode-single');
            const btnWebsite = document.getElementById('btn-mode-website');
            const panelSingle = document.getElementById('panel-mode-single');
            const panelWebsite = document.getElementById('panel-mode-website');
            const txtSingle = document.getElementById('scrape-urls');
            const inpWebsite = document.getElementById('scrape-website-url');
            const btnText = document.getElementById('submit-btn-text');

            if (mode === 'single') {
                btnSingle.classList.add('active');
                btnWebsite.classList.remove('active');
                panelSingle.style.display = 'block';
                panelWebsite.style.display = 'none';
                
                txtSingle.setAttribute('required', '');
                inpWebsite.removeAttribute('required');
                
                btnText.textContent = 'Crawl & Add URL(s)';
            } else {
                btnSingle.classList.remove('active');
                btnWebsite.classList.add('active');
                panelSingle.style.display = 'none';
                panelWebsite.style.display = 'block';
                
                txtSingle.removeAttribute('required');
                inpWebsite.setAttribute('required', '');
                
                btnText.textContent = 'Crawl & Analyze Website';
            }
        }

        function addPage(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('mode', activeScrapeMode);

            if (activeScrapeMode === 'single') {
                const urlsTextarea = document.getElementById('scrape-urls');
                formData.append('url', urlsTextarea.value);
                showFullscreenLoader("Crawling web page URL(s)...");
            } else {
                const websiteInput = document.getElementById('scrape-website-url');
                const maxPagesInput = document.getElementById('scrape-max-pages');
                formData.append('url', websiteInput.value);
                formData.append('max_pages', maxPagesInput.value);
                showFullscreenLoader("Crawling website recursively... (This might take a moment)");
            }

            fetch('api.php?action=page_add', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideFullscreenLoader();
                if (data.success) {
                    if (data.pages && Array.isArray(data.pages)) {
                        pagesData.push(...data.pages);
                    }
                    renderPages();
                    
                    // Clear inputs
                    document.getElementById('scrape-urls').value = '';
                    document.getElementById('scrape-website-url').value = '';
                    
                    if (data.errors && data.errors.length > 0) {
                        alert("Some crawl operations failed:\n" + data.errors.join("\n"));
                    }
                } else {
                    alert(data.error || "Failed to add pages.");
                }
            })
            .catch(err => {
                hideFullscreenLoader();
                console.error(err);
                alert("Crawling timed out or failed.");
            });
        }

        function addPageTech(e) {
            e.preventDefault();
            const input = document.getElementById('tech-scrape-url');
            const url = input.value.trim();
            if (!url) return;

            const btn = e.target.querySelector('button[type="submit"]');
            const originalBtnHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span><span>Crawling...</span>`;

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('mode', 'single');
            formData.append('url', url);

            fetch('api.php?action=page_add', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                if (data.success) {
                    if (data.pages && Array.isArray(data.pages)) {
                        pagesData.push(...data.pages);
                    }
                    renderPages();
                    input.value = '';
                } else {
                    alert(data.error || 'Failed to crawl URL.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                console.error(err);
                alert('Error crawling URL.');
            });
        }

        function deletePage(id) {
            if (!confirm('Are you sure you want to remove this page from the audit?')) return;

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=page_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    pagesData = pagesData.filter(p => p.id !== id);
                    renderPages();
                } else {
                    alert(data.error);
                }
            });
        }

        // -------------------------------------------------------------
        // Page / Competitor update Modal logic
        // -------------------------------------------------------------
        function openEditPageModal(id, type) {
            let record = null;
            if (type === 'page') {
                record = pagesData.find(p => p.id === id);
                document.querySelectorAll('.tech-only').forEach(el => el.style.display = 'block');
            } else {
                record = competitorAnalysesData.find(c => c.id === id);
                document.querySelectorAll('.tech-only').forEach(el => el.style.display = 'none');
            }

            if (!record) return;

            document.getElementById('edit-page-id').value = id;
            document.getElementById('edit-page-type').value = type;
            document.getElementById('edit-page-url').value = record.url;
            document.getElementById('edit-page-meta-title').value = record.meta_title || '';
            document.getElementById('edit-page-meta-description').value = record.meta_description || '';
            document.getElementById('edit-page-h1').value = record.h1 || '';
            document.getElementById('edit-page-visits').value = record.monthly_visits || '';
            document.getElementById('edit-page-duration').value = record.avg_time_per_visit || '';
            document.getElementById('edit-page-audience').value = record.audience_country_proportion || '';
            document.getElementById('edit-page-search-terms').value = record.search_terms || '';
            document.getElementById('edit-page-global-rank').value = record.global_ranking || '';
            document.getElementById('edit-page-country-rank').value = record.country_ranking || '';

            if (type === 'page') {
                document.getElementById('edit-page-gsc').value = record.indexing_gsc || '';
                document.getElementById('edit-page-errors').value = record.crawl_errors || '';
            }

            openModal('edit-page-modal');
        }

        function savePageUpdate(e) {
            e.preventDefault();
            const id = parseInt(document.getElementById('edit-page-id').value);
            const type = document.getElementById('edit-page-type').value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('meta_title', document.getElementById('edit-page-meta-title').value);
            formData.append('meta_description', document.getElementById('edit-page-meta-description').value);
            formData.append('h1', document.getElementById('edit-page-h1').value);
            formData.append('monthly_visits', document.getElementById('edit-page-visits').value);
            formData.append('avg_time_per_visit', document.getElementById('edit-page-duration').value);
            formData.append('audience_country_proportion', document.getElementById('edit-page-audience').value);
            formData.append('search_terms', document.getElementById('edit-page-search-terms').value);
            formData.append('global_ranking', document.getElementById('edit-page-global-rank').value);
            formData.append('country_ranking', document.getElementById('edit-page-country-rank').value);

            let actionUrl = '';
            if (type === 'page') {
                actionUrl = 'api.php?action=page_update';
                formData.append('indexing_gsc', document.getElementById('edit-page-gsc').value);
                formData.append('crawl_errors', document.getElementById('edit-page-errors').value);
            } else {
                actionUrl = 'api.php?action=competitor_analysis_update';
            }

            window.activeSavePromise = fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('edit-page-modal');
                    if (type === 'page') {
                        pagesData = pagesData.map(p => p.id === id ? data.page : p);
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === id ? data.competitor : c);
                        renderCompetitorAnalyses();
                    }
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    alert(data.error);
                }
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }

        let editingHeaderId = null;
        let editingHeaderType = null;
        let editingHeaderStructure = null;
        let editingHeaderScreenshot = null;
        let currentHeaderMode = 'text';

        function isScreenshotPath(str) {
            if (!str) return false;
            return str.startsWith('uploads/') || /\.(png|jpe?g|gif|webp|svg)$/i.test(str);
        }

        function setHeaderMode(mode) {
            currentHeaderMode = mode;
            const btnText = document.getElementById('btn-header-mode-text');
            const btnImg = document.getElementById('btn-header-mode-screenshot');
            const containerText = document.getElementById('headers-text-container');
            const containerImg = document.getElementById('headers-screenshot-container');
            
            if (mode === 'text') {
                btnText.style.background = 'rgba(139, 92, 246, 0.15)';
                btnText.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnText.style.color = 'var(--primary)';
                
                btnImg.style.background = 'none';
                btnImg.style.borderColor = 'var(--border-glass)';
                btnImg.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'block';
                containerImg.style.display = 'none';
            } else {
                btnImg.style.background = 'rgba(139, 92, 246, 0.15)';
                btnImg.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btnImg.style.color = 'var(--primary)';
                
                btnText.style.background = 'none';
                btnText.style.borderColor = 'var(--border-glass)';
                btnText.style.color = 'var(--text-primary)';
                
                containerText.style.display = 'none';
                containerImg.style.display = 'block';
            }
        }

        function renderHeadersTree(structureStr, containerId) {
            const treeContainer = document.getElementById(containerId);
            if (!treeContainer) return;
            treeContainer.innerHTML = '';

            if (!structureStr || structureStr === '[]') {
                treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding-top: 150px;">No heading elements (H1-H6) found in HTML document.</div>';
                return;
            }

            // Try parsing JSON structure
            let structure = [];
            let isJson = false;
            if (structureStr.trim().startsWith('[')) {
                try {
                    structure = JSON.parse(structureStr);
                    isJson = true;
                } catch(e) {
                    isJson = false;
                }
            }

            if (isJson) {
                if (structure.length === 0) {
                    treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding-top: 150px;">No heading elements (H1-H6) found in HTML document.</div>';
                } else {
                    structure.forEach(h => {
                        const item = document.createElement('div');
                        item.className = `header-tree-item ${h.tag.toUpperCase()}`;
                        item.innerHTML = `&lt;${h.tag.toUpperCase()}&gt; : ${escapeHtml(h.text)}`;
                        treeContainer.appendChild(item);
                    });
                }
            } else {
                // Plain text mode: render each line, detecting <H1> to <H6>
                const lines = structureStr.split('\n');
                let hasContent = false;
                lines.forEach(line => {
                    const trimmed = line.trim();
                    if (!trimmed) return;
                    hasContent = true;

                    const match = trimmed.match(/^<(H[1-6])>\s*:\s*(.*)/i);
                    const item = document.createElement('div');
                    if (match) {
                        const tag = match[1].toUpperCase();
                        const text = match[2];
                        item.className = `header-tree-item ${tag}`;
                        item.innerHTML = `&lt;${tag}&gt; : ${escapeHtml(text)}`;
                    } else {
                        item.className = 'header-tree-item';
                        item.style.paddingLeft = '10px';
                        item.style.color = 'var(--text-primary)';
                        item.textContent = trimmed;
                    }
                    treeContainer.appendChild(item);
                });

                if (!hasContent) {
                    treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding-top: 150px;">No heading elements (H1-H6) found in HTML document.</div>';
                }
            }
        }

        function viewHeadersStructure(id, type) {
            editingHeaderId = id;
            editingHeaderType = type;

            let record = null;
            if (type === 'page') {
                record = pagesData.find(p => p.id === id);
            } else {
                record = competitorAnalysesData.find(c => c.id === id);
            }
            if (!record) return;

            editingHeaderStructure = record.headers_structure || '';
            editingHeaderScreenshot = record.headers_screenshot || '';

            // Populate heading counts inputs
            document.getElementById('edit-h1-count').value = record.h1_count || 0;
            document.getElementById('edit-h2-count').value = record.h2_count || 0;
            document.getElementById('edit-h3-count').value = record.h3_count || 0;
            document.getElementById('edit-h4-count').value = record.h4_count || 0;
            document.getElementById('edit-h5-count').value = record.h5_count || 0;
            document.getElementById('edit-h6-count').value = record.h6_count || 0;

            const uploadZone = document.getElementById('headers-upload-zone');
            const previewBox = document.getElementById('headers-screenshot-preview-box');
            const previewImg = document.getElementById('headers-screenshot-img');

            if (editingHeaderScreenshot) {
                // Screenshot mode
                setHeaderMode('screenshot');
                previewImg.src = editingHeaderScreenshot + '?v=' + new Date().getTime();
                previewBox.style.display = 'flex';
                uploadZone.style.display = 'none';
            } else {
                // Text structure mode
                setHeaderMode('text');
                previewBox.style.display = 'none';
                uploadZone.style.display = 'flex';
                previewImg.src = '';
            }

            renderHeadersTree(editingHeaderStructure, 'headers-modal-tree');
            openModal('headers-modal');
        }

        function uploadHeadersScreenshotFile(event) {
            const file = event.target.files[0];
            if (file) {
                uploadHeadersScreenshot(file);
            }
        }

        function uploadHeadersScreenshot(file) {
            if (!editingHeaderId || !editingHeaderType) return;
            
            const formData = new FormData();
            formData.append('id', editingHeaderId);
            formData.append('type', editingHeaderType);
            formData.append('screenshot', file);
            
            showFullscreenLoader("Uploading screenshot...");
            
            fetch('api.php?action=upload_headers_screenshot', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideFullscreenLoader();
                if (data.success) {
                    editingHeaderScreenshot = data.filepath;
                    
                    // Show screenshot preview
                    document.getElementById('headers-screenshot-img').src = data.filepath + '?v=' + new Date().getTime();
                    document.getElementById('headers-screenshot-preview-box').style.display = 'flex';
                    document.getElementById('headers-upload-zone').style.display = 'none';
                    
                    // Refresh data in memory
                    if (editingHeaderType === 'page') {
                        pagesData = pagesData.map(p => p.id === editingHeaderId ? data.page : p);
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === editingHeaderId ? data.competitor : c);
                        renderCompetitorAnalyses();
                    }
                    setHeaderMode('screenshot');
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                hideFullscreenLoader();
                console.error(err);
                alert("Upload failed.");
            });
        }

        function removeHeadersScreenshot() {
            editingHeaderScreenshot = '';
            document.getElementById('headers-screenshot-preview-box').style.display = 'none';
            document.getElementById('headers-upload-zone').style.display = 'flex';
            setHeaderMode('text');
        }

        function clearHeadersStructure() {
            // Clear counts
            document.getElementById('edit-h1-count').value = 0;
            document.getElementById('edit-h2-count').value = 0;
            document.getElementById('edit-h3-count').value = 0;
            document.getElementById('edit-h4-count').value = 0;
            document.getElementById('edit-h5-count').value = 0;
            document.getElementById('edit-h6-count').value = 0;
            
            // Clear structure
            editingHeaderStructure = '';
            editingHeaderScreenshot = '';
            renderHeadersTree('', 'headers-modal-tree');
            
            // Show upload zone, hide preview
            document.getElementById('headers-screenshot-preview-box').style.display = 'none';
            document.getElementById('headers-upload-zone').style.display = 'flex';
            
            // Set mode back to text
            setHeaderMode('text');
        }

        function saveHeadersStructure() {
            if (!editingHeaderId || !editingHeaderType) return;

            const h1_count = parseInt(document.getElementById('edit-h1-count').value) || 0;
            const h2_count = parseInt(document.getElementById('edit-h2-count').value) || 0;
            const h3_count = parseInt(document.getElementById('edit-h3-count').value) || 0;
            const h4_count = parseInt(document.getElementById('edit-h4-count').value) || 0;
            const h5_count = parseInt(document.getElementById('edit-h5-count').value) || 0;
            const h6_count = parseInt(document.getElementById('edit-h6-count').value) || 0;

            const formData = new FormData();
            formData.append('id', editingHeaderId);
            formData.append('type', editingHeaderType);
            formData.append('h1_count', h1_count);
            formData.append('h2_count', h2_count);
            formData.append('h3_count', h3_count);
            formData.append('h4_count', h4_count);
            formData.append('h5_count', h5_count);
            formData.append('h6_count', h6_count);
            formData.append('headers_structure', editingHeaderStructure);
            formData.append('headers_screenshot', editingHeaderScreenshot || '');

            window.activeSavePromise = fetch('api.php?action=save_headers_structure', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('headers-modal');
                    if (editingHeaderType === 'page') {
                        pagesData = pagesData.map(p => p.id === editingHeaderId ? data.page : p);
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === editingHeaderId ? data.competitor : c);
                        renderCompetitorAnalyses();
                    }
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Failed to save headings.");
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }

        function reFetchHeaders(button) {
            if (!editingHeaderId || !editingHeaderType) return;

            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 4px;"></span><span>Fetching...</span>`;

            const formData = new FormData();
            formData.append('id', editingHeaderId);

            const actionUrl = editingHeaderType === 'page' ? 'api.php?action=page_refresh' : 'api.php?action=competitor_analysis_refresh';

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = originalHTML;
                if (data.success) {
                    let record = null;
                    if (editingHeaderType === 'page') {
                        pagesData = pagesData.map(p => p.id === editingHeaderId ? data.page : p);
                        record = data.page;
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === editingHeaderId ? data.competitor : c);
                        record = data.competitor;
                        renderCompetitorAnalyses();
                    }
                    
                    // Reload modal inputs and structure tree with new record values
                    editingHeaderStructure = record.headers_structure || '';
                    editingHeaderScreenshot = record.headers_screenshot || '';

                    document.getElementById('edit-h1-count').value = record.h1_count || 0;
                    document.getElementById('edit-h2-count').value = record.h2_count || 0;
                    document.getElementById('edit-h3-count').value = record.h3_count || 0;
                    document.getElementById('edit-h4-count').value = record.h4_count || 0;
                    document.getElementById('edit-h5-count').value = record.h5_count || 0;
                    document.getElementById('edit-h6-count').value = record.h6_count || 0;

                    renderHeadersTree(editingHeaderStructure, 'headers-modal-tree');
                    showToast('Headers re-fetched successfully!');
                } else {
                    alert(data.error || 'Failed to re-fetch headers.');
                }
            })
            .catch(err => {
                button.disabled = false;
                button.innerHTML = originalHTML;
                console.error(err);
                alert('Error re-fetching headers.');
            });
        }

        function initHeadersModalHandlers() {
            // Drag & drop handlers for headers screenshot upload zone
            const dropZone = document.getElementById('headers-upload-zone');
            if (dropZone) {
                dropZone.addEventListener('dragover', e => {
                    e.preventDefault();
                    dropZone.classList.add('dragover');
                });
                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('dragover');
                });
                dropZone.addEventListener('drop', e => {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                    const file = e.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        uploadHeadersScreenshot(file);
                    }
                });
            }

            // Paste handler for screenshot
            window.addEventListener('paste', e => {
                const modal = document.getElementById('headers-modal');
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                let imageFile = null;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') === 0) {
                        imageFile = items[i].getAsFile();
                        break;
                    }
                }
                if (!imageFile) return;

                // 1. Headers modal paste
                if (modal && modal.style.display !== 'none') {
                    setHeaderMode('screenshot');
                    uploadHeadersScreenshot(imageFile);
                    return;
                }

                // 2. Audit Breakdown by Country paste
                const subtabPerf = document.getElementById('subtab-perf');
                const breakdownImgContainer = document.getElementById('breakdown-screenshot-container');
                if (subtabPerf && subtabPerf.style.display !== 'none' && breakdownImgContainer && breakdownImgContainer.style.display !== 'none') {
                    uploadBreakdownScreenshot(imageFile, 'audit', activeAuditId);
                    return;
                }

                // 3. Competitor Breakdown by Country paste
                const visibleCompContainer = Array.from(document.querySelectorAll('.comp-breakdown-screenshot-container'))
                    .find(el => el.style.display !== 'none' && el.getBoundingClientRect().height > 0);
                if (visibleCompContainer) {
                    const compId = visibleCompContainer.getAttribute('data-comp-id');
                    uploadBreakdownScreenshot(imageFile, 'competitor', compId);
                    return;
                }
            });
        }

        // -------------------------------------------------------------
        // Core Web Vitals & PageSpeed Insight API
        // -------------------------------------------------------------
        function renderCoreWebVitals() {
            const results = document.getElementById('cwv-results');
            const placeholder = document.getElementById('cwv-placeholder');

            if (!cwtData) {
                results.style.display = 'none';
                placeholder.style.display = 'block';
                return;
            }

            placeholder.style.display = 'none';
            results.style.display = 'grid';

            // Desktop render
            const dScore = document.getElementById('cwv-desktop-score');
            dScore.textContent = (cwtData.desktop_score !== null && cwtData.desktop_score !== undefined) ? cwtData.desktop_score : '-';
            dScore.className = `cwv-score-circle ${getCwvScoreClass(cwtData.desktop_score)}`;
            
            const dA11y = document.getElementById('cwv-desktop-accessibility');
            dA11y.textContent = (cwtData.desktop_accessibility !== null && cwtData.desktop_accessibility !== undefined) ? cwtData.desktop_accessibility : '-';
            dA11y.className = `cwv-score-circle ${getCwvScoreClass(cwtData.desktop_accessibility)}`;

            const dBest = document.getElementById('cwv-desktop-best-practices');
            dBest.textContent = (cwtData.desktop_best_practices !== null && cwtData.desktop_best_practices !== undefined) ? cwtData.desktop_best_practices : '-';
            dBest.className = `cwv-score-circle ${getCwvScoreClass(cwtData.desktop_best_practices)}`;

            const dSeo = document.getElementById('cwv-desktop-seo');
            dSeo.textContent = (cwtData.desktop_seo !== null && cwtData.desktop_seo !== undefined) ? cwtData.desktop_seo : '-';
            dSeo.className = `cwv-score-circle ${getCwvScoreClass(cwtData.desktop_seo)}`;

            const dAgentic = document.getElementById('cwv-desktop-agentic-browsing');
            dAgentic.textContent = cwtData.desktop_agentic_browsing || '-';
            dAgentic.className = `cwv-score-circle ${getAgenticScoreClass(cwtData.desktop_agentic_browsing)}`;

            document.getElementById('cwv-desktop-fcp').textContent = cwtData.desktop_fcp || '-';
            document.getElementById('cwv-desktop-lcp').textContent = cwtData.desktop_lcp || '-';
            document.getElementById('cwv-desktop-tbt').textContent = cwtData.desktop_tbt || '-';
            document.getElementById('cwv-desktop-cls').textContent = cwtData.desktop_cls || '-';
            document.getElementById('cwv-desktop-si').textContent = cwtData.desktop_si || '-';

            // Mobile render
            const mScore = document.getElementById('cwv-mobile-score');
            mScore.textContent = (cwtData.mobile_score !== null && cwtData.mobile_score !== undefined) ? cwtData.mobile_score : '-';
            mScore.className = `cwv-score-circle ${getCwvScoreClass(cwtData.mobile_score)}`;
            
            const mA11y = document.getElementById('cwv-mobile-accessibility');
            mA11y.textContent = (cwtData.mobile_accessibility !== null && cwtData.mobile_accessibility !== undefined) ? cwtData.mobile_accessibility : '-';
            mA11y.className = `cwv-score-circle ${getCwvScoreClass(cwtData.mobile_accessibility)}`;

            const mBest = document.getElementById('cwv-mobile-best-practices');
            mBest.textContent = (cwtData.mobile_best_practices !== null && cwtData.mobile_best_practices !== undefined) ? cwtData.mobile_best_practices : '-';
            mBest.className = `cwv-score-circle ${getCwvScoreClass(cwtData.mobile_best_practices)}`;

            const mSeo = document.getElementById('cwv-mobile-seo');
            mSeo.textContent = (cwtData.mobile_seo !== null && cwtData.mobile_seo !== undefined) ? cwtData.mobile_seo : '-';
            mSeo.className = `cwv-score-circle ${getCwvScoreClass(cwtData.mobile_seo)}`;

            const mAgentic = document.getElementById('cwv-mobile-agentic-browsing');
            mAgentic.textContent = cwtData.mobile_agentic_browsing || '-';
            mAgentic.className = `cwv-score-circle ${getAgenticScoreClass(cwtData.mobile_agentic_browsing)}`;

            document.getElementById('cwv-mobile-fcp').textContent = cwtData.mobile_fcp || '-';
            document.getElementById('cwv-mobile-lcp').textContent = cwtData.mobile_lcp || '-';
            document.getElementById('cwv-mobile-tbt').textContent = cwtData.mobile_tbt || '-';
            document.getElementById('cwv-mobile-cls').textContent = cwtData.mobile_cls || '-';
            document.getElementById('cwv-mobile-si').textContent = cwtData.mobile_si || '-';
        }

        function getCwvScoreClass(score) {
            if (score === null || score === undefined || score === '-') return 'poor';
            const num = parseInt(score, 10);
            if (isNaN(num)) return 'poor';
            if (num >= 90) return 'good';
            if (num >= 50) return 'needs-improvement';
            return 'poor';
        }

        function getAgenticScoreClass(scoreStr) {
            if (!scoreStr || scoreStr === '-') return 'poor';
            const parts = scoreStr.split('/');
            if (parts.length !== 2) return 'poor';
            const passed = parseInt(parts[0], 10);
            const total = parseInt(parts[1], 10);
            if (total <= 0) return 'poor';
            const ratio = passed / total;
            if (ratio >= 0.9) return 'good';
            if (ratio >= 0.5) return 'needs-improvement';
            return 'poor';
        }

        function fetchCoreWebVitals(strategy) {
            if (!activeAuditId || !strategy) return;

            // Find representative client homepage
            fetch(`api.php?action=clients_list`)
                .then(res => res.json())
                .then(clients => {
                    const client = clients.find(c => c.id == activeClientId);
                    if (!client) return;

                    const url = client.homepage_url;

                    if (!cwtData) {
                        cwtData = {};
                    }

                    document.getElementById('cwv-placeholder').style.display = 'none';
                    document.getElementById('cwv-results').style.display = 'grid';

                    const loader = document.getElementById(`cwv-${strategy}-loader`);
                    const content = document.getElementById(`cwv-${strategy}-content`);
                    const btn = document.getElementById(`cwv-${strategy}-btn`);

                    if (loader) loader.style.display = 'block';
                    if (content) content.style.display = 'none';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span> <span>Fetching...</span>`;
                    }

                    const formData = new FormData();
                    formData.append('audit_id', activeAuditId);
                    formData.append('url', url);
                    formData.append('strategy', strategy);

                    fetch('api.php?action=cwt_fetch', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Reset loader state
                        if (loader) loader.style.display = 'none';
                        if (content) content.style.display = 'block';
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = `<i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> <span>Refresh ${strategy === 'desktop' ? 'Desktop' : 'Mobile'}</span>`;
                        }
                        lucide.createIcons();

                        if (data.success) {
                            cwtData = data.data;
                            renderCoreWebVitals();
                            if (data.mocked) {
                                alert("PSI API limit hit or host offline. Used cached/mocked local performance scores.");
                            }
                        } else {
                            alert(data.error);
                            renderCoreWebVitals();
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        // Reset loader state
                        if (loader) loader.style.display = 'none';
                        if (content) content.style.display = 'block';
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = `<i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> <span>Refresh ${strategy === 'desktop' ? 'Desktop' : 'Mobile'}</span>`;
                        }
                        lucide.createIcons();
                        alert("API request failed.");
                        renderCoreWebVitals();
                    });
                });
        }

        function fetchCoreWebVitalsAll() {
            fetchCoreWebVitals('desktop');
            fetchCoreWebVitals('mobile');
        }

        // -------------------------------------------------------------
        // Search Terms Tab
        // -------------------------------------------------------------
        function renderSearchTerms() {
            const wrapper = document.getElementById('search-terms-wrapper');
            wrapper.innerHTML = '';

            if (searchTermsData.length === 0) {
                wrapper.innerHTML = '<div class="glass-panel" style="padding:40px; text-align:center; color:var(--text-muted);">No search terms added. Add one above to begin profiling competitors.</div>';
                return;
            }

            searchTermsData.forEach(t => {
                const card = document.createElement('div');
                card.className = 'glass-panel';
                card.style.padding = '24px';

                // filter competitors for this term
                const comps = competitorsData.filter(c => c.search_term_id === t.id);
                const organic = comps.filter(c => c.type === 'organic');
                const sponsored = comps.filter(c => c.type === 'sponsored');
                const isCollapsed = collapsedSearchTerms.has(t.id);

                card.innerHTML = `
                    <div class="flex-space" style="margin-bottom:${isCollapsed ? '0' : '20px'}; border-bottom:${isCollapsed ? 'none' : '1px solid var(--border-glass)'}; padding-bottom:${isCollapsed ? '0' : '12px'};">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary); margin-right: 8px; display: flex; align-items: center;">
                                <i data-lucide="key" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                                <span>${escapeHtml(t.term)}</span>
                            </h4>
                            <button class="btn btn-secondary btn-icon" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px;" onclick="toggleSearchTermCollapse(${t.id})" title="${isCollapsed ? 'Expand' : 'Collapse'}">
                                <i data-lucide="${isCollapsed ? 'chevron-down' : 'chevron-up'}" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                        <div style="display:flex; gap: 8px;">
                            <button class="btn btn-danger btn-icon" style="padding:6px;" onclick="deleteSearchTerm(${t.id})">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>

                    <div style="display:${isCollapsed ? 'none' : 'grid'}; grid-template-columns: 1fr 1fr; gap:24px;">
                        
                        <!-- Organic competitors -->
                        <div>
                            <div class="flex-space" style="margin-bottom:12px;">
                                <span style="font-size: 0.8rem; font-weight:600; text-transform:uppercase; color:var(--text-secondary);">Organic Competitors</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:8px;" class="organic-list">
                                ${organic.length === 0 ? '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;" class="empty-msg">No organic competitors entered yet.</div>' : ''}
                            </div>
                            
                            <!-- Toggle Button for Organic Form -->
                            <div style="margin-top: 12px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddOrganicForm(${t.id})" style="font-size: 0.8rem; padding: 6px 12px;">
                                    <i data-lucide="plus" style="width: 12px; height: 12px;"></i>
                                    <span>Add Organic Competitor</span>
                                </button>
                            </div>

                            <!-- Inline creation for Organic (Collapsed by default) -->
                            <form id="organic-add-form-${t.id}" onsubmit="addCompetitorInline(event, ${t.id}, 'organic')" style="display: none; background: rgba(0, 0, 0, 0.15); border: 1px dashed var(--border-glass); border-radius: var(--radius-md); padding: 16px; margin-top: 12px; flex-direction: column; gap: 10px;">
                                <div style="font-weight: 600; font-size: 0.8rem; color: var(--secondary); display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="plus" style="width: 12px; height: 12px;"></i> Add Organic Competitor
                                </div>
                                <div>
                                    <input type="url" placeholder="Competitor URL (e.g. https://...)" required class="form-input comp-url" style="padding: 6px 10px; font-size: 0.85rem;">
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-secondary); display: block; margin-bottom: 2px;">Bounce %</label>
                                        <input type="number" step="0.01" min="0" max="100" placeholder="40.0" class="form-input comp-bounce" style="padding: 6px; font-size: 0.8rem; text-align: center;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-secondary); display: block; margin-bottom: 2px;">Pages/V</label>
                                        <input type="number" step="0.1" min="0" placeholder="2.8" class="form-input comp-pages" style="padding: 6px; font-size: 0.8rem; text-align: center;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-secondary); display: block; margin-bottom: 2px;">Visits</label>
                                        <input type="number" min="0" placeholder="35000" class="form-input comp-visits" style="padding: 6px; font-size: 0.8rem; text-align: center;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-secondary); display: block; margin-bottom: 2px;">Duration(s)</label>
                                        <input type="number" min="0" placeholder="120" class="form-input comp-duration" style="padding: 6px; font-size: 0.8rem; text-align: center;">
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">
                                        Add Competitor
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Sponsored competitors -->
                        <div>
                            <div class="flex-space" style="margin-bottom:12px;">
                                <span style="font-size: 0.8rem; font-weight:600; text-transform:uppercase; color:var(--text-secondary);">Sponsored Competitors</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:8px;" class="sponsored-list">
                                ${sponsored.length === 0 ? '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;" class="empty-msg">No sponsored competitors entered.</div>' : ''}
                            </div>
                            
                            <!-- Streamlined Inline creation for Sponsored -->
                            <form onsubmit="addCompetitorInline(event, ${t.id}, 'sponsored')" style="margin-top: 12px; display: flex; gap: 8px;">
                                <input type="url" placeholder="Paste sponsored competitor URL..." required class="form-input comp-url" style="padding: 8px 12px; font-size: 0.85rem; flex: 1;">
                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 14px; font-size: 0.85rem; white-space: nowrap;">
                                    Add Sponsored
                                </button>
                            </form>
                        </div>

                    </div>
                `;

                // Add competitors items
                const orgList = card.querySelector('.organic-list');
                organic.forEach(o => {
                    const el = document.createElement('div');
                    el.className = 'glass-card';
                    el.style.padding = '12px 16px';
                    el.id = `competitor-card-${o.id}`;
                    el.innerHTML = `
                        <!-- View State -->
                        <div class="view-state">
                            <div class="flex-space">
                                <a href="${escapeHtml(o.url)}" target="_blank" class="url-link" style="font-size:0.85rem; font-weight:500; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: calc(100% - 70px);" title="${escapeHtml(o.url)}">
                                    ${escapeHtml(getCompetitorDisplayName(o.url))}
                                </a>
                                <div style="display: flex; gap: 4px; flex-shrink: 0;">
                                    <button class="btn btn-secondary btn-icon" style="padding:3px; border-radius:4px;" onclick="toggleEditCompetitor(${o.id}, true)" title="Edit">
                                        <i data-lucide="edit-3" style="width: 12px; height: 12px;"></i>
                                    </button>
                                    <button class="btn btn-danger btn-icon" style="padding:3px; border-radius:4px;" onclick="deleteCompetitor(${o.id})" title="Delete">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </button>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; margin-top:8px; font-size:0.75rem; text-align:center; color:var(--text-secondary);">
                                <div>Bounce: <b>${o.bounce_rate !== null ? o.bounce_rate + '%' : '-'}</b></div>
                                <div>Pages/V: <b>${o.pages_per_visit !== null ? o.pages_per_visit : '-'}</b></div>
                                <div>Visits: <b>${o.avg_monthly_visits !== null ? o.avg_monthly_visits : '-'}</b></div>
                                <div>Dur: <b>${o.avg_visit_duration !== null ? o.avg_visit_duration + 's' : '-'}</b></div>
                            </div>
                        </div>

                        <!-- Edit State -->
                        <form class="edit-state" style="display: none;" onsubmit="saveCompetitorEdit(event, ${o.id})">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <input type="url" value="${escapeHtml(o.url)}" required class="form-input edit-url" style="padding: 4px 8px; font-size: 0.8rem; flex: 1;">
                                    <button type="submit" class="btn btn-success btn-icon" style="padding: 4px; border-radius:4px;" title="Save">
                                        <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-icon" style="padding: 4px; border-radius:4px;" onclick="toggleEditCompetitor(${o.id}, false)" title="Cancel">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </button>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                                    <div>
                                        <input type="number" step="0.01" min="0" max="100" placeholder="Bounce" value="${o.bounce_rate !== null ? o.bounce_rate : ''}" class="form-input edit-bounce" style="padding: 4px; font-size: 0.75rem; text-align: center;">
                                    </div>
                                    <div>
                                        <input type="number" step="0.1" min="0" placeholder="Pages/V" value="${o.pages_per_visit !== null ? o.pages_per_visit : ''}" class="form-input edit-pages" style="padding: 4px; font-size: 0.75rem; text-align: center;">
                                    </div>
                                    <div>
                                        <input type="number" min="0" placeholder="Visits" value="${o.avg_monthly_visits !== null ? o.avg_monthly_visits : ''}" class="form-input edit-visits" style="padding: 4px; font-size: 0.75rem; text-align: center;">
                                    </div>
                                    <div>
                                        <input type="number" min="0" placeholder="Duration" value="${o.avg_visit_duration !== null ? o.avg_visit_duration : ''}" class="form-input edit-duration" style="padding: 4px; font-size: 0.75rem; text-align: center;">
                                    </div>
                                </div>
                            </div>
                        </form>
                    `;
                    orgList.appendChild(el);
                });

                const spList = card.querySelector('.sponsored-list');
                sponsored.forEach(s => {
                    const el = document.createElement('div');
                    el.className = 'glass-card';
                    el.style.padding = '10px 14px';
                    el.id = `competitor-card-${s.id}`;
                    el.innerHTML = `
                        <!-- View State -->
                        <div class="view-state flex-space" style="width: 100%;">
                            <a href="${escapeHtml(s.url)}" target="_blank" class="url-link" style="font-size:0.85rem; font-weight:500; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: calc(100% - 70px);" title="${escapeHtml(s.url)}">
                                ${escapeHtml(getCompetitorDisplayName(s.url))}
                            </a>
                            <div style="display: flex; gap: 4px; flex-shrink: 0;">
                                <button class="btn btn-secondary btn-icon" style="padding:3px; border-radius:4px;" onclick="toggleEditCompetitor(${s.id}, true)" title="Edit">
                                    <i data-lucide="edit-3" style="width: 12px; height: 12px;"></i>
                                </button>
                                <button class="btn btn-danger btn-icon" style="padding:3px; border-radius:4px;" onclick="deleteCompetitor(${s.id})" title="Delete">
                                    <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Edit State -->
                        <form class="edit-state" style="display: none; width: 100%;" onsubmit="saveCompetitorEdit(event, ${s.id})">
                            <div style="display: flex; gap: 6px; align-items: center; width: 100%;">
                                <input type="url" value="${escapeHtml(s.url)}" required class="form-input edit-url" style="padding: 4px 8px; font-size: 0.8rem; flex: 1;">
                                <button type="submit" class="btn btn-success btn-icon" style="padding: 4px; border-radius:4px;" title="Save">
                                    <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                                </button>
                                <button type="button" class="btn btn-secondary btn-icon" style="padding: 4px; border-radius:4px;" onclick="toggleEditCompetitor(${s.id}, false)" title="Cancel">
                                    <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                        </form>
                    `;
                    spList.appendChild(el);
                });

                wrapper.appendChild(card);
            });
            lucide.createIcons();
        }

        function addSearchTerm(e) {
            e.preventDefault();
            const termInput = document.getElementById('new-search-term');
            const term = termInput.value;

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('term', term);

            fetch('api.php?action=search_term_add', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    searchTermsData.push(data.term);
                    renderSearchTerms();
                    termInput.value = '';
                } else {
                    alert(data.error);
                }
            });
        }

        function toggleSearchTermCollapse(id) {
            if (collapsedSearchTerms.has(id)) {
                collapsedSearchTerms.delete(id);
            } else {
                collapsedSearchTerms.add(id);
            }
            renderSearchTerms();
        }

        function toggleCompetitorTechCollapse(id) {
            interactedCompetitorTech.add(id);
            if (collapsedCompetitorTech.has(id)) {
                collapsedCompetitorTech.delete(id);
            } else {
                collapsedCompetitorTech.add(id);
            }
            renderCompetitorAnalyses();
        }

        function toggleAuditTechCollapse() {
            collapsedAuditTech = !collapsedAuditTech;
            updateAuditTechCollapseUI();
        }

        function updateAuditTechCollapseUI() {
            const body = document.getElementById('audit-cwv-body');
            const icon = document.getElementById('audit-cwv-toggle-icon');
            const btn = document.getElementById('audit-cwv-toggle-btn');
            if (!body) return;
            if (collapsedAuditTech) {
                body.style.display = 'none';
                if (icon) icon.setAttribute('data-lucide', 'chevron-down');
                if (btn) btn.title = 'Expand';
            } else {
                body.style.display = 'block';
                if (icon) icon.setAttribute('data-lucide', 'chevron-up');
                if (btn) btn.title = 'Collapse';
            }
            lucide.createIcons();
        }

        function toggleCompetitorPerfCollapse(id) {
            if (collapsedCompetitorPerf.has(id)) {
                collapsedCompetitorPerf.delete(id);
            } else {
                collapsedCompetitorPerf.add(id);
            }
            renderCompetitorAnalyses();
        }

        function toggleAddOrganicForm(termId) {
            const form = document.getElementById(`organic-add-form-${termId}`);
            if (!form) return;
            if (form.style.display === 'none') {
                form.style.display = 'flex';
            } else {
                form.style.display = 'none';
            }
        }

        function deleteSearchTerm(id) {
            if (!confirm('Are you sure you want to delete this search term and all its competitor listings?')) return;

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=search_term_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    searchTermsData = searchTermsData.filter(t => t.id !== id);
                    competitorsData = competitorsData.filter(c => c.search_term_id !== id);
                    renderSearchTerms();
                } else {
                    alert(data.error);
                }
            });
        }

        function addCompetitorInline(e, termId, type) {
            e.preventDefault();
            const form = e.target;
            const urlInput = form.querySelector('.comp-url');
            const url = urlInput.value;

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('search_term_id', termId);
            formData.append('url', url);
            formData.append('type', type);

            if (type === 'organic') {
                const bounce = form.querySelector('.comp-bounce').value;
                const pages = form.querySelector('.comp-pages').value;
                const visits = form.querySelector('.comp-visits').value;
                const duration = form.querySelector('.comp-duration').value;

                formData.append('bounce_rate', bounce);
                formData.append('pages_per_visit', pages);
                formData.append('avg_monthly_visits', visits);
                formData.append('avg_visit_duration', duration);
            }

            fetch('api.php?action=competitor_add', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    competitorsData.push(data.competitor);
                    renderSearchTerms();
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Failed to add competitor.");
            });
        }

        function toggleEditCompetitor(id, showEdit) {
            const card = document.getElementById(`competitor-card-${id}`);
            if (!card) return;
            const viewState = card.querySelector('.view-state');
            const editState = card.querySelector('.edit-state');
            
            if (showEdit) {
                viewState.style.setProperty('display', 'none', 'important');
                editState.style.setProperty('display', 'block', 'important');
            } else {
                viewState.style.removeProperty('display');
                editState.style.setProperty('display', 'none', 'important');
            }
        }

        function saveCompetitorEdit(e, id) {
            e.preventDefault();
            const form = e.target;
            const urlInput = form.querySelector('.edit-url');
            const url = urlInput.value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('url', url);

            const bounce = form.querySelector('.edit-bounce');
            const pages = form.querySelector('.edit-pages');
            const visits = form.querySelector('.edit-visits');
            const duration = form.querySelector('.edit-duration');

            if (bounce) formData.append('bounce_rate', bounce.value);
            if (pages) formData.append('pages_per_visit', pages.value);
            if (visits) formData.append('avg_monthly_visits', visits.value);
            if (duration) formData.append('avg_visit_duration', duration.value);

            window.activeSavePromise = fetch('api.php?action=competitor_update', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const idx = competitorsData.findIndex(c => c.id === id);
                    if (idx !== -1) {
                        competitorsData[idx] = data.competitor;
                    }
                    renderSearchTerms();
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Failed to update competitor.");
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }

        function deleteCompetitor(id) {
            if (!confirm('Remove competitor URL entry?')) return;

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=competitor_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    competitorsData = competitorsData.filter(c => c.id !== id);
                    renderSearchTerms();
                } else {
                    alert(data.error);
                }
            });
        }

        // -------------------------------------------------------------
        // Competitor Suggestions & Recommendations Engine
        // -------------------------------------------------------------
        function suggestCompetitors() {
            openModal('suggestions-modal');
            document.getElementById('suggestions-loader').style.display = 'block';
            document.getElementById('suggestions-empty').style.display = 'none';
            document.getElementById('suggestions-list').innerHTML = '';
            document.getElementById('suggestions-send-btn').style.display = 'none';

            fetch(`api.php?action=competitors_suggest&audit_id=${activeAuditId}`)
                .then(res => res.json())
                .then(suggestions => {
                    document.getElementById('suggestions-loader').style.display = 'none';
                    
                    if (suggestions.length === 0) {
                        document.getElementById('suggestions-empty').style.display = 'block';
                        return;
                    }

                    document.getElementById('suggestions-send-btn').style.display = 'inline-flex';
                    const container = document.getElementById('suggestions-list');

                    suggestions.forEach((s, idx) => {
                        const item = document.createElement('div');
                        item.className = 'glass-card suggestion-card';
                        
                        // Checkbox attributes data
                        const dataAttr = encodeURIComponent(JSON.stringify(s));

                        item.innerHTML = `
                            <div class="suggestion-header">
                                <div class="flex-align">
                                    <span style="font-weight:700; color:var(--text-muted); font-size:1.1rem; width:24px;">#${s.rank}</span>
                                    <span style="font-weight:600; font-size:1.05rem; color:var(--primary);">${escapeHtml(s.name)}</span>
                                    <span style="font-size:0.75rem; background:rgba(6, 182, 212, 0.1); color:var(--secondary); padding:2px 8px; border-radius:10px;">${s.appearances} occurrences</span>
                                </div>
                                <input type="checkbox" name="suggested-competitors" class="form-checkbox" style="width:20px; height:20px; cursor:pointer;" value="${dataAttr}">
                            </div>
                            <div style="font-size:0.85rem;">
                                <div>Representative URL: <a href="${escapeHtml(s.representative_url)}" target="_blank" class="url-link">${escapeHtml(s.representative_url)}</a></div>
                                <div style="margin-top:6px; color:var(--text-muted);">Triggers Keywords: <span style="color:var(--text-secondary); font-style:italic;">${escapeHtml(s.terms.join(', '))}</span></div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                });
        }

        function sendSelectedSuggestions() {
            const checkboxes = document.querySelectorAll('input[name="suggested-competitors"]:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one competitor to import.');
                return;
            }

            const selected = [];
            checkboxes.forEach(cb => {
                selected.push(JSON.parse(decodeURIComponent(cb.value)));
            });

            closeModal('suggestions-modal');
            showFullscreenLoader("Importing and crawling selected competitors...");

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('selected', JSON.stringify(selected));

            fetch('api.php?action=competitors_send_to_analysis', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideFullscreenLoader();
                if (data.success) {
                    // Populate modal contents
                    document.getElementById('import-results-summary').textContent = `Successfully imported ${data.added} competitor(s).`;
                    
                    const warningEl = document.getElementById('import-results-warning');
                    const errorsListEl = document.getElementById('import-results-errors-list');
                    const errorsContainerEl = document.getElementById('import-results-errors-container');
                    
                    if (data.errors && data.errors.length > 0) {
                        warningEl.style.display = 'block';
                        errorsListEl.style.display = 'block';
                        errorsContainerEl.innerHTML = '';
                        
                        data.errors.forEach(err => {
                            const errDiv = document.createElement('div');
                            errDiv.style.borderLeft = '3px solid #ef4444';
                            errDiv.style.paddingLeft = '8px';
                            errDiv.textContent = err;
                            errorsContainerEl.appendChild(errDiv);
                        });
                        
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    } else {
                        warningEl.style.display = 'none';
                        errorsListEl.style.display = 'none';
                    }
                    
                    openModal('import-results-modal');
                    
                    // Refresh competitor list
                    fetch(`api.php?action=audit_get&id=${activeAuditId}`)
                        .then(r => r.json())
                        .then(d => {
                            competitorAnalysesData = d.competitor_analyses;
                            renderCompetitorAnalyses();
                            // Switch to competitor analysis tab
                            document.querySelector('.tab-link[onclick*="tab-competitor-analysis"]').click();
                        });
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                hideFullscreenLoader();
                console.error(err);
                alert("Import failed or timed out.");
            });
        }

        // -------------------------------------------------------------
        // Competitor Analyses Tab
        // -------------------------------------------------------------
        // -------------------------------------------------------------
        // Competitor Analyses Tab & Subtabs
        // -------------------------------------------------------------
        function renderCompetitorAnalyses() {
            closeActiveDropdown();

            // Sort competitorAnalysesData by domain name (full domain name including TLD)
            competitorAnalysesData.sort((a, b) => {
                const domainA = getDomainFromUrl(a.url).toLowerCase();
                const domainB = getDomainFromUrl(b.url).toLowerCase();
                return domainA.localeCompare(domainB);
            });

            // 1. Render SEO State Table
            const list = document.getElementById('competitors-analysis-list');
            list.innerHTML = '';

            if (competitorAnalysesData.length === 0) {
                list.innerHTML = '<tr><td colspan="10" style="text-align:center; color:var(--text-muted);">No competitors audited yet. Add one above, or generate from keywords suggestions.</td></tr>';
            } else {
                competitorAnalysesData.forEach(c => {
                    const row = document.createElement('tr');
                    const titleLen = c.meta_title ? c.meta_title.length : 0;
                    const descLen = c.meta_description ? c.meta_description.length : 0;
                    const h1Len = c.h1 ? c.h1.length : 0;

                    const headingCount = (parseInt(c.h1_count) || 0) + 
                                         (parseInt(c.h2_count) || 0) + 
                                         (parseInt(c.h3_count) || 0) + 
                                         (parseInt(c.h4_count) || 0) + 
                                         (parseInt(c.h5_count) || 0) + 
                                         (parseInt(c.h6_count) || 0);

                    row.innerHTML = `
                        <td style="white-space: nowrap; vertical-align: middle;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <button class="btn btn-secondary btn-icon action-trigger-btn" style="padding: 2px; width: 20px; height: 20px; min-width: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;" onclick="showUrlActionsDropdown(event, this, ${c.id}, 'competitor', '${escapeHtml(c.url)}')">
                                    <i data-lucide="more-vertical" style="width: 12px; height: 12px;"></i>
                                </button>
                                <a href="${escapeHtml(c.url)}" target="_blank" class="url-link" title="${escapeHtml(c.url)}">${escapeHtml(getDomainFromUrl(c.url))}</a>
                            </div>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="meta_title" data-value="${escapeHtml(c.meta_title || '')}">
                            <div class="text-truncate-cell" style="font-weight: 500;" title="${escapeHtml(c.meta_title || '')}">${escapeHtml(truncateCellText(c.meta_title))}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                <span>${titleLen} chars</span>
                            </div>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="meta_description" data-input-type="textarea" data-value="${escapeHtml(c.meta_description || '')}">
                            <div class="text-truncate-cell" style="font-size:0.85rem;" title="${escapeHtml(c.meta_description || '')}">${escapeHtml(truncateCellText(c.meta_description))}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                <span>${descLen} chars</span>
                            </div>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="h1" data-value="${escapeHtml(c.h1 || '')}">
                            <div class="text-truncate-cell" style="font-weight: 500;" title="${escapeHtml(c.h1 || '')}">${escapeHtml(truncateCellText(c.h1))}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                <span>${h1Len} chars</span>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;" onclick="viewHeadersStructure(${c.id}, 'competitor')">
                                <i data-lucide="list-tree" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                                <span>View (${headingCount} tags)</span>
                            </button>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="internal_links" data-input-type="number" data-value="${c.internal_links || 0}">
                            <span style="font-weight:600; color:var(--secondary);">${c.internal_links || 0}</span>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="external_links" data-input-type="number" data-value="${c.external_links || 0}">
                            <span style="font-weight:600; color:var(--accent);">${c.external_links || 0}</span>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="missing_alt_images" data-input-type="number" data-value="${c.missing_alt_images || 0}">
                            <span class="badge ${(parseInt(c.missing_alt_images) || 0) > 0 ? 'badge-warning' : 'badge-success'}">
                                ${c.missing_alt_images || 0}
                            </span>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="search_terms" data-value="${escapeHtml(c.search_terms || '')}">
                            <div class="text-truncate-cell" title="${escapeHtml(c.search_terms || '')}">${formatSearchTermsAsBullets(c.search_terms)}</div>
                        </td>
                        <td data-editable="true" data-id="${c.id}" data-type="competitor_analysis" data-field="notes" data-value="${escapeHtml(c.notes || '')}">
                            <div class="text-truncate-cell" title="${escapeHtml(c.notes || '')}">${escapeHtml(c.notes || '')}</div>
                        </td>
                    `;
                    list.appendChild(row);
                });
            }

            // 2. Render Technical State (CWV score cards)
            const techContainer = document.getElementById('comp-tech-cards-container');
            techContainer.innerHTML = '';

            if (competitorAnalysesData.length === 0) {
                techContainer.innerHTML = '<div class="glass-panel" style="text-align:center; color:var(--text-muted); padding:40px;">No competitors audited yet. Add one in the SEO State tab.</div>';
            } else {
                competitorAnalysesData.forEach(c => {
                    const card = document.createElement('div');
                    card.className = 'glass-panel';
                    card.style.padding = '30px';
                    card.style.marginBottom = '24px';

                    const isDesktopLoading = compCwvLoading[`${c.id}-desktop`] || false;
                    const isMobileLoading = compCwvLoading[`${c.id}-mobile`] || false;
                    const hasData = (c.desktop_score !== null || c.mobile_score !== null);
                    const showResults = hasData || isDesktopLoading || isMobileLoading;

                    const desktopBtnHTML = isDesktopLoading ?
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-desktop-btn-${c.id}" disabled style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span>
                            <span>Fetching...</span>
                         </button>` :
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-desktop-btn-${c.id}" onclick="fetchCompCoreWebVitals(${c.id}, 'desktop')" style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                            <span>Refresh Desktop</span>
                         </button>`;

                    const mobileBtnHTML = isMobileLoading ?
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-mobile-btn-${c.id}" disabled style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span>
                            <span>Fetching...</span>
                         </button>` :
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-mobile-btn-${c.id}" onclick="fetchCompCoreWebVitals(${c.id}, 'mobile')" style="padding: 6px 12px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                            <span>Refresh Mobile</span>
                         </button>`;

                    const isAnyCwvLoading = isDesktopLoading || isMobileLoading;
                    const cwvAllBtnHTML = isAnyCwvLoading ?
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-all-btn-${c.id}" disabled style="padding: 4px 10px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 10px; height: 10px; margin-right: 4px;"></span>
                            <span>Fetching...</span>
                         </button>` :
                        `<button class="btn btn-secondary btn-sm" id="comp-cwv-all-btn-${c.id}" onclick="fetchCompCoreWebVitalsAll(${c.id})" style="padding: 4px 10px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i>
                            <span>Fetch Core Web Vitals (All)</span>
                         </button>`;

                    // Check if manually toggled. If not, auto-collapse when empty.
                    if (!interactedCompetitorTech.has(c.id)) {
                        const isEmpty = (c.desktop_score === null || c.desktop_score === undefined) && 
                                        (c.mobile_score === null || c.mobile_score === undefined);
                        if (isEmpty) {
                            collapsedCompetitorTech.add(c.id);
                        } else {
                            collapsedCompetitorTech.delete(c.id);
                        }
                    }
                    const isCollapsed = collapsedCompetitorTech.has(c.id);

                    card.innerHTML = `
                        <!-- Competitor Domain Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: ${isCollapsed ? 'none' : '1px solid var(--border-glass)'}; padding-bottom: 12px; margin-bottom: ${isCollapsed ? '0' : '20px'};">
                            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h4 style="font-weight: 700; margin: 0; font-size: 1.15rem; color: var(--primary);">
                                        <a href="${escapeHtml(c.url)}" target="_blank" class="url-link">${escapeHtml(getDomainFromUrl(c.url))}</a>
                                    </h4>
                                    <button class="btn btn-secondary btn-icon" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;" onclick="toggleCompetitorTechCollapse(${c.id})" title="${isCollapsed ? 'Expand' : 'Collapse'}">
                                        <i data-lucide="${isCollapsed ? 'chevron-down' : 'chevron-up'}" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </div>
                                <div style="width: 1px; height: 24px; background: var(--border-glass);"></div>
                                <div style="text-align: left;">
                                    <span style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary); display: block;">Core Web Vitals & Speed Scores</span>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary); display: block; margin-top: 2px;">Checked using competitor URL as representative page</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                ${cwvAllBtnHTML}
                                <button class="btn btn-danger btn-icon" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;" onclick="deleteCompetitorAnalysis(${c.id})" title="Delete competitor">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                        </div>

                        <div style="display: ${isCollapsed ? 'none' : 'block'};">

                            <!-- Two strategies results side-by-side -->
                            <div id="comp-cwv-results-${c.id}" class="cwv-grid" style="display: ${showResults ? 'grid' : 'none'}; grid-template-columns: 1fr 1fr; gap: 24px;">
                                <!-- Desktop Card -->
                                <div class="glass-card cwv-strategy-card" style="margin: 0; background: rgba(255, 255, 255, 0.02);">
                                    <div class="cwv-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <div class="flex-align" style="gap: 8px;">
                                            <i data-lucide="monitor" style="width: 20px; height: 20px; color: var(--primary);"></i>
                                            <span style="font-weight: 600;">Desktop Strategy</span>
                                        </div>
                                        ${desktopBtnHTML}
                                    </div>
                                    
                                    <div id="comp-cwv-desktop-loader-${c.id}" style="display: ${isDesktopLoading ? 'block' : 'none'}; text-align: center; padding: 60px 0;">
                                        <div class="spinner spinner-large" style="margin: 0 auto 12px;"></div>
                                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Querying PageSpeed Insights API...</p>
                                    </div>

                                    <div id="comp-cwv-desktop-content-${c.id}" style="display: ${isDesktopLoading ? 'none' : 'block'};">
                                        <!-- Row of 5 circular scores -->
                                        <div class="cwv-scores-row">
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.desktop_score)}">${c.desktop_score !== null ? c.desktop_score : '-'}</div>
                                                <div class="cwv-score-label">Perf</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Performance</div>
                                                    <div class="cwv-tooltip-text">Measures page load speed, responsiveness, and visual stability.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.desktop_accessibility)}">${c.desktop_accessibility !== null ? c.desktop_accessibility : '-'}</div>
                                                <div class="cwv-score-label">A11y</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Accessibility</div>
                                                    <div class="cwv-tooltip-text">Measures how easy the website is to use for people with disabilities.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.desktop_best_practices)}">${c.desktop_best_practices !== null ? c.desktop_best_practices : '-'}</div>
                                                <div class="cwv-score-label">Best</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Best Practices</div>
                                                    <div class="cwv-tooltip-text">Checks if the website follows web standards and security best practices.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.desktop_seo)}">${c.desktop_seo !== null ? c.desktop_seo : '-'}</div>
                                                <div class="cwv-score-label">SEO</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Search Engine Optimization</div>
                                                    <div class="cwv-tooltip-text">Checks how well search engines can crawl, index, and understand the page.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getAgenticScoreClass(c.desktop_agentic_browsing)}">${c.desktop_agentic_browsing !== null ? c.desktop_agentic_browsing : '-'}</div>
                                                <div class="cwv-score-label">Agentic</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Agentic Browsing</div>
                                                    <div class="cwv-tooltip-text">Measures suitability for AI agents: checks visual stability (CLS &le; 0.1), Accessibility (&ge; 80), and SEO (&ge; 90).</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cwv-metrics-list">
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">First Contentful Paint</div>
                                                <div class="cwv-metric-val">${c.desktop_fcp !== null ? c.desktop_fcp : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Largest Contentful Paint</div>
                                                <div class="cwv-metric-val">${c.desktop_lcp !== null ? c.desktop_lcp : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Total Blocking Time</div>
                                                <div class="cwv-metric-val">${c.desktop_tbt !== null ? c.desktop_tbt : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Cumulative Layout Shift</div>
                                                <div class="cwv-metric-val">${c.desktop_cls !== null ? c.desktop_cls : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item" style="grid-column: span 2;">
                                                <div class="cwv-metric-lbl">Speed Index</div>
                                                <div class="cwv-metric-val">${c.desktop_si !== null ? c.desktop_si : '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Card -->
                                <div class="glass-card cwv-strategy-card" style="margin: 0; background: rgba(255, 255, 255, 0.02);">
                                    <div class="cwv-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <div class="flex-align" style="gap: 8px;">
                                            <i data-lucide="smartphone" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                            <span style="font-weight: 600;">Mobile Strategy</span>
                                        </div>
                                        ${mobileBtnHTML}
                                    </div>
                                    
                                    <div id="comp-cwv-mobile-loader-${c.id}" style="display: ${isMobileLoading ? 'block' : 'none'}; text-align: center; padding: 60px 0;">
                                        <div class="spinner spinner-large" style="margin: 0 auto 12px;"></div>
                                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Querying PageSpeed Insights API...</p>
                                    </div>

                                    <div id="comp-cwv-mobile-content-${c.id}" style="display: ${isMobileLoading ? 'none' : 'block'};">
                                        <!-- Row of 5 circular scores -->
                                        <div class="cwv-scores-row">
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.mobile_score)}">${c.mobile_score !== null ? c.mobile_score : '-'}</div>
                                                <div class="cwv-score-label">Perf</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Performance</div>
                                                    <div class="cwv-tooltip-text">Measures page load speed, responsiveness, and visual stability.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.mobile_accessibility)}">${c.mobile_accessibility !== null ? c.mobile_accessibility : '-'}</div>
                                                <div class="cwv-score-label">A11y</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Accessibility</div>
                                                    <div class="cwv-tooltip-text">Measures how easy the website is to use for people with disabilities.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.mobile_best_practices)}">${c.mobile_best_practices !== null ? c.mobile_best_practices : '-'}</div>
                                                <div class="cwv-score-label">Best</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Best Practices</div>
                                                    <div class="cwv-tooltip-text">Checks if the website follows web standards and security best practices.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getCwvScoreClass(c.mobile_seo)}">${c.mobile_seo !== null ? c.mobile_seo : '-'}</div>
                                                <div class="cwv-score-label">SEO</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Search Engine Optimization</div>
                                                    <div class="cwv-tooltip-text">Checks how well search engines can crawl, index, and understand the page.</div>
                                                </div>
                                            </div>
                                            <div class="cwv-score-card">
                                                <div class="cwv-score-circle ${getAgenticScoreClass(c.mobile_agentic_browsing)}">${c.mobile_agentic_browsing !== null ? c.mobile_agentic_browsing : '-'}</div>
                                                <div class="cwv-score-label">Agentic</div>
                                                <div class="cwv-tooltip">
                                                    <div class="cwv-tooltip-title">Agentic Browsing</div>
                                                    <div class="cwv-tooltip-text">Measures suitability for AI agents: checks visual stability (CLS &le; 0.1), Accessibility (&ge; 80), and SEO (&ge; 90).</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cwv-metrics-list">
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">First Contentful Paint</div>
                                                <div class="cwv-metric-val">${c.mobile_fcp !== null ? c.mobile_fcp : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Largest Contentful Paint</div>
                                                <div class="cwv-metric-val">${c.mobile_lcp !== null ? c.mobile_lcp : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Total Blocking Time</div>
                                                <div class="cwv-metric-val">${c.mobile_tbt !== null ? c.mobile_tbt : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item">
                                                <div class="cwv-metric-lbl">Cumulative Layout Shift</div>
                                                <div class="cwv-metric-val">${c.mobile_cls !== null ? c.mobile_cls : '-'}</div>
                                            </div>
                                            <div class="cwv-metric-item" style="grid-column: span 2;">
                                                <div class="cwv-metric-lbl">Speed Index</div>
                                                <div class="cwv-metric-val">${c.mobile_si !== null ? c.mobile_si : '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Big central placeholder (mirroring Website Audit) -->
                            <div id="comp-cwv-placeholder-${c.id}" style="display: ${showResults ? 'none' : 'block'}; text-align: center; padding: 50px 0; color: var(--text-muted);">
                                <i data-lucide="gauge" style="width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.5;"></i>
                                <p style="font-size: 0.85rem;">No Core Web Vitals data pulled yet. Click "Fetch Core Web Vitals (All)" or "Refresh Desktop/Mobile" to run automated analysis.</p>
                            </div>
                        </div>
                    `;
                    techContainer.appendChild(card);
                });
            }

            // 3. Render Traffic & Performance forms
            const perfContainer = document.getElementById('comp-perf-cards-container');
            perfContainer.innerHTML = '';

            if (competitorAnalysesData.length === 0) {
                perfContainer.innerHTML = '<div class="glass-panel" style="text-align:center; color:var(--text-muted); padding:40px;">No competitors audited yet. Add one above or in the SEO State tab.</div>';
            } else {
                competitorAnalysesData.forEach(c => {
                    const isCollapsed = collapsedCompetitorPerf.has(c.id);
                    const card = document.createElement('div');
                    card.className = 'glass-panel';
                    card.style.padding = '24px';
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: ${isCollapsed ? 'none' : '1px solid var(--border-glass)'}; padding-bottom: 12px; margin-bottom: ${isCollapsed ? '0' : '20px'};">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <h4 style="font-weight: 700; margin: 0; font-size: 1.15rem; color: var(--primary);">
                                    <a href="${escapeHtml(c.url)}" target="_blank" class="url-link">${escapeHtml(getDomainFromUrl(c.url))}</a>
                                </h4>
                                <button class="btn btn-secondary btn-icon" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;" onclick="toggleCompetitorPerfCollapse(${c.id})" title="${isCollapsed ? 'Expand' : 'Collapse'}">
                                    <i data-lucide="${isCollapsed ? 'chevron-down' : 'chevron-up'}" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">${escapeHtml(c.url)}</span>
                                <button class="btn btn-danger btn-icon" style="padding: 4px; border-radius: 4px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;" onclick="deleteCompetitorAnalysis(${c.id})" title="Delete competitor">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div style="display: ${isCollapsed ? 'none' : 'block'};">
                            <div class="grid-form">
                                <!-- Left Column: Metrics -->
                                <div style="display: flex; flex-direction: column; gap: 20px;">
                                    <!-- Row 1: Bounce Rate & Pages Per Visit -->
                                    <div style="display: flex; gap: 15px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Bounce Rate (%)</label>
                                            <input type="number" step="0.01" min="0" max="100" id="comp-bounce-${c.id}" class="form-input" placeholder="e.g. 45.5" value="${c.bounce_rate !== null ? c.bounce_rate : ''}" onchange="saveCompetitorTraffic(${c.id})">
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Pages per Visit</label>
                                            <input type="number" step="0.01" min="0" id="comp-pages-${c.id}" class="form-input" placeholder="e.g. 2.5" value="${c.pages_per_visit !== null ? c.pages_per_visit : ''}" onchange="saveCompetitorTraffic(${c.id})">
                                        </div>
                                    </div>

                                    <!-- Row 2: Average Monthly Visits & Average Visit Duration -->
                                    <div style="display: flex; gap: 15px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Average Monthly Visits</label>
                                            <input type="number" min="0" id="comp-monthly-visits-${c.id}" class="form-input" placeholder="e.g. 50000" value="${c.avg_monthly_visits !== null ? c.avg_monthly_visits : ''}" onchange="saveCompetitorTraffic(${c.id})">
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Average Visit Duration</label>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <input type="number" min="0" id="comp-duration-min-${c.id}" class="form-input" placeholder="Min" value="${c.avg_visit_duration ? Math.floor(c.avg_visit_duration / 60) : ''}" style="flex: 1; text-align: center;" onchange="saveCompetitorTraffic(${c.id})">
                                                <span style="font-size: 0.85rem; color: var(--text-muted);">m</span>
                                                <input type="number" min="0" max="59" id="comp-duration-sec-${c.id}" class="form-input" placeholder="Sec" value="${c.avg_visit_duration ? (c.avg_visit_duration % 60) : ''}" style="flex: 1; text-align: center;" onchange="saveCompetitorTraffic(${c.id})">
                                                <span style="font-size: 0.85rem; color: var(--text-muted);">s</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3: Global Rank & Country Rank -->
                                    <div style="display: flex; gap: 15px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Global Rank</label>
                                            <input type="number" min="0" id="comp-global-rank-${c.id}" class="form-input" placeholder="e.g. 250000" value="${c.global_ranking !== null ? c.global_ranking : ''}" onchange="saveCompetitorTraffic(${c.id})">
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.8rem; margin-bottom: 6px; color: var(--text-secondary);">Country Rank</label>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <input type="number" min="0" id="comp-country-rank-${c.id}" class="form-input" placeholder="e.g. 45000" value="${c.country_ranking !== null ? c.country_ranking : ''}" onchange="saveCompetitorTraffic(${c.id})" style="flex: 1;">
                                                <span style="font-size: 0.85rem; color: var(--text-secondary);">in</span>
                                                <input type="text" class="form-input" value="${escapeHtml(activeAuditCountry)}" style="flex: 1.5; opacity: 0.7;" disabled title="Competitors use the same target country as defined in the website audit Traffic & Performance tab.">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Breakdown By Country -->
                                <div style="display: flex; flex-direction: column; height: 100%;">
                                    <div class="form-group" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0;">
                                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 8px;">
                                            <label style="font-size: 0.8rem; margin-bottom: 0; color: var(--text-secondary);">Breakdown by Country</label>
                                            <div class="mode-tabs" style="display: flex; gap: 4px;">
                                                <button type="button" class="btn btn-secondary btn-sm" id="btn-comp-breakdown-mode-text-${c.id}" onclick="setCompBreakdownMode(${c.id}, 'text')" style="font-size: 0.7rem; padding: 1px 6px; ${!isScreenshotPath(c.breakdown_by_country) ? 'background: rgba(139, 92, 246, 0.15); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);' : ''}">Text</button>
                                                <button type="button" class="btn btn-secondary btn-sm" id="btn-comp-breakdown-mode-screenshot-${c.id}" onclick="setCompBreakdownMode(${c.id}, 'screenshot')" style="font-size: 0.7rem; padding: 1px 6px; ${isScreenshotPath(c.breakdown_by_country) ? 'background: rgba(139, 92, 246, 0.15); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);' : ''}">Screenshot</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Mode Text: Textarea -->
                                        <div id="comp-breakdown-text-container-${c.id}" style="${isScreenshotPath(c.breakdown_by_country) ? 'display: none;' : ''} flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
                                            <textarea id="comp-breakdown-${c.id}" class="form-input" style="flex-grow: 1; min-height: 180px; resize: vertical; height: 100%;" placeholder="e.g. United States: 40%, United Kingdom: 20%, France: 10%" onchange="saveCompetitorTraffic(${c.id})">${escapeHtml(!isScreenshotPath(c.breakdown_by_country) ? c.breakdown_by_country || '' : '')}</textarea>
                                        </div>
                                        
                                        <!-- Mode Screenshot: Upload Zone & Preview -->
                                        <div id="comp-breakdown-screenshot-container-${c.id}" class="comp-breakdown-screenshot-container" data-comp-id="${c.id}" style="${!isScreenshotPath(c.breakdown_by_country) ? 'display: none;' : ''} flex-grow: 1; flex-direction: column; height: 100%;">
                                            <div class="screenshot-upload-zone" id="zone-comp-breakdown-${c.id}" onclick="triggerFileInput('comp-breakdown-file-${c.id}')" style="flex-grow: 1; height: 100%; min-height: 180px; padding: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; overflow: hidden; position: relative;">
                                                <input type="file" id="comp-breakdown-file-${c.id}" accept="image/*" style="display: none;" onchange="handleCompBreakdownFileChange(event, ${c.id})">
                                                <div class="upload-placeholder" id="placeholder-comp-breakdown-${c.id}" style="${isScreenshotPath(c.breakdown_by_country) ? 'display: none;' : ''} display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px;">
                                                    <i data-lucide="image" style="width: 28px; height: 28px; color: var(--text-muted); margin-bottom: 8px;"></i>
                                                    <p style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; margin: 0; text-align: center; line-height: 1.4;">Click, drag & drop, or paste (Ctrl+V) breakdown screenshot</p>
                                                </div>
                                                <div class="upload-preview" id="preview-comp-breakdown-${c.id}" style="${isScreenshotPath(c.breakdown_by_country) ? 'display: block;' : 'display: none;'} position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                                    <img src="${isScreenshotPath(c.breakdown_by_country) ? escapeHtml(c.breakdown_by_country) : ''}" id="img-comp-breakdown-${c.id}" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-sm); cursor: zoom-in;" onclick="event.stopPropagation(); openImageLightbox(this.src)">
                                                    <button type="button" class="btn-delete-screenshot" onclick="removeCompBreakdownScreenshot(event, ${c.id})">
                                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                        <span>Remove</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    perfContainer.appendChild(card);
                });
            }

            lucide.createIcons();
        }

        function addCompetitorManual(e) {
            e.preventDefault();
            const urlInput = document.getElementById('comp-manual-url');
            const termsInput = document.getElementById('comp-manual-terms');
            
            const url = urlInput.value;
            const terms = termsInput.value;

            showFullscreenLoader("Crawling competitor webpage HTML structure...");

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('url', url);
            formData.append('search_terms', terms);

            fetch('api.php?action=competitor_analysis_add_manual', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideFullscreenLoader();
                if (data.success) {
                    competitorAnalysesData.push(data.competitor);
                    renderCompetitorAnalyses();
                    urlInput.value = '';
                    termsInput.value = '';
                } else {
                    alert(data.error);
                }
            })
            .catch(err => {
                hideFullscreenLoader();
                console.error(err);
                alert("Crawling timed out or failed.");
            });
        }

        function addCompetitorManualTech(e) {
            e.preventDefault();
            const urlInput = document.getElementById('comp-manual-url-tech');
            const url = urlInput.value.trim();
            const searchTerms = '';

            if (!url) return;

            const btn = e.target.querySelector('button[type="submit"]');
            const originalBtnHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span><span>Crawling...</span>`;

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('url', url);
            formData.append('search_terms', searchTerms);

            fetch('api.php?action=competitor_analysis_add_manual', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                if (data.success) {
                    competitorAnalysesData.push(data.competitor);
                    renderCompetitorAnalyses();
                    urlInput.value = '';
                } else {
                    alert(data.error || 'Failed to crawl and add competitor.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                console.error(err);
                alert('Error adding competitor.');
            });
        }

        function addCompetitorManualPerf(e) {
            e.preventDefault();
            const urlInput = document.getElementById('comp-manual-url-perf');
            const url = urlInput.value.trim();
            const searchTerms = '';

            if (!url) return;

            const btn = e.target.querySelector('button[type="submit"]');
            const originalBtnHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px; margin-right: 6px;"></span><span>Crawling...</span>`;

            const formData = new FormData();
            formData.append('audit_id', activeAuditId);
            formData.append('url', url);
            formData.append('search_terms', searchTerms);

            fetch('api.php?action=competitor_analysis_add_manual', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                if (data.success) {
                    competitorAnalysesData.push(data.competitor);
                    renderCompetitorAnalyses();
                    urlInput.value = '';
                } else {
                    alert(data.error || 'Failed to crawl and add competitor.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHTML;
                console.error(err);
                alert('Error adding competitor.');
            });
        }

        function deleteCompetitorAnalysis(id) {
            if (!confirm('Are you sure you want to remove this competitor?')) return;

            const formData = new FormData();
            formData.append('id', id);

            fetch('api.php?action=competitor_analysis_delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    competitorAnalysesData = competitorAnalysesData.filter(c => c.id !== id);
                    renderCompetitorAnalyses();
                } else {
                    alert(data.error);
                }
            });
        }

        function switchCompSubTab(sub) {
            document.getElementById('comp-subtab-btn-seo').style.background = 'none';
            document.getElementById('comp-subtab-btn-seo').style.color = 'var(--text-primary)';
            document.getElementById('comp-subtab-btn-seo').style.borderColor = 'var(--border-glass)';
            
            document.getElementById('comp-subtab-btn-tech').style.background = 'none';
            document.getElementById('comp-subtab-btn-tech').style.color = 'var(--text-primary)';
            document.getElementById('comp-subtab-btn-tech').style.borderColor = 'var(--border-glass)';
            
            document.getElementById('comp-subtab-btn-perf').style.background = 'none';
            document.getElementById('comp-subtab-btn-perf').style.color = 'var(--text-primary)';
            document.getElementById('comp-subtab-btn-perf').style.borderColor = 'var(--border-glass)';

            const btn = document.getElementById(`comp-subtab-btn-${sub}`);
            btn.style.background = 'rgba(139, 92, 246, 0.1)';
            btn.style.borderColor = 'rgba(139, 92, 246, 0.3)';
            btn.style.color = 'var(--primary)';

            document.getElementById('comp-subtab-seo').style.display = sub === 'seo' ? 'block' : 'none';
            document.getElementById('comp-subtab-tech').style.display = sub === 'tech' ? 'block' : 'none';
            document.getElementById('comp-subtab-perf').style.display = sub === 'perf' ? 'block' : 'none';
            
            lucide.createIcons();
        }

        function fetchCompCoreWebVitals(competitorId, strategy) {
            const comp = competitorAnalysesData.find(c => c.id === competitorId);
            if (!comp) return;

            // Set loading state in tracking object
            compCwvLoading[`${competitorId}-${strategy}`] = true;

            // Render to update UI state immediately (show loader, disable buttons, hide placeholder)
            renderCompetitorAnalyses();

            const formData = new FormData();
            formData.append('type', 'competitor');
            formData.append('competitor_id', competitorId);
            formData.append('url', comp.url);
            formData.append('strategy', strategy);

            fetch('api.php?action=cwt_fetch', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                // Clear loading state
                compCwvLoading[`${competitorId}-${strategy}`] = false;

                if (data.success) {
                    competitorAnalysesData = competitorAnalysesData.map(c => c.id === competitorId ? data.data : c);
                    renderCompetitorAnalyses();
                } else {
                    renderCompetitorAnalyses();
                    alert(data.error || 'Failed to fetch PageSpeed insights.');
                }
            })
            .catch(err => {
                // Clear loading state
                compCwvLoading[`${competitorId}-${strategy}`] = false;
                renderCompetitorAnalyses();
                console.error(err);
                alert('PageSpeed request failed or timed out.');
            });
        }

        function fetchCompCoreWebVitalsAll(competitorId) {
            fetchCompCoreWebVitals(competitorId, 'desktop');
            fetchCompCoreWebVitals(competitorId, 'mobile');
        }

        function saveCompetitorTraffic(competitorId) {
            const comp = competitorAnalysesData.find(c => c.id === competitorId);
            if (!comp) return;

            const bounce = document.getElementById(`comp-bounce-${competitorId}`).value;
            const pages = document.getElementById(`comp-pages-${competitorId}`).value;
            const visits = document.getElementById(`comp-monthly-visits-${competitorId}`).value;
            
            const min = parseInt(document.getElementById(`comp-duration-min-${competitorId}`).value) || 0;
            const sec = parseInt(document.getElementById(`comp-duration-sec-${competitorId}`).value) || 0;
            const duration = (min * 60) + sec;

            const globalRank = document.getElementById(`comp-global-rank-${competitorId}`).value;
            const countryRank = document.getElementById(`comp-country-rank-${competitorId}`).value;

            const breakdown = document.getElementById(`comp-breakdown-${competitorId}`).value;

            const formData = new FormData();
            formData.append('id', competitorId);
            formData.append('url', comp.url);
            
            // Retain original SEO fields
            formData.append('meta_title', comp.meta_title || '');
            formData.append('meta_description', comp.meta_description || '');
            formData.append('h1', comp.h1 || '');
            formData.append('search_terms', comp.search_terms || '');
            formData.append('monthly_visits', comp.monthly_visits || '');
            formData.append('avg_time_per_visit', comp.avg_time_per_visit || '');

            formData.append('bounce_rate', bounce !== '' ? bounce : '');
            formData.append('pages_per_visit', pages !== '' ? pages : '');
            formData.append('avg_monthly_visits', visits !== '' ? visits : '');
            formData.append('avg_visit_duration', duration > 0 ? duration : '');
            formData.append('global_ranking', globalRank !== '' ? globalRank : '');
            formData.append('country_ranking', countryRank !== '' ? countryRank : '');
            formData.append('breakdown_by_country', breakdown);

            window.activeSavePromise = fetch('api.php?action=competitor_analysis_update', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    competitorAnalysesData = competitorAnalysesData.map(c => c.id === competitorId ? data.competitor : c);
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    showToast('Failed to auto-save competitor data: ' + (data.error || ''), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Request failed or timed out.', 'error');
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }


        // -------------------------------------------------------------
        // Core Layout/Navigation and UI Helpers
        // -------------------------------------------------------------
        function switchTab(eOrTabId, tabId) {
            let targetTabId;
            let targetBtn = null;

            if (typeof eOrTabId === 'string') {
                targetTabId = eOrTabId;
                targetBtn = document.querySelector(`button[onclick*="${targetTabId}"]`);
            } else if (eOrTabId && eOrTabId.currentTarget) {
                targetTabId = tabId;
                targetBtn = eOrTabId.currentTarget;
            } else {
                return;
            }

            document.querySelectorAll('.tab-link').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

            if (targetBtn) {
                targetBtn.classList.add('active');
            }
            const pane = document.getElementById(targetTabId);
            if (pane) {
                pane.classList.add('active');
            }

            updateUrlHash();
        }

        function switchSubTab(sub) {
            document.getElementById('subtab-btn-seo').style.background = 'none';
            document.getElementById('subtab-btn-seo').style.color = 'var(--text-primary)';
            document.getElementById('subtab-btn-seo').style.borderColor = 'var(--border-glass)';
            
            document.getElementById('subtab-btn-tech').style.background = 'none';
            document.getElementById('subtab-btn-tech').style.color = 'var(--text-primary)';
            document.getElementById('subtab-btn-tech').style.borderColor = 'var(--border-glass)';
            
            document.getElementById('subtab-btn-perf').style.background = 'none';
            document.getElementById('subtab-btn-perf').style.color = 'var(--text-primary)';
            document.getElementById('subtab-btn-perf').style.borderColor = 'var(--border-glass)';

            const btn = document.getElementById(`subtab-btn-${sub}`);
            if (btn) {
                btn.style.background = 'rgba(139, 92, 246, 0.1)';
                btn.style.borderColor = 'rgba(139, 92, 246, 0.3)';
                btn.style.color = 'var(--primary)';
            }

            const seoDiv = document.getElementById('subtab-seo');
            if (seoDiv) seoDiv.style.display = sub === 'seo' ? 'block' : 'none';
            const techDiv = document.getElementById('subtab-tech');
            if (techDiv) techDiv.style.display = sub === 'tech' ? 'block' : 'none';
            const perfDiv = document.getElementById('subtab-perf');
            if (perfDiv) perfDiv.style.display = sub === 'perf' ? 'block' : 'none';

            updateUrlHash();
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function closeModalOnOuterClick(e, id) {
            if (e.target.id === id) {
                closeModal(id);
            }
        }

        function showToast(message, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            
            const toast = document.createElement('div');
            toast.className = `toast-msg ${type}`;
            
            let iconHtml = '';
            if (type === 'success') {
                iconHtml = `<i data-lucide="check-circle" style="width: 18px; height: 18px; color: var(--success);"></i>`;
            } else if (type === 'error') {
                iconHtml = `<i data-lucide="alert-circle" style="width: 18px; height: 18px; color: var(--danger);"></i>`;
            } else {
                iconHtml = `<i data-lucide="info" style="width: 18px; height: 18px; color: var(--primary);"></i>`;
            }
            
            toast.innerHTML = `
                <div class="toast-icon">${iconHtml}</div>
                <div class="toast-text">${message}</div>
            `;
            
            container.appendChild(toast);
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            toast.offsetHeight; // Force reflow
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        function showFullscreenLoader(text) {
            document.getElementById('loader-text').textContent = text || 'Processing...';
            document.getElementById('fullscreen-loader').style.display = 'flex';
        }

        function hideFullscreenLoader() {
            document.getElementById('fullscreen-loader').style.display = 'none';
        }

        function logout() {
            fetch('api.php?action=logout')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'login.php';
                    }
                });
        }

        // String Helpers
        function escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function escapeJs(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'");
        }

        function getCompetitorDisplayName(url) {
            try {
                const parsed = new URL(url);
                let hostname = parsed.hostname.toLowerCase();
                hostname = hostname.replace(/^www\./, '');
                const parts = hostname.split('.');
                if (parts.length > 0) {
                    const shortTlds = ['com', 'co', 'org', 'net', 'gov', 'edu', 'mil', 'nom', 'ac'];
                    if (parts.length >= 3 && shortTlds.includes(parts[parts.length - 2])) {
                        return parts[parts.length - 3];
                    } else if (parts.length >= 2) {
                        return parts[parts.length - 2];
                    } else {
                        return parts[0];
                    }
                }
                return hostname;
            } catch (e) {
                return url;
            }
        }

        // Inline double-click editor listener
        document.addEventListener('dblclick', function(e) {
            const cell = e.target.closest('[data-editable="true"]');
            if (!cell) return;
            
            // If already editing, do nothing
            if (cell.querySelector('.inline-editor')) return;
            
            const id = cell.getAttribute('data-id');
            const type = cell.getAttribute('data-type');
            const field = cell.getAttribute('data-field');
            
            // Intercept Meta Title, Description, H1, Search Terms, and Notes to open the Text Viewer/Editor modal
            if (field === 'meta_title' || field === 'meta_description' || field === 'h1' || field === 'search_terms' || field === 'notes') {
                const currentValue = cell.getAttribute('data-value') || '';
                
                // Find page or competitor to get URL
                let record = null;
                if (type === 'page') {
                    record = pagesData.find(p => p.id == id);
                } else if (type === 'competitor_analysis') {
                    record = competitorAnalysesData.find(c => c.id == id);
                }
                const url = record ? record.url : '';
                openTextViewer(id, type, field, url, currentValue);
                return;
            }
            
            const inputType = cell.getAttribute('data-input-type') || 'text';
            const currentValue = cell.getAttribute('data-value') || '';
            
            const originalHtml = cell.innerHTML;
            cell.innerHTML = '';
            
            let input;
            if (inputType === 'textarea') {
                input = document.createElement('textarea');
                input.rows = 3;
            } else {
                input = document.createElement('input');
                input.type = inputType;
            }
            
            input.value = currentValue;
            input.className = 'form-input inline-editor';
            
            cell.appendChild(input);
            input.focus();
            if (inputType !== 'textarea') {
                input.select();
            }
            
            let finished = false;
            
            function finishEdit(save) {
                if (finished) return;
                finished = true;
                
                if (!save) {
                    cell.innerHTML = originalHtml;
                    return;
                }
                
                const newValue = input.value;
                if (newValue === currentValue) {
                    cell.innerHTML = originalHtml;
                    return;
                }
                
                cell.style.opacity = '0.5';
                
                const formData = new FormData();
                formData.append('id', id);
                formData.append('type', type);
                formData.append('field', field);
                formData.append('value', newValue);
                
                window.activeSavePromise = fetch('api.php?action=update_field', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    cell.style.opacity = '1';
                    if (data.success) {
                        const record = data.record;
                        if (type === 'page') {
                            pagesData = pagesData.map(p => p.id == id ? record : p);
                            renderPages();
                        } else if (type === 'competitor_analysis') {
                            competitorAnalysesData = competitorAnalysesData.map(c => c.id == id ? record : c);
                            renderCompetitorAnalyses();
                        } else if (type === 'audit') {
                            activeAuditCountry = record.target_country || "Website's Country";
                            const targetCountryInput = document.getElementById('perf-target-country');
                            if (targetCountryInput) {
                                targetCountryInput.value = activeAuditCountry;
                            }
                            renderPages();
                            renderCompetitorAnalyses();
                        }
                        saveAuditMetrics(true); // Save audit silently
                    } else {
                        alert(data.error || 'Failed to update field.');
                        cell.innerHTML = originalHtml;
                    }
                })
                .catch(err => {
                    cell.style.opacity = '1';
                    console.error(err);
                    alert('Error updating field.');
                    cell.innerHTML = originalHtml;
                })
                .finally(() => {
                    window.activeSavePromise = null;
                });
            }
            
            input.addEventListener('keydown', function(evt) {
                if (evt.key === 'Enter' && inputType !== 'textarea') {
                    evt.preventDefault();
                    finishEdit(true);
                } else if (evt.key === 'Escape') {
                    evt.preventDefault();
                    finishEdit(false);
                }
            });
            
            input.addEventListener('blur', function() {
                finishEdit(true);
            });
        });

        // Close active status selector dropdown
        function closeActiveDropdown() {
            if (activeDropdown) {
                console.log('closeActiveDropdown: removing dropdown');
                activeDropdown.remove();
                activeDropdown = null;
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (activeDropdown && !activeDropdown.contains(e.target) && !e.target.closest('.badge-clickable') && !e.target.closest('.action-trigger-btn')) {
                console.log('click outside: closing dropdown');
                closeActiveDropdown();
            }
        });

        // GSC Selector dropdown
        function showGscSelector(event, badge, id, currentVal) {
            console.log('showGscSelector called', { id, currentVal });
            event.stopPropagation();
            closeActiveDropdown();
            
            const rect = badge.getBoundingClientRect();
            console.log('Badge rect:', rect);
            
            const menu = document.createElement('div');
            menu.className = 'floating-selector-menu';
            
            // Position menu below the badge
            menu.style.top = `${rect.bottom + window.scrollY + 6}px`;
            menu.style.left = `${rect.left + window.scrollX + (rect.width / 2) - 70}px`;
            console.log('Calculated menu positions:', { top: menu.style.top, left: menu.style.left, scrollY: window.scrollY, scrollX: window.scrollX });
            
            const options = [
                { value: 'yes', label: 'YES (Indexed)', color: 'var(--success)' },
                { value: 'no', label: 'NO (Not Indexed)', color: 'var(--danger)' },
                { value: '', label: '-', color: 'var(--text-secondary)' }
            ];
            
            options.forEach(opt => {
                const item = document.createElement('div');
                item.className = 'floating-selector-item' + (currentVal === opt.value ? ' active' : '');
                item.innerHTML = `
                    <span>${opt.label}</span>
                    <span class="status-dot" style="background-color: ${opt.color};"></span>
                `;
                item.onclick = function(e) {
                    console.log('Option clicked:', opt.value);
                    e.stopPropagation();
                    updateGscStatus(id, opt.value);
                };
                menu.appendChild(item);
            });
            
            document.body.appendChild(menu);
            activeDropdown = menu;
            console.log('Dropdown appended to body');
        }

        function updateGscStatus(id, newVal) {
            console.log('updateGscStatus called', { id, newVal });
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', 'page');
            formData.append('field', 'indexing_gsc');
            formData.append('value', newVal);
            
            fetch('api.php?action=update_field', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log('updateGscStatus API response:', data);
                if (data.success) {
                    pagesData = pagesData.map(p => p.id == id ? data.record : p);
                    renderPages();
                } else {
                    alert(data.error || 'Failed to update GSC status.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error updating GSC status.');
            });
        }

        // Crawl Errors Selector dropdown
        function showCrawlErrorsSelector(event, badge, id, currentVal) {
            console.log('showCrawlErrorsSelector called', { id, currentVal });
            event.stopPropagation();
            closeActiveDropdown();
            
            const rect = badge.getBoundingClientRect();
            console.log('Badge rect:', rect);
            
            const menu = document.createElement('div');
            menu.className = 'floating-selector-menu';
            
            // Position menu below the badge
            menu.style.top = `${rect.bottom + window.scrollY + 6}px`;
            menu.style.left = `${rect.left + window.scrollX + (rect.width / 2) - 70}px`;
            console.log('Calculated menu positions:', { top: menu.style.top, left: menu.style.left, scrollY: window.scrollY, scrollX: window.scrollX });
            
            const options = [
                { value: 'yes', label: 'ERRORS', color: 'var(--danger)' },
                { value: 'no', label: 'NO ERRORS', color: 'var(--success)' },
                { value: '', label: '-', color: 'var(--text-secondary)' }
            ];
            
            options.forEach(opt => {
                const item = document.createElement('div');
                item.className = 'floating-selector-item' + (currentVal === opt.value ? ' active' : '');
                item.innerHTML = `
                    <span>${opt.label}</span>
                    <span class="status-dot" style="background-color: ${opt.color};"></span>
                `;
                item.onclick = function(e) {
                    console.log('Option clicked:', opt.value);
                    e.stopPropagation();
                    updateCrawlErrorsStatus(id, opt.value);
                };
                menu.appendChild(item);
            });
            
            document.body.appendChild(menu);
            activeDropdown = menu;
            console.log('Dropdown appended to body');
        }

        function updateCrawlErrorsStatus(id, newVal) {
            console.log('updateCrawlErrorsStatus called', { id, newVal });
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', 'page');
            formData.append('field', 'crawl_errors');
            formData.append('value', newVal);
            
            fetch('api.php?action=update_field', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log('updateCrawlErrorsStatus API response:', data);
                if (data.success) {
                    pagesData = pagesData.map(p => p.id == id ? data.record : p);
                    renderPages();
                } else {
                    alert(data.error || 'Failed to update crawl errors.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error updating crawl errors.');
            });
        }

        // Floating URL Actions dropdown menu
        function showUrlActionsDropdown(event, button, id, type, url) {
            event.stopPropagation();
            closeActiveDropdown();
            
            const rect = button.getBoundingClientRect();
            const menu = document.createElement('div');
            menu.className = 'floating-selector-menu';
            
            // Position menu below the button
            menu.style.top = `${rect.bottom + window.scrollY + 6}px`;
            menu.style.left = `${rect.left + window.scrollX}px`;
            
            // Refresh Option
            const refreshItem = document.createElement('div');
            refreshItem.className = 'floating-selector-item';
            refreshItem.style.display = 'flex';
            refreshItem.style.alignItems = 'center';
            refreshItem.style.gap = '8px';
            refreshItem.innerHTML = `
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                <span>Refresh Audit</span>
            `;
            refreshItem.onclick = function(e) {
                e.stopPropagation();
                closeActiveDropdown();
                if (type === 'page') {
                    refreshPageAudit(id, button);
                } else {
                    refreshCompetitorAudit(id, button);
                }
            };
            menu.appendChild(refreshItem);
            
            // Delete Option
            const deleteItem = document.createElement('div');
            deleteItem.className = 'floating-selector-item';
            deleteItem.style.display = 'flex';
            deleteItem.style.alignItems = 'center';
            deleteItem.style.gap = '8px';
            deleteItem.style.color = 'var(--danger)';
            deleteItem.innerHTML = `
                <i data-lucide="trash-2" style="width: 14px; height: 14px; color: var(--danger);"></i>
                <span>Delete URL</span>
            `;
            deleteItem.onclick = function(e) {
                e.stopPropagation();
                closeActiveDropdown();
                if (type === 'page') {
                    deletePage(id);
                } else {
                    deleteCompetitorAnalysis(id);
                }
            };
            menu.appendChild(deleteItem);
            
            document.body.appendChild(menu);
            activeDropdown = menu;
            
            lucide.createIcons();
        }

        function refreshPageAudit(id, button) {
            button.disabled = true;
            button.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px;"></span>`;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=page_refresh', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    pagesData = pagesData.map(p => p.id == id ? data.page : p);
                    renderPages();
                } else {
                    alert(data.error || 'Failed to refresh page audit.');
                    renderPages();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error refreshing page audit.');
                renderPages();
            });
        }

        function refreshCompetitorAudit(id, button) {
            button.disabled = true;
            button.innerHTML = `<span class="spinner" style="border-color: currentColor; border-top-color: transparent; width: 12px; height: 12px;"></span>`;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('api.php?action=competitor_analysis_refresh', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    competitorAnalysesData = competitorAnalysesData.map(c => c.id == id ? data.competitor : c);
                    renderCompetitorAnalyses();
                } else {
                    alert(data.error || 'Failed to refresh competitor audit.');
                    renderCompetitorAnalyses();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error refreshing competitor audit.');
                renderCompetitorAnalyses();
            });
        }

        // Country name to ISO code mapping for FlagCDN flags
        const countryNameToCode = {
            "united states": "us", "usa": "us", "united states of america": "us",
            "france": "fr", "fr": "fr",
            "spain": "es", "espagne": "es",
            "côte d'ivoire": "ci", "cote d'ivoire": "ci", "ivory coast": "ci",
            "switzerland": "ch", "suisse": "ch",
            "united kingdom": "gb", "uk": "gb", "great britain": "gb",
            "canada": "ca",
            "germany": "de", "deutschland": "de", "allemagne": "de",
            "italy": "it", "italia": "it", "italie": "it",
            "netherlands": "nl", "holland": "nl", "pays-bas": "nl",
            "belgium": "be", "belgique": "be",
            "mexico": "mx", "mexique": "mx",
            "brazil": "br", "brasil": "br", "brésil": "br",
            "argentina": "ar", "argentine": "ar",
            "japan": "jp", "japon": "jp",
            "china": "cn", "chine": "cn",
            "india": "in", "inde": "in",
            "australia": "au", "australie": "au",
            "russia": "ru", "russie": "ru",
            "south africa": "za", "afrique du sud": "za",
            "morocco": "ma", "maroc": "ma",
            "algeria": "dz", "algérie": "dz",
            "tunisia": "tn", "tunisie": "tn",
            "senegal": "sn", "sénégal": "sn",
            "portugal": "pt",
            "ireland": "ie", "irlande": "ie",
            "sweden": "se", "suède": "se",
            "norway": "no", "norvège": "no",
            "denmark": "dk", "danemark": "dk",
            "finland": "fi", "finlande": "fi",
            "poland": "pl", "pologne": "pl",
            "romania": "ro", "roumanie": "ro",
            "turkey": "tr", "turquie": "tr",
            "ukraine": "ua",
            "saudi arabia": "sa",
            "united arab emirates": "ae", "uae": "ae",
            "singapore": "sg", "singapour": "sg",
            "south korea": "kr", "corée du sud": "kr",
            "vietnam": "vn", "thailand": "th", "indonesia": "id",
            "malaysia": "my", "philippines": "ph", "new zealand": "nz",
            "greece": "gr", "austria": "at", "egypt": "eg", "colombia": "co",
            "chile": "cl", "peru": "pe", "venezuela": "ve"
        };



        function updateFlagPreview(inputEl) {
            const name = inputEl.value.trim().toLowerCase();
            const code = countryNameToCode[name] || '';
            const img = inputEl.parentNode.querySelector('.audience-flag-img');
            if (img) {
                if (code) {
                    img.src = `https://flagcdn.com/w40/${code}.png`;
                    img.style.display = 'inline-block';
                } else {
                    img.style.display = 'none';
                }
            }
        }

        function parseLegacyAudience(str) {
            if (!str) return [];
            const result = [];
            const parts = str.split(',');
            parts.forEach(p => {
                const sub = p.split(':');
                if (sub.length === 2) {
                    const country = sub[0].trim();
                    const percentStr = sub[1].replace('%', '').trim();
                    const percent = parseFloat(percentStr) || '';
                    if (country) {
                        result.push({ country, percent });
                    }
                }
            });
            return result;
        }

        function getAudienceCount(val) {
            if (!val) return 0;
            try {
                if (val.trim().startsWith('[')) {
                    return JSON.parse(val).length;
                }
            } catch(e) {}
            return val.split(',').filter(x => x.trim().length > 0).length;
        }

        let currentAudienceEditingId = null;
        let currentAudienceEditingType = null;

        function viewAudience(id, type) {
            currentAudienceEditingId = id;
            currentAudienceEditingType = type;

            let record = null;
            if (type === 'page') {
                record = pagesData.find(p => p.id === id);
            } else {
                record = competitorAnalysesData.find(c => c.id === id);
            }
            if (!record) return;

            const audienceVal = record.audience_country_proportion || '';
            let parsed = [];
            try {
                if (audienceVal.trim().startsWith('[')) {
                    parsed = JSON.parse(audienceVal);
                } else {
                    parsed = parseLegacyAudience(audienceVal);
                }
            } catch(e) {
                parsed = [];
            }

            const container = document.getElementById('audience-editor-rows');
            container.innerHTML = '';

            for (let i = 0; i < 5; i++) {
                const item = parsed[i] || { country: '', percent: '' };
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.gap = '12px';

                const flagCode = countryNameToCode[item.country.trim().toLowerCase()] || '';
                const flagSrc = flagCode ? `https://flagcdn.com/w40/${flagCode}.png` : '';

                row.innerHTML = `
                    <div style="width: 40px; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <img class="audience-flag-img" src="${flagSrc}" style="width: 24px; height: 18px; border-radius: 2px; border: 1px solid rgba(255,255,255,0.1); display: ${flagCode ? 'inline-block' : 'none'};">
                    </div>
                    <input type="text" class="form-input audience-country-name" style="flex: 2; padding: 8px 12px; font-size: 0.9rem;" placeholder="Country Name (e.g. France)" value="${escapeHtml(item.country)}" oninput="updateFlagPreview(this)">
                    <input type="number" step="0.01" min="0" max="100" class="form-input audience-country-percent" style="flex: 1; padding: 8px 12px; font-size: 0.9rem;" placeholder="Proportion %" value="${item.percent}">
                    <span style="font-weight: 500; font-size: 0.9rem;">%</span>
                `;
                container.appendChild(row);
            }

            openModal('audience-modal');
        }

        function saveAudience() {
            if (!currentAudienceEditingId || !currentAudienceEditingType) return;

            const nameInputs = document.querySelectorAll('.audience-country-name');
            const percentInputs = document.querySelectorAll('.audience-country-percent');
            const data = [];

            for (let i = 0; i < nameInputs.length; i++) {
                const country = nameInputs[i].value.trim();
                const percentVal = percentInputs[i].value.trim();
                const percent = percentVal !== '' ? parseFloat(percentVal) : null;
                if (country) {
                    data.push({ country, percent });
                }
            }

            const jsonStr = JSON.stringify(data);
            const formData = new FormData();
            formData.append('id', currentAudienceEditingId);
            formData.append('type', currentAudienceEditingType === 'page' ? 'page' : 'competitor_analysis');
            formData.append('field', 'audience_country_proportion');
            formData.append('value', jsonStr);

            window.activeSavePromise = fetch('api.php?action=update_field', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('audience-modal');
                    const record = data.record;
                    if (currentAudienceEditingType === 'page') {
                        pagesData = pagesData.map(p => p.id === currentAudienceEditingId ? record : p);
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id === currentAudienceEditingId ? record : c);
                        renderCompetitorAnalyses();
                    }
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    alert(data.error || 'Failed to save audience.');
                }
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }

        let textViewerEditingId = null;
        let textViewerEditingType = null;
        let textViewerEditingField = null;

        function getFieldLabel(field) {
            if (field === 'meta_title') return 'Meta Title';
            if (field === 'meta_description') return 'Meta Description';
            if (field === 'h1') return 'H1';
            if (field === 'notes') return 'Notes';
            return field;
        }

        function openTextViewer(id, type, field, url, text) {
            textViewerEditingId = parseInt(id);
            textViewerEditingType = type;
            textViewerEditingField = field;

            document.getElementById('text-viewer-field-name').textContent = 'Edit ' + getFieldLabel(field);
            document.getElementById('text-viewer-url').textContent = 'URL: ' + url;
            
            const textarea = document.getElementById('text-viewer-content');
            textarea.value = text || '';
            
            const charCount = document.getElementById('text-viewer-char-count');
            charCount.textContent = (text || '').length + ' characters';
            
            textarea.oninput = function() {
                charCount.textContent = this.value.length + ' characters';
            };

            const hint = document.getElementById('text-viewer-hint');
            if (field === 'search_terms') {
                hint.style.display = 'flex';
                textarea.setAttribute('wrap', 'off');
                textarea.style.whiteSpace = 'pre';
                textarea.style.overflowX = 'auto';
            } else {
                hint.style.display = 'none';
                textarea.setAttribute('wrap', 'soft');
                textarea.style.whiteSpace = 'pre-wrap';
                textarea.style.overflowX = 'hidden';
            }

            openModal('text-viewer-modal');
        }

        function saveTextViewerContent() {
            if (!textViewerEditingId || !textViewerEditingType || !textViewerEditingField) return;

            const newValue = document.getElementById('text-viewer-content').value;

            const formData = new FormData();
            formData.append('id', textViewerEditingId);
            formData.append('type', textViewerEditingType === 'page' ? 'page' : 'competitor_analysis');
            formData.append('field', textViewerEditingField);
            formData.append('value', newValue);

            window.activeSavePromise = fetch('api.php?action=update_field', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('text-viewer-modal');
                    const record = data.record;
                    if (textViewerEditingType === 'page') {
                        pagesData = pagesData.map(p => p.id == textViewerEditingId ? record : p);
                        renderPages();
                    } else {
                        competitorAnalysesData = competitorAnalysesData.map(c => c.id == textViewerEditingId ? record : c);
                        renderCompetitorAnalyses();
                    }
                    saveAuditMetrics(true); // Save audit silently
                } else {
                    alert(data.error || 'Failed to update content.');
                }
            })
            .finally(() => {
                window.activeSavePromise = null;
            });
        }

        function truncateCellText(text) {
            if (!text) return 'N/A';
            if (text.length <= 20) return text;
            return text.substring(0, 20) + '...';
        }

        function getDomainFromUrl(url) {
            if (!url) return '';
            try {
                const parsed = new URL(url);
                let host = parsed.hostname.replace(/^www\./i, '');
                const doubleTlds = /\.(?:com?|org|net|gov|edu|co)\.[a-z]{2,3}$/i;
                if (doubleTlds.test(host)) {
                    host = host.replace(doubleTlds, '');
                } else {
                    host = host.replace(/\.[a-z]{2,8}$/i, '');
                }
                return host;
            } catch (e) {
                const clean = url.trim().replace(/^(?:https?:\/\/)?(?:www\.)?/i, '');
                let host = clean.split('/')[0];
                const doubleTlds = /\.(?:com?|org|net|gov|edu|co)\.[a-z]{2,3}$/i;
                if (doubleTlds.test(host)) {
                    host = host.replace(doubleTlds, '');
                } else {
                    host = host.replace(/\.[a-z]{2,8}$/i, '');
                }
                return host;
            }
        }

        function getUrlDisplayName(url) {
            if (!url) return 'N/A';
            try {
                const cleanUrl = url.replace(/\/+$/, '');
                const parsed = new URL(cleanUrl);
                const pathname = parsed.pathname;
                
                if (!pathname || pathname === '/' || pathname === '') {
                    const host = parsed.hostname.replace('www.', '');
                    const firstPart = host.split('.')[0];
                    return firstPart.charAt(0).toUpperCase() + firstPart.slice(1);
                }
                
                const parts = pathname.split('/').filter(x => x.length > 0);
                if (parts.length === 0) return 'Home';
                
                let lastPart = parts[parts.length - 1];
                lastPart = lastPart.replace(/\.(html|php|asp|aspx|htm)$/i, '');
                
                if (lastPart.startsWith('casa-')) {
                    lastPart = lastPart.substring(5);
                }
                
                lastPart = lastPart.replace(/[-_]/g, ' ');
                return lastPart.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            } catch(e) {
                return url;
            }
        }

        function formatSearchTermsAsBullets(str) {
            if (!str || !str.trim()) return '-';
            const terms = str.split(/[,\;\n\r]+/).map(t => t.trim()).filter(t => t.length > 0);
            if (terms.length === 0) return '-';
            let html = '<ul style="margin: 0; padding-left: 14px; list-style-type: disc; text-align: left;">';
            terms.forEach(t => {
                html += `<li>${escapeHtml(t)}</li>`;
            });
            html += '</ul>';
            return html;
        }

        function openImageLightbox(src) {
            const modal = document.getElementById('image-lightbox-modal');
            const img = document.getElementById('lightbox-image');
            if (modal && img) {
                img.src = src;
                modal.style.display = 'flex';
            }
        }

        function closeImageLightbox() {
            const modal = document.getElementById('image-lightbox-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <!-- Lightbox Modal for fullscreen image view -->
    <div id="image-lightbox-modal" class="modal-overlay" onclick="closeImageLightbox()" style="display: none; justify-content: center; align-items: center; background: rgba(0,0,0,0.9); z-index: 10000; position: fixed; top: 0; left: 0; width: 100%; height: 100%;">
        <button type="button" onclick="closeImageLightbox()" style="position: absolute; top: 20px; right: 25px; background: none; border: none; color: #fff; font-size: 35px; cursor: pointer; font-weight: 300; line-height: 1;">&times;</button>
        <img id="lightbox-image" src="" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 4px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);" onclick="event.stopPropagation();">
    </div>
</body>
</html>
