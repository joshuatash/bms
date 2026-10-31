<?php
require __DIR__ . '/../config.php';
$u = current_user();
json_out($u ? ['loggedIn' => true, 'name' => $u['name'], 'role' => $u['role'], 'home' => home_for($u['role'])] : ['loggedIn' => false]);
