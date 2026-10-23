```php
<?php
// ============================================================
//  VentureIQ – Results Dashboard
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

$uuid = clean($_GET['id'] ?? '');
if (!$uuid) redirect('/quiz.php');

$result = DB::one('SELECT r.*, m.name AS model_name, m.slug, m.emoji, m.description,
                          m.investment_min, m.investment_max, m.roi_label, m.risk_level,
                          m.time_to_profit, m.tech_level, m.scalability, m.tags,
                          m.roadmap_steps, m.why_template,
                          m.score_capital, m.score_risk, m.score_tech, m.score_time,
                          m.score_passive, m.score_creative, m.score_team
                   FROM quiz_results r
                   JOIN business_models m ON m.id = r.top_model_id
                   WHERE r.uuid = ?', [$uuid]);

if (!$result) {
    http_response_code(404);
    die('Result not found.');
}

$allScores     = json_decode($result['all_scores'], true) ?? [];
$profileScores = json_decode($result['profile_scores'], true) ?? [];
$roadmap       = json_decode($result['roadmap_steps'], true) ?? [];
$tags          = json_decode($result['tags'], true) ?? [];
$top3          = array_slice($allScores, 0, 3);
$alts           = array_slice($allScores, 1, 3); // 2nd, 3rd, 4th

// Radar chart data
$dims      = ['Capital', 'Risk', 'Tech', 'Time', 'Passive', 'Creative', 'Team'];
$dimKeys   = ['capital', 'risk_score', 'tech', 'time_avail', 'passive', 'creative', 'team'];
$modelKeys = ['score_capital', 'score_risk', 'score_tech', 'score_time', 'score_passive', 'score_creative', 'score_team'];

$userDat  = array_map(fn($k) => (int)($profileScores[$k] ?? 3), $dimKeys);
$modelDat = array_map(fn($k) => (int)($result[$k] ?? 3), $modelKeys);

$riskColor = match ($result['risk_level']) {
    'Very Low', 'Low' => 'green',
    'Medium' => 'amber',
    default => 'red'
};

$loadCharts = true;
$pageTitle = $result['model_name'] . ' – Your Results';

// FIX: Use double quotes for the JavaScript empty string
$inlineScript = 'const BP = window.BASE_PATH || ""; 
document.addEventListener("DOMContentLoaded",()=>{Charts.init(' . json_encode([
    'radar' => [
        'labels' => $dims,
        'userScores' => $userDat,
        'modelScores' => $modelDat,
        'modelName' => $result['model_name']
    ],
    'bar' => [
        'labels' => array_column($top3, 'name'),
        'scores' => array_column($top3, 'confidence')
    ],
]) . ');});';

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container" style="padding-top:48px;padding-bottom:80px">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:48px" class="fade-in">
    <div class="label">Your Analysis is Ready</div>
    <h1 style="margin-bottom:10px"><?= htmlspecialchars($result['emoji']) ?> <?= htmlspecialchars($result['model_name']) ?></h1>
    <p style="font-size:1rem">
      <span style="background:linear-gradient(135deg,#7c6dfa,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:1.2rem"><?= $result['confidence_pct'] ?>% confidence match</span>
      &nbsp;·&nbsp; Based on your 7-dimension profile
    </p>
  </div>

  <!-- Primary Result Card -->
  <div class="card card-featured" style="margin-bottom:20px;padding:32px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <h2 style="font-size:1.5rem"><?= htmlspecialchars($result['emoji'] . ' ' . $result['model_name']) ?></h2>
      <span class="badge badge-purple" style="font-size:.9rem;padding:6px 16px"><?= $result['confidence_pct'] ?>% Match</span>
    </div>
    <p style="font-size:.95rem;line-height:1.75;margin-bottom:20px"><?= htmlspecialchars($result['why_template'] ?? $result['description']) ?></p>

    <!-- Meta pills -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px">
      <span class="badge badge-<?= $riskColor ?>">● <?= htmlspecialchars($result['risk_level']) ?> Risk</span>
      <span class="badge badge-cyan">💰 $<?= number_format($result['investment_min']) ?>–$<?= number_format($result['investment_max']) ?></span>
      <span class="badge badge-purple">⏳ <?= htmlspecialchars($result['time_to_profit']) ?></span>
      <span class="badge badge-amber">📈 <?= htmlspecialchars($result['roi_label']) ?> ROI</span>
      <span class="badge badge-green">⚙ <?= htmlspecialchars($result['tech_level']) ?> Tech</span>
    </div>

    <!-- Tags -->
    <?php if ($tags): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px">
      <?php foreach ($tags as $tag): ?>
      <span style="background:var(--glass2);border:1px solid var(--border);font-size:.75rem;padding:3px 10px;border-radius:6px;color:var(--text3)"><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Startup Roadmap -->
    <?php if ($roadmap): ?>
    <div style="border-top:1px solid var(--border);padding-top:20px">
      <div class="label" style="margin-bottom:14px">Your Startup Roadmap</div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($roadmap as $i => $step): ?>
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div style="width:24px;height:24px;border-radius:50%;background:rgba(124,109,250,.15);border:1px solid rgba(124,109,250,.35);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--purple3);flex-shrink:0;margin-top:1px"><?= $i + 1 ?></div>
          <div style="font-size:.9rem;color:var(--text2);line-height:1.6"><?= htmlspecialchars($step) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Charts Row -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <div class="card" style="padding:24px">
      <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">📡 Profile Match Radar</div>
      <div style="position:relative;height:240px">
        <canvas id="radarChart" role="img" aria-label="Radar chart comparing your profile to <?= htmlspecialchars($result['model_name']) ?>"></canvas>
      </div>
      <div style="display:flex;gap:16px;margin-top:12px;justify-content:center;font-size:.75rem;color:var(--text3)">
        <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#7c6dfa;display:inline-block"></span>Your Profile</span>
        <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#00d4aa;display:inline-block"></span><?= htmlspecialchars($result['model_name']) ?></span>
      </div>
    </div>

    <div class="card" style="padding:24px">
      <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">📊 Top 3 Model Confidence</div>
      <div style="position:relative;height:240px">
        <canvas id="barChart" role="img" aria-label="Bar chart of top 3 recommended models by confidence"></canvas>
      </div>
    </div>
  </div>

  <!-- Alternatives -->
  <?php if ($alts): ?>
  <div class="card" style="margin-bottom:24px">
    <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">🔄 Top Alternatives For You</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
      <?php foreach ($alts as $alt):
          $altModel = DB::one('SELECT * FROM business_models WHERE id=?', [$alt['id']]);
          if (!$altModel) continue;
      ?>
      <div class="card" style="padding:16px;cursor:pointer" onclick="window.location='/explore.php#<?= htmlspecialchars($altModel['slug']) ?>'">
        <div style="font-size:1.4rem;margin-bottom:8px"><?= htmlspecialchars($altModel['emoji']) ?></div>
        <div style="font-size:.92rem;font-weight:600;margin-bottom:4px"><?= htmlspecialchars($altModel['name']) ?></div>
        <div style="font-size:.75rem;color:var(--text3);margin-bottom:8px"><?= $alt['confidence'] ?>% match · <?= htmlspecialchars($altModel['risk_level']) ?> risk</div>
        <div class="score-bar"><div class="score-fill" data-width="<?= $alt['confidence'] ?>" style="width:0"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="<?= url('/quiz.php') ?>" class="btn btn-ghost">🔄 Retake Quiz</a>
    <a href="<?= url('/explore.php') ?>" class="btn btn-ghost">🗂 Explore All Models</a>
    <button onclick="window.print()" class="btn btn-outline">🖨 Print / Save PDF</button>
    <?php if (!is_logged_in()): ?>
    <a href="<?= url('/register.php') ?>" class="btn btn-primary">💾 Save Results — Free</a>
    <?php else: ?>
    <a href="<?= url('/dashboard.php') ?>" class="btn btn-primary">📊 My Dashboard</a>
    <?php endif; ?>
  </div>

</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
```