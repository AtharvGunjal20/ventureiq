-- ============================================================
--  VentureIQ Database Schema
--  Engine: MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS ventureiq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ventureiq;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36)        NOT NULL UNIQUE DEFAULT (UUID()),
    first_name      VARCHAR(60)     NOT NULL,
    last_name       VARCHAR(60)     NOT NULL,
    email           VARCHAR(180)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    is_active       TINYINT(1)      NOT NULL DEFAULT 0,
    is_verified     TINYINT(1)      NOT NULL DEFAULT 0,
    verify_token    VARCHAR(120)    NULL,
    reset_token     VARCHAR(120)    NULL,
    reset_expires   DATETIME        NULL,
    avatar_url      VARCHAR(255)    NULL,
    last_login      DATETIME        NULL,
    login_attempts  TINYINT         NOT NULL DEFAULT 0,
    locked_until    DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verify_token (verify_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB;

-- ─── SESSIONS ────────────────────────────────────────────────
CREATE TABLE user_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    session_token   VARCHAR(128)    NOT NULL UNIQUE,
    ip_address      VARCHAR(45)     NOT NULL,
    user_agent      VARCHAR(500)    NOT NULL,
    expires_at      DATETIME        NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ─── BUSINESS MODELS ─────────────────────────────────────────
CREATE TABLE business_models (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug            VARCHAR(60)     NOT NULL UNIQUE,
    name            VARCHAR(100)    NOT NULL,
    emoji           VARCHAR(10)     NOT NULL DEFAULT '🚀',
    description     TEXT            NOT NULL,
    difficulty      ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    investment_min  INT             NOT NULL DEFAULT 0,
    investment_max  INT             NOT NULL DEFAULT 10000,
    roi_label       VARCHAR(50)     NOT NULL DEFAULT 'Medium',
    risk_level      ENUM('Very Low','Low','Medium','High','Very High') NOT NULL DEFAULT 'Medium',
    time_to_profit  VARCHAR(50)     NOT NULL,
    tech_level      VARCHAR(50)     NOT NULL,
    scalability     VARCHAR(50)     NOT NULL,
    tags            JSON            NULL,
    roadmap_steps   JSON            NULL,
    why_template    TEXT            NULL,
    score_capital   TINYINT         NOT NULL DEFAULT 3,
    score_risk      TINYINT         NOT NULL DEFAULT 3,
    score_tech      TINYINT         NOT NULL DEFAULT 3,
    score_time      TINYINT         NOT NULL DEFAULT 3,
    score_passive   TINYINT         NOT NULL DEFAULT 3,
    score_creative  TINYINT         NOT NULL DEFAULT 3,
    score_team      TINYINT         NOT NULL DEFAULT 3,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── QUIZ RESULTS ─────────────────────────────────────────────
CREATE TABLE quiz_results (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36)        NOT NULL UNIQUE DEFAULT (UUID()),
    user_id         INT UNSIGNED    NULL,
    guest_token     VARCHAR(64)     NULL,
    answers         JSON            NOT NULL,
    profile_scores  JSON            NOT NULL,
    top_model_id    INT UNSIGNED    NOT NULL,
    confidence_pct  TINYINT         NOT NULL DEFAULT 0,
    all_scores      JSON            NOT NULL,
    ip_address      VARCHAR(45)     NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (top_model_id) REFERENCES business_models(id),
    INDEX idx_user (user_id),
    INDEX idx_uuid (uuid),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ─── AUDIT LOG ────────────────────────────────────────────────
CREATE TABLE audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NULL,
    action      VARCHAR(100)    NOT NULL,
    target_type VARCHAR(60)     NULL,
    target_id   INT UNSIGNED    NULL,
    detail      TEXT            NULL,
    ip_address  VARCHAR(45)     NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id),
    INDEX idx_action (action),
    INDEX idx_created(created_at)
) ENGINE=InnoDB;

-- ─── RATE LIMIT ───────────────────────────────────────────────
CREATE TABLE rate_limit (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45)     NOT NULL,
    action      VARCHAR(60)     NOT NULL DEFAULT 'login',
    attempts    SMALLINT        NOT NULL DEFAULT 1,
    window_start DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ip_action (ip_address, action),
    INDEX idx_window (window_start)
) ENGINE=InnoDB;

-- ─── SEED: Default Admin ──────────────────────────────────────
-- Password: Admin@123  (bcrypt, cost 12)
INSERT INTO users (first_name, last_name, email, password_hash, role, is_active, is_verified)
VALUES ('VentureIQ', 'Admin', 'admin@ventureiq.com',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@123
        'admin', 1, 1);

-- ─── SEED: Business Models ────────────────────────────────────
INSERT INTO business_models
(slug,name,emoji,description,difficulty,investment_min,investment_max,roi_label,risk_level,time_to_profit,tech_level,scalability,tags,roadmap_steps,why_template,score_capital,score_risk,score_tech,score_time,score_passive,score_creative,score_team,sort_order)
VALUES
('dropshipping','Dropshipping','📦','Sell products online without holding inventory. Suppliers ship directly to your customers — zero warehouse needed.',
 'easy',200,2000,'Low–Medium','Low','1–3 months','Low','Medium',
 '["Low startup cost","No inventory","Supplier dependent","Ads-driven"]',
 '["Register LLC & choose a winning niche","Set up Shopify store and do product research","Connect AliExpress or CJ Dropshipping supplier","Launch paid ads (Facebook/TikTok) — start at $300","Optimize ads to 2.5x ROAS or better","Scale ad spend and introduce upsells & bundles"]',
 'Dropshipping is ideal for lean starters with low capital. You matched low inventory risk preference and a quick launch timeline.',
 1,1,1,2,3,2,1,1),

