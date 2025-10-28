<?php
// app/lib/rbac.php

function user_homes(PDO $pdo, $userId) {
  $stmt = $pdo->prepare('SELECT h.*, m.role FROM homes h JOIN home_members m ON m.home_id = h.id WHERE m.user_id = ?');
  $stmt->execute([$userId]);
  return $stmt->fetchAll();
}

function assert_home_access(PDO $pdo, $userId, $homeId, $minRole = null) {
  $stmt = $pdo->prepare('SELECT role FROM home_members WHERE home_id = ? AND user_id = ?');
  $stmt->execute([$homeId, $userId]);
  $row = $stmt->fetch();
  if (!$row) {
    json_response(['error'=>'Forbidden'], 403);
  }
  if ($minRole) {
    $order = ['GUEST'=>0,'MEMBER'=>1,'ADMIN'=>2,'OWNER'=>3];
    if ($order[$row['role']] < $order[$minRole]) {
      json_response(['error'=>'Insufficient role'], 403);
    }
  }
}
