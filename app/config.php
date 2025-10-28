<?php
// app/config.php
date_default_timezone_set('Europe/Athens');

$envPath = __DIR__ . '/../.env.php';
if (file_exists($envPath)) {
  $ENV = require $envPath;
} else {
  $ENV = require __DIR__ . '/../.env.sample.php';
}

define('APP_ENV', $ENV['APP_ENV'] ?? 'dev');
define('APP_TZ',  $ENV['APP_TIMEZONE'] ?? 'Europe/Athens');

// Database (MySQL PDO)
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
  $ENV['DB_HOST'] ?? '127.0.0.1',
  $ENV['DB_PORT'] ?? '3306',
  $ENV['DB_NAME'] ?? 'smart_home'
);

try {
  $pdo = new PDO($dsn, $ENV['DB_USER'] ?? 'root', $ENV['DB_PASS'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  die('DB connection error: ' . $e->getMessage());
}

session_name($ENV['SESSION_NAME'] ?? 'smarthome_sess');
session_start();

function json_response($data, $status=200) {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function require_login() {
  if (!isset($_SESSION['user'])) {
    json_response(['error' => 'Unauthorized'], 401);
  }
}

function current_user_id() {
  return $_SESSION['user']['id'] ?? null;
}

function sanitize_email($email) {
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}
