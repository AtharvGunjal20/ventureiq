<?php
// ============================================================
//  VentureIQ – Admin: Quiz Results
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page-1)*$perPage;
$search  = clean($_GET['q'] ?? '');
$model   = (int)($_GET['model'] ?? 0);

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'u.email LIKE ?'; $params[] = "%$search%"; }
if ($model)  { $where[] = 'r.top_model_id = ?'; $params[] = $model; }
$whereStr = implode(' AND ', $where);

$total   = DB::one("SELECT COUNT(*) as n FROM quiz_results r LEFT JOIN users u ON u.id=r.user_id WHERE $whereStr", $params)['n'] ?? 0;
$pages   = max(1,(int)ceil($total/$perPage));
$results = DB::all("SELECT r.uuid, r.confidence_pct, r.created_at, r.ip_address,
                           m.name AS model_name, m.emoji,
                           u.email, u.first_name, u.last_name
                    FROM quiz_results r
                    JOIN business_models m ON m.id=r.top_model_id
                    LEFT JOIN users u ON u.id=r.user_id
                    WHERE $whereStr
                    ORDER BY r.created_at DESC
                    LIMIT $perPage OFFSET $offset", [...$params]);

$allModels = DB::all('SELECT id,name FROM business_models WHERE is_active=1 ORDER BY sort_order');

// Quick stats
$todayCount = DB::one('SELECT COUNT(*) as n FROM quiz_results WHERE DATE(created_at)=CURDATE()')['n'] ?? 0;
$weekCount  = DB::one('SELECT COUNT(*) as n FROM quiz_results WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)')['n'] ?? 0;
$avgConf    = DB::one('SELECT ROUND(AVG(confidence_pct)) as a FROM quiz_results')['a'] ?? 0;

$pageTitle = 'Quiz Results';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">
<aside class="sidebar">
  <div class="sidebar-section">Overview</div>
  <a href="<?= url('/admin/') ?>"              class="sidebar-link">📊 Dashboard</a>
  <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link">📈 Analytics</a>
  <div class="sidebar-section">Manage</div>
  <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link">👥 Users</a>
  <a href="<?= url('/admin/results.php') ?>" class="sidebar-link active">📋 Quiz Results</a>
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
    <h1 style="font-size:1.4rem;margin-bottom:4px">Quiz Results</h1>
    <p style="font-size:.83rem;color:var(--text3)"><?= number_format($total) ?> total assessments</p>
  </div>

  <!-- Quick stats -->
  <div class="stat-grid" style="margin-bottom:24px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card"><div class="stat-number"><?= $todayCount ?></div><div class="stat-label">Today</div></div>
    <div class="stat-card"><div class="stat-number"><?= $weekCount ?></div><div class="stat-label">This week</div></div>
    <div class="stat-card"><div class="stat-number"><?= $avgConf ?>%</div><div class="stat-label">Avg confidence</div></div>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
    <input class="form-control" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by email…" style="max-width:240px">
    <select class="form-control" name="model" style="max-width:200px">
      <option value="">All Models</option>
      <?php foreach ($allModels as $m): ?>
      <option value="<?= $m['id'] ?>" <?= $model===$m['id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
    <?php if ($search||$model): ?><a href="<?= url('/admin/results.php') ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    <a href="<?= url('/admin/api/export-results.php') ?>" class="btn btn-outline btn-sm" style="margin-left:auto">⬇ Export CSV</a>
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>User</th><th>Top Model</th><th>Confidence</th><th>IP</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($results as $r): ?>
        <tr>
          <td>
            <?php if ($r['email']): ?>
              <div style="font-size:.85rem;font-weight:500;color:var(--text)"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
              <div style="font-size:.75rem;color:var(--text3)"><?= htmlspecialchars($r['email']) ?></div>
            <?php else: ?>
              <span style="font-size:.82rem;color:var(--text4)">Guest user</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:500;color:var(--text)"><?= htmlspecialchars($r['emoji'].' '.$r['model_name']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <span class="badge badge-purple"><?= $r['confidence_pct'] ?>%</span>
              <div style="width:60px;height:4px;background:var(--border2);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:<?= $r['confidence_pct'] ?>%;background:linear-gradient(90deg,#7c6dfa,#00d4aa);border-radius:2px"></div>
              </div>
            </div>
          </td>
          <td style="font-size:.75rem;color:var(--text4);font-family:monospace"><?= htmlspecialchars($r['ip_address']) ?></td>
          <td style="font-size:.8rem;color:var(--text3)"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
          <td>
            <a href="/results.php?id=<?= htmlspecialchars($r['uuid']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="font-size:.75rem">View →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&model=<?= $model ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
