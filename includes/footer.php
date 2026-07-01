<?php
$in_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$root     = $in_admin ? '../' : '';
?>
<footer class="footer">
  <div class="footer-inner">

    <div class="footer-brand-block">
      <div class="footer-brand">LocalMarket</div>
      <div class="footer-tagline">Buy &amp; sell locally, simply.</div>
      <p class="footer-desc">
        A free community marketplace connecting buyers and sellers
        in the Paarl &amp; Western Cape region.
      </p>
    </div>

    <div class="footer-col">
      <div class="footer-col-title">Marketplace</div>
      <div class="footer-links">
        <a href="<?= $root ?>index.php">Browse Listings</a>
        <a href="<?= $root ?>create-listing.php">Post a Listing</a>
        <a href="<?= $root ?>orders.php">My Orders</a>
        <a href="<?= $root ?>messages.php">Messages</a>
      </div>
    </div>

    <div class="footer-col">
      <div class="footer-col-title">Company</div>
      <div class="footer-links">
        <a href="<?= $root ?>about.php">About Us</a>
        <a href="<?= $root ?>contact.php">Contact</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="<?= $root ?>admin/dashboard.php">Admin Panel</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="footer-col">
      <div class="footer-col-title">Contact Us</div>
      <div class="footer-links">
        <a href="mailto:support@localmarket.com">support@localmarket.com</a>
        <a href="tel:+27210000000">+27 21 000 0000</a>
        <span style="color:var(--gray-soft);font-size:13px;">Mon–Fri, 08:00–17:00 SAST</span>
        <span style="color:var(--gray-soft);font-size:13px;">📍 Paarl, Western Cape, SA</span>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> LocalMarket. All rights reserved.</span>
    <span>Made for the community, by the community.</span>
  </div>
</footer>