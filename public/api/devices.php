<?php
// public/api/devices.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/lib/rbac.php';
require_once __DIR__ . '/../../app/models/Devices.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
require_login();
$uid = current_user_id();

if ($action === 'list') {
  $roomId = isset($_GET['roomId']) ? (int)$_GET['roomId'] : null;
  $devices = list_devices_for_user($pdo, $uid, $roomId);
  json_response(['devices'=>$devices]);
}

if ($action === 'command') {
  // Expect POST JSON
  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input) json_response(['error'=>'Invalid JSON'], 400);
  $deviceId = (int)($input['deviceId'] ?? 0);
  $statePatch = $input['statePatch'] ?? null;
  if (!$deviceId || !is_array($statePatch)) json_response(['error'=>'Missing fields'], 422);

  // Ensure user has access to this device via home membership
  $stmt = $pdo->prepare('SELECT r.home_id FROM devices d JOIN rooms r ON r.id=d.room_id WHERE d.id=?');
  $stmt->execute([$deviceId]);
  $row = $stmt->fetch();
  if (!$row) json_response(['error'=>'Device not found'], 404);
  assert_home_access($pdo, $uid, $row['home_id'], 'MEMBER');

  $newState = append_device_state($pdo, $deviceId, $statePatch);
  // audit log
  $pdo->prepare('INSERT INTO audit_log (home_id, user_id, action, data_json) VALUES (?,?,?,?)')
      ->execute([$row['home_id'], $uid, 'device_command', json_encode(['deviceId'=>$deviceId,'patch'=>$statePatch])]);

  json_response(['ok'=>true, 'state'=>$newState]);
}

json_response(['error'=>'Unknown action'], 400);
