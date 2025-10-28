<?php
// app/lib/auth.php

function find_user_by_email(PDO $pdo, $email) {
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  return $stmt->fetch();
}

function create_user(PDO $pdo, $name, $email, $password) {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
  $stmt->execute([$name, $email, $hash]);
  return $pdo->lastInsertId();
}

function bootstrap_user_home(PDO $pdo, $userId) {
  // Create default home & rooms & sample devices if none exists
  $stmt = $pdo->prepare('SELECT h.id FROM homes h JOIN home_members m ON m.home_id = h.id WHERE m.user_id = ? LIMIT 1');
  $stmt->execute([$userId]);
  $home = $stmt->fetch();
  if ($home) return $home['id'];

  // Create home
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO homes (name, timezone, owner_id) VALUES (?, ?, ?)');
    $stmt->execute(["My Home", "Europe/Athens", $userId]);
    $homeId = (int)$pdo->lastInsertId();

    // Owner membership
    $pdo->prepare('INSERT INTO home_members (home_id, user_id, role) VALUES (?,?,?)')
        ->execute([$homeId, $userId, 'OWNER']);

    // Rooms
    $rooms = ["Living Room", "Bedroom", "Kitchen"];
    $roomIds = [];
    $stmtR = $pdo->prepare('INSERT INTO rooms (home_id, name) VALUES (?, ?)');
    foreach ($rooms as $r) {
      $stmtR->execute([$homeId, $r]);
      $roomIds[$r] = (int)$pdo->lastInsertId();
    }

    // Devices
    $stmtD = $pdo->prepare('INSERT INTO devices (room_id, name, type, online, last_seen_at) VALUES (?,?,?,?,NOW())');
    $stmtD->execute([$roomIds["Living Room"], "Main Light", "LIGHT", 1]);
    $lid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)')->execute([$lid, json_encode(['on'=>true,'brightness'=>70])]);

    $stmtD->execute([$roomIds["Living Room"], "Thermostat", "THERMOSTAT", 1]);
    $tid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)')->execute([$tid, json_encode(['mode'=>'cool','target'=>24,'current'=>26])]);

    $stmtD->execute([$roomIds["Bedroom"], "Bed Light", "LIGHT", 1]);
    $bid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)')->execute([$bid, json_encode(['on'=>false,'brightness'=>0])]);

    $stmtD->execute([$roomIds["Bedroom"], "Blinds", "BLIND", 1]);
    $blid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)')->execute([$blid, json_encode(['position'=>50])]);

    $stmtD->execute([$roomIds["Kitchen"], "Temp Sensor", "SENSOR_TEMP", 1]);
    $kid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO device_state (device_id, state_json) VALUES (?, ?)')->execute([$kid, json_encode(['current'=>23.0,'unit'=>'C'])]);

    $pdo->commit();
    return $homeId;
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}
