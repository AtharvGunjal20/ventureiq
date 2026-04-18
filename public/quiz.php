<?php
// ============================================================
//  VentureIQ – Interactive Quiz
// ============================================================
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('BASE_PATH') && file_exists(dirname(dirname(__FILE__)) . '/config/config.php')) require_once dirname(dirname(__FILE__)) . '/config/config.php';
$pageTitle  = 'Business Model Quiz';
require_once ROOT_PATH . '/includes/header.php';

// Quiz questions definition (could be DB-driven)
$questions = [
  ['question'=>'How much startup capital can you invest?','hint'=>'Be honest — this shapes everything downstream.','options'=>[
    ['icon'=>'💸','label'=>'Under $1,000','sub'=>'Ultra bootstrap mode','vals'=>['capital'=>1]],
    ['icon'=>'💰','label'=>'$1K – $5K',  'sub'=>'Lean startup',        'vals'=>['capital'=>2]],
    ['icon'=>'💼','label'=>'$5K – $25K', 'sub'=>'Moderate investment', 'vals'=>['capital'=>3]],
    ['icon'=>'🏦','label'=>'$25K+',       'sub'=>'Serious capital',     'vals'=>['capital'=>4]],
  ]],
  ['question'=>'What is your risk tolerance?','hint'=>'Higher risk often means higher reward — and higher stress.','options'=>[
    ['icon'=>'🛡️','label'=>'Very Low', 'sub'=>'I need safe, stable bets','vals'=>['risk_score'=>1]],
    ['icon'=>'⚖️','label'=>'Low–Medium','sub'=>'Measured risks only',    'vals'=>['risk_score'=>2]],
    ['icon'=>'📈','label'=>'Medium–High','sub'=>'I can absorb setbacks', 'vals'=>['risk_score'=>3]],
    ['icon'=>'🚀','label'=>'High',       'sub'=>'All in — big upside',   'vals'=>['risk_score'=>4]],
  ]],
  ['question'=>'What is your technical skill level?','hint'=>'No wrong answer — it determines your tool choices.','options'=>[
    ['icon'=>'🌱','label'=>'Non-technical','sub'=>'No coding, basic tools only','vals'=>['tech'=>1]],
    ['icon'=>'🔧','label'=>'Semi-technical','sub'=>'Some tools, no code',       'vals'=>['tech'=>2]],
    ['icon'=>'💡','label'=>'Technical',     'sub'=>'Can build basic apps',      'vals'=>['tech'=>3]],
    ['icon'=>'⚙️','label'=>'Developer',     'sub'=>'Full-stack capable',        'vals'=>['tech'=>5]],
  ]],
  ['question'=>'How much time can you dedicate weekly?','hint'=>'Part-time founders need very different models.','options'=>[
    ['icon'=>'⏱️','label'=>'< 5 hrs/week',  'sub'=>'Side hustle mode',    'vals'=>['time_avail'=>1]],
    ['icon'=>'🕐','label'=>'5–15 hrs/week', 'sub'=>'Serious side project','vals'=>['time_avail'=>2]],
    ['icon'=>'🗓️','label'=>'15–30 hrs/week','sub'=>'Quasi full-time',     'vals'=>['time_avail'=>3]],
    ['icon'=>'💪','label'=>'40+ hrs/week',  'sub'=>'Full commitment',      'vals'=>['time_avail'=>4]],
  ]],
  ['question'=>'What is your primary income goal?','hint'=>'Passive income vs active growth need very different strategies.','options'=>[
    ['icon'=>'🏝️','label'=>'Passive income stream','sub'=>'Set it and forget it','vals'=>['passive'=>5]],
    ['icon'=>'📊','label'=>'Scalable business',    'sub'=>'Grow a real company', 'vals'=>['passive'=>2]],
    ['icon'=>'💵','label'=>'Quick cash flow',       'sub'=>'Revenue in <3 months','vals'=>['passive'=>1]],
    ['icon'=>'🌍','label'=>'Long-term brand',       'sub'=>'Build something lasting','vals'=>['passive'=>3]],
  ]],
  ['question'=>'How creative are you?','hint'=>'Some models are design-first; others are pure analysis.','options'=>[
    ['icon'=>'📊','label'=>'Data-driven',      'sub'=>'I love spreadsheets',     'vals'=>['creative'=>1]],
    ['icon'=>'🔁','label'=>'Process-oriented', 'sub'=>'Systems thinker',         'vals'=>['creative'=>2]],
    ['icon'=>'✍️','label'=>'Moderately creative','sub'=>'I enjoy making content','vals'=>['creative'=>3]],
    ['icon'=>'🎨','label'=>'Highly creative',  'sub'=>'Design is my superpower', 'vals'=>['creative'=>5]],
  ]],
  ['question'=>'Do you plan to build a team?','hint'=>'Solo founders need automated models; teams unlock complexity.','options'=>[
    ['icon'=>'👤','label'=>'Solo forever',    'sub'=>'Just me',            'vals'=>['team'=>1]],
    ['icon'=>'🤝','label'=>'Maybe 1–2 hires','sub'=>'Tiny team',           'vals'=>['team'=>2]],
    ['icon'=>'👥','label'=>'Small team 3–10','sub'=>'Startup squad',       'vals'=>['team'=>3]],
    ['icon'=>'🏢','label'=>'Scale a company','sub'=>'Aiming for 10+ people','vals'=>['team'=>4]],
  ]],
];
$questionsJson = json_encode($questions);
?>

