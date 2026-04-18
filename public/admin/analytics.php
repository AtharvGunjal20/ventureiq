<?php
// ============================================================
//  VentureIQ – Admin: Analytics
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

// Last 30 days quiz volume
$daily = DB::all(
    'SELECT DATE(created_at) as day, COUNT(*) as count
     FROM quiz_results
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at) ORDER BY day'
);
// Fill gaps
$dailyMap = array_column($daily, 'count', 'day');
$days = []; $dayCounts = [];
for ($i=29; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days[]      = date('M j', strtotime($d));
    $dayCounts[] = (int)($dailyMap[$d] ?? 0);
}

// Model distribution
$modelDist = DB::all('SELECT m.name, m.emoji, COUNT(*) as count FROM quiz_results r JOIN business_models m ON m.id=r.top_model_id GROUP BY r.top_model_id ORDER BY count DESC');

// Confidence distribution buckets
$confDist = DB::all('SELECT FLOOR(confidence_pct/10)*10 as bucket, COUNT(*) as count FROM quiz_results GROUP BY bucket ORDER BY bucket');

// Registrations last 30 days
$regDaily = DB::all('SELECT DATE(created_at) as day, COUNT(*) as count FROM users WHERE role="user" AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY day ORDER BY day');
$regMap = array_column($regDaily,'count','day');
$regCounts = [];
for ($i=29;$i>=0;$i--) { $d=date('Y-m-d',strtotime("-$i days")); $regCounts[]=($regMap[$d]??0); }

// Totals
$totalUsers   = DB::one('SELECT COUNT(*) as n FROM users WHERE role="user"')['n'] ?? 0;
$totalResults = DB::one('SELECT COUNT(*) as n FROM quiz_results')['n'] ?? 0;
$avgConf      = DB::one('SELECT ROUND(AVG(confidence_pct)) as a FROM quiz_results')['a'] ?? 0;
$guestPct     = $totalResults ? round(DB::one('SELECT COUNT(*) as n FROM quiz_results WHERE user_id IS NULL')['n']/$totalResults*100) : 0;

$loadCharts = true;
$pageTitle  = 'Analytics';

$inlineScript = 'document.addEventListener("DOMContentLoaded",()=>{
  // Line chart – quiz volume
  new Chart(document.getElementById("lineChart"),{type:"line",data:{labels:'.json_encode($days).',datasets:[{label:"Assessments",data:'.json_encode($dayCounts).',borderColor:"#7c6dfa",backgroundColor:"rgba(124,109,250,.1)",fill:true,tension:.4,pointRadius:3,pointBackgroundColor:"#7c6dfa"},{label:"Signups",data:'.json_encode($regCounts).',borderColor:"#00d4aa",backgroundColor:"rgba(0,212,170,.08)",fill:true,tension:.4,pointRadius:3,pointBackgroundColor:"#00d4aa"}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,grid:{color:"rgba(255,255,255,.05)"},ticks:{color:"#9898b8",font:{size:10}}},x:{grid:{display:false},ticks:{color:"#9898b8",font:{size:9},maxRotation:45}}},plugins:{legend:{labels:{color:"#9898b8",font:{size:11},boxWidth:10}}}}});
  // Doughnut – model dist
  new Chart(document.getElementById("doughnutChart"),{type:"doughnut",data:{labels:'.json_encode(array_map(fn($m)=>$m['emoji'].' '.$m['name'],$modelDist)).',datasets:[{data:'.json_encode(array_column($modelDist,"count")).',backgroundColor:["rgba(124,109,250,.8)","rgba(79,159,255,.8)","rgba(0,212,170,.8)","rgba(255,179,71,.8)","rgba(255,107,157,.8)","rgba(0,229,160,.8)","rgba(255,107,107,.8)","rgba(255,179,71,.6)"],borderWidth:0,hoverOffset:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:"bottom",labels:{color:"#9898b8",font:{size:10},boxWidth:10,padding:10}}},cutout:"60%"}});
  // Bar – confidence buckets
  new Chart(document.getElementById("confChart"),{type:"bar",data:{labels:'.json_encode(array_map(fn($c)=>$c['bucket'].'–'.($c['bucket']+10).'%',$confDist)).',datasets:[{label:"Results",data:'.json_encode(array_column($confDist,"count")).',backgroundColor:"rgba(124,109,250,.7)",borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,grid:{color:"rgba(255,255,255,.05)"},ticks:{color:"#9898b8",font:{size:10}}},x:{grid:{display:false},ticks:{color:"#9898b8",font:{size:10}}}},plugins:{legend:{display:false}}}});
});';

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">
<aside class="sidebar">
  <div class="sidebar-section">Overview</div>
  <a href="<?= url('/admin/') ?>"              class="sidebar-link">📊 Dashboard</a>
  <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link active">📈 Analytics</a>
  <div class="sidebar-section">Manage</div>
  <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link">👥 Users</a>
  <a href="<?= url('/admin/results.php') ?>" class="sidebar-link">📋 Quiz Results</a>
  <a href="<?= url('/admin/models.php') ?>"  class="sidebar-link">🗂 Business Models</a>
  <div class="sidebar-section">System</div>
  <a href="<?= url('/admin/audit.php') ?>"    class="sidebar-link">🔍 Audit Log</a>
  <a href="<?= url('/admin/settings.php') ?>" class="sidebar-link">⚙️ Settings</a>
  <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
  <a href="/" class="sidebar-link">← Back to Site</a>
  <a href="<?= url('/logout.php') ?>" class="sidebar-link" style="color:var(--red)">🚪 Sign Out</a>
