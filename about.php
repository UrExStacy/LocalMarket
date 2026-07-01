<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="static-page-box">

      <h1>About LocalMarket</h1>
      <p class="text-muted mb-3">Connecting the Paarl community since 2024</p>

      <p>
        LocalMarket is a free, community-driven buy-and-sell platform built for
        people in the Paarl and greater Western Cape area. We make it simple to
        turn unwanted items into cash, or find exactly what you need from someone
        nearby — no shipping delays, no middlemen, no fees.
      </p>
      <p style="margin-top:12px;">
        Whether you're selling a second-hand bicycle, looking for affordable
        furniture, or clearing out your garage, LocalMarket is the place to do it.
        Every listing goes live instantly and reaches real buyers in your area.
      </p>

      <hr class="divider"/>

      <h2>Our Mission</h2>
      <p>
        We believe sustainable communities are built on sharing resources locally.
        Our mission is to reduce waste, support everyday people in earning extra
        income, and keep money circulating within our community rather than going
        to large corporations. LocalMarket is free to use and always will be.
      </p>

      <hr class="divider"/>

      <h2>How It Works</h2>
      <div class="how-it-works">
        <div class="how-step">
          <div class="how-icon">📝</div>
          <h3>1. Post a Listing</h3>
          <p>Create a free account, upload a photo, set your price, and your item is live in under a minute.</p>
        </div>
        <div class="how-step">
          <div class="how-icon">💬</div>
          <h3>2. Chat Instantly</h3>
          <p>Buyers message you directly through our real-time chat — no phone number needed.</p>
        </div>
        <div class="how-step">
          <div class="how-icon">🤝</div>
          <h3>3. Meet &amp; Sell</h3>
          <p>Agree on a meetup spot, hand over the item, and done. Simple as that.</p>
        </div>
      </div>

      <hr class="divider"/>

      <h2>Why LocalMarket?</h2>
      <ul class="about-list">
        <li>✅ 100% free to list — no commissions or selling fees ever</li>
        <li>✅ Real-time messaging between buyers and sellers</li>
        <li>✅ Browse by category or search across all listings</li>
        <li>✅ Manage your listings, orders and messages in one place</li>
        <li>✅ Trusted admin team reviewing reports and keeping the platform safe</li>
        <li>✅ Built for the local community — not a faceless corporation</li>
      </ul>

      <hr class="divider"/>

      <h2>By the Numbers</h2>
      <div class="about-stats">
        <div class="about-stat">
          <div class="about-stat-number">500+</div>
          <div class="about-stat-label">Active Listings</div>
        </div>
        <div class="about-stat">
          <div class="about-stat-number">1,200+</div>
          <div class="about-stat-label">Registered Users</div>
        </div>
        <div class="about-stat">
          <div class="about-stat-number">850+</div>
          <div class="about-stat-label">Successful Sales</div>
        </div>
        <div class="about-stat">
          <div class="about-stat-number">4.8★</div>
          <div class="about-stat-label">Average Rating</div>
        </div>
      </div>

      <hr class="divider"/>

      <h2>Meet the Team</h2>
      <div class="team-grid">
        <div class="team-card">
          <div class="team-avatar">👨‍💼</div>
          <div class="team-name">James van der Berg</div>
          <div class="team-role">Founder &amp; CEO</div>
          <p class="team-bio">Born and raised in Paarl. Started LocalMarket to help his neighbours sell unwanted goods without driving to a flea market.</p>
        </div>
        <div class="team-card">
          <div class="team-avatar">👩‍💻</div>
          <div class="team-name">Lerato Mokoena</div>
          <div class="team-role">Lead Developer</div>
          <p class="team-bio">Full-stack developer with a passion for building tools that make everyday life easier for real people.</p>
        </div>
        <div class="team-card">
          <div class="team-avatar">👩‍🎨</div>
          <div class="team-name">Anri Joubert</div>
          <div class="team-role">Design &amp; UX</div>
          <p class="team-bio">Keeps the platform looking clean and feeling intuitive. Believes good design should be invisible.</p>
        </div>
      </div>

      <hr class="divider"/>
      <p class="text-muted" style="font-size:14px;">
        Have questions? Visit our <a href="contact.php">Contact page</a> — we reply within one business day.
      </p>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>