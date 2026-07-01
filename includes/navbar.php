<?php
if (!isset($_SESSION)) session_start();

// Check for unread messages if logged in
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        // db.php may already be included — only include if $pdo not set
        if (!isset($pdo)) {
            $in_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
            require_once ($in_admin ? '../' : '') . 'includes/db.php';
        }
        $u = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :id AND is_read = 0");
        $u->execute([':id' => $_SESSION['user_id']]);
        $unread_count = (int)$u->fetchColumn();
    } catch (Exception $e) {
        $unread_count = 0;
    }
}

$in_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$root     = $in_admin ? '../' : '';
?>
<nav class="navbar">
  <div class="navbar-inner">

    <a href="<?= $root ?>index.php" class="navbar-logo">Local<span>Market</span></a>

    <form class="navbar-search" action="<?= $root ?>index.php" method="GET">
      <input type="text" name="q"
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             placeholder="Search listings…"/>
      <button type="submit">Go</button>
    </form>

    <div class="navbar-links">
      <?php if (isset($_SESSION['user_id'])): ?>

        <?php if ($_SESSION['user_role'] === 'admin'): ?>
          <a href="<?= $in_admin ? '' : 'admin/' ?>dashboard.php" class="btn-nav-admin">⚙ Admin</a>
        <?php endif; ?>

        <a href="<?= $root ?>messages.php" class="nav-messages-link">
          💬 Messages
          <span class="nav-unread" id="nav-unread-badge" style="<?= $unread_count > 0 ? '' : 'display:none' ?>"><?= $unread_count ?></span>
        </a>

        <a href="<?= $root ?>orders.php">📦 Orders</a>
        <a href="<?= $root ?>account.php">
          👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
        </a>

        <a href="<?= $root ?>create-listing.php" class="btn-nav-sell">+ Sell</a>
        <a href="<?= $root ?>logout.php">Log out</a>

      <?php else: ?>
        <a href="<?= $root ?>login.php">Log in</a>
        <a href="<?= $root ?>register.php" class="btn-nav-sell">Register</a>
      <?php endif; ?>
    </div>

  </div>
</nav>