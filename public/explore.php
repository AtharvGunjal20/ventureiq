<?php
// ============================================================
//  VentureIQ – Business Model Explorer
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
$pageTitle = 'Business Model Explorer';
require_once ROOT_PATH . '/includes/header.php';

$models = DB::all('SELECT * FROM business_models WHERE is_active=1 ORDER BY sort_order,id');
$filter = clean($_GET['filter'] ?? 'all');
$search = clean($_GET['q'] ?? '');
?>

<div class="container" style="padding-top:56px;padding-bottom:80px">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:48px">
    <div class="label">Model Library</div>
    <h1 style="margin-bottom:12px">Business Model Explorer</h1>
    <p style="max-width:520px;margin:0 auto;font-size:1rem">Browse all e-commerce models, compare side-by-side, and find your best fit.</p>
  </div>

  <!-- Search + Filters -->
  <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:center;margin-bottom:36px">
    <input id="searchInput" class="form-control" placeholder="Search models…"
           value="<?= htmlspecialchars($search) ?>"
           style="max-width:260px"
           oninput="filterModels()">

    <?php foreach (['all'=>'All Models','easy'=>'Beginner','medium'=>'Intermediate','hard'=>'Advanced','passive'=>'Passive Income','low-risk'=>'Low Risk','high-roi'=>'High ROI'] as $val=>$label): ?>
    <button class="filter-btn <?= $filter===$val?'active':'' ?>"
            data-filter="<?= $val ?>"
            onclick="setFilter('<?= $val ?>',this)">
      <?= $label ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Compare bar -->
  <div id="compare-bar" style="display:none;position:sticky;top:62px;z-index:150;background:rgba(124,109,250,.95);backdrop-filter:blur(12px);padding:12px 20px;border-radius:var(--radius);margin-bottom:20px;display:none;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <span style="font-size:.88rem;font-weight:600;color:white" id="compare-label">Select 2–3 models to compare</span>
    <div style="display:flex;gap:8px">
      <button onclick="doCompare()" class="btn" style="background:white;color:#7c6dfa;font-size:.83rem;padding:7px 16px">Compare Now →</button>
      <button onclick="clearCompare()" class="btn" style="background:rgba(255,255,255,.2);color:white;border:1px solid rgba(255,255,255,.3);font-size:.83rem;padding:7px 14px">Clear</button>
    </div>
  </div>

  <!-- Model Grid -->
  <div id="model-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
    <?php foreach ($models as $m):
      $tags     = json_decode($m['tags'],   true) ?? [];
      $diffColor = ['easy'=>'green','medium'=>'amber','hard'=>'red'][$m['difficulty']] ?? 'amber';
      $riskColor = match($m['risk_level']) { 'Very Low','Low'=>'green','Medium'=>'amber',default=>'red' };
    ?>
    <div class="model-card card"
         data-name="<?= htmlspecialchars(strtolower($m['name'])) ?>"
         data-diff="<?= $m['difficulty'] ?>"
         data-risk="<?= strtolower(str_replace(' ','',$m['risk_level'])) ?>"
         data-roi="<?= strtolower($m['roi_label']) ?>"
         data-passive="<?= $m['score_passive'] >= 4 ? 'passive' : '' ?>"
         data-id="<?= $m['id'] ?>"
         id="mc-<?= $m['id'] ?>"
         style="cursor:default;position:relative">

      <!-- Compare checkbox -->
      <div style="position:absolute;top:14px;right:14px">
        <label style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:var(--text3);cursor:pointer">
          <input type="checkbox" style="accent-color:#7c6dfa;width:14px;height:14px"
                 onchange="toggleCompare(<?= $m['id'] ?>, '<?= htmlspecialchars($m['emoji'].' '.$m['name']) ?>', this)"> Compare
        </label>
      </div>

      <!-- Card header -->
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;padding-right:70px">
        <div style="font-size:2.2rem;line-height:1"><?= htmlspecialchars($m['emoji']) ?></div>
        <div>
          <div style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:4px"><?= htmlspecialchars($m['name']) ?></div>
          <span class="badge badge-<?= $diffColor ?>"><?= ucfirst($m['difficulty']) ?></span>
        </div>
      </div>

      <!-- Description -->
      <p style="font-size:.85rem;line-height:1.65;color:var(--text2);margin-bottom:16px"><?= htmlspecialchars($m['description']) ?></p>

      <!-- Meta row -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <span style="font-size:.75rem;color:var(--text3)">💰 $<?= number_format($m['investment_min']) ?>–$<?= number_format($m['investment_max']) ?></span>
        <span style="font-size:.75rem;color:var(--text3)">📈 <?= htmlspecialchars($m['roi_label']) ?></span>
        <span style="font-size:.75rem;color:var(--text3)">⏳ <?= htmlspecialchars($m['time_to_profit']) ?></span>
      </div>

      <!-- Risk + ROI badges -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span class="badge badge-<?= $riskColor ?>">● <?= htmlspecialchars($m['risk_level']) ?> Risk</span>
        <span class="badge badge-cyan">↑ <?= htmlspecialchars($m['scalability']) ?> Scale</span>
      </div>

      <!-- Tags -->
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
        <?php foreach ($tags as $tag): ?>
        <span style="background:var(--glass2);border:1px solid var(--border);font-size:.7rem;padding:2px 8px;border-radius:5px;color:var(--text3)"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>

      <!-- CTA -->
      <a href="<?= url('/quiz.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:.83rem">Take Quiz → Get Match Score</a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- No results -->
  <div id="no-results" style="display:none;text-align:center;padding:64px 24px">
    <div style="font-size:2.5rem;margin-bottom:12px">🔍</div>
    <h3>No models match your filter</h3>
    <p>Try a different filter or search term.</p>
    <button onclick="setFilter('all',document.querySelector('[data-filter=all]'));document.getElementById('searchInput').value='';filterModels()" class="btn btn-ghost" style="margin-top:16px">Clear filters</button>
  </div>

  <!-- CTA -->
  <div style="text-align:center;margin-top:56px">
    <p style="margin-bottom:16px">Not sure which fits? Let our engine decide.</p>
    <a href="<?= url('/quiz.php') ?>" class="btn btn-primary btn-lg">Take the 3-Minute Quiz →</a>
  </div>
