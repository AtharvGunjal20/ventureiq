<?php
// ============================================================
//  VentureIQ – Recommendation Engine API
//  POST /api/recommend.php
// ============================================================
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['success'=>false,'error'=>'Method not allowed'], 405); }

// CSRF — sent as X-CSRF-Token header by JS
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfHeader)) {
    json_out(['success'=>false,'error'=>'Invalid CSRF token'], 403);
}

if (!rate_check(client_ip(), 'quiz')) { json_out(['success'=>false,'error'=>'Rate limit exceeded'], 429); }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['profile'])) { json_out(['success'=>false,'error'=>'Invalid payload'], 400); }

$profile = $body['profile'];

// ── Weighted Scoring Engine ─────────────────────────────────
$dims    = ['capital','risk_score','tech','time_avail','passive','creative','team'];
$weights = ['capital'=>1.2,'risk_score'=>1.3,'tech'=>1.0,'time_avail'=>0.9,'passive'=>1.1,'creative'=>0.8,'team'=>0.7];

$models = DB::all('SELECT * FROM business_models WHERE is_active=1 ORDER BY sort_order');

$scores = [];
foreach ($models as $model) {
    $raw = 0; $max = 0;
    foreach ($dims as $dim) {
        $w     = $weights[$dim] ?? 1.0;
        $diff  = abs(($profile[$dim] ?? 3) - ($model['score_'.$dim] ?? 3));
        $score = max(0, 5 - $diff) * $w;
        $raw  += $score;
        $max  += 5 * $w;
    }
    $confidence = $max > 0 ? (int) round(($raw / $max) * 100) : 0;
    $scores[]   = ['model' => $model, 'confidence' => $confidence];
}

usort($scores, fn($a,$b) => $b['confidence'] <=> $a['confidence']);
$top = $scores[0];

$allScores = array_map(fn($s) => [
    'id'         => $s['model']['id'],
    'name'       => $s['model']['name'],
    'confidence' => $s['confidence']
], $scores);

$userId   = auth_user()['id'] ?? null;
$guestTok = $userId ? null : bin2hex(random_bytes(16));

$resultId = DB::insert(
    'INSERT INTO quiz_results (user_id,guest_token,answers,profile_scores,top_model_id,confidence_pct,all_scores,ip_address) VALUES (?,?,?,?,?,?,?,?)',
    [
        $userId, $guestTok,
        json_encode($body['answers'] ?? []),
        json_encode($profile),
        $top['model']['id'],
        $top['confidence'],
        json_encode($allScores),
        client_ip(),
    ]
);

$result = DB::one('SELECT uuid FROM quiz_results WHERE id=?', [$resultId]);
audit('quiz.complete', $resultId, 'quiz_result', "model={$top['model']['slug']},confidence={$top['confidence']}");

json_out([
    'success'     => true,
    'result_uuid' => $result['uuid'],
    'top'         => ['name'=>$top['model']['name'],'confidence'=>$top['confidence']],
]);