('subscription','Subscription Box','📬','Curated physical products delivered monthly. Build recurring revenue and a loyal brand community.',
 'medium',5000,25000,'High','Medium','4–8 months','Medium','High',
 '["Subscription box","Recurring revenue","Brand community"]',
 '["Define niche and target persona","Source 5–7 signature products, negotiate minimums","Build waitlist landing page + social presence","Launch Kickstarter or pre-sell 50 boxes","Ship first box and film unboxing content","Focus on churn reduction — referrals & community"]',
 'Subscription models reward creativity and community-building. Your profile shows strong creative inclination and medium capital tolerance.',
 3,3,2,3,2,4,2,2),

('saas','SaaS / Digital Tools','⚙️','Build software that solves a recurring problem. Subscription-based revenue with near-infinite scalability.',
 'hard',10000,100000,'Very High','High','9–18 months','High','Very High',
 '["Validate with 10 customer discovery interviews","Build MVP in 4–6 weeks (no-code if needed)","Launch on Product Hunt + HackerNews","Get to 5 paying customers before scaling","Instrument analytics, improve NPS score","Build self-serve funnel to reduce CAC"]',
 'SaaS rewards technical founders with patience. Your high tech score and long-term horizon make this your highest-upside path.',
 4,4,5,4,4,3,3,3),

('affiliate','Affiliate Marketing','🔗','Promote other brands and earn commissions on sales. Content or SEO driven — near-zero overhead.',
 'easy',0,500,'Medium','Very Low','3–6 months','Low','Medium',
 '["Choose a niche with strong affiliate programs","Create a niche blog or YouTube channel","Publish 20 SEO-optimized reviews and articles","Apply to affiliate programs (3–40% commission)","Build an email list with automated sequences","Diversify to 3+ affiliate programs"]',
 'Affiliate is the ultimate low-capital, high-passivity model. Your desire for flexibility and minimal capital risk aligns perfectly.',
 1,1,1,1,5,3,1,4),

('print-on-demand','Print on Demand','🎨','Design products — t-shirts, mugs, posters — printed and shipped on order. Zero inventory risk whatsoever.',
 'easy',100,1000,'Low–Medium','Very Low','2–4 months','Low','Medium',
 '["Create accounts on Printful and Etsy or Shopify","Design 20 products using Canva or Adobe","Research trending niches on Pinterest and Etsy","Run Etsy ads and optimize keywords","Build brand presence on Instagram and TikTok","Expand to 2–3 POD platforms for reach"]',
 'Your strong creative profile and low capital tolerance point straight to Print on Demand. Platforms like Printful + Etsy need zero upfront inventory.',
 1,1,1,2,3,5,1,5),

('private-label','Private Label','🏷️','Source generic products, brand them as your own, and sell via Amazon FBA or your own store.',
 'medium',3000,20000,'High','Medium','6–12 months','Medium','High',
 '["Use Jungle Scout or Helium 10 to find opportunity","Source 3 suppliers on Alibaba, order samples","Create brand identity — name, logo, packaging","Register on Amazon Seller Central","Launch with 30 reviews via Vine and outreach","Scale via Amazon PPC ads systematically"]',
 'Private label suits you if you think in brands. Medium capital and patience are your signals — this path rewards systematic thinkers.',
 3,3,2,3,2,2,2,6),

('digital-products','Digital Products','💻','Sell e-books, templates, courses, or presets. Create once, sell forever at near-100% margin.',
 'easy',0,500,'Very High','Low','1–3 months','Low','Very High',
 '["Identify your expertise — what do people pay to learn","Create a high-quality PDF, course, or template","List on Gumroad, Lemon Squeezy, or Teachable","Build audience via TikTok, Twitter X, or YouTube","Launch with a limited-time offer discount","Add upsells — bundle, premium version, community access"]',
 'Digital products offer the best margin in e-commerce. Your passive income goal and creative score make this a natural fit.',
 1,1,2,1,5,4,1,7),

('marketplace-reselling','Marketplace Reselling','🔄','Buy undervalued products via retail or wholesale arbitrage and resell for profit on Amazon or eBay.',
 'medium',1000,10000,'Medium','Medium','1–2 months','Low','Low',
 '["Learn retail arbitrage fundamentals using Keepa and RevSeller","Source from clearance sales, retail stores, wholesale","Create Amazon Seller and eBay accounts","List first 20 products and learn buy box strategy","Build cash flow and reinvest systematically","Transition to wholesale accounts for better margins"]',
 'Reselling suits analytical, hands-on founders. Your medium capital and preference for tangible, data-driven decisions fit this model well.',
 2,2,1,4,1,1,1,8);
