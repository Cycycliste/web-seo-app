<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report - Viewer Mode</title>
    <link rel="stylesheet" href="index.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="share-body" style="padding: 20px 0;">

    <div class="share-container">
        
        <!-- Header -->
        <header class="glass-panel" style="padding: 30px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div class="flex-align">
                <div class="logo-icon">
                    <i data-lucide="award" style="width: 22px; height: 22px; color: white;"></i>
                </div>
                <div>
                    <h1 style="font-weight: 800; font-size: 1.5rem;" id="audit-title">SEO Audit Report</h1>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                        Prepared for <span id="client-name" style="font-weight: 600; color: white;">...</span> 
                        <span id="client-industry" style="font-style: italic; color: var(--text-muted); margin-left: 6px;"></span>
                    </p>
                </div>
            </div>
            
            <div style="text-align: right;">
                <a id="client-homepage" href="#" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem;">
                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                    <span id="client-homepage-text">Visit Website</span>
                </a>
            </div>
        </header>

        <!-- Main stats summary widgets -->
        <div class="grid-3">
            <div class="glass-panel metric-card">
                <div class="metric-icon" style="color: var(--primary);">
                    <i data-lucide="mouse-pointer-click" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase;">Average Monthly Visits</span>
                    <div id="stat-monthly-visits" class="metric-val">-</div>
                </div>
            </div>
            <div class="glass-panel metric-card">
                <div class="metric-icon" style="color: var(--secondary);">
                    <i data-lucide="percent" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase;">Bounce Rate</span>
                    <div id="stat-bounce-rate" class="metric-val">-</div>
                </div>
            </div>
            <div class="glass-panel metric-card">
                <div class="metric-icon" style="color: var(--success);">
                    <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase;">Avg. Visit Duration</span>
                    <div id="stat-visit-duration" class="metric-val">-</div>
                </div>
            </div>
        </div>

        <!-- Tabs Headers -->
        <div class="tabs-header">
            <button class="tab-link active" onclick="switchTab(event, 'tab-website-audit')">
                <i data-lucide="search-code" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                <span>Website Audit Summary</span>
            </button>
            <button class="tab-link" onclick="switchTab(event, 'tab-search-terms')">
                <i data-lucide="key-round" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;"></i>
                <span>Search Terms & Competitors</span>
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
            <div class="flex-space" style="margin-bottom: 24px;">
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-secondary btn-sm" id="subtab-btn-seo" onclick="switchSubTab('seo')" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3); color: var(--primary);">SEO State</button>
                    <button class="btn btn-secondary btn-sm" id="subtab-btn-tech" onclick="switchSubTab('tech')">Technical & Speed State</button>
                    <button class="btn btn-secondary btn-sm" id="subtab-btn-perf" onclick="switchSubTab('perf')">Detailed Traffic Analytics</button>
                </div>
            </div>

            <!-- Subtab: SEO State -->
            <div id="subtab-seo">
                <h3 style="font-weight: 700; margin-bottom: 16px;">Audited Website Pages (SEO)</h3>
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
                            <!-- JS populated -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subtab: Technical State -->
            <div id="subtab-tech" style="display: none;">
                <!-- Web Vitals homepage -->
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
                    </div>

                    <div id="audit-cwv-body">
                        <div id="cwv-results" class="cwv-container">
                            <!-- Desktop -->
                            <div class="glass-card cwv-strategy-card">
                                <div class="cwv-header">
                                    <div class="flex-align" style="gap: 8px;">
                                        <i data-lucide="monitor" style="width: 20px; height: 20px; color: var(--secondary);"></i>
                                        <span style="font-weight: 600;">Desktop Strategy</span>
                                    </div>
                                </div>
                                
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

                            <!-- Mobile -->
                            <div class="glass-card cwv-strategy-card">
                                <div class="cwv-header">
                                    <div class="flex-align" style="gap: 8px;">
                                        <i data-lucide="smartphone" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                        <span style="font-weight: 600;">Mobile Strategy</span>
                                    </div>
                                </div>

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
                </div>

                 <div style="display: grid; grid-template-columns: minmax(auto, 676px) 350px 350px; gap: 0 24px; width: 100%;">
                     
                     <!-- Row 1, Column 1: Header -->
                     <h3 style="font-weight: 700; margin-bottom: 16px; grid-column: 1; grid-row: 1;">Indexing & GSC Crawl Logs</h3>
                     
                     <!-- Row 2, Column 1: Table Wrapper -->
                     <div class="table-wrapper" style="grid-column: 1; grid-row: 2; margin-bottom: 30px; width: 100%;">
                         <table class="table-custom">
                             <thead>
                                 <tr>
                                     <th style="width: 50%;">URL</th>
                                     <th style="width: 25%; text-align: center;">Indexed in GSC</th>
                                     <th style="width: 25%; text-align: center;">Crawl Errors</th>
                                 </tr>
                             </thead>
                             <tbody id="tech-pages-list">
                                 <!-- JS populated -->
                             </tbody>
                         </table>
                     </div>
 
                     <!-- Row 2, Column 2: Sitemap Status & Setup Details -->
                     <div class="glass-panel" style="grid-column: 2; grid-row: 2; padding: 24px; margin-bottom: 30px; display: flex; flex-direction: column;">
                         <h4 style="margin-bottom: 16px; font-weight: 600;">Sitemap Status & Setup Details</h4>
                         <div id="sitemap-details-box" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; background: rgba(0,0,0,0.2); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); flex-grow: 1;">
                             No sitemap information registered.
                         </div>
                     </div>
 
                     <!-- Row 2, Column 3: Additional Notes -->
                     <div class="glass-panel" style="grid-column: 3; grid-row: 2; padding: 24px; margin-bottom: 30px; display: flex; flex-direction: column;">
                         <h4 style="margin-bottom: 16px; font-weight: 600;">Additional Notes</h4>
                         <div id="additional-notes-box" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; background: rgba(0,0,0,0.2); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); flex-grow: 1;">
                             No additional notes registered.
                         </div>
                     </div>
 
                 </div>
            </div>

            <!-- Subtab: Traffic Analytics -->
            <div id="subtab-perf" style="display: none;">
                <div class="glass-panel" style="padding: 30px;">
                    <h3 style="font-weight: 700; margin-bottom: 24px; border-bottom: 1px solid var(--border-glass); padding-bottom: 12px;">Detailed Web Traffic Insights</h3>
                    
                    <div class="grid-form">
                        <!-- Left Column: Metrics -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <!-- Row 1: Bounce Rate & Pages Per Visit -->
                            <div style="display: flex; gap: 15px;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Bounce Rate (%)</label>
                                    <input type="text" id="perf-bounce-rate" class="form-input" style="opacity:0.8;" readonly>
                                </div>
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Pages Per Visit</label>
                                    <input type="text" id="perf-pages-per-visit" class="form-input" style="opacity:0.8;" readonly>
                                </div>
                            </div>

                            <!-- Row 2: Average Monthly Visits & Average Visit Duration -->
                            <div style="display: flex; gap: 15px;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Average Monthly Visits</label>
                                    <input type="text" id="perf-avg-monthly-visits" class="form-input" style="opacity:0.8;" readonly>
                                </div>
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Average Visit Duration</label>
                                    <input type="text" id="perf-avg-visit-duration" class="form-input" style="opacity:0.8;" readonly>
                                </div>
                            </div>

                            <!-- Row 3: Global Rank & Country Rank -->
                            <div style="display: flex; gap: 15px;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Global Rank</label>
                                    <input type="text" id="perf-global-ranking" class="form-input" style="opacity:0.8;" readonly>
                                </div>
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label>Country Rank</label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <input type="text" id="perf-country-ranking" class="form-input" style="opacity:0.8; flex: 1;" readonly>
                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">in</span>
                                        <input type="text" id="perf-target-country" class="form-input" style="opacity:0.8; flex: 1.5;" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Country Breakdown -->
                        <div style="display: flex; flex-direction: column; height: 100%;">
                            <div class="form-group" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0;">
                                <label style="margin-bottom: 8px;">Country Breakdown</label>
                                <div id="share-breakdown-country-container" style="flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
                                    <textarea id="perf-breakdown-country" class="form-input" style="opacity:0.8; flex-grow: 1; min-height: 180px; resize: none; height: 100%;" readonly></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Main Channels</label>
                            <div id="share-main-channels-box" style="margin-top: 8px;">
                                <!-- Image populated dynamically -->
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Traffic Trends (Last 3-6 Months)</label>
                            <div id="share-traffic-trends-box" style="margin-top: 8px;">
                                <!-- Image populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Search Terms & Competitors -->
        <div id="tab-search-terms" class="tab-pane">
            <h3 style="font-weight: 700; margin-bottom: 20px;">Keywords & Target Competitor Mapping</h3>
            <div id="search-terms-wrapper" style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 40px;">
                <!-- Filled via JS -->
            </div>
        </div>

        <!-- Tab 3: Competitor Analyses -->
        <div id="tab-competitor-analysis" class="tab-pane">
            <h3 style="font-weight: 700; margin-bottom: 24px;">In-Depth Competitor Page Audits</h3>
            <div id="competitor-analysis-cards" style="display: flex; flex-direction: column; gap: 28px;">
                <!-- JS populated cards -->
            </div>
        </div>

        <!-- Tab 4: Global Report & Strategy -->
        <div id="tab-global-report" class="tab-pane">
            <h3 style="font-weight: 700; margin-bottom: 24px;">Global Analysis & Strategic Report</h3>
            <div class="glass-panel" style="padding: 30px; margin-bottom: 24px;">
                <h4 style="font-weight: 600; margin-bottom: 16px; color: var(--primary); display: flex; align-items: center; gap: 6px; font-size: 1.1rem;">
                    <i data-lucide="compass" style="width: 18px; height: 18px;"></i>
                    <span>Global Analysis & Audit Meaning</span>
                </h4>
                <div id="share-global-analysis" style="color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; font-size: 0.95rem; margin-bottom: 36px; padding: 14px; background: rgba(0,0,0,0.15); border-radius: var(--radius-sm); border: 1px solid var(--border-glass);">
                    <!-- JS populated -->
                </div>
                
                <h4 style="font-weight: 600; margin-bottom: 16px; color: var(--secondary); display: flex; align-items: center; gap: 6px; font-size: 1.1rem; border-top: 1px solid var(--border-glass); padding-top: 28px;">
                    <i data-lucide="trending-up" style="width: 18px; height: 18px;"></i>
                    <span>Recommendations & Strategy to Adopt</span>
                </h4>
                <div id="share-global-strategy" style="color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; font-size: 0.95rem; padding: 14px; background: rgba(0,0,0,0.15); border-radius: var(--radius-sm); border: 1px solid var(--border-glass);">
                    <!-- JS populated -->
                </div>
            </div>
        </div>

    </div>

    <!-- Headers Modal -->
    <div id="headers-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'headers-modal')">
        <div class="modal-content glass-panel" style="max-width: 980px; width: 90%;">
            <div class="modal-header" style="margin-bottom: 16px;">
                <h3 style="font-weight: 700;">Semantic Headers Structure</h3>
                <button class="modal-close" onclick="closeModal('headers-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="headers-modal-tree" class="headers-structure-tree" style="height: 420px; max-height: 420px; overflow-y: auto;">
                    <!-- Tree items rendered via JS -->
                </div>
            </div>
            
            <!-- Footer with static H1-H6 counts display -->
            <div class="flex-space" style="border-top: 1px solid var(--border-glass); padding-top: 20px; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H1</div>
                        <div id="share-h1-count" style="font-weight: bold; font-size: 0.95rem; color: var(--primary);">0</div>
                    </div>
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H2</div>
                        <div id="share-h2-count" style="font-weight: bold; font-size: 0.95rem; color: var(--secondary);">0</div>
                    </div>
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H3</div>
                        <div id="share-h3-count" style="font-weight: bold; font-size: 0.95rem; color: var(--success);">0</div>
                    </div>
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H4</div>
                        <div id="share-h4-count" style="font-weight: bold; font-size: 0.95rem; color: var(--warning);">0</div>
                    </div>
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H5</div>
                        <div id="share-h5-count" style="font-weight: bold; font-size: 0.95rem; color: var(--accent);">0</div>
                    </div>
                    <div style="text-align: center; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); min-width: 50px;">
                        <div style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 2px;">H6</div>
                        <div id="share-h6-count" style="font-weight: bold; font-size: 0.95rem; color: var(--text-muted);">0</div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="closeModal('headers-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal: View Full Text -->
    <div id="text-viewer-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'text-viewer-modal')">
        <div class="modal-content glass-panel" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;" id="text-viewer-field-name">Field Value</h3>
                <button class="modal-close" onclick="closeModal('text-viewer-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;" id="text-viewer-url">URL: </div>
                <div class="form-group">
                    <textarea id="text-viewer-content" class="form-input" style="height: 160px; resize: vertical; opacity: 0.9; font-family: inherit;" readonly></textarea>
                    <div id="text-viewer-hint" style="display: none; font-size: 0.8rem; color: var(--primary); margin-top: 8px; align-items: center; gap: 6px;">
                        <i data-lucide="info" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                        <span>Search terms list (one per line).</span>
                    </div>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); text-align: right;" id="text-viewer-char-count">0 characters</div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('text-viewer-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal: Audience Countries (Read-only) -->
    <div id="audience-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'audience-modal')">
        <div class="modal-content glass-panel" style="max-width: 550px;">
            <div class="modal-header">
                <h3 style="font-weight: 700;">Audience Location & Proportions</h3>
                <button class="modal-close" onclick="closeModal('audience-modal')">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 20px;">Top countries contributing to this page's traffic (SimilarWeb metrics):</p>
                
                <div id="audience-view-rows" style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- JS renders read-only rows here -->
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--border-glass); padding-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('audience-modal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables for viewer
        let activeAuditCountry = 'Website\'s Country';
        let pagesData = [];
        let searchTermsData = [];
        let competitorsData = [];
        let competitorAnalysesData = [];
        let collapsedAuditTech = false;

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

        function updateUrlHash() {
            if (isRouting) return;
            const activeTab = getActiveTab();
            const activeSubTab = getActiveSubTab();
            window.location.hash = `tab=${activeTab}&subtab=${activeSubTab}`;
        }

        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const token = params.get('token');

            if (!token) {
                document.body.innerHTML = `
                    <div style="text-align:center; padding:100px 20px; font-family:sans-serif; color:white;">
                        <h2 style="color:var(--danger)">Access Denied</h2>
                        <p style="color:var(--text-secondary); margin-top:10px;">Missing share token. Verify your link URL.</p>
                    </div>
                `;
                return;
            }

            loadShareAudit(token).then(() => {
                const hash = window.location.hash;
                if (hash.startsWith('#')) {
                    const hashParams = new URLSearchParams(hash.substring(1));
                    const tab = hashParams.get('tab');
                    const subtab = hashParams.get('subtab');

                    isRouting = true;
                    if (tab) {
                        switchTab(tab);
                    }
                    if (subtab) {
                        switchSubTab(subtab);
                    }
                    isRouting = false;
                    updateUrlHash();
                }
            });
        });

        function loadShareAudit(token) {
            return fetch(`api.php?action=get_share_audit&token=${encodeURIComponent(token)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        document.body.innerHTML = `
                            <div style="text-align:center; padding:100px 20px; font-family:sans-serif; color:white;">
                                <h2 style="color:var(--danger)">Report Unavailable</h2>
                                <p style="color:var(--text-secondary); margin-top:10px;">${escapeHtml(data.error)}</p>
                            </div>
                        `;
                        return;
                    }

                    // Populate layout
                    document.getElementById('audit-title').textContent = data.audit.name + ' - SEO Audit';
                    document.getElementById('client-name').textContent = data.audit.client_name;
                    document.getElementById('client-industry').textContent = data.audit.client_industry ? `(${data.audit.client_industry})` : '';
                    
                    document.getElementById('client-homepage').href = data.audit.client_homepage_url;
                    document.getElementById('client-homepage-text').textContent = data.audit.client_homepage_url.replace(/^https?:\/\//, '');

                    // Metric values
                    document.getElementById('stat-monthly-visits').textContent = data.audit.avg_monthly_visits ? data.audit.avg_monthly_visits.toLocaleString() : '-';
                    document.getElementById('stat-bounce-rate').textContent = data.audit.bounce_rate ? data.audit.bounce_rate + '%' : '-';
                    document.getElementById('stat-visit-duration').textContent = data.audit.avg_visit_duration ? Math.floor(data.audit.avg_visit_duration / 60) + 'm ' + (data.audit.avg_visit_duration % 60) + 's' : '-';

                    // Populate Traffic detailed form fields
                    document.getElementById('perf-bounce-rate').value = data.audit.bounce_rate !== null ? data.audit.bounce_rate + '%' : '-';
                    document.getElementById('perf-pages-per-visit').value = data.audit.pages_per_visit !== null ? data.audit.pages_per_visit : '-';
                    document.getElementById('perf-avg-monthly-visits').value = data.audit.avg_monthly_visits !== null ? data.audit.avg_monthly_visits.toLocaleString() : '-';
                    
                    const duration = data.audit.avg_visit_duration;
                    document.getElementById('perf-avg-visit-duration').value = duration !== null ? Math.floor(duration / 60) + 'm ' + (duration % 60) + 's' : '-';
                    
                    const breakdownVal = data.audit.breakdown_by_country || '';
                    const breakdownContainer = document.getElementById('share-breakdown-country-container');
                    if (isScreenshotPath(breakdownVal)) {
                        breakdownContainer.innerHTML = `<img src="${breakdownVal}" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--radius-sm); border: 1px solid var(--border-glass);">`;
                    } else {
                        breakdownContainer.innerHTML = `<textarea id="perf-breakdown-country" class="form-input" style="opacity:0.8; flex-grow: 1; min-height: 180px; resize: none; height: 100%;" readonly></textarea>`;
                        document.getElementById('perf-breakdown-country').value = breakdownVal || 'No country breakdown registered.';
                    }
                    
                    // Render Main Channels screenshot
                    const mainChannelsBox = document.getElementById('share-main-channels-box');
                    if (data.audit.main_channels) {
                        mainChannelsBox.innerHTML = `<img src="${data.audit.main_channels}" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">`;
                    } else {
                        mainChannelsBox.innerHTML = `<div style="min-height: 280px; display: flex; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); background: rgba(0,0,0,0.15); border-radius: var(--radius-sm); border: 1px dashed var(--border-glass); font-size: 0.85rem; padding: 24px;">No Main Channels screenshot uploaded.</div>`;
                    }

                    // Render Traffic Trends screenshot
                    const trafficTrendsBox = document.getElementById('share-traffic-trends-box');
                    if (data.audit.traffic_trends) {
                        trafficTrendsBox.innerHTML = `<img src="${data.audit.traffic_trends}" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">`;
                    } else {
                        trafficTrendsBox.innerHTML = `<div style="min-height: 280px; display: flex; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); background: rgba(0,0,0,0.15); border-radius: var(--radius-sm); border: 1px dashed var(--border-glass); font-size: 0.85rem; padding: 24px;">No Traffic Trends screenshot uploaded.</div>`;
                    }

                    document.getElementById('perf-global-ranking').value = data.audit.global_ranking !== null ? data.audit.global_ranking.toLocaleString() : '-';
                    document.getElementById('perf-country-ranking').value = data.audit.country_ranking !== null ? data.audit.country_ranking.toLocaleString() : '-';
                    document.getElementById('perf-target-country').value = data.audit.target_country || '-';

                    // Sitemap details
                    if (data.audit.sitemap_details) {
                        document.getElementById('sitemap-details-box').textContent = data.audit.sitemap_details;
                    } else {
                        document.getElementById('sitemap-details-box').textContent = 'No sitemap information registered.';
                    }

                    // Additional notes
                    if (data.audit.additional_notes) {
                        document.getElementById('additional-notes-box').textContent = data.audit.additional_notes;
                    } else {
                        document.getElementById('additional-notes-box').textContent = 'No additional notes registered.';
                    }

                    // Global report & strategy
                    if (data.audit.global_analysis) {
                        document.getElementById('share-global-analysis').textContent = data.audit.global_analysis;
                    } else {
                        document.getElementById('share-global-analysis').innerHTML = '<div style="color: var(--text-muted); font-style: italic;">No global analysis registered.</div>';
                    }
                    if (data.audit.global_strategy) {
                        document.getElementById('share-global-strategy').textContent = data.audit.global_strategy;
                    } else {
                        document.getElementById('share-global-strategy').innerHTML = '<div style="color: var(--text-muted); font-style: italic;">No recommendations or strategy registered.</div>';
                    }

                    activeAuditCountry = data.audit.target_country || "Website's Country";

                    // Save data variables
                    pagesData = data.pages;
                    searchTermsData = data.search_terms;
                    competitorsData = data.competitors;
                    competitorAnalysesData = data.competitor_analyses;

                    // Render tables
                    renderPages();
                    renderSearchTerms();
                    renderCompetitorAnalyses();

                    // Render Speed Metrics
                    collapsedAuditTech = (!data.core_web_vitals || (data.core_web_vitals.desktop_score === null && data.core_web_vitals.mobile_score === null));
                    updateAuditTechCollapseUI();

                    if (data.core_web_vitals) {
                        const cwt = data.core_web_vitals;
                        
                        // Desktop
                        document.getElementById('cwv-desktop-score').textContent = (cwt.desktop_score !== null && cwt.desktop_score !== undefined) ? cwt.desktop_score : '-';
                        document.getElementById('cwv-desktop-score').className = `cwv-score-circle ${getCwvScoreClass(cwt.desktop_score)}`;
                        
                        document.getElementById('cwv-desktop-accessibility').textContent = (cwt.desktop_accessibility !== null && cwt.desktop_accessibility !== undefined) ? cwt.desktop_accessibility : '-';
                        document.getElementById('cwv-desktop-accessibility').className = `cwv-score-circle ${getCwvScoreClass(cwt.desktop_accessibility)}`;
                        
                        document.getElementById('cwv-desktop-best-practices').textContent = (cwt.desktop_best_practices !== null && cwt.desktop_best_practices !== undefined) ? cwt.desktop_best_practices : '-';
                        document.getElementById('cwv-desktop-best-practices').className = `cwv-score-circle ${getCwvScoreClass(cwt.desktop_best_practices)}`;
                        
                        document.getElementById('cwv-desktop-seo').textContent = (cwt.desktop_seo !== null && cwt.desktop_seo !== undefined) ? cwt.desktop_seo : '-';
                        document.getElementById('cwv-desktop-seo').className = `cwv-score-circle ${getCwvScoreClass(cwt.desktop_seo)}`;
                        
                        document.getElementById('cwv-desktop-agentic-browsing').textContent = cwt.desktop_agentic_browsing || '-';
                        document.getElementById('cwv-desktop-agentic-browsing').className = `cwv-score-circle ${getAgenticScoreClass(cwt.desktop_agentic_browsing)}`;

                        document.getElementById('cwv-desktop-fcp').textContent = cwt.desktop_fcp || '-';
                        document.getElementById('cwv-desktop-lcp').textContent = cwt.desktop_lcp || '-';
                        document.getElementById('cwv-desktop-tbt').textContent = cwt.desktop_tbt || '-';
                        document.getElementById('cwv-desktop-cls').textContent = cwt.desktop_cls || '-';
                        document.getElementById('cwv-desktop-si').textContent = cwt.desktop_si || '-';

                        // Mobile
                        document.getElementById('cwv-mobile-score').textContent = (cwt.mobile_score !== null && cwt.mobile_score !== undefined) ? cwt.mobile_score : '-';
                        document.getElementById('cwv-mobile-score').className = `cwv-score-circle ${getCwvScoreClass(cwt.mobile_score)}`;
                        
                        document.getElementById('cwv-mobile-accessibility').textContent = (cwt.mobile_accessibility !== null && cwt.mobile_accessibility !== undefined) ? cwt.mobile_accessibility : '-';
                        document.getElementById('cwv-mobile-accessibility').className = `cwv-score-circle ${getCwvScoreClass(cwt.mobile_accessibility)}`;
                        
                        document.getElementById('cwv-mobile-best-practices').textContent = (cwt.mobile_best_practices !== null && cwt.mobile_best_practices !== undefined) ? cwt.mobile_best_practices : '-';
                        document.getElementById('cwv-mobile-best-practices').className = `cwv-score-circle ${getCwvScoreClass(cwt.mobile_best_practices)}`;
                        
                        document.getElementById('cwv-mobile-seo').textContent = (cwt.mobile_seo !== null && cwt.mobile_seo !== undefined) ? cwt.mobile_seo : '-';
                        document.getElementById('cwv-mobile-seo').className = `cwv-score-circle ${getCwvScoreClass(cwt.mobile_seo)}`;
                        
                        document.getElementById('cwv-mobile-agentic-browsing').textContent = cwt.mobile_agentic_browsing || '-';
                        document.getElementById('cwv-mobile-agentic-browsing').className = `cwv-score-circle ${getAgenticScoreClass(cwt.mobile_agentic_browsing)}`;

                        document.getElementById('cwv-mobile-fcp').textContent = cwt.mobile_fcp || '-';
                        document.getElementById('cwv-mobile-lcp').textContent = cwt.mobile_lcp || '-';
                        document.getElementById('cwv-mobile-tbt').textContent = cwt.mobile_tbt || '-';
                        document.getElementById('cwv-mobile-cls').textContent = cwt.mobile_cls || '-';
                        document.getElementById('cwv-mobile-si').textContent = cwt.mobile_si || '-';
                    } else {
                        document.getElementById('cwv-results').innerHTML = '<div style="grid-column: span 2; text-align:center; color:var(--text-muted); padding:30px;">Core Web Vitals scores not requested yet.</div>';
                    }

                    lucide.createIcons();
                });
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
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
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

        // Render SEO & Tech pages
        function renderPages() {
            const seoList = document.getElementById('seo-pages-list');
            const techList = document.getElementById('tech-pages-list');
            seoList.innerHTML = '';
            techList.innerHTML = '';

            if (pagesData.length === 0) {
                seoList.innerHTML = '<tr><td colspan="14" style="text-align:center; color:var(--text-muted);">No pages audited in this campaign.</td></tr>';
                techList.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No pages audited.</td></tr>';
                return;
            }

            pagesData.forEach(p => {
                const seoRow = document.createElement('tr');
                const titleLen = p.meta_title ? p.meta_title.length : 0;
                const descLen = p.meta_description ? p.meta_description.length : 0;
                const h1Len = p.h1 ? p.h1.length : 0;

                seoRow.innerHTML = `
                    <td style="white-space: nowrap;"><a href="${escapeHtml(p.url)}" target="_blank" class="url-link" title="${escapeHtml(p.url)}">${escapeHtml(getUrlDisplayName(p.url))}</a></td>
                    <td data-viewable="true" data-field="meta_title" data-value="${escapeHtml(p.meta_title || '')}" data-url="${escapeHtml(p.url)}">
                        <div class="text-truncate-cell" style="font-weight: 500;" title="${escapeHtml(p.meta_title || '')}">${escapeHtml(truncateCellText(p.meta_title))}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                            <span>${titleLen} chars</span>
                        </div>
                    </td>
                    <td data-viewable="true" data-field="meta_description" data-value="${escapeHtml(p.meta_description || '')}" data-url="${escapeHtml(p.url)}">
                        <div class="text-truncate-cell" style="font-size:0.85rem;" title="${escapeHtml(p.meta_description || '')}">${escapeHtml(truncateCellText(p.meta_description))}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                            <span>${descLen} chars</span>
                        </div>
                    </td>
                    <td data-viewable="true" data-field="h1" data-value="${escapeHtml(p.h1 || '')}" data-url="${escapeHtml(p.url)}">
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
                    <td><span style="font-weight:600; color:var(--secondary);">${p.internal_links}</span></td>
                    <td><span style="font-weight:600; color:var(--accent);">${p.external_links}</span></td>
                    <td>
                        <span class="badge ${p.missing_alt_images > 0 ? 'badge-warning' : 'badge-success'}">
                            ${p.missing_alt_images}
                        </span>
                    </td>
                    <td data-viewable="true" data-field="search_terms" data-value="${escapeHtml(p.search_terms || '')}" data-url="${escapeHtml(p.url)}">
                        <div class="text-truncate-cell" title="${escapeHtml(p.search_terms || '')}">${formatSearchTermsAsBullets(p.search_terms)}</div>
                    </td>
                    <td data-viewable="true" data-field="notes" data-value="${escapeHtml(p.notes || '')}" data-url="${escapeHtml(p.url)}">
                        <div class="text-truncate-cell" title="${escapeHtml(p.notes || '')}">${escapeHtml(p.notes || '')}</div>
                    </td>
                `;
                seoList.appendChild(seoRow);

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
                    <td style="white-space: nowrap;"><a href="${escapeHtml(p.url)}" target="_blank" class="url-link" title="${escapeHtml(p.url)}">${escapeHtml(getUrlDisplayName(p.url))}</a></td>
                    <td style="text-align: center;">
                        <span class="badge ${gscBadgeClass}">
                            ${gscText}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge ${errorsBadgeClass}">
                            ${errorsText}
                        </span>
                    </td>
                `;
                techList.appendChild(techRow);
            });
            lucide.createIcons();
        }

        // Render Search Terms & Competitors
        function renderSearchTerms() {
            const wrapper = document.getElementById('search-terms-wrapper');
            wrapper.innerHTML = '';

            if (searchTermsData.length === 0) {
                wrapper.innerHTML = '<div class="glass-panel" style="padding:40px; text-align:center; color:var(--text-muted);">No search terms mapped in this report.</div>';
                return;
            }

            searchTermsData.forEach(t => {
                const card = document.createElement('div');
                card.className = 'glass-panel';
                card.style.padding = '24px';

                const comps = competitorsData.filter(c => c.search_term_id === t.id);
                const organic = comps.filter(c => c.type === 'organic');
                const sponsored = comps.filter(c => c.type === 'sponsored');

                card.innerHTML = `
                    <div style="margin-bottom:20px; border-bottom:1px solid var(--border-glass); padding-bottom:12px;">
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">
                            <i data-lucide="key" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                            <span>${escapeHtml(t.term)}</span>
                        </h4>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
                        
                        <!-- Organic competitors -->
                        <div>
                            <div class="flex-space" style="margin-bottom:12px;">
                                <span style="font-size: 0.8rem; font-weight:600; text-transform:uppercase; color:var(--text-secondary);">Organic Competitors</span>
                                <span style="font-size: 0.8rem; color:var(--text-muted);">${organic.length} / 5</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:8px;" class="organic-list">
                                ${organic.length === 0 ? '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">No organic competitors entered.</div>' : ''}
                            </div>
                        </div>

                        <!-- Sponsored competitors -->
                        <div>
                            <div class="flex-space" style="margin-bottom:12px;">
                                <span style="font-size: 0.8rem; font-weight:600; text-transform:uppercase; color:var(--text-secondary);">Sponsored Competitors</span>
                                <span style="font-size: 0.8rem; color:var(--text-muted);">${sponsored.length} / 3</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:8px;" class="sponsored-list">
                                ${sponsored.length === 0 ? '<div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">No sponsored competitors.</div>' : ''}
                            </div>
                        </div>

                    </div>
                `;

                const orgList = card.querySelector('.organic-list');
                organic.forEach(o => {
                    const el = document.createElement('div');
                    el.className = 'glass-card';
                    el.style.padding = '12px 16px';
                    el.innerHTML = `
                        <div class="flex-space">
                            <a href="${escapeHtml(o.url)}" target="_blank" class="url-link" style="font-size:0.85rem; font-weight:500;">${escapeHtml(o.url)}</a>
                        </div>
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; margin-top:8px; font-size:0.75rem; text-align:center; color:var(--text-secondary);">
                            <div>Bounce: <b>${o.bounce_rate !== null ? o.bounce_rate + '%' : '-'}</b></div>
                            <div>Pages/V: <b>${o.pages_per_visit !== null ? o.pages_per_visit : '-'}</b></div>
                            <div>Visits: <b>${o.avg_monthly_visits !== null ? o.avg_monthly_visits.toLocaleString() : '-'}</b></div>
                            <div>Dur: <b>${o.avg_visit_duration !== null ? o.avg_visit_duration + 's' : '-'}</b></div>
                        </div>
                    `;
                    orgList.appendChild(el);
                });

                const spList = card.querySelector('.sponsored-list');
                sponsored.forEach(s => {
                    const el = document.createElement('div');
                    el.className = 'glass-card';
                    el.style.padding = '10px 14px';
                    el.innerHTML = `
                        <a href="${escapeHtml(s.url)}" target="_blank" class="url-link" style="font-size:0.85rem; font-weight:500;">${escapeHtml(s.url)}</a>
                    `;
                    spList.appendChild(el);
                });

                wrapper.appendChild(card);
            });
            lucide.createIcons();
        }

        function renderCompetitorAnalyses() {
            const list = document.getElementById('competitor-analysis-cards');
            if (!list) return;
            list.innerHTML = '';

            if (competitorAnalysesData.length === 0) {
                list.innerHTML = '<div class="glass-panel" style="text-align:center; color:var(--text-muted); padding: 40px;">No competitors analyzed in this report.</div>';
                return;
            }

            // Sort competitorAnalysesData by domain name (full domain name including TLD)
            competitorAnalysesData.sort((a, b) => {
                const domainA = getDomainFromUrl(a.url).toLowerCase();
                const domainB = getDomainFromUrl(b.url).toLowerCase();
                return domainA.localeCompare(domainB);
            });

            competitorAnalysesData.forEach(c => {
                const card = document.createElement('div');
                card.className = 'glass-panel';
                card.style.padding = '30px';
                card.style.marginBottom = '24px';

                const titleLen = c.meta_title ? c.meta_title.length : 0;
                const descLen = c.meta_description ? c.meta_description.length : 0;
                const h1Len = c.h1 ? c.h1.length : 0;

                // Format average duration
                let formattedDuration = '-';
                if (c.avg_visit_duration !== null && c.avg_visit_duration !== undefined) {
                    const min = Math.floor(c.avg_visit_duration / 60);
                    const sec = c.avg_visit_duration % 60;
                    formattedDuration = min > 0 ? `${min}m ${sec}s` : `${sec}s`;
                }

                // Render CWV Content
                let cwvHTML = `
                    <div style="text-align:center; padding: 40px 0; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
                        <i data-lucide="gauge" style="width: 32px; height: 32px; margin-bottom: 8px; opacity:0.5;"></i>
                        <span style="font-size:0.85rem;">No Speed Scores pulled yet.</span>
                    </div>
                `;

                if (c.desktop_score !== null || c.mobile_score !== null) {
                    const getTooltipHTML = (label) => {
                        const l = label.toLowerCase();
                        if (l === 'perf' || l === 'performance') {
                            return `
                                <div class="cwv-tooltip">
                                    <div class="cwv-tooltip-title">Performance</div>
                                    <div class="cwv-tooltip-text">Measures page load speed, responsiveness, and visual stability.</div>
                                </div>
                            `;
                        } else if (l === 'a11y' || l === 'accessibility') {
                            return `
                                <div class="cwv-tooltip">
                                    <div class="cwv-tooltip-title">Accessibility</div>
                                    <div class="cwv-tooltip-text">Measures how easy the website is to use for people with disabilities.</div>
                                </div>
                            `;
                        } else if (l === 'best' || l === 'best practices' || l === 'best_practices') {
                            return `
                                <div class="cwv-tooltip">
                                    <div class="cwv-tooltip-title">Best Practices</div>
                                    <div class="cwv-tooltip-text">Checks if the website follows web standards and security best practices.</div>
                                </div>
                            `;
                        } else if (l === 'seo') {
                            return `
                                <div class="cwv-tooltip">
                                    <div class="cwv-tooltip-title">Search Engine Optimization</div>
                                    <div class="cwv-tooltip-text">Checks how well search engines can crawl, index, and understand the page.</div>
                                </div>
                            `;
                        } else if (l === 'agentic' || l === 'agentic browsing') {
                            return `
                                <div class="cwv-tooltip">
                                    <div class="cwv-tooltip-title">Agentic Browsing</div>
                                    <div class="cwv-tooltip-text">Measures suitability for AI agents: checks visual stability (CLS &le; 0.1), Accessibility (&ge; 80), and SEO (&ge; 90).</div>
                                </div>
                            `;
                        }
                        return '';
                    };

                    const renderCircle = (score, label) => {
                        const scoreVal = score !== null ? score : '-';
                        const scoreClass = getCwvScoreClass(score);
                        return `
                            <div class="cwv-score-card">
                                <div class="cwv-score-circle ${scoreClass}" style="width:36px; height:36px; line-height:34px; font-size:0.8rem; border-width:2px; font-weight:700; margin-bottom:4px;">${scoreVal}</div>
                                <div class="cwv-score-label" style="font-size:0.65rem; text-transform:uppercase;">${label}</div>
                                ${getTooltipHTML(label)}
                            </div>
                        `;
                    };

                    const renderAgenticCircle = (scoreStr, label) => {
                        const scoreVal = scoreStr || '-';
                        const scoreClass = getAgenticScoreClass(scoreStr);
                        return `
                            <div class="cwv-score-card">
                                <div class="cwv-score-circle ${scoreClass}" style="width:36px; height:36px; line-height:34px; font-size:0.8rem; border-width:2px; font-weight:700; margin-bottom:4px;">${scoreVal}</div>
                                <div class="cwv-score-label" style="font-size:0.65rem; text-transform:uppercase;">${label}</div>
                                ${getTooltipHTML(label)}
                            </div>
                        `;
                    };

                    cwvHTML = `
                        <div style="display:flex; flex-direction:column; gap:20px; height:100%;">
                            <!-- Desktop Strategy -->
                            <div class="glass-card" style="padding:16px;">
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:6px;">
                                    <i data-lucide="monitor" style="width:14px; height:14px; color:var(--secondary);"></i>
                                    <span style="font-size:0.8rem; font-weight:600;">Desktop Strategy</span>
                                </div>
                                <div class="cwv-scores-row" style="gap:6px; margin-bottom:12px;">
                                    ${renderCircle(c.desktop_score, 'Perf')}
                                    ${renderCircle(c.desktop_accessibility, 'A11y')}
                                    ${renderCircle(c.desktop_best_practices, 'Best')}
                                    ${renderCircle(c.desktop_seo, 'SEO')}
                                    ${renderAgenticCircle(c.desktop_agentic_browsing, 'Agentic')}
                                </div>
                                <div class="cwv-metrics-list" style="grid-template-columns: repeat(2, 1fr); gap:8px; font-size:0.75rem;">
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">FCP</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.desktop_fcp || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">LCP</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.desktop_lcp || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">TBT</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.desktop_tbt || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">CLS</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.desktop_cls || '-'}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Strategy -->
                            <div class="glass-card" style="padding:16px;">
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:6px;">
                                    <i data-lucide="smartphone" style="width:14px; height:14px; color:var(--accent);"></i>
                                    <span style="font-size:0.8rem; font-weight:600;">Mobile Strategy</span>
                                </div>
                                <div class="cwv-scores-row" style="gap:6px; margin-bottom:12px;">
                                    ${renderCircle(c.mobile_score, 'Perf')}
                                    ${renderCircle(c.mobile_accessibility, 'A11y')}
                                    ${renderCircle(c.mobile_best_practices, 'Best')}
                                    ${renderCircle(c.mobile_seo, 'SEO')}
                                    ${renderAgenticCircle(c.mobile_agentic_browsing, 'Agentic')}
                                </div>
                                <div class="cwv-metrics-list" style="grid-template-columns: repeat(2, 1fr); gap:8px; font-size:0.75rem;">
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">FCP</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.mobile_fcp || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">LCP</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.mobile_lcp || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">TBT</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.mobile_tbt || '-'}</div>
                                    </div>
                                    <div class="cwv-metric-item" style="padding:4px 8px;">
                                        <div class="cwv-metric-lbl">CLS</div>
                                        <div class="cwv-metric-val" style="font-size:0.8rem; font-weight:600;">${c.mobile_cls || '-'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                card.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-glass); padding-bottom:14px; margin-bottom:20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i data-lucide="swords" style="width:20px; height:20px; color:var(--primary);"></i>
                            <span style="font-size:1.15rem; font-weight:700; letter-spacing: -0.02em;">${escapeHtml(getDomainFromUrl(c.url))}</span>
                            <a href="${escapeHtml(c.url)}" target="_blank" class="url-link" style="font-size:0.8rem; margin-top:2px;">(${escapeHtml(c.url)})</a>
                        </div>
                        <div class="badge badge-primary" style="font-size:0.75rem; padding: 6px 12px; font-weight:600;">
                            Keywords: ${escapeHtml(c.search_terms || '-')}
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 340px 520px; gap: 24px; align-items: stretch;">
                        
                        <!-- SEO State Column -->
                        <div style="display:flex; flex-direction:column; gap:16px; border-right:1px solid var(--border-glass); padding-right:24px;">
                            <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
                                <i data-lucide="search-code" style="width:14px; height:14px;"></i>
                                <span>SEO State Overview</span>
                            </h4>

                            <div>
                                <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Meta Title</div>
                                <div style="font-size:0.85rem; font-weight:500; line-height:1.4;">${escapeHtml(c.meta_title || 'N/A')}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">${titleLen} characters</div>
                            </div>

                            <div>
                                <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Meta Description</div>
                                <div style="font-size:0.85rem; line-height:1.4; color:var(--text-secondary);">${escapeHtml(c.meta_description || 'N/A')}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">${descLen} characters</div>
                            </div>

                            <div>
                                <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">H1 Tag</div>
                                <div style="font-size:0.85rem; font-weight:500; color:var(--text-primary);">${escapeHtml(c.h1 || 'N/A')}</div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">${h1Len} characters</div>
                            </div>

                            <div data-viewable="true" data-field="search_terms" data-value="${escapeHtml(c.search_terms || '')}" data-url="${escapeHtml(c.url)}" style="cursor: pointer;">
                                <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Keywords / Search Terms</div>
                                <div class="text-truncate-cell" style="font-size:0.85rem; line-height:1.4; color:var(--text-secondary);">${formatSearchTermsAsBullets(c.search_terms)}</div>
                            </div>

                            <div data-viewable="true" data-field="notes" data-value="${escapeHtml(c.notes || '')}" data-url="${escapeHtml(c.url)}" style="cursor: pointer;">
                                <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Notes</div>
                                <div class="text-truncate-cell" style="font-size:0.85rem; line-height:1.4; color:var(--text-secondary);">${escapeHtml(c.notes || '-')}</div>
                            </div>

                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-top:4px;">
                                <div class="glass-card" style="padding:10px; text-align:center;">
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Internal Links</div>
                                    <div style="font-size:1.1rem; font-weight:700; color:var(--secondary); margin-top:2px;">${c.internal_links}</div>
                                </div>
                                <div class="glass-card" style="padding:10px; text-align:center;">
                                    <div style="font-size:0.75rem; color:var(--text-muted);">External Links</div>
                                    <div style="font-size:1.1rem; font-weight:700; color:var(--accent); margin-top:2px;">${c.external_links}</div>
                                </div>
                                <div class="glass-card" style="padding:10px; text-align:center;">
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Missing Alt</div>
                                    <div style="font-size:1.1rem; font-weight:700; color:var(--warning); margin-top:2px;">${c.missing_alt_images}</div>
                                </div>
                            </div>

                            <div style="display:flex; gap:10px; margin-top:6px;">
                                <button class="btn btn-secondary btn-sm" onclick="viewHeadersStructure(${c.id}, 'competitor')" style="flex:1; font-size:0.75rem;">
                                    <i data-lucide="list-tree" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                                    <span>Headers (${c.h1_count + c.h2_count + c.h3_count + c.h4_count + c.h5_count + c.h6_count} tags)</span>
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="viewAudience(${c.id}, 'competitor')" style="flex:1; font-size:0.75rem;">
                                    <i data-lucide="globe" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                                    <span>Audience (${getAudienceCount(c.audience_country_proportion)})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Technical State Column (CWV Scores) -->
                        <div style="display:flex; flex-direction:column; gap:16px; border-right:1px solid var(--border-glass); padding-right:24px;">
                            <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
                                <i data-lucide="gauge" style="width:14px; height:14px;"></i>
                                <span>Core Web Vitals & Speed</span>
                            </h4>
                            <div style="flex-grow:1;">
                                ${cwvHTML}
                            </div>
                        </div>

                        <!-- Traffic & Performance Column -->
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
                                <i data-lucide="bar-chart-2" style="width:14px; height:14px;"></i>
                                <span>Traffic & Audience</span>
                            </h4>

                            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; flex-grow: 1;">
                                <!-- Left Column: Metrics -->
                                <div style="display:flex; flex-direction:column; gap:12px;">
                                    <!-- Row 1: Bounce Rate & Pages Per Visit -->
                                    <div style="display: flex; gap: 12px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Bounce Rate (%)</label>
                                            <input type="text" class="form-input" style="opacity:0.8;" value="${c.bounce_rate !== null ? c.bounce_rate + '%' : '-'}" readonly>
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Pages per Visit</label>
                                            <input type="text" class="form-input" style="opacity:0.8;" value="${c.pages_per_visit !== null ? c.pages_per_visit : '-'}" readonly>
                                        </div>
                                    </div>

                                    <!-- Row 2: Average Monthly Visits & Average Visit Duration -->
                                    <div style="display: flex; gap: 12px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Average Monthly Visits</label>
                                            <input type="text" class="form-input" style="opacity:0.8;" value="${c.avg_monthly_visits !== null ? c.avg_monthly_visits.toLocaleString() : '-'}" readonly>
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Average Visit Duration</label>
                                            <input type="text" class="form-input" style="opacity:0.8;" value="${formattedDuration}" readonly>
                                        </div>
                                    </div>

                                    <!-- Row 3: Global Rank & Country Rank -->
                                    <div style="display: flex; gap: 12px;">
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Global Rank</label>
                                            <input type="text" class="form-input" style="opacity:0.8;" value="${c.global_ranking !== null ? c.global_ranking.toLocaleString() : '-'}" readonly>
                                        </div>
                                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                            <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Country Rank</label>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <input type="text" class="form-input" style="opacity:0.8; flex: 1;" value="${c.country_ranking !== null ? c.country_ranking.toLocaleString() : '-'}" readonly>
                                                <span style="font-size: 0.85rem; color: var(--text-secondary);">in</span>
                                                <input type="text" class="form-input" style="opacity:0.8; flex: 1.5;" value="${escapeHtml(activeAuditCountry)}" readonly disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Country Breakdown -->
                                <div style="display: flex; flex-direction: column; height: 100%;">
                                    <div class="form-group" style="height: 100%; display: flex; flex-direction: column; margin-bottom: 0;">
                                        <label style="font-size: 0.75rem; margin-bottom: 4px; color: var(--text-secondary);">Country Breakdown</label>
                                        <div style="flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
                                            ${isScreenshotPath(c.breakdown_by_country) ? `
                                                <div style="background:rgba(255,255,255,0.02); padding:8px; border-radius:6px; border:1px solid var(--border-glass); flex-grow:1; display:flex; align-items:center; justify-content:center; min-height:180px;">
                                                    <img src="${escapeHtml(c.breakdown_by_country)}" style="width:100%; max-height:400px; object-fit:contain; border-radius:4px;">
                                                </div>
                                            ` : `
                                                <textarea class="form-input" style="opacity:0.8; flex-grow: 1; min-height: 180px; resize: none; height: 100%;" readonly>${escapeHtml(c.breakdown_by_country || 'No country breakdown provided.')}</textarea>
                                            `}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                `;

                list.appendChild(card);
            });
            lucide.createIcons();
        }

        function isScreenshotPath(str) {
            if (!str) return false;
            return str.startsWith('uploads/') || /\.(png|jpe?g|gif|webp|svg)$/i.test(str);
        }

        // Modal tree views
        function viewHeadersStructure(id, type) {
            let record = null;
            if (type === 'page') {
                record = pagesData.find(p => p.id === id);
            } else {
                record = competitorAnalysesData.find(c => c.id === id);
            }
            if (!record) return;

            // Populate static heading counts
            document.getElementById('share-h1-count').textContent = record.h1_count || 0;
            document.getElementById('share-h2-count').textContent = record.h2_count || 0;
            document.getElementById('share-h3-count').textContent = record.h3_count || 0;
            document.getElementById('share-h4-count').textContent = record.h4_count || 0;
            document.getElementById('share-h5-count').textContent = record.h5_count || 0;
            document.getElementById('share-h6-count').textContent = record.h6_count || 0;

            const treeContainer = document.getElementById('headers-modal-tree');
            treeContainer.innerHTML = '';

            const structureStr = record.headers_structure || '';
            const screenshotStr = record.headers_screenshot || '';

            if (!structureStr && !screenshotStr) {
                treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center;">No heading elements (H1-H6) found in HTML document.</div>';
            } else {
                if (screenshotStr) {
                    const div = document.createElement('div');
                    div.style.textAlign = 'center';
                    div.style.marginBottom = '20px';
                    div.innerHTML = `
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600; text-align: left;">Page Screenshot Structure</div>
                        <img src="${escapeHtml(screenshotStr)}" style="max-width: 100%; border-radius: var(--radius-sm); border: 1px solid var(--border-glass);" />
                    `;
                    treeContainer.appendChild(div);
                }

                if (structureStr) {
                    const textHeaderDiv = document.createElement('div');
                    textHeaderDiv.style.marginTop = screenshotStr ? '24px' : '0';
                    if (screenshotStr) {
                        textHeaderDiv.innerHTML = `<div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px; font-weight: 600; border-top: 1px solid var(--border-glass); padding-top: 16px;">Text Structure</div>`;
                    }
                    treeContainer.appendChild(textHeaderDiv);

                    // Try parsing JSON structure to convert to human readable format or plain text
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
                            if (!screenshotStr) {
                                treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center;">No heading elements (H1-H6) found in HTML document.</div>';
                            }
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

                        if (!hasContent && !screenshotStr) {
                            treeContainer.innerHTML = '<div style="color:var(--text-muted); text-align:center;">No heading elements (H1-H6) found in HTML document.</div>';
                        }
                    }
                }
            }

            openModal('headers-modal');
        }

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

        function viewAudience(id, type) {
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

            const container = document.getElementById('audience-view-rows');
            container.innerHTML = '';

            if (parsed.length === 0) {
                container.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding: 20px 0;">No audience location data registered.</div>';
            } else {
                parsed.forEach(item => {
                    const countryTrimmed = item.country.trim();
                    const flagCode = countryNameToCode[countryTrimmed.toLowerCase()] || '';
                    const flagSrc = flagCode ? `https://flagcdn.com/w40/${flagCode}.png` : '';
                    const percent = item.percent !== null && item.percent !== undefined && item.percent !== '' ? parseFloat(item.percent) : 0;
                    
                    const row = document.createElement('div');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.gap = '16px';
                    row.style.padding = '8px 0';
                    
                    row.innerHTML = `
                        <div style="width: 32px; display: flex; align-items: center; justify-content: center;">
                            ${flagCode ? `<img src="${flagSrc}" style="width: 24px; height: 18px; border-radius: 2px; border: 1px solid rgba(255,255,255,0.1);" title="${escapeHtml(countryTrimmed)}">` : `<i data-lucide="globe" style="width: 18px; height: 18px; color: var(--text-muted);"></i>`}
                        </div>
                        <div style="flex: 1;">
                            <div class="flex-space" style="margin-bottom: 6px; font-size: 0.9rem;">
                                <span style="font-weight: 500;">${escapeHtml(countryTrimmed)}</span>
                                <span style="font-weight: 600; color: var(--secondary);">${percent}%</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; border: 1px solid var(--border-glass);">
                                <div style="width: ${percent}%; height: 100%; background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 4px;"></div>
                            </div>
                        </div>
                    `;
                    container.appendChild(row);
                });
            }

            openModal('audience-modal');
            lucide.createIcons();
        }

        function showFullText(e, url, field, text) {
            if (e) e.stopPropagation();
            document.getElementById('text-viewer-field-name').textContent = field;
            document.getElementById('text-viewer-url').textContent = 'URL: ' + url;
            
            const textarea = document.getElementById('text-viewer-content');
            textarea.value = text;
            document.getElementById('text-viewer-char-count').textContent = text.length + ' characters';

            const hint = document.getElementById('text-viewer-hint');
            if (field === 'Search Terms') {
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

        // Double click listener for viewing Meta Title, Meta Description, H1 fully
        document.addEventListener('dblclick', function(e) {
            const cell = e.target.closest('[data-viewable="true"]');
            if (!cell) return;
            
            const field = cell.getAttribute('data-field');
            const url = cell.getAttribute('data-url');
            const value = cell.getAttribute('data-value') || '';
            
            let fieldLabel = 'Field Value';
            if (field === 'meta_title') fieldLabel = 'Meta Title';
            else if (field === 'meta_description') fieldLabel = 'Meta Description';
            else if (field === 'h1') fieldLabel = 'H1';
            else if (field === 'search_terms') fieldLabel = 'Search Terms';
            else if (field === 'notes') fieldLabel = 'Notes';
            
            showFullText(null, url, fieldLabel, value);
        });

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
    </script>
</body>
</html>
