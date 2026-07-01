<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$step    = $_GET['step'] ?? 'request';
$error   = '';
$success = '';

if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                $_SESSION['reset_token']   = $token;
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_expires'] = $expires;

                $reset_link = 'http://localhost/ITECA/forgot-password.php?step=reset&token=' . $token;
                $_SESSION['reset_link_debug'] = $reset_link;
            }

            $success = 'If that email is registered, a reset link has been generated.';

        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

if ($step === 'reset') {
    $token = $_GET['token'] ?? '';

    if (
        empty($_SESSION['reset_token']) ||
        !hash_equals($_SESSION['reset_token'], $token) ||
        !isset($_SESSION['reset_expires']) ||
        strtotime($_SESSION['reset_expires']) < time()
    ) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
        $step  = 'expired';
    }
}

if ($step === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd    = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $upd->execute([
                ':password' => $hashed,
                ':id'       => $_SESSION['reset_user_id'],
            ]);

            unset($_SESSION['reset_token'], $_SESSION['reset_user_id'],
                  $_SESSION['reset_expires'], $_SESSION['reset_link_debug']);

            $success = 'Password updated successfully. You can now log in.';
            $step    = 'done';
        } catch (Exception $e) {
            $error = 'Could not update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="auth-box">

      <?php if ($step === 'request'): ?>

        <div class="auth-header">
          <h1>Forgot Password</h1>
          <p>Enter your email and we'll generate a reset link.</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>

          <?php if (!empty($_SESSION['reset_link_debug'])): ?>
            <div class="alert alert-info" style="margin-top:8px;font-size:13px;">
              <strong>Localhost mode:</strong> In production this link would be emailed.
              For now, click it directly:<br/><br/>
              <a href="<?= htmlspecialchars($_SESSION['reset_link_debug']) ?>">
                <?= htmlspecialchars($_SESSION['reset_link_debug']) ?>
              </a>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <form action="forgot-password.php?step=request" method="POST">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-control"
                     placeholder="you@example.com" maxlength="150" required/>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
          </form>
        <?php endif; ?>

        <p class="auth-footer"><a href="login.php">← Back to Login</a></p>

      <?php elseif ($step === 'reset'): ?>

        <div class="auth-header">
          <h1>Reset Password</h1>
          <p>Enter your new password below.</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="forgot-password.php?step=reset&token=<?= htmlspecialchars($_GET['token'] ?? '') ?>"
              method="POST">
          <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Min. 6 characters" maxlength="255" required/>
          </div>
          <div class="form-group">
            <label for="confirm">Confirm Password</label>
            <input type="password" id="confirm" name="confirm" class="form-control"
                   placeholder="Repeat password" maxlength="255" required/>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Update Password</button>
        </form>

      <?php elseif ($step === 'done'): ?>

        <div class="auth-header">
          <h1>Password Updated</h1>
        </div>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn btn-primary btn-full mt-2">Log In Now</a>

      <?php elseif ($step === 'expired'): ?>

        <div class="auth-header">
          <h1>Link Expired</h1>
        </div>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <a href="forgot-password.php" class="btn btn-outline btn-full mt-2">
          Request a New Link
        </a>

      <?php endif; ?>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>