</div>

<!-- Compare Modal -->
<div id="compare-modal" style="position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:500;display:none;align-items:flex-start;justify-content:center;padding:24px;overflow-y:auto">
  <div style="background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius-xl);padding:32px;max-width:900px;width:100%;margin:auto" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 style="font-size:1.2rem">Side-by-Side Comparison</h2>
      <button onclick="document.getElementById('compare-modal').style.display='none'" style="background:none;border:none;color:var(--text3);font-size:1.4rem;cursor:pointer">✕</button>
    </div>
    <div id="compare-content" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;overflow-x:auto"></div>
  </div>
</div>

<script>
const allModels = <?= json_encode(array_map(fn($m) => [
  'id'            => $m['id'],
  'name'          => $m['name'],
  'emoji'         => $m['emoji'],
  'difficulty'    => $m['difficulty'],
  'investment_min'=> $m['investment_min'],
  'investment_max'=> $m['investment_max'],
  'roi_label'     => $m['roi_label'],
  'risk_level'    => $m['risk_level'],
  'time_to_profit'=> $m['time_to_profit'],
  'tech_level'    => $m['tech_level'],
  'scalability'   => $m['scalability'],
  'description'   => $m['description'],
  'score_capital' => $m['score_capital'],
  'score_risk'    => $m['score_risk'],
  'score_tech'    => $m['score_tech'],
  'score_passive' => $m['score_passive'],
  'score_creative'=> $m['score_creative'],
], $models), JSON_UNESCAPED_UNICODE) ?>;

let activeFilter = '<?= htmlspecialchars($filter) ?>';
let compareList  = []; // [{id, name}]

function setFilter(val, btn) {
  activeFilter = val;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterModels();
}

