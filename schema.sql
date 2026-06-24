CREATE DATABASE IF NOT EXISTS `seo_audit_tool` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `seo_audit_tool`;

-- Users table (admin accounts)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Clients table
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `homepage_url` VARCHAR(255) NOT NULL,
  `industry` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Audits table
CREATE TABLE IF NOT EXISTS `audits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `share_token` VARCHAR(64) UNIQUE NOT NULL,
  `bounce_rate` DECIMAL(5,2) DEFAULT NULL,
  `pages_per_visit` DECIMAL(4,2) DEFAULT NULL,
  `avg_monthly_visits` INT DEFAULT NULL,
  `avg_visit_duration` INT DEFAULT NULL,
  `breakdown_by_country` TEXT DEFAULT NULL,
  `main_channels` TEXT DEFAULT NULL,
  `traffic_trends` TEXT DEFAULT NULL,
  `sitemap_details` TEXT DEFAULT NULL,
  `additional_notes` TEXT DEFAULT NULL,
  `global_analysis` TEXT DEFAULT NULL,
  `global_strategy` TEXT DEFAULT NULL,
  `global_ranking` INT DEFAULT NULL,
  `country_ranking` INT DEFAULT NULL,
  `target_country` VARCHAR(100) DEFAULT 'Website\'s Country',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Audit Pages table (both SEO and Technical states)
CREATE TABLE IF NOT EXISTS `audit_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `audit_id` INT NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `meta_title` VARCHAR(500) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `h1` VARCHAR(500) DEFAULT NULL,
  `h1_count` INT DEFAULT 0,
  `h2_count` INT DEFAULT 0,
  `h3_count` INT DEFAULT 0,
  `h4_count` INT DEFAULT 0,
  `h5_count` INT DEFAULT 0,
  `h6_count` INT DEFAULT 0,
  `headers_structure` TEXT DEFAULT NULL, -- JSON formatted array
  `headers_screenshot` VARCHAR(500) DEFAULT NULL,
  `internal_links` INT DEFAULT 0,
  `external_links` INT DEFAULT 0,
  `missing_alt_images` INT DEFAULT 0,
  `monthly_visits` INT DEFAULT NULL,
  `avg_time_per_visit` INT DEFAULT NULL,
  `audience_country_proportion` VARCHAR(255) DEFAULT NULL,
  `global_ranking` INT DEFAULT NULL,
  `country_ranking` INT DEFAULT NULL,
  `search_terms` VARCHAR(1000) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `indexing_gsc` VARCHAR(10) DEFAULT NULL,
  `crawl_errors` VARCHAR(10) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Search terms table
CREATE TABLE IF NOT EXISTS `search_terms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `audit_id` INT NOT NULL,
  `term` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Competitors table under search terms
CREATE TABLE IF NOT EXISTS `competitors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `search_term_id` INT DEFAULT NULL,
  `audit_id` INT NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `type` VARCHAR(20) NOT NULL, -- 'organic' or 'sponsored'
  `bounce_rate` DECIMAL(5,2) DEFAULT NULL,
  `pages_per_visit` DECIMAL(4,2) DEFAULT NULL,
  `avg_monthly_visits` INT DEFAULT NULL,
  `avg_visit_duration` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`search_term_id`) REFERENCES `search_terms` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Competitor Analyses table
