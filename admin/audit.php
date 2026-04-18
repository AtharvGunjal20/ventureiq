<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$page    = max(1,(int)($_GET['page']??1));
$perPage = 25;
$offset  = ($page-1)*$perPage;
$action  = clean($_GET['action']??'');

$where  = $action ? 'WHERE al.action LIKE ?' : '';
$params = $action ? ["%$action%"] : [];
$total  = DB::one("SELECT COUNT(*) as n FROM audit_log al $where", $params)['n']??0;
$pages  = max(1,(int)ceil($total/$perPage));
$logs   = DB::all("SELECT al.*, u.email, u.first_name, u.last_name FROM audit_log al LEFT JOIN users u ON u.id=al.user_id $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$actions= DB::all('SELECT DISTINCT action FROM audit_log ORDER BY action');

$pageTitle = 'Audit Log';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">
<aside class="sidebar">
  <div class="sidebar-section">Overview</div>
  <a href="<?= url('/admin/') ?>"              class="sidebar-link">📊 Dashboard</a>
  <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link">📈 Analytics</a>
  <div class="sidebar-section">Manage</div>
  <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link">👥 Users</a>
  <a href="<?= url('/admin/results.php') ?>" class="sidebar-link">📋 Quiz Results</a>
  <a href="<?= url('/admin/models.php') ?>"  class="sidebar-link">🗂 Business Models</a>
  <div class="sidebar-section">System</div>
  <a href="<?= url('/admin/audit.php') ?>"    class="sidebar-link active">🔍 Audit Log</a>
  <a href="<?= url('/admin/settings.php') ?>" class="sidebar-link">⚙️ Settings</a>
  <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
  <a href="/" class="sidebar-link">← Back to Site</a>
  <a href="<?= url('/logout.php') ?>" class="sidebar-link" style="color:var(--red)">🚪 Sign Out</a>
</aside>

<div class="admin-content">
  <div style="margin-bottom:24px">
    <h1 style="font-size:1.4rem;margin-bottom:4px">Audit Log</h1>
    <p style="font-size:.83rem;color:var(--text3)"><?= number_format($total) ?> total events</p>
  </div>

  <form method="GET" style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
    <select class="form-control" name="action" style="max-width:220px">
      <option value="">All Actions</option>
      <?php foreach ($actions as $a): ?>
      <option value="<?= htmlspecialchars($a['action']) ?>" <?= $action===$a['action']?'selected':'' ?>><?= htmlspecialchars($a['action']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-ghost btn-sm">Filter</button>
    <?php if ($action): ?><a href="<?= url('/admin/audit.php') ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Action</th><th>User</th><th>Detail</th><th>IP</th><th>Time</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $log):
          $icon = match(true) {
            str_contains($log['action'],'login')    => '🔐',
            str_contains($log['action'],'delete')   => '🗑',
            str_contains($log['action'],'create')   => '✨',
            str_contains($log['action'],'update')   => '✏️',
            str_contains($log['action'],'quiz')     => '📊',
            str_contains($log['action'],'password') => '🔑',
            default                                  => '📋',
          };
          $badgeClass = str_contains($log['action'],'fail')||str_contains($log['action'],'block')||str_contains($log['action'],'delete') ? 'badge-red' : (str_contains($log['action'],'login.success')||str_contains($log['action'],'create') ? 'badge-green' : 'badge-purple');
        ?>
        <tr>
          <td><span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($log['action']) ?></span></td>
          <td style="font-size:.82rem">
            <?= $log['email'] ? htmlspecialchars($log['first_name'].' '.$log['last_name']).'<br><span style="color:var(--text4);font-size:.73rem">'.htmlspecialchars($log['email']).'</span>' : '<span style="color:var(--text4)">System</span>' ?>
          </td>
          <td style="font-size:.78rem;color:var(--text3);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($log['detail']??'') ?>"><?= htmlspecialchars($log['detail']??'—') ?></td>
          <td style="font-size:.75rem;color:var(--text4);font-family:monospace"><?= htmlspecialchars($log['ip_address']) ?></td>
          <td style="font-size:.78rem;color:var(--text3)"><?= date('M j, H:i:s', strtotime($log['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>&action=<?= urlencode($action) ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
