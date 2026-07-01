<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($name) > 100) {
        $error = 'Name must be under 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $error = 'An account with that email already exists.';
            } else {
                $role   = str_ends_with(strtolower($email), '@admin.com') ? 'admin' : 'buyer';
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)"
                );
                $insert->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashed,
                    ':role'     => $role,
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id']   = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $role;

                header('Location: index.php');
                exit;
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
  <title>Register – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="auth-box">

      <div class="auth-header">
        <h1>Create account</h1>
        <p>Join LocalMarket and start buying or selling</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="register.php" method="POST">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="John Smith" maxlength="100" required/>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com" maxlength="150" required/>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Min. 6 characters" maxlength="255" required/>
        </div>
        <div class="form-group">
          <label for="confirm">Confirm Password</label>
          <input type="password" id="confirm" name="confirm" class="form-control"
                 placeholder="Repeat password" maxlength="255" required/>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Create Account</button>
      </form>

      <p class="auth-footer">
        Already have an account? <a href="login.php">Log in</a>
      </p>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>