CREATE TABLE IF NOT EXISTS `competitor_analyses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `audit_id` INT NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `meta_title` VARCHAR(500) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `h1` VARCHAR(500) DEFAULT NULL,
  `h1_count` INT DEFAULT 0,
  `h2_count` INT DEFAULT 0,
  `h3_count` INT DEFAULT 0,
  `h4_count` INT DEFAULT 0,
  `h5_count` INT DEFAULT 0,
  `h6_count` INT DEFAULT 0,
  `headers_structure` TEXT DEFAULT NULL,
  `headers_screenshot` VARCHAR(500) DEFAULT NULL,
  `internal_links` INT DEFAULT 0,
  `external_links` INT DEFAULT 0,
  `missing_alt_images` INT DEFAULT 0,
  `monthly_visits` INT DEFAULT NULL,
  `avg_time_per_visit` INT DEFAULT NULL,
  `audience_country_proportion` VARCHAR(255) DEFAULT NULL,
  `global_ranking` INT DEFAULT NULL,
  `country_ranking` INT DEFAULT NULL,
  `search_terms` VARCHAR(1000) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `desktop_score` INT DEFAULT NULL,
  `desktop_fcp` VARCHAR(50) DEFAULT NULL,
  `desktop_lcp` VARCHAR(50) DEFAULT NULL,
  `desktop_tbt` VARCHAR(50) DEFAULT NULL,
  `desktop_cls` VARCHAR(50) DEFAULT NULL,
  `desktop_si` VARCHAR(50) DEFAULT NULL,
  `desktop_accessibility` INT DEFAULT NULL,
  `desktop_best_practices` INT DEFAULT NULL,
  `desktop_seo` INT DEFAULT NULL,
  `desktop_agentic_browsing` VARCHAR(20) DEFAULT NULL,
  `mobile_score` INT DEFAULT NULL,
  `mobile_fcp` VARCHAR(50) DEFAULT NULL,
  `mobile_lcp` VARCHAR(50) DEFAULT NULL,
  `mobile_tbt` VARCHAR(50) DEFAULT NULL,
  `mobile_cls` VARCHAR(50) DEFAULT NULL,
  `mobile_si` VARCHAR(50) DEFAULT NULL,
  `mobile_accessibility` INT DEFAULT NULL,
  `mobile_best_practices` INT DEFAULT NULL,
  `mobile_seo` INT DEFAULT NULL,
  `mobile_agentic_browsing` VARCHAR(20) DEFAULT NULL,
  `bounce_rate` DECIMAL(5,2) DEFAULT NULL,
  `pages_per_visit` DECIMAL(4,2) DEFAULT NULL,
  `avg_monthly_visits` INT DEFAULT NULL,
  `avg_visit_duration` INT DEFAULT NULL,
  `breakdown_by_country` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Core Web Vitals cache
CREATE TABLE IF NOT EXISTS `core_web_vitals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `audit_id` INT NOT NULL UNIQUE,
  `desktop_score` INT DEFAULT NULL,
  `desktop_fcp` VARCHAR(50) DEFAULT NULL,
  `desktop_lcp` VARCHAR(50) DEFAULT NULL,
  `desktop_tbt` VARCHAR(50) DEFAULT NULL,
  `desktop_cls` VARCHAR(50) DEFAULT NULL,
  `desktop_si` VARCHAR(50) DEFAULT NULL,
  `desktop_accessibility` INT DEFAULT NULL,
  `desktop_best_practices` INT DEFAULT NULL,
  `desktop_seo` INT DEFAULT NULL,
  `desktop_agentic_browsing` VARCHAR(20) DEFAULT NULL,
  `mobile_score` INT DEFAULT NULL,
  `mobile_fcp` VARCHAR(50) DEFAULT NULL,
  `mobile_lcp` VARCHAR(50) DEFAULT NULL,
  `mobile_tbt` VARCHAR(50) DEFAULT NULL,
  `mobile_cls` VARCHAR(50) DEFAULT NULL,
  `mobile_si` VARCHAR(50) DEFAULT NULL,
  `mobile_accessibility` INT DEFAULT NULL,
  `mobile_best_practices` INT DEFAULT NULL,
  `mobile_seo` INT DEFAULT NULL,
  `mobile_agentic_browsing` VARCHAR(20) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed default admin account (username: admin, password: admin123)
-- password_hash for 'admin123' using PASSWORD_DEFAULT: $2y$10$DOknoMxZZOXpjrF8VbSMTOBvCs7ysu9UknOzToc7DdWX7DKCD63Zi
INSERT INTO `users` (`id`, `username`, `password_hash`)
VALUES (1, 'admin', '$2y$10$DOknoMxZZOXpjrF8VbSMTOBvCs7ysu9UknOzToc7DdWX7DKCD63Zi')
ON DUPLICATE KEY UPDATE `username`='admin';