</aside>

<div class="admin-content">
  <div style="margin-bottom:24px">
    <h1 style="font-size:1.4rem;margin-bottom:4px">Analytics</h1>
    <p style="font-size:.83rem;color:var(--text3)">Last 30 days of platform activity</p>
  </div>

  <!-- KPI Row -->
  <div class="stat-grid" style="margin-bottom:24px;grid-template-columns:repeat(4,1fr)">
    <div class="stat-card"><div class="stat-number"><?= number_format($totalUsers) ?></div><div class="stat-label">Total users</div></div>
    <div class="stat-card"><div class="stat-number"><?= number_format($totalResults) ?></div><div class="stat-label">Total assessments</div></div>
    <div class="stat-card"><div class="stat-number"><?= $avgConf ?>%</div><div class="stat-label">Avg confidence</div></div>
    <div class="stat-card"><div class="stat-number"><?= $guestPct ?>%</div><div class="stat-label">Guest rate</div></div>
  </div>

  <!-- Line Chart - full width -->
  <div class="card-elevated" style="padding:20px;margin-bottom:20px">
    <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">📈 Daily Activity — Last 30 Days</div>
    <div style="position:relative;height:260px"><canvas id="lineChart" role="img" aria-label="Line chart showing daily quiz completions and signups over the last 30 days"></canvas></div>
  </div>

  <!-- Doughnut + Confidence Bar -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <div class="card-elevated" style="padding:20px">
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">🥧 Model Distribution</div>
      <div style="position:relative;height:240px"><canvas id="doughnutChart" role="img" aria-label="Doughnut chart of business model recommendation distribution"></canvas></div>
    </div>
    <div class="card-elevated" style="padding:20px">
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">📊 Confidence Score Distribution</div>
      <div style="position:relative;height:240px"><canvas id="confChart" role="img" aria-label="Bar chart showing distribution of confidence scores across all assessments"></canvas></div>
    </div>
  </div>

  <!-- Model Breakdown Table -->
  <div class="card-elevated" style="padding:20px">
    <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">🏆 Model Performance Breakdown</div>
    <div class="table-wrap" style="border:none">
      <table>
        <thead><tr><th>Model</th><th>Total Recommendations</th><th>Share</th><th>Visual</th></tr></thead>
        <tbody>
          <?php
          $totalRec = array_sum(array_column($modelDist,'count'));
          foreach ($modelDist as $m):
            $pct = $totalRec ? round($m['count']/$totalRec*100) : 0;
          ?>
          <tr>
            <td style="font-weight:500;color:var(--text)"><?= htmlspecialchars($m['emoji'].' '.$m['name']) ?></td>
            <td><?= number_format($m['count']) ?></td>
            <td><span class="badge badge-purple"><?= $pct ?>%</span></td>
            <td style="width:200px">
              <div style="height:5px;background:var(--border2);border-radius:3px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#7c6dfa,#00d4aa);border-radius:3px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
