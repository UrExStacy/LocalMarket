<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$error   = '';
$success = '';
$categories = ['Electronics','Clothing','Furniture','Books','Vehicles','Garden','Toys','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $image_name  = '';

    if ($title === '' || $description === '' || $price === '' || $category === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Please enter a valid price.';
    } elseif (!in_array($category, $categories)) {
        $error = 'Invalid category selected.';
    } else {
  
        if (!empty($_FILES['image']['name'])) {
            $allowed_types = ['image/jpeg'=>'jpg','image/png'=>'png',
                              'image/webp'=>'webp','image/gif'=>'gif'];
            $file_size     = $_FILES['image']['size'];
            $max_size      = 5 * 1024 * 1024;

            $image_info = @getimagesize($_FILES['image']['tmp_name']);
            $real_mime  = $image_info ? $image_info['mime'] : '';

            if (!isset($allowed_types[$real_mime])) {
                $error = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
            } elseif ($file_size > $max_size) {
                $error = 'Image must be under 5MB.';
            } else {
                $ext        = $allowed_types[$real_mime];
                $image_name = uniqid('img_', true) . '.' . $ext;
                $upload_dir = 'images/products/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                    $error      = 'Image upload failed. Please try again.';
                    $image_name = '';
                }
            }
        }

        if ($error === '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO listings (user_id, title, description, price, category, image, location, status)
                    VALUES (:user_id, :title, :description, :price, :category, :image, :location, 'active')
                ");
                $stmt->execute([
                    ':user_id'     => $_SESSION['user_id'],
                    ':title'       => $title,
                    ':description' => $description,
                    ':price'       => $price,
                    ':category'    => $category,
                    ':image'       => $image_name,
                    ':location'    => $location,
                ]);
                $new_id = $pdo->lastInsertId();
                header('Location: product.php?id=' . $new_id . '&posted=1');
                exit;
            } catch (Exception $e) {
                $error = 'Could not save listing. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Post a Listing – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="listing-form-box">

      <div style="margin-bottom:24px;">
        <h1>Post a Listing</h1>
        <p class="text-muted">Fill in the details below to list your item for sale.</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="create-listing.php" method="POST" enctype="multipart/form-data">

        <div class="form-group">
          <label for="title">Title <span class="text-muted">*</span></label>
          <input type="text" id="title" name="title" class="form-control"
                 value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                 placeholder="e.g. Samsung 55 inch TV" required/>
        </div>

        <div class="form-group">
          <label for="category">Category <span class="text-muted">*</span></label>
          <select id="category" name="category" class="form-control" required>
            <option value="">— Select a category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"
                <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                <?= $cat ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="price">Price (R) <span class="text-muted">*</span></label>
          <input type="number" id="price" name="price" class="form-control"
                 value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                 placeholder="0.00" min="0" step="0.01" required/>
          <div id="price-preview" style="font-size:13px;color:var(--gray-soft);margin-top:4px;height:16px;"></div>
        </div>

        <div class="form-group">
          <label for="description">Description <span class="text-muted">*</span></label>
          <textarea id="description" name="description" class="form-control"
                    rows="5" data-maxlength="1000"
                    placeholder="Describe your item — condition, size, any extras included…"
                    required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" class="form-control"
                 value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
                 placeholder="e.g. Cape Town, Paarl"/>
        </div>

        <div class="form-group">
          <label for="image">Photo</label>
          <div class="image-upload-area" id="upload-area">
            <input type="file" id="image" name="image" accept="image/*"
                   style="display:none" onchange="previewImage(this)"/>
            <div id="upload-placeholder" onclick="document.getElementById('image').click()">
              <div style="font-size:2rem;">📷</div>
              <div style="font-weight:500; margin-top:6px;">Click to upload a photo</div>
              <div class="text-muted" style="font-size:13px;">JPG, PNG, WEBP — max 5MB</div>
            </div>
            <img id="image-preview" src="" alt="Preview"
                 style="display:none; max-height:200px; border-radius:6px; margin:0 auto;"/>
          </div>
          <button type="button" id="change-photo-btn"
                  style="display:none; margin-top:8px;"
                  class="btn btn-outline btn-sm"
                  onclick="document.getElementById('image').click()">
            Change Photo
          </button>
        </div>

        <div style="display:flex; gap:10px; margin-top:10px;">
          <button type="submit" class="btn btn-primary" style="flex:1;">Post Listing</button>
          <a href="index.php" class="btn btn-outline">Cancel</a>
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
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            changeBtn.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<script src="js/script.js"></script>
</body>
</html>