<div class="container-sm" style="padding-top:56px;padding-bottom:80px">

  <!-- Progress -->
  <div class="progress-wrap" id="quiz-progress">
    <div class="progress-meta">
      <span class="step-counter" style="font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--purple)">Step 1 of <?= count($questions) ?></span>
      <span class="pct-label"   style="font-size:.75rem;color:var(--text3)">14%</span>
    </div>
    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="14">
      <div class="progress-fill" style="width:14%"></div>
    </div>
  </div>

  <!-- Question area -->
  <div class="fade-in" id="quiz-card">
    <h2 id="quiz-question" style="font-size:clamp(1.3rem,3vw,1.8rem);margin-bottom:8px"></h2>
    <p  id="quiz-hint"     style="font-size:.9rem;color:var(--text3);margin-bottom:28px"></p>
    <div class="quiz-options" id="quiz-options" role="radiogroup" aria-labelledby="quiz-question"></div>
  </div>

  <!-- Navigation -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:32px">
    <button class="btn btn-ghost" id="btn-back" onclick="Quiz.back()" style="visibility:hidden">← Back</button>
    <button class="btn btn-primary" id="btn-next" onclick="Quiz.next()" disabled>Continue →</button>
  </div>
</div>

<script>
const BP = window.BASE_PATH || '';
  const Quiz = {
  step: 0,
  answers: {},
  questions: <?= $questionsJson ?>,

  init() { this.render(); },

  render() {
    const q     = this.questions[this.step];
    const total = this.questions.length;
    const pct   = Math.round(((this.step + 1) / total) * 100);

    document.querySelector('.progress-fill').style.width = pct + '%';
    document.querySelector('.progress-fill').closest('[role=progressbar]').setAttribute('aria-valuenow', pct);
    document.querySelector('.step-counter').textContent = `Step ${this.step + 1} of ${total}`;
    document.querySelector('.pct-label').textContent    = pct + '%';
    document.getElementById('quiz-question').textContent = q.question;
    document.getElementById('quiz-hint').textContent     = q.hint || '';

    const optWrap = document.getElementById('quiz-options');
    optWrap.innerHTML = q.options.map((o, i) => `
      <label class="option-card ${this.answers[this.step] === i ? 'selected' : ''}" data-idx="${i}" tabindex="0" role="radio" aria-checked="${this.answers[this.step] === i}">
        <input type="radio" name="q${this.step}" value="${i}" ${this.answers[this.step] === i ? 'checked' : ''} style="display:none">
        <span class="option-icon">${o.icon}</span>
        <span>
          <span class="option-label">${o.label}</span><br>
          <span class="option-sub">${o.sub || ''}</span>
        </span>
      </label>
    `).join('');

    optWrap.querySelectorAll('.option-card').forEach(card => {
      const activate = () => {
        this.answers[this.step] = +card.dataset.idx;
        optWrap.querySelectorAll('.option-card').forEach(c => { c.classList.remove('selected'); c.setAttribute('aria-checked','false'); });
        card.classList.add('selected'); card.setAttribute('aria-checked','true');
        document.getElementById('btn-next').disabled = false;
      };
      card.addEventListener('click', activate);
      card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') activate(); });
    });

    document.getElementById('btn-back').style.visibility = this.step === 0 ? 'hidden' : 'visible';
    document.getElementById('btn-next').disabled = this.answers[this.step] === undefined;
    document.getElementById('btn-next').textContent = this.step === total - 1 ? 'Get My Results →' : 'Continue →';
  },

  next() {
    if (this.answers[this.step] === undefined) return;
    if (this.step < this.questions.length - 1) {
      this.step++;
      // Animate transition
      const card = document.getElementById('quiz-card');
      card.style.opacity = '0'; card.style.transform = 'translateY(12px)';
      setTimeout(() => { this.render(); card.style.transition = 'all .35s ease'; card.style.opacity = '1'; card.style.transform = 'translateY(0)'; }, 180);
    } else { this.submit(); }
  },

  back() {
    if (this.step > 0) { this.step--; this.render(); }
  },

  async submit() {
    const btn = document.getElementById('btn-next');
    btn.disabled = true; btn.textContent = 'Analyzing your profile…';

    // Build profile vals
    const profile = {};
    this.questions.forEach((q, i) => {
      const idx = this.answers[i];
      if (idx !== undefined) Object.assign(profile, q.options[idx]?.vals || {});
    });

    const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
    try {
      const res  = await fetch(BP+'/api/recommend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ answers: this.answers, profile }),
      });
      const data = await res.json();
      if (data.success) {
        window.location.href = BP + '/results.php?id=' + data.result_uuid;
      } else {
        alert('Something went wrong. Please try again.');
        btn.disabled = false; btn.textContent = 'Get My Results →';
      }
    } catch(e) {
      alert('Network error. Please check your connection.');
      btn.disabled = false; btn.textContent = 'Get My Results →';
    }
  },
};

document.addEventListener('DOMContentLoaded', () => Quiz.init());
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
