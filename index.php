<?php
session_start();
$logged_in = isset($_SESSION['user_id']);
$listings  = [];
$total     = 0;

require_once 'includes/db.php';

$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

$categories = ['Electronics','Clothing','Furniture','Books','Vehicles','Garden','Toys','Other'];

$allowed_sorts = [
    'newest'     => 'l.created_at DESC',
    'oldest'     => 'l.created_at ASC',
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
];
$order_by = $allowed_sorts[$sort] ?? 'l.created_at DESC';

try {
    $where  = "WHERE l.status = 'active'";
    $params = [];

    if ($search !== '') {
        $where .= " AND (l.title LIKE :search OR l.description LIKE :search2)";
        $params[':search']  = "%$search%";
        $params[':search2'] = "%$search%";
    }
    if ($category !== '') {
        $where .= " AND l.category = :category";
        $params[':category'] = $category;
    }

    $cstmt = $pdo->prepare("SELECT COUNT(*) FROM listings l $where");
    $cstmt->execute($params);
    $total = (int)$cstmt->fetchColumn();

    $sql  = "SELECT l.*, u.name AS seller_name FROM listings l JOIN users u ON l.user_id = u.id $where ORDER BY $order_by LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listings = [];
}

$total_pages = ceil($total / $per_page);

function build_url($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '');
    return 'index.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>LocalMarket – Buy &amp; Sell Locally</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<?php if ($search === '' && $category === '' && $page === 1): ?>
<section class="hero">
  <h1>Buy &amp; Sell <span>Locally</span></h1>
  <p>Find great deals from people in your area.</p>
</section>
<?php endif; ?>

<main>
  <div class="container">

    <form class="browse-bar" action="index.php" method="GET">
      <?php if ($category !== ''): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"/>
      <?php endif; ?>
      <input type="text" name="q"
             value="<?= htmlspecialchars($search) ?>"
             placeholder="Search listings…"/>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($search !== ''): ?>
        <a href="<?= build_url(['q' => '', 'page' => 1]) ?>"
           class="btn btn-outline btn-sm">Clear</a>
      <?php endif; ?>
    </form>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success mb-2">🗑 Listing deleted successfully.</div>
    <?php endif; ?>

    <div class="categories">
      <a class="category-pill <?= $category === '' ? 'active' : '' ?>" href="index.php<?= $search !== '' ? '?q='.urlencode($search) : '' ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="category-pill <?= $category === $cat ? 'active' : '' ?>"
           href="<?= build_url(['category' => $cat, 'page' => 1]) ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="section-header">
      <h2>
        <?php if ($search !== ''): ?>
          Results for "<?= htmlspecialchars($search) ?>"
          <span class="text-muted" style="font-size:.9rem;">(<?= $total ?>)</span>
        <?php elseif ($category !== ''): ?>
          <?= htmlspecialchars($category) ?>
          <span class="text-muted" style="font-size:.9rem;">(<?= $total ?>)</span>
        <?php else: ?>
          Recent Listings
        <?php endif; ?>
      </h2>
      <select class="form-control" style="width:auto;padding:6px 30px 6px 10px;"
              onchange="window.location=this.value">
        <option value="<?= build_url(['sort'=>'newest','page'=>1]) ?>" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
        <option value="<?= build_url(['sort'=>'oldest','page'=>1]) ?>" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        <option value="<?= build_url(['sort'=>'price_asc','page'=>1]) ?>" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low–High</option>
        <option value="<?= build_url(['sort'=>'price_desc','page'=>1]) ?>" <?= $sort==='price_desc'?'selected':'' ?>>Price: High–Low</option>
      </select>
    </div>

    <?php if (empty($listings)): ?>
      <div class="alert alert-info">
        No listings found.
        <?php if ($search !== '' || $category !== ''): ?>
          <a href="index.php">Clear filters</a>
        <?php else: ?>
          <a href="create-listing.php">Be the first to post one!</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($listings as $item): ?>
          <a href="product.php?id=<?= (int)$item['id'] ?>" class="product-card">
            <?php if (!empty($item['image'])): ?>
              <img class="product-card-img"
                   src="images/products/<?= htmlspecialchars($item['image']) ?>"
                   alt="<?= htmlspecialchars($item['title']) ?>"/>
            <?php else: ?>
              <div class="product-card-img-placeholder">No Image</div>
            <?php endif; ?>
            <div class="product-card-body">
              <div class="product-card-title"><?= htmlspecialchars($item['title']) ?></div>
              <div class="product-card-price">R<?= number_format($item['price'], 2) ?></div>
              <div class="product-card-location"><?= htmlspecialchars($item['location'] ?? '') ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="<?= build_url(['page' => $page - 1]) ?>" class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="<?= build_url(['page' => $p]) ?>"
               class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <a href="<?= build_url(['page' => $page + 1]) ?>" class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($search === '' && $category === ''): ?>
    <div class="card mt-3 text-center" style="padding:40px 20px;">
      <h2 style="margin-bottom:10px;">Got something to sell?</h2>
      <p class="mb-2">List it for free and reach buyers in your area.</p>
      <?php if ($logged_in): ?>
        <a href="create-listing.php" class="btn btn-primary">Post a Listing</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <p class="text-muted mt-1" style="font-size:13px;">
          Already have an account? <a href="login.php">Log in</a>
        </p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>