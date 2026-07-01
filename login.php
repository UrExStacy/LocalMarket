<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$error = '';

$ip          = md5($_SERVER['REMOTE_ADDR']);
$attempt_key = 'login_attempts_' . $ip;
$lockout_key = 'login_lockout_'  . $ip;

$is_locked = isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > time();

if ($is_locked) {
    $wait  = ceil(($_SESSION[$lockout_key] - time()) / 60);
    $error = "Too many failed attempts. Please wait {$wait} minute(s) before trying again.";
}

if (!$is_locked && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                unset($_SESSION[$attempt_key], $_SESSION[$lockout_key]);
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: index.php');
                exit;
            } else {
                $_SESSION[$attempt_key] = ($_SESSION[$attempt_key] ?? 0) + 1;

                if ($_SESSION[$attempt_key] >= 5) {
                    $_SESSION[$lockout_key]  = time() + (15 * 60);
                    $_SESSION[$attempt_key]  = 0;
                    $error = 'Too many failed attempts. You are locked out for 15 minutes.';
                } else {
                    $remaining = 5 - $_SESSION[$attempt_key];
                    $error     = "Incorrect email or password. {$remaining} attempt(s) remaining.";
                }
            }
        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Log In – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="auth-box">

      <div class="auth-header">
        <h1>Welcome back</h1>
        <p>Log in to your LocalMarket account</p>
      </div>

      <?php if (isset($_GET['timeout'])): ?>
        <div class="alert alert-info">Your session expired. Please log in again.</div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com"
                 maxlength="150" required/>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••" maxlength="255" required/>
        </div>
        <button type="submit" class="btn btn-primary btn-full"
                <?= $is_locked ? 'disabled' : '' ?>>
          Log In
        </button>
      </form>

      <p class="auth-footer" style="margin-top:12px;">
        <a href="forgot-password.php">Forgot your password?</a>
      </p>
      <p class="auth-footer">
        Don't have an account? <a href="register.php">Register</a>
      </p>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>