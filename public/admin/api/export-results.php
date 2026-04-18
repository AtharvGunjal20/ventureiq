<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

audit('admin.export.results', null, 'system');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ventureiq-results-'.date('Y-m-d').'.csv"');

$rows = DB::all(
    'SELECT r.uuid, r.confidence_pct, r.created_at, r.ip_address,
            m.name AS model, m.risk_level, m.roi_label,
            u.email, u.first_name, u.last_name
     FROM quiz_results r
     JOIN business_models m ON m.id=r.top_model_id
     LEFT JOIN users u ON u.id=r.user_id
     ORDER BY r.created_at DESC'
);

$out = fopen('php://output', 'w');
fputcsv($out, ['UUID','Email','Name','Top Model','Confidence %','Risk','ROI','IP','Date']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['uuid'],
        $r['email']      ?? 'Guest',
        trim(($r['first_name']??'').' '.($r['last_name']??'')),
        $r['model'],
        $r['confidence_pct'],
        $r['risk_level'],
        $r['roi_label'],
        $r['ip_address'],
        $r['created_at'],
    ]);
}
fclose($out);
