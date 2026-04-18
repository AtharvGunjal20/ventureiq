<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
$pageTitle = 'How It Works';
require_once ROOT_PATH . '/includes/header.php';
?>

<div style="max-width:800px;margin:0 auto;padding:64px 24px 100px">

  <div style="text-align:center;margin-bottom:64px">
    <div class="label">The Process</div>
    <h1 style="margin-bottom:14px">How VentureIQ Works</h1>
    <p style="font-size:1.05rem;max-width:520px;margin:0 auto">
      From zero to a data-backed e-commerce strategy in under 3 minutes.
    </p>
  </div>

  <!-- Steps -->
  <?php
  $steps = [
    ['🧠','Answer 7 Smart Questions','Our quiz covers 7 founder dimensions: startup capital, risk tolerance, technical skill, weekly time availability, income goal, creativity level, and team size. Each answer feeds our weighted scoring engine.'],
    ['⚙️','AI Scores Your Profile','Our recommendation engine calculates a match score against 8 business model archetypes using a weighted distance algorithm. Each dimension is normalized, weighted differently, and scored from 0–100%.'],
    ['📊','Receive Your Dashboard','You get a personalized results page with: confidence score, radar chart comparing your profile vs the top model, investment range, risk level indicator, ROI forecast, and top 3 alternatives with scores.'],
    ['🗺️','Get Your Startup Roadmap','Every top recommendation comes with a personalized 6-step startup roadmap — from legal setup to first revenue. Steps are tailored to your specific model and skill level.'],
    ['⚖️','Compare & Explore','Use the Model Explorer to compare any 2–3 models head-to-head on 10 data dimensions. Filter by difficulty, risk level, or passive income potential.'],
    ['💾','Save & Track Over Time','Create a free account to save every result to your dashboard. Take the quiz multiple times as your situation changes and track how your recommendations evolve.'],
  ];
  foreach ($steps as $i => [$icon, $title, $desc]):
  ?>
  <div style="display:flex;gap:24px;margin-bottom:<?= $i < count($steps)-1 ? '0' : '0' ?>;padding-bottom:40px;position:relative">
    <?php if ($i < count($steps)-1): ?>
    <div style="position:absolute;left:20px;top:44px;bottom:0;width:1px;background:linear-gradient(to bottom,rgba(124,109,250,.4),transparent)"></div>
    <?php endif; ?>
    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#7c6dfa,#4f9fff);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:white;flex-shrink:0;z-index:1"><?= $i+1 ?></div>
    <div style="padding-top:6px">
      <div style="font-size:1.4rem;margin-bottom:6px"><?= $icon ?></div>
      <h3 style="font-size:1.05rem;margin-bottom:8px"><?= $title ?></h3>
      <p style="font-size:.9rem;line-height:1.75"><?= $desc ?></p>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Science section -->
  <div class="card card-featured" style="margin-top:8px;padding:32px">
    <div class="label" style="margin-bottom:10px">The Science</div>
    <h2 style="font-size:1.2rem;margin-bottom:14px">Weighted Scoring Algorithm</h2>
    <p style="font-size:.9rem;margin-bottom:16px">
      Each quiz answer is mapped to one of 7 numerical dimensions. These are compared against the ideal profile score for each of our 8 business model archetypes using a weighted Euclidean distance approach.
    </p>
    <div style="background:var(--bg3);border-radius:var(--radius);padding:16px;font-family:monospace;font-size:.8rem;color:var(--cyan);overflow-x:auto">
      confidence = Σ ( max(0, 5 − |user[dim] − model[dim]|) × weight[dim] ) / max_score × 100
    </div>
    <p style="font-size:.82rem;color:var(--text3);margin-top:12px">
      Risk tolerance and capital availability carry the highest weights (1.3× and 1.2×). Technical skill, passivity, and creative orientation are weighted at 1.0×. Team size carries the lowest weight (0.7×) as it's the most flexible dimension.
    </p>
  </div>

  <!-- CTA -->
  <div style="text-align:center;margin-top:48px">
    <a href="<?= url('/quiz.php') ?>" class="btn btn-primary btn-lg">Start Your Free Assessment →</a>
    <p style="font-size:.82rem;color:var(--text4);margin-top:12px">No signup required. Results in under 3 minutes.</p>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
