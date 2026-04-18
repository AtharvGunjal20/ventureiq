<?php
// ============================================================
//  VentureIQ – Admin: User Management
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$flash  = flash_get();
$search = clean($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash_set('error','Invalid token'); redirect('/admin/users.php'); }
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active' && $uid) {
        $u = DB::one('SELECT is_active FROM users WHERE id=?', [$uid]);
        if ($u) {
            $newVal = $u['is_active'] ? 0 : 1;
            DB::run('UPDATE users SET is_active=? WHERE id=?', [$newVal, $uid]);
            audit('admin.user.toggle', $uid, 'user', "active=$newVal");
            flash_set('success', 'User status updated.');
        }
    } elseif ($action === 'change_role' && $uid) {
        $role = in_array($_POST['role'], ['user','admin']) ? $_POST['role'] : 'user';
        if ($uid !== (int)auth_user()['id']) {
            DB::run('UPDATE users SET role=? WHERE id=?', [$role, $uid]);
            audit('admin.user.role', $uid, 'user', "role=$role");
            flash_set('success', 'User role updated.');
        }
    } elseif ($action === 'delete' && $uid) {
        if ($uid !== (int)auth_user()['id']) {
            DB::run('DELETE FROM users WHERE id=?', [$uid]);
            audit('admin.user.delete', $uid, 'user');
            flash_set('success', 'User deleted.');
        }
    } elseif ($action === 'add_user') {
        $fn    = clean($_POST['first_name'] ?? '');
        $ln    = clean($_POST['last_name']  ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'], ['user','admin']) ? $_POST['role'] : 'user';
        if ($fn && $ln && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 8) {
            $exists = DB::one('SELECT id FROM users WHERE email=?', [$email]);
            if (!$exists) {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                DB::insert('INSERT INTO users (first_name,last_name,email,password_hash,role,is_active,is_verified) VALUES (?,?,?,?,?,1,1)',
                    [$fn,$ln,$email,$hash,$role]);
                audit('admin.user.create', null, 'user', "email=$email,role=$role");
                flash_set('success', 'User created successfully.');
            } else { flash_set('error', 'Email already exists.'); }
        } else { flash_set('error', 'Please fill all required fields correctly.'); }
    }
    redirect('/admin/users.php');
}

// Build query
$where  = $search ? 'WHERE email LIKE :s OR first_name LIKE :s OR last_name LIKE :s' : '';
$params = $search ? ['s' => "%$search%"] : [];
$total  = DB::one("SELECT COUNT(*) as n FROM users $where", $params)['n'] ?? 0;
$pages  = max(1, (int)ceil($total / $perPage));
$users  = DB::all("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'User Management';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="admin-layout">
<aside class="sidebar" role="navigation">
  <div class="sidebar-section">Overview</div>
  <a href="<?= url('/admin/') ?>"              class="sidebar-link">📊 Dashboard</a>
  <a href="<?= url('/admin/analytics.php') ?>" class="sidebar-link">📈 Analytics</a>
  <div class="sidebar-section">Manage</div>
  <a href="<?= url('/admin/users.php') ?>"   class="sidebar-link active">👥 Users</a>
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
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:1.4rem;margin-bottom:2px">User Management</h1>
      <p style="font-size:.83rem;color:var(--text3)"><?= number_format($total) ?> total users</p>
    </div>
    <button onclick="document.getElementById('addUserModal').classList.add('open')" class="btn btn-primary btn-sm">+ Add User</button>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>" data-auto-dismiss><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Search -->
  <form method="GET" style="margin-bottom:20px">
    <div style="display:flex;gap:8px">
      <input class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…" style="max-width:340px">
      <button class="btn btn-ghost btn-sm" type="submit">Search</button>
      <?php if ($search): ?><a href="<?= url('/admin/users.php') ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th><th>Email</th><th>Role</th><th>Active</th>
          <th>Joined</th><th>Last Login</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7c6dfa,#4f9fff);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:white;flex-shrink:0">
                <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
              </div>
              <span style="font-weight:500;color:var(--text)"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></span>
            </div>
          </td>
          <td style="font-size:.85rem"><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <?= csrf_field() ?><input type="hidden" name="action" value="change_role"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role" onchange="this.form.submit()" class="form-control" style="padding:4px 8px;font-size:.78rem;width:auto;display:inline-block" <?= $u['id']==(int)auth_user()['id']?'disabled':'' ?>>
                <option value="user"  <?= $u['role']==='user' ?'selected':'' ?>>User</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
              </select>
            </form>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <label class="toggle">
                <input type="checkbox" <?= $u['is_active']?'checked':'' ?> onchange="this.form.submit()">
                <span class="toggle-slider"></span>
              </label>
            </form>
          </td>
          <td style="font-size:.8rem;color:var(--text3)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
          <td style="font-size:.8rem;color:var(--text3)"><?= $u['last_login'] ? date('M j, H:i', strtotime($u['last_login'])) : '—' ?></td>
          <td>
            <?php if ($u['id'] !== (int)auth_user()['id']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
            <?php else: ?>
            <span style="font-size:.75rem;color:var(--text4)">You</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination" style="margin-top:20px">
    <?php for ($i=1; $i<=$pages; $i++): ?>
    <a href="?page=<?= $i ?><?= $search?'&search='.urlencode($search):'' ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" style="position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;display:none;align-items:center;justify-content:center;padding:24px" onclick="this.id&&this.classList.remove('open')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h2 style="font-size:1.1rem">Add New User</h2>
      <button class="modal-close" onclick="document.getElementById('addUserModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <?= csrf_field() ?><input type="hidden" name="action" value="add_user">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">First Name</label><input class="form-control" name="first_name" required></div>
        <div class="form-group"><label class="form-label">Last Name</label><input class="form-control" name="last_name" required></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
      <div class="form-group"><label class="form-label">Password (min 8 chars)</label><input class="form-control" name="password" type="password" required minlength="8"></div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select class="form-control" name="role"><option value="user">User</option><option value="admin">Admin</option></select>
      </div>
      <button class="btn btn-primary" style="width:100%">Create User</button>
    </form>
  </div>
</div>

<script>
document.getElementById('addUserModal').addEventListener('click', function(e){if(e.target===this)this.classList.remove('open');});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
