<?php
// ============================================================
//  VentureIQ – Admin: Business Model Editor
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$flash  = flash_get();
$editId = (int)($_GET['edit'] ?? 0);
$editModel = $editId ? DB::one('SELECT * FROM business_models WHERE id=?', [$editId]) : null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash_set('error','Invalid CSRF'); redirect('/admin/models.php'); }
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['create','update'])) {
        $data = [
            'slug'         => preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ','-',trim($_POST['slug']??'')))),
            'name'         => clean($_POST['name']??''),
            'emoji'        => clean($_POST['emoji']??'🚀'),
            'description'  => clean($_POST['description']??''),
            'difficulty'   => in_array($_POST['difficulty'],['easy','medium','hard'])?$_POST['difficulty']:'medium',
            'investment_min'=> (int)($_POST['investment_min']??0),
            'investment_max'=> (int)($_POST['investment_max']??10000),
            'roi_label'    => clean($_POST['roi_label']??'Medium'),
            'risk_level'   => in_array($_POST['risk_level'],['Very Low','Low','Medium','High','Very High'])?$_POST['risk_level']:'Medium',
            'time_to_profit'=> clean($_POST['time_to_profit']??''),
            'tech_level'   => clean($_POST['tech_level']??''),
            'scalability'  => clean($_POST['scalability']??''),
            'tags'         => json_encode(array_filter(array_map('trim', explode(',', $_POST['tags']??'')))),
            'roadmap_steps'=> json_encode(array_filter(array_map('trim', explode("\n", $_POST['roadmap_steps']??'')))),
            'why_template' => clean($_POST['why_template']??''),
            'score_capital'=> max(1,min(5,(int)($_POST['score_capital']??3))),
            'score_risk'   => max(1,min(5,(int)($_POST['score_risk']??3))),
            'score_tech'   => max(1,min(5,(int)($_POST['score_tech']??3))),
            'score_time'   => max(1,min(5,(int)($_POST['score_time']??3))),
            'score_passive'=> max(1,min(5,(int)($_POST['score_passive']??3))),
            'score_creative'=> max(1,min(5,(int)($_POST['score_creative']??3))),
            'score_team'   => max(1,min(5,(int)($_POST['score_team']??3))),
            'sort_order'   => (int)($_POST['sort_order']??0),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($action === 'create') {
            DB::insert('INSERT INTO business_models (slug,name,emoji,description,difficulty,investment_min,investment_max,roi_label,risk_level,time_to_profit,tech_level,scalability,tags,roadmap_steps,why_template,score_capital,score_risk,score_tech,score_time,score_passive,score_creative,score_team,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array_values($data));
            audit('admin.model.create', null, 'model', "slug={$data['slug']}");
            flash_set('success','Model created.');
        } else {
            $mid = (int)($_POST['model_id']??0);
            $cols = implode('=?,', array_keys($data)).'=?';
            DB::run("UPDATE business_models SET $cols WHERE id=?", [...array_values($data), $mid]);
            audit('admin.model.update', $mid, 'model');
            flash_set('success','Model updated.');
        }
    } elseif ($action === 'delete') {
        $mid = (int)($_POST['model_id']??0);
        DB::run('UPDATE business_models SET is_active=0 WHERE id=?', [$mid]);
        audit('admin.model.delete', $mid, 'model');
        flash_set('success','Model deactivated.');
    }
    redirect('/admin/models.php');
}

$models    = DB::all('SELECT * FROM business_models ORDER BY sort_order,id');
$pageTitle = 'Business Models';
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
  <a href="<?= url('/admin/models.php') ?>"  class="sidebar-link active">🗂 Business Models</a>
  <div class="sidebar-section">System</div>
  <a href="<?= url('/admin/audit.php') ?>"   class="sidebar-link">🔍 Audit Log</a>
  <a href="<?= url('/admin/settings.php') ?>" class="sidebar-link">⚙️ Settings</a>
  <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
  <a href="/" class="sidebar-link">← Back to Site</a>
  <a href="<?= url('/logout.php') ?>" class="sidebar-link" style="color:var(--red)">🚪 Sign Out</a>
</aside>

<div class="admin-content">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:1.4rem;margin-bottom:2px">Business Models</h1>
      <p style="font-size:.83rem;color:var(--text3)"><?= count($models) ?> models configured</p>
    </div>
    <button onclick="openModelForm()" class="btn btn-primary btn-sm">+ Add Model</button>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>" data-auto-dismiss><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Model</th><th>Difficulty</th><th>Investment</th><th>ROI</th><th>Risk</th><th>Active</th><th>Scores</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($models as $m): ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['emoji'].' '.$m['name']) ?></div>
            <div style="font-size:.75rem;color:var(--text4)"><?= htmlspecialchars($m['slug']) ?></div>
          </td>
          <td><span class="badge badge-<?= $m['difficulty']==='easy'?'green':($m['difficulty']==='medium'?'amber':'red') ?>"><?= ucfirst($m['difficulty']) ?></span></td>
          <td style="font-size:.82rem">$<?= number_format($m['investment_min']) ?>–$<?= number_format($m['investment_max']) ?></td>
          <td style="font-size:.82rem"><?= htmlspecialchars($m['roi_label']) ?></td>
          <td><span class="badge badge-<?= in_array($m['risk_level'],['Very Low','Low'])?'green':(($m['risk_level']==='Medium')?'amber':'red') ?>"><?= htmlspecialchars($m['risk_level']) ?></span></td>
          <td>
            <form method="POST">
              <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="model_id" value="<?= $m['id'] ?>">
              <?php foreach (['slug','name','emoji','description','difficulty','investment_min','investment_max','roi_label','risk_level','time_to_profit','tech_level','scalability','tags','roadmap_steps','why_template','score_capital','score_risk','score_tech','score_time','score_passive','score_creative','score_team','sort_order'] as $f): ?>
              <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars(is_array($m[$f])?json_encode($m[$f]):($m[$f]??'')) ?>">
              <?php endforeach; ?>
              <label class="toggle"><input type="checkbox" name="is_active" <?= $m['is_active']?'checked':'' ?> onchange="this.form.submit()"><span class="toggle-slider"></span></label>
            </form>
          </td>
          <td style="font-size:.75rem;color:var(--text3)">C<?= $m['score_capital'] ?> R<?= $m['score_risk'] ?> T<?= $m['score_tech'] ?></td>
          <td>
            <button onclick="editModel(<?= htmlspecialchars(json_encode($m)) ?>)" class="btn btn-ghost btn-sm" style="font-size:.75rem">Edit</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this model?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="model_id" value="<?= $m['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit" style="font-size:.75rem">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- Model Form Modal -->
<div id="modelModal" style="position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:500;display:none;align-items:flex-start;justify-content:center;padding:24px;overflow-y:auto">
  <div class="modal-box" style="max-width:700px;width:100%;margin:auto" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h2 style="font-size:1.1rem" id="modelModalTitle">Add Model</h2>
      <button class="modal-close" onclick="closeModelForm()">✕</button>
    </div>
    <form method="POST" id="modelForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="model_id" id="formModelId">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Name *</label><input class="form-control" name="name" id="f_name" required></div>
        <div class="form-group"><label class="form-label">Slug *</label><input class="form-control" name="slug" id="f_slug" required placeholder="e.g. dropshipping"></div>
      </div>
      <div style="display:grid;grid-template-columns:80px 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Emoji</label><input class="form-control" name="emoji" id="f_emoji" value="🚀"></div>
        <div class="form-group"><label class="form-label">Description *</label><textarea class="form-control" name="description" id="f_description" rows="2" required></textarea></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Difficulty</label><select class="form-control" name="difficulty" id="f_difficulty"><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></div>
        <div class="form-group"><label class="form-label">Investment Min ($)</label><input class="form-control" type="number" name="investment_min" id="f_investment_min" value="0"></div>
        <div class="form-group"><label class="form-label">Investment Max ($)</label><input class="form-control" type="number" name="investment_max" id="f_investment_max" value="10000"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">ROI Label</label><input class="form-control" name="roi_label" id="f_roi_label" value="Medium"></div>
        <div class="form-group"><label class="form-label">Risk Level</label><select class="form-control" name="risk_level" id="f_risk_level"><option>Very Low</option><option>Low</option><option>Medium</option><option>High</option><option>Very High</option></select></div>
        <div class="form-group"><label class="form-label">Time to Profit</label><input class="form-control" name="time_to_profit" id="f_time_to_profit" placeholder="e.g. 3–6 months"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Tech Level</label><input class="form-control" name="tech_level" id="f_tech_level" value="Low"></div>
        <div class="form-group"><label class="form-label">Scalability</label><input class="form-control" name="scalability" id="f_scalability" value="Medium"></div>
      </div>
      <div class="form-group"><label class="form-label">Tags (comma separated)</label><input class="form-control" name="tags" id="f_tags" placeholder="Low cost, Passive, Creative"></div>
      <div class="form-group"><label class="form-label">Roadmap Steps (one per line)</label><textarea class="form-control" name="roadmap_steps" id="f_roadmap_steps" rows="5" placeholder="Step 1&#10;Step 2&#10;Step 3"></textarea></div>
      <div class="form-group"><label class="form-label">Why Template (personalized explanation)</label><textarea class="form-control" name="why_template" id="f_why_template" rows="2"></textarea></div>

      <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px">
        <div class="label" style="margin-bottom:12px">Scoring Dimensions (1=low, 5=high)</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
          <?php foreach ([['Capital','score_capital'],['Risk','score_risk'],['Tech','score_tech'],['Time','score_time'],['Passive','score_passive'],['Creative','score_creative'],['Team','score_team']] as [$label,$key]): ?>
          <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:.72rem"><?= $label ?></label>
            <input class="form-control" type="number" name="<?= $key ?>" id="f_<?= $key ?>" min="1" max="5" value="3" style="padding:6px 10px">
          </div>
          <?php endforeach; ?>
          <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:.72rem">Sort Order</label>
            <input class="form-control" type="number" name="sort_order" id="f_sort_order" value="0" style="padding:6px 10px">
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <label class="toggle"><input type="checkbox" name="is_active" id="f_is_active" checked><span class="toggle-slider"></span></label>
        <span style="font-size:.85rem;color:var(--text2)">Active (visible to users)</span>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%" id="formSubmitBtn">Create Model</button>
    </form>
  </div>
</div>

<script>
function openModelForm(){ document.getElementById('modelModalTitle').textContent='Add Model'; document.getElementById('formAction').value='create'; document.getElementById('formSubmitBtn').textContent='Create Model'; document.getElementById('modelForm').reset(); document.getElementById('modelModal').style.display='flex'; }
function closeModelForm(){ document.getElementById('modelModal').style.display='none'; }
function editModel(m){
  document.getElementById('modelModalTitle').textContent='Edit: '+m.name;
  document.getElementById('formAction').value='update';
  document.getElementById('formModelId').value=m.id;
  document.getElementById('formSubmitBtn').textContent='Save Changes';
  const fields=['name','slug','emoji','description','difficulty','investment_min','investment_max','roi_label','risk_level','time_to_profit','tech_level','scalability','why_template','score_capital','score_risk','score_tech','score_time','score_passive','score_creative','score_team','sort_order'];
  fields.forEach(f=>{ const el=document.getElementById('f_'+f); if(el) el.value=m[f]??''; });
  // Tags
  try{ const tags=JSON.parse(m.tags||'[]'); document.getElementById('f_tags').value=tags.join(', '); } catch(e){}
  // Roadmap
  try{ const steps=JSON.parse(m.roadmap_steps||'[]'); document.getElementById('f_roadmap_steps').value=steps.join('\n'); } catch(e){}
  document.getElementById('f_is_active').checked=m.is_active==1;
  document.getElementById('f_difficulty').value=m.difficulty;
  document.getElementById('f_risk_level').value=m.risk_level;
  document.getElementById('modelModal').style.display='flex';
}
document.getElementById('modelModal').addEventListener('click',function(e){if(e.target===this)closeModelForm();});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
