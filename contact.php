<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$me         = $_SESSION['user_id'];
$listing_id = (int)($_GET['listing'] ?? $_POST['listing_id'] ?? 0);
$user_id    = (int)($_GET['user']    ?? $_POST['user_id']    ?? 0);
$error      = '';
$submitted  = false;

$listing = null;
$target_user = null;

if ($listing_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, user_id FROM listings WHERE id = :id");
        $stmt->execute([':id' => $listing_id]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if (!$listing && !$target_user) {
    header('Location: index.php');
    exit;
}

if ($listing && (int)$listing['user_id'] === $me) {
    header('Location: product.php?id=' . $listing_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        $error = 'Please select or enter a reason.';
    } else {
        try {
            $ins = $pdo->prepare("
                INSERT INTO reports (reporter_id, reported_listing_id, reported_user_id, reason)
                VALUES (:reporter, :listing, :user, :reason)
            ");
            $ins->execute([
                ':reporter' => $me,
                ':listing'  => $listing_id ?: null,
                ':user'     => $user_id    ?: null,
                ':reason'   => $reason,
            ]);
            $submitted = true;
        } catch (Exception $e) {
            $error = 'Could not submit report. Please try again.';
        }
    }
}

$reasons = [
    'Spam or duplicate listing',
    'Misleading or false description',
    'Prohibited or illegal item',
    'Offensive content',
    'Suspected scam',
    'Inappropriate images',
    'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Report – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="listing-form-box">

      <h1 style="margin-bottom:4px;">Submit a Report</h1>
      <p class="text-muted mb-3">Reports are reviewed by our admin team.</p>

      <?php if ($submitted): ?>
        <div class="alert alert-success">
          ✅ Your report has been submitted. Thank you — our team will review it shortly.
        </div>
        <a href="index.php" class="btn btn-outline btn-full mt-2">Back to Listings</a>
      <?php else: ?>

        <?php if ($error !== ''): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="report-context">
          <?php if ($listing): ?>
            <div class="report-context-label">Reporting listing:</div>
            <div class="report-context-value">
              <a href="product.php?id=<?= $listing['id'] ?>">
                <?= htmlspecialchars($listing['title']) ?>
              </a>
            </div>
          <?php elseif ($target_user): ?>
            <div class="report-context-label">Reporting user:</div>
            <div class="report-context-value"><?= htmlspecialchars($target_user['name']) ?></div>
          <?php endif; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="listing_id" value="<?= $listing_id ?>"/>
          <input type="hidden" name="user_id"    value="<?= $user_id ?>"/>

          <div class="form-group">
            <label>Reason for report <span class="text-muted">*</span></label>
            <div class="report-reasons">
              <?php foreach ($reasons as $r): ?>
                <label class="report-reason-item">
                  <input type="radio" name="reason" value="<?= htmlspecialchars($r) ?>"
                         <?= ($_POST['reason'] ?? '') === $r ? 'checked' : '' ?>/>
                  <?= htmlspecialchars($r) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="display:flex;gap:10px;margin-top:8px;">
            <button type="submit" name="submit_report" class="btn btn-primary" style="flex:1;">
              Submit Report
            </button>
            <?php if ($listing_id): ?>
              <a href="product.php?id=<?= $listing_id ?>" class="btn btn-outline">Cancel</a>
            <?php else: ?>
              <a href="index.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>