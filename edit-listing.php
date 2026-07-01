<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$user_id    = $_SESSION['user_id'];
$listing_id = (int)($_GET['id'] ?? 0);
$error      = '';
$categories = ['Electronics','Clothing','Furniture','Books','Vehicles','Garden','Toys','Other'];

if ($listing_id === 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = :id");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listing = null;
}

if (!$listing || (int)$listing['user_id'] !== $user_id) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $status      = $_POST['status'] ?? 'active';
    $image_name  = $listing['image'];

    if ($title === '' || $description === '' || $price === '' || $category === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Please enter a valid price.';
    } elseif (!in_array($category, $categories)) {
        $error = 'Invalid category.';
    } elseif (!in_array($status, ['active', 'sold'])) {
        $error = 'Invalid status.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $allowed    = ['image/jpeg'=>'jpg','image/png'=>'png',
                           'image/webp'=>'webp','image/gif'=>'gif'];
            $image_info = @getimagesize($_FILES['image']['tmp_name']);
            $real_mime  = $image_info ? $image_info['mime'] : '';
            if (!isset($allowed[$real_mime])) {
                $error = 'Only JPG, PNG, WEBP or GIF images allowed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $ext        = $allowed[$real_mime];
                $new_name   = uniqid('img_', true) . '.' . $ext;
                $upload_dir = 'images/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    if (!empty($listing['image']) && file_exists($upload_dir . $listing['image'])) {
                        unlink($upload_dir . $listing['image']);
                    }
                    $image_name = $new_name;
                } else {
                    $error = 'Image upload failed.';
                }
            }
        }

        if (isset($_POST['remove_image']) && empty($_FILES['image']['name'])) {
            $upload_dir = 'images/products/';
            if (!empty($listing['image']) && file_exists($upload_dir . $listing['image'])) {
                unlink($upload_dir . $listing['image']);
            }
            $image_name = '';
        }

        if ($error === '') {
            try {
                $upd = $pdo->prepare("
                    UPDATE listings
                    SET title=:title, description=:description, price=:price,
                        category=:category, location=:location, status=:status, image=:image
                    WHERE id=:id AND user_id=:user_id
                ");
                $upd->execute([
                    ':title'       => $title,
                    ':description' => $description,
                    ':price'       => $price,
                    ':category'    => $category,
                    ':location'    => $location,
                    ':status'      => $status,
                    ':image'       => $image_name,
                    ':id'          => $listing_id,
                    ':user_id'     => $user_id,
                ]);
                header('Location: product.php?id=' . $listing_id . '&updated=1');
                exit;
            } catch (Exception $e) {
                $error = 'Could not save changes. Please try again.';
            }
        }
    }

    $listing = array_merge($listing, [
        'title'       => $title,
        'description' => $description,
        'price'       => $price,
        'category'    => $category,
        'location'    => $location,
        'status'      => $status,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Listing – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="listing-form-box">

      <div style="margin-bottom:24px;">
        <a href="product.php?id=<?= $listing_id ?>" class="btn btn-outline btn-sm" style="margin-bottom:12px;">
          ← Back to Listing
        </a>
        <h1>Edit Listing</h1>
        <p class="text-muted">Update your listing details below.</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="edit-listing.php?id=<?= $listing_id ?>" method="POST" enctype="multipart/form-data">

        <div class="form-group">
          <label for="title">Title <span class="text-muted">*</span></label>
          <input type="text" id="title" name="title" class="form-control"
                 value="<?= htmlspecialchars($listing['title']) ?>" required/>
        </div>

        <div class="form-group">
          <label for="category">Category <span class="text-muted">*</span></label>
          <select id="category" name="category" class="form-control" required>
            <option value="">— Select a category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= $listing['category'] === $cat ? 'selected' : '' ?>>
                <?= $cat ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="price">Price (R) <span class="text-muted">*</span></label>
          <input type="number" id="price" name="price" class="form-control"
                 value="<?= htmlspecialchars($listing['price']) ?>"
                 min="0" step="0.01" required/>
          <div id="price-preview" style="font-size:13px;color:var(--gray-soft);margin-top:4px;height:16px;"></div>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status" class="form-control">
            <option value="active" <?= $listing['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="sold"   <?= $listing['status'] === 'sold'   ? 'selected' : '' ?>>Sold</option>
          </select>
        </div>

        <div class="form-group">
          <label for="description">Description <span class="text-muted">*</span></label>
          <textarea id="description" name="description" class="form-control"
                    rows="5" data-maxlength="1000" required><?= htmlspecialchars($listing['description']) ?></textarea>
        </div>

        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" class="form-control"
                 value="<?= htmlspecialchars($listing['location'] ?? '') ?>"
                 placeholder="e.g. Cape Town, Paarl"/>
        </div>

        <div class="form-group">
          <label>Photo</label>
          <?php if (!empty($listing['image'])): ?>
            <div style="margin-bottom:10px;">
              <img src="images/products/<?= htmlspecialchars($listing['image']) ?>"
                   style="max-height:160px; border-radius:var(--radius); display:block; margin-bottom:8px;"/>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" name="remove_image" value="1"/>
                Remove current photo
              </label>
            </div>
          <?php endif; ?>
          <div class="image-upload-area" id="upload-area">
            <input type="file" id="image" name="image" accept="image/*"
                   style="display:none" onchange="previewImage(this)"/>
            <div id="upload-placeholder" onclick="document.getElementById('image').click()">
              <div style="font-size:1.8rem;">📷</div>
              <div style="font-weight:500;margin-top:6px;">
                <?= !empty($listing['image']) ? 'Upload a new photo to replace' : 'Click to upload a photo' ?>
              </div>
              <div class="text-muted" style="font-size:13px;">JPG, PNG, WEBP — max 5MB</div>
            </div>
            <img id="image-preview" src="" alt="Preview"
                 style="display:none;max-height:160px;border-radius:6px;margin:0 auto;"/>
          </div>
          <button type="button" id="change-photo-btn" style="display:none;margin-top:8px;"
                  class="btn btn-outline btn-sm"
                  onclick="document.getElementById('image').click()">
            Change Photo
          </button>
        </div>

        <div style="display:flex;gap:10px;margin-top:10px;">
          <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
          <a href="product.php?id=<?= $listing_id ?>" class="btn btn-outline">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script>
function previewImage(input) {
    const preview     = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const changeBtn   = document.getElementById('change-photo-btn');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src          = e.target.result;
            preview.style.display    = 'block';
            placeholder.style.display = 'none';
            changeBtn.style.display  = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<script src="js/script.js"></script>
</body>
</html>