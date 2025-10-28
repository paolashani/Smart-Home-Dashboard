<?php
// public/api/auth.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/lib/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
  case 'register':
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !sanitize_email($email) || strlen($password) < 8) {
      json_response(['error'=>'Invalid fields (password >= 8)'], 422);
    }
    if (find_user_by_email($pdo, $email)) {
      json_response(['error'=>'Email already registered'], 409);
    }
    $userId = create_user($pdo, $name, $email, $password);
    $_SESSION['user'] = ['id'=>$userId, 'name'=>$name, 'email'=>$email];
    bootstrap_user_home($pdo, $userId);
    json_response(['ok'=>true]);
    break;

  case 'login':
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = find_user_by_email($pdo, $email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
      json_response(['error'=>'Invalid credentials'], 401);
    }
    $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email']];
    bootstrap_user_home($pdo, $user['id']);
    json_response(['ok'=>true, 'user'=>$_SESSION['user']]);
    break;

  case 'me':
    if (!isset($_SESSION['user'])) json_response(['user'=>null]);
    json_response(['user'=>$_SESSION['user']]);
    break;

  case 'logout':
    session_destroy();
    json_response(['ok'=>true]);
    break;

  default:
    json_response(['error'=>'Unknown action'], 400);
}
