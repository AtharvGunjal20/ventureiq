<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
if (!defined('BASE_PATH')) require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();
require_admin();

$id  = (int)($_GET['id'] ?? 0);
$data = json_decode(file_get_contents('php://input'), true);

if (!$id || !isset($data['active'])) { json_out(['success'=>false,'message'=>'Invalid request'], 400); }
$active = $data['active'] ? 1 : 0;
DB::run('UPDATE users SET is_active=? WHERE id=?', [$active, $id]);
audit('admin.user.toggle', $id, 'user', "active=$active");
json_out(['success'=>true,'message'=>'Status updated']);
