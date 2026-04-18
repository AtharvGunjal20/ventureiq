<?php
// ============================================================
//  VentureIQ – Landing Page
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
$pageTitle = 'Find Your Perfect E-Commerce Business Model';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hero" aria-labelledby="hero-heading">
  <div class="hero-badge"><span class="hero-badge-dot"></span> AI-Powered Business Intelligence</div>
  <h1 id="hero-heading" style="max-width:780px">
    Discover Your Ideal<br>
    <span class="grad-text">E-Commerce Model</span>
  </h1>
  <p>Answer 7 smart questions. Receive a data-driven recommendation, confidence score, and a personalized startup roadmap — in under 3 minutes.</p>
  <div class="hero-btns">
    <a href="<?= url('/quiz.php') ?>" class="btn btn-primary btn-lg">Take the Free Quiz →</a>
    <a href="<?= url('/explore.php') ?>" class="btn btn-ghost btn-lg">Explore Models</a>
  </div>
  <div style="margin-top:48px;display:flex;gap:32px;justify-content:center;flex-wrap:wrap">
    <?php foreach ([['12K+','Founders assessed'],['8','Business models'],['94%','Match accuracy'],['3 min','Time to results']] as [$n,$l]): ?>
    <div style="text-align:center">
      <div style="font-size:1.8rem;font-weight:800;background:var(--grad-glow);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $n ?></div>
      <div style="font-size:.75rem;color:var(--text3);margin-top:2px;text-transform:uppercase;letter-spacing:.06em"><?= $l ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── FEATURES ──────────────────────────────────────────────── -->
<section class="section" aria-labelledby="features-heading">
  <div class="container">
    <div class="label" style="text-align:center">Platform Features</div>
    <h2 id="features-heading" style="text-align:center;margin-bottom:12px">Built for serious founders</h2>
    <p style="text-align:center;max-width:500px;margin:0 auto 48px">Every tool you need to validate your e-commerce direction — before spending a dollar.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
      <?php
      $features = [
        ['🧠','Weighted Recommendation Engine','7-dimension scoring algorithm evaluates capital, risk, tech, timeline, and goals to surface your perfect model — not a generic quiz.','purple'],
        ['📊','Visual Confidence Dashboard','Radar charts, confidence scores, and comparison tools give founders instant data clarity on every recommendation.','cyan'],
        ['🗺️','Startup Roadmap Generator','Get a personalized, step-by-step action plan — from validation to first revenue milestone — for your top model.','pink'],
        ['⚖️','Model Comparison Tool','Compare any models head-to-head on ROI potential, startup cost, tech complexity, and time to profitability.','amber'],
        ['🔒','Secure User Accounts','Save your results, track progress, and return anytime. Enterprise-grade auth with CAPTCHA and brute-force protection.','purple'],
        ['📥','PDF Report Export','Download a branded PDF of your full analysis to share with co-founders, investors, or mentors.','cyan'],
      ];
      foreach ($features as [$icon, $title, $desc, $color]): ?>
      <div class="card">
        <div style="font-size:1.8rem;margin-bottom:14px"><?= $icon ?></div>
        <h3 style="margin-bottom:8px"><?= htmlspecialchars($title) ?></h3>
        <p style="font-size:.88rem"><?= htmlspecialchars($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS PREVIEW ──────────────────────────────────── -->
<section class="section-sm" style="background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0;text-align:center">
      <?php foreach (['Answer 7 questions','AI scores your profile','Get your recommendation','Download your roadmap'] as $i => $step): ?>
      <div style="padding:28px 20px;position:relative">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#7c6dfa,#4f9fff);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;color:white;margin:0 auto 12px"><?= $i+1 ?></div>
        <div style="font-size:.9rem;font-weight:600;color:var(--text)"><?= htmlspecialchars($step) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── TESTIMONIALS ───────────────────────────────────────────── -->
<section class="section" aria-labelledby="testi-heading">
  <div class="container">
    <div class="label" style="text-align:center">Social Proof</div>
    <h2 id="testi-heading" style="text-align:center;margin-bottom:48px">Founders love VentureIQ</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
      <?php
      $testimonials = [
        ['I spent weeks researching models. VentureIQ gave me a clearer answer in 3 minutes — and it was right. We launched our SaaS and hit $10k MRR in 4 months.','Aarav Mehta','SaaS Founder, Mumbai','AM','av1'],
        ['The radar chart sold me instantly. Seeing my profile vs each model made it obvious. Dropshipping was wrong for me — subscription was perfect.','Sophia Lee','E-Commerce Founder, Singapore','SL','av2'],
        ['The startup roadmap was the game changer. Not just "what model" — but concrete next steps. That\'s worth paying for.','James Rivera','Digital Founder, Toronto','JR','av3'],
      ];
      $avColors = ['av1'=>'linear-gradient(135deg,#7c6dfa,#4f9fff)','av2'=>'linear-gradient(135deg,#00d4aa,#00e5a0)','av3'=>'linear-gradient(135deg,#ff6b9d,#ff6b6b)'];
      foreach ($testimonials as [$text, $name, $role, $initials, $av]): ?>
      <div class="card">
        <p style="font-size:.9rem;line-height:1.75;font-style:italic;color:var(--text2);margin-bottom:20px">"<?= htmlspecialchars($text) ?>"</p>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:36px;height:36px;border-radius:50%;background:<?= $avColors[$av] ?>;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:white;flex-shrink:0"><?= $initials ?></div>
          <div>
            <div style="font-size:.84rem;font-weight:600;color:var(--text)"><?= htmlspecialchars($name) ?></div>
            <div style="font-size:.75rem;color:var(--text3)"><?= htmlspecialchars($role) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA BANNER ────────────────────────────────────────────── -->
<section class="section-sm">
  <div class="container" style="text-align:center">
    <div class="card-featured" style="padding:48px 32px;max-width:640px;margin:0 auto">
      <h2 style="margin-bottom:12px">Ready to find your path?</h2>
      <p style="margin-bottom:28px">Join 12,000+ founders who used VentureIQ to launch with confidence.</p>
      <a href="<?= url('/quiz.php') ?>" class="btn btn-primary btn-lg">Start Your Free Assessment →</a>
    </div>
  </div>
</section>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
