<?php
require __DIR__ . '/config.php';
if (current_user()) {
    log_activity('logout', 'Signed out');
    end_login_session();
}
session_destroy();
header('Location: index.html');
