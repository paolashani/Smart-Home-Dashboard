<?php
// app/models/Devices.php

function latest_device_state(PDO $pdo, $deviceId) {
  $stmt = $pdo->prepare('SELECT state_json, created_at FROM device_state WHERE device_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
  $stmt->execute([$deviceId]);
  $row = $stmt->fetch();
  return $row ? json_decode($row['state_json'], true) : null;
}

function list_devices_for_user(PDO $pdo, $userId, $roomId = null) {
  $sql = 'SELECT d.*, r.name AS room_name, r.home_id FROM devices d 
          JOIN rooms r ON r.id = d.room_id 
          JOIN home_members m ON m.home_id = r.home_id 
          WHERE m.user_id = ?';
  $params = [$userId];
  if ($roomId) { $sql .= ' AND r.id = ?'; $params[] = $roomId; }
  $sql .= ' ORDER BY r.name, d.name';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
  foreach ($rows as &$row) {
    $row['state'] = latest_device_state($pdo, $row['id']);
  }
  return $rows;
}

function append_device_state(PDO $pdo, $deviceId, array $statePatch) {
  // Merge with last known state on PHP side
  $current = latest_device_state($pdo, $deviceId) ?? [];
  $merged = array_replace_recursive($current, $statePatch);
  $stmt = $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)');
  $stmt->execute([$deviceId, json_encode($merged)]);
  $pdo->prepare('UPDATE devices SET last_seen_at = NOW() WHERE id = ?')->execute([$deviceId]);
  return $merged;
}