function filterModels() {
  const q    = document.getElementById('searchInput').value.toLowerCase();
  const cards = document.querySelectorAll('.model-card');
  let visible  = 0;
  cards.forEach(card => {
    const name    = card.dataset.name;
    const diff    = card.dataset.diff;
    const risk    = card.dataset.risk;
    const roi     = card.dataset.roi;
    const passive = card.dataset.passive;
    let show = true;
    if (activeFilter === 'easy'    && diff !== 'easy')    show = false;
    if (activeFilter === 'medium'  && diff !== 'medium')  show = false;
    if (activeFilter === 'hard'    && diff !== 'hard')    show = false;
    if (activeFilter === 'passive' && passive !== 'passive') show = false;
    if (activeFilter === 'low-risk' && !['verylowrisk','lowrisk'].some(r => risk.includes(r.replace('risk','')))) show = false;
    if (activeFilter === 'high-roi' && !roi.includes('high')) show = false;
    if (q && !name.includes(q)) show = false;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('no-results').style.display = visible === 0 ? 'block' : 'none';
}

function toggleCompare(id, name, checkbox) {
  if (checkbox.checked) {
    if (compareList.length >= 3) { checkbox.checked = false; VIQ?.toast('Max 3 models', 'warn'); return; }
    compareList.push({id, name});
  } else {
    compareList = compareList.filter(m => m.id !== id);
  }
  const bar = document.getElementById('compare-bar');
  if (compareList.length >= 1) {
    bar.style.display = 'flex';
    document.getElementById('compare-label').textContent = compareList.map(m=>m.name).join(' vs ');
    document.querySelector('#compare-bar button').disabled = compareList.length < 2;
  } else {
    bar.style.display = 'none';
  }
}

function clearCompare() {
  compareList = [];
  document.querySelectorAll('.model-card input[type=checkbox]').forEach(cb => cb.checked = false);
  document.getElementById('compare-bar').style.display = 'none';
}

function doCompare() {
  if (compareList.length < 2) return;
  const selected = allModels.filter(m => compareList.find(c => c.id == m.id));
  const rows = [
    ['Investment', m => '$'+m.investment_min.toLocaleString()+'–$'+m.investment_max.toLocaleString()],
    ['ROI Potential', m => m.roi_label],
    ['Risk Level', m => m.risk_level],
    ['Time to Profit', m => m.time_to_profit],
    ['Tech Required', m => m.tech_level],
    ['Scalability', m => m.scalability],
    ['Difficulty', m => m.difficulty.charAt(0).toUpperCase()+m.difficulty.slice(1)],
    ['Capital Score', m => '⭐'.repeat(m.score_capital)],
    ['Passive Score', m => '⭐'.repeat(m.score_passive)],
    ['Creative Score', m => '⭐'.repeat(m.score_creative)],
  ];
  document.getElementById('compare-content').innerHTML = selected.map(m => `
    <div style="background:var(--glass);border:1px solid var(--border2);border-radius:var(--radius);padding:18px;min-width:200px">
      <div style="font-size:1.8rem;margin-bottom:8px">${m.emoji}</div>
      <div style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border)">${m.name}</div>
      ${rows.map(([label, fn]) => `
        <div style="padding:7px 0;border-bottom:1px solid var(--border)">
          <div style="font-size:.7rem;color:var(--text4);margin-bottom:2px;text-transform:uppercase;letter-spacing:.06em">${label}</div>
          <div style="font-size:.85rem;font-weight:500;color:var(--text2)">${fn(m)}</div>
        </div>`).join('')}
    </div>
  `).join('');
  document.getElementById('compare-modal').style.display = 'flex';
}

document.getElementById('compare-modal').addEventListener('click', function(e){if(e.target===this)this.style.display='none';});
filterModels();
</script>

<style>
.filter-btn { background:var(--glass);border:1px solid var(--border);color:var(--text2);font-size:.82rem;padding:7px 16px;border-radius:100px;cursor:pointer;transition:all .2s; }
.filter-btn:hover { border-color:var(--border2);color:var(--text); }
.filter-btn.active { background:rgba(124,109,250,.15);border-color:var(--purple);color:var(--purple3); }
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
