<?php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token() {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid or expired request. Please go back and try again.');
    }
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(generate_csrf_token()) . '"/>';
}

function require_login($redirect = 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_role($role, $redirect = 'index.php') {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ' . $redirect);
        exit;
    }
}