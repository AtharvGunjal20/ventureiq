<?php
// ============================================================
//  VentureIQ – User Dashboard
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_login();

$user    = auth_user();
$results = DB::all(
    'SELECT r.uuid, r.confidence_pct, r.created_at,
            m.name AS model_name, m.emoji, m.risk_level, m.roi_label
     FROM quiz_results r
     JOIN business_models m ON m.id = r.top_model_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC LIMIT 20',
    [$user['id']]
);
$totalQuizzes  = count($results);
$bestResult    = $results[0] ?? null;
$avgConfidence = $totalQuizzes ? (int) round(array_sum(array_column($results, 'confidence_pct')) / $totalQuizzes) : 0;

$pageTitle  = 'My Dashboard';
$loadCharts = true;

// Build activity data for mini chart (last 30 days)
$activity = DB::all(
    'SELECT DATE(created_at) as day, COUNT(*) as n
     FROM quiz_results WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at) ORDER BY day',
    [$user['id']]
);

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container" style="padding-top:48px;padding-bottom:80px">

  <!-- Greeting -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:36px">
    <div>
      <div class="label">Your Dashboard</div>
      <h1 style="font-size:1.8rem;margin-bottom:6px">
        Welcome back, <?= htmlspecialchars($user['first_name']) ?> 👋
      </h1>
      <p style="color:var(--text3);font-size:.9rem"><?= htmlspecialchars($user['email']) ?></p>
    </div>
    <a href="<?= url('/quiz.php') ?>" class="btn btn-primary">+ New Assessment</a>
  </div>

  <!-- Stat Cards -->
  <div class="stat-grid" style="margin-bottom:32px">
    <div class="stat-card">
      <div class="stat-number"><?= $totalQuizzes ?></div>
      <div class="stat-label">Assessments taken</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= $avgConfidence ?>%</div>
      <div class="stat-label">Avg confidence score</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= $bestResult ? htmlspecialchars($bestResult['emoji']) : '—' ?></div>
      <div class="stat-label">Latest top model</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= $bestResult ? $bestResult['confidence_pct'].'%' : '—' ?></div>
      <div class="stat-label">Latest match score</div>
    </div>
  </div>

  <?php if ($totalQuizzes === 0): ?>
  <!-- Empty state -->
  <div class="card" style="text-align:center;padding:64px 32px">
    <div style="font-size:3rem;margin-bottom:16px">🚀</div>
    <h2 style="font-size:1.3rem;margin-bottom:10px">Take your first assessment</h2>
    <p style="margin-bottom:24px;max-width:400px;margin-left:auto;margin-right:auto">
      Answer 7 questions and get a data-driven recommendation, radar chart analysis, and a personalized startup roadmap.
    </p>
    <a href="<?= url('/quiz.php') ?>" class="btn btn-primary btn-lg">Start Free Quiz →</a>
  </div>

  <?php else: ?>

  <!-- Results History + Best Result Side by Side -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;margin-bottom:24px">

    <!-- History Table -->
    <div class="card-elevated" style="padding:24px">
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">Assessment History</div>
      <div class="table-wrap" style="border:none">
        <table>
          <thead>
            <tr><th>Model</th><th>Match</th><th>Risk</th><th>Date</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
              <td style="font-weight:500;color:var(--text)">
                <?= htmlspecialchars($r['emoji'].' '.$r['model_name']) ?>
              </td>
              <td>
                <span class="badge badge-purple"><?= $r['confidence_pct'] ?>%</span>
              </td>
              <td>
                <?php
                $rc = match($r['risk_level']) { 'Very Low','Low'=>'green','Medium'=>'amber',default=>'red' };
                echo '<span class="badge badge-'.$rc.'">'.htmlspecialchars($r['risk_level']).'</span>';
                ?>
              </td>
              <td style="font-size:.8rem;color:var(--text3)">
                <?= date('M j, Y', strtotime($r['created_at'])) ?>
              </td>
              <td>
                <a href="/results.php?id=<?= htmlspecialchars($r['uuid']) ?>"
                   class="btn btn-ghost btn-sm" style="font-size:.75rem;padding:4px 10px">View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Latest Result Card -->
    <?php if ($bestResult): ?>
    <div class="card card-featured" style="padding:24px">
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:14px">Latest Result</div>
      <div style="font-size:2.5rem;margin-bottom:10px"><?= htmlspecialchars($bestResult['emoji']) ?></div>
      <div style="font-size:1.1rem;font-weight:700;margin-bottom:6px;color:var(--text)"><?= htmlspecialchars($bestResult['model_name']) ?></div>
      <div style="margin-bottom:16px">
        <span style="font-size:2rem;font-weight:800;background:linear-gradient(135deg,#7c6dfa,#00d4aa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $bestResult['confidence_pct'] ?>%</span>
        <span style="font-size:.83rem;color:var(--text3);margin-left:6px">confidence</span>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        <?php
        $rc = match($bestResult['risk_level']) { 'Very Low','Low'=>'green','Medium'=>'amber',default=>'red' };
        echo '<span class="badge badge-'.$rc.'">'.htmlspecialchars($bestResult['risk_level']).' Risk</span>';
        echo '<span class="badge badge-amber">'.htmlspecialchars($bestResult['roi_label']).' ROI</span>';
        ?>
      </div>
      <a href="/results.php?id=<?= htmlspecialchars($bestResult['uuid']) ?>"
         class="btn btn-primary" style="width:100%;justify-content:center">View Full Report →</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Quick Actions -->
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="<?= url('/quiz.php') ?>"    class="btn btn-ghost">🔄 Retake Quiz</a>
    <a href="<?= url('/explore.php') ?>" class="btn btn-ghost">🗂 Explore Models</a>
    <a href="<?= url('/profile.php') ?>" class="btn btn-ghost">👤 Edit Profile</a>
  </div>

  <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
