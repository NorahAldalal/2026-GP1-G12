<?php
startSession();
$navRole = $_SESSION['user_role'] ?? '';
$navName = $_SESSION['user_name'] ?? '';
$active  = $activePage ?? '';
$initials = strtoupper(substr($navName, 0, 1) ?: 'U');

// Nav items config
$adminLinks = [
  ['href'=>'admin-home.php',    'key'=>'home',    'label'=>'Home',      'icon'=>'⌂'],
  ['href'=>'admin-status.php',  'key'=>'status',  'label'=>'Status',    'icon'=>'◉'],
  ['href'=>'admin-map.php',     'key'=>'map',     'label'=>'Map',       'icon'=>'◎'],
  ['href'=>'admin-users.php',   'key'=>'users',   'label'=>'Employees', 'icon'=>'◈'],
  ['href'=>'admin-reports.php', 'key'=>'reports', 'label'=>'Reports',   'icon'=>'◇'],
  ['href'=>'admin-analytics.php', 'key'=>'analytics', 'label'=>'analytics',   'icon'=>'⌂'],
];
$empLinks = [
  ['href'=>'employee-home.php',       'key'=>'home',    'label'=>'Home',       'icon'=>'⌂'],
  ['href'=>'employee-status.php',     'key'=>'status',  'label'=>'Status',     'icon'=>'◉'],
  ['href'=>'employee-map.php',        'key'=>'map',     'label'=>'Map',        'icon'=>'◎'],
  ['href'=>'employee-my-reports.php', 'key'=>'reports', 'label'=>'My Reports', 'icon'=>'◇'],
  ['href'=>'employee-analytics.php', 'key'=>'analytics', 'label'=>'analytics', 'icon'=>'⌂'],
];
$links = $navRole === 'admin' ? $adminLinks : $empLinks;
?>
<nav class="siraj-nav">
  <!-- Logo -->
  <a href="<?= $navRole==='admin' ? 'admin-home.php' : 'employee-home.php' ?>" class="brand">
<img src="assets/img/logo.png?v=3" alt="SIRAJ" class="nav-logo-img"/>
    <span class="brand-text">SIRAJ</span>
  </a>

  <!-- Center nav links (desktop) -->
  <div class="nav-links">
    <?php foreach ($links as $link): ?>
      <a href="<?= $link['href'] ?>"
         class="nav-btn <?= $active===$link['key'] ? 'active' : '' ?>"
         data-label="<?= $link['label'] ?>">
        <span class="nav-icon"><?= $link['icon'] ?></span>
        <span class="nav-label"><?= $link['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Right controls -->
  <div class="nav-right">
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
      <span class="t-dark">✦</span>
      <span class="t-light" style="display:none;">☀</span>
    </button>
    <a href="logout.php" class="nav-btn danger" title="Sign Out">
      <span class="nav-icon">→</span>
      <span class="nav-label">Sign Out</span>
    </a>
    <a href="profile.php"
       class="nav-avatar <?= $active==='profile' ? 'active' : '' ?>"
       title="<?= htmlspecialchars($navName) ?> · <?= ucfirst($navRole) ?>">
      <?= htmlspecialchars($initials) ?>
      <span class="role-dot <?= $navRole ?>"></span>
    </a>
  </div>
</nav>
