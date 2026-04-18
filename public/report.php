<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/helpers.php';
session_secure_start();

$uuid = clean($_GET['id'] ?? '');
if (!$uuid) redirect('/quiz.php');

redirect('/results.php?id=' . urlencode($uuid));