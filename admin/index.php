<?php
// ============================================================
//  VentureIQ – Admin Dashboard
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

// Dashboard stats
$stats = [
    'users'      => DB::one('SELECT COUNT(*) as n FROM users WHERE role="user"')['n'] ?? 0,
    'results'    => DB::one('SELECT COUNT(*) as n FROM quiz_results')['n'] ?? 0,
    'today'      => DB::one('SELECT COUNT(*) as n FROM quiz_results WHERE DATE(created_at)=CURDATE()')['n'] ?? 0,
    'models'     => DB::one('SELECT COUNT(*) as n FROM business_models WHERE is_active=1')['n'] ?? 0,
];

// Top models chart data
$topModels = DB::all('SELECT m.name, m.emoji, COUNT(*) as count FROM quiz_results r JOIN business_models m ON m.id=r.top_model_id GROUP BY r.top_model_id ORDER BY count DESC LIMIT 6');

// Recent users
$recentUsers = DB::all('SELECT id,first_name,last_name,email,role,is_active,created_at FROM users ORDER BY created_at DESC LIMIT 8');

// Recent quiz completions
$recentResults = DB::all('SELECT r.uuid, r.confidence_pct, r.created_at, m.name AS model, m.emoji, u.email FROM quiz_results r JOIN business_models m ON m.id=r.top_model_id LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC LIMIT 8');

$loadCharts = true;
$pageTitle  = 'Admin Dashboard';
$bodyClass  = 'admin-page';

// Inline chart init
$chartLabels = json_encode(array_map(fn($m) => $m['emoji'].' '.$m['name'], $topModels));
$chartData   = json_encode(array_column($topModels, 'count'));
$inlineScript = <<<JS
document.addEventListener('DOMContentLoaded', () => {
  new Chart(document.getElementById('modelChart'), {
    type: 'doughnut',
    data: {
      labels: $chartLabels,
      datasets: [{ data: $chartData,
        backgroundColor: ['rgba(124,109,250,.75)','rgba(79,159,255,.75)','rgba(0,212,170,.75)','rgba(255,179,71,.75)','rgba(255,107,157,.75)','rgba(255,107,107,.75)'],
        borderWidth: 0, hoverOffset: 6 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ color:'#9898b8', font:{size:11}, boxWidth:10, padding:12 } } }, cutout:'65%' }
  });
});
JS;

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar" role="navigation" aria-label="Admin navigation">
    <div class="sidebar-section">Overview</div>
    <a href="<?= url('/admin/') ?>"              class="sidebar-link active">📊 Dashboard</a>
    <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link">📈 Analytics</a>

    <div class="sidebar-section">Manage</div>
    <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link">👥 Users</a>
    <a href="<?= url('/admin/results.php') ?>" class="sidebar-link">📋 Quiz Results</a>
    <a href="<?= url('/admin/models.php') ?>"  class="sidebar-link">🗂 Business Models</a>

    <div class="sidebar-section">System</div>
    <a href="<?= url('/admin/audit.php') ?>"  class="sidebar-link">🔍 Audit Log</a>
    <a href="<?= url('/admin/settings.php') ?>" class="sidebar-link">⚙️ Settings</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
    <a href="/"          class="sidebar-link">← Back to Site</a>
    <a href="<?= url('/logout.php') ?>" class="sidebar-link" style="color:var(--red)">🚪 Sign Out</a>
  </aside>

  <!-- ── Main Content ────────────────────────────────────── -->
  <div class="admin-content">
    <div style="margin-bottom:28px">
      <h1 style="font-size:1.5rem;margin-bottom:4px">Dashboard</h1>
      <p style="color:var(--text3);font-size:.85rem">Welcome back, <?= htmlspecialchars(auth_user()['first_name']) ?>. Here's what's happening.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid" style="margin-bottom:28px">
      <div class="stat-card">
        <div class="stat-number"><?= number_format($stats['users']) ?></div>
        <div class="stat-label">Registered Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= number_format($stats['results']) ?></div>
        <div class="stat-label">Total Assessments</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= number_format($stats['today']) ?></div>
        <div class="stat-label">Assessments Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= number_format($stats['models']) ?></div>
        <div class="stat-label">Active Models</div>
      </div>
    </div>

    <!-- Charts + Recent -->
    <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;margin-bottom:28px;align-items:start">
      <div class="card-elevated" style="padding:20px">
        <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);margin-bottom:16px">Top Recommended Models</div>
        <div style="position:relative;height:220px"><canvas id="modelChart" role="img" aria-label="Doughnut chart of most recommended business models"></canvas></div>
      </div>

      <div class="card-elevated" style="padding:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
          <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)">Recent Assessments</div>
          <a href="<?= url('/admin/results.php') ?>" style="font-size:.78rem;color:var(--purple2)">View all →</a>
        </div>
        <div class="table-wrap" style="border:none">
          <table>
            <thead><tr><th>User</th><th>Model</th><th>Confidence</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentResults as $r): ?>
              <tr>
                <td><?= $r['email'] ? htmlspecialchars($r['email']) : '<span style="color:var(--text4)">Guest</span>' ?></td>
                <td><?= htmlspecialchars($r['emoji'].' '.$r['model']) ?></td>
                <td><span class="badge badge-purple"><?= $r['confidence_pct'] ?>%</span></td>
                <td style="color:var(--text3);font-size:.8rem"><?= date('M j, H:i', strtotime($r['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card-elevated" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)">Recent Users</div>
        <a href="<?= url('/admin/users.php') ?>" style="font-size:.78rem;color:var(--purple2)">Manage users →</a>
      </div>
      <div class="table-wrap" style="border:none">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td style="font-weight:500;color:var(--text)"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <?php if ($u['role']==='admin'): ?>
                <span class="badge badge-purple">Admin</span>
                <?php else: ?>
                <span class="badge" style="background:var(--glass);border:1px solid var(--border);color:var(--text3)">User</span>
                <?php endif; ?>
              </td>
              <td>
                <label class="toggle" title="Toggle active">
                  <input type="checkbox" <?= $u['is_active'] ? 'checked' : '' ?>
                         onchange="toggleStatus(this,'/admin/api/toggle-user.php?id=<?= $u['id'] ?>')">
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td style="color:var(--text3);font-size:.8rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <a href="/admin/users.php?edit=<?= $u['id'] ?>" class="btn btn-ghost btn-sm" style="padding:4px 10px;font-size:.75rem">Edit</a>
                <?php if ($u['id'] !== auth_user()['id']): ?>
                <button onclick="confirmDelete('/admin/api/delete-user.php?id=<?= $u['id'] ?>','<?= htmlspecialchars($u['first_name']) ?>')" class="btn btn-danger btn-sm" style="padding:4px 10px;font-size:.75rem">Del</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-layout -->

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
