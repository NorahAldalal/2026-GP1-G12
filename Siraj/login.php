<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
startSession();

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['user_role'] ?? '') === 'admin' ? 'admin-home.php' : 'employee-home.php'));
    exit;
}

$error        = '';
$selectedRole = $_POST['role'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role']     ?? '';
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!in_array($role, ['admin','employee'])) {
        $error = 'Please select a login type.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = db()->prepare(
            'SELECT UserID, UserName, Password, Role, AreaID
             FROM `User` WHERE Email = ? AND Role = ?'
        );
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            // Regenerate session ID on login (session fixation prevention)
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['UserID'];
            $_SESSION['user_name']  = $user['UserName'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = $user['Role'];
            $_SESSION['user_area']  = $user['AreaID'];

            header('Location: ' . ($role === 'admin' ? 'admin-home.php' : 'employee-home.php'));
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>
// Apply saved theme before page renders (prevents flash)
(function(){
  if(localStorage.getItem('siraj_theme')==='light'){
    document.documentElement.classList.add('light-mode');
    document.addEventListener('DOMContentLoaded',function(){
      document.body.classList.add('light-mode');
    });
  }
})();
</script>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Log In — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/auth.css"/>
</head>
<body class="auth-page">
<div class="auth-wrapper">

  <!-- Form Panel -->
  <div class="auth-form-panel">
    <a href="index.php" class="back-home-btn">← Back to Home</a>
    <h1 class="auth-form-title">Log In</h1>
    <p class="auth-form-sub">Select your role and sign in to continue.</p>

    <?php if ($error): ?>
      <div class="alert alert-error visible"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="POST" id="login-form" novalidate>
      <!-- Role tabs -->
      <div class="role-tabs">
        <button type="button" class="role-tab admin-tab    <?= $selectedRole==='admin'    ?'active':'' ?>" onclick="selectRole('admin')">Admin</button>
        <button type="button" class="role-tab employee-tab <?= $selectedRole==='employee' ?'active':'' ?>" onclick="selectRole('employee')">Employee</button>
      </div>
      <input type="hidden" name="role" id="role-input" value="<?= htmlspecialchars($selectedRole) ?>"/>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email"
               placeholder="your@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
        <span class="field-error" id="email_error"></span>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Your password" required/>
          <span class="toggle-pw">👁</span>
        </div>
        <span class="field-error" id="password_error"></span>
      </div>

      <button type="submit" class="btn btn-accent btn-full" id="login-btn">Log In</button>
      <div class="auth-links" style="margin-top:14px;"><a href="forgot-password.php">Forgot your password?</a></div>
    </form>
  </div>

  <!-- Brand Panel -->
  <div class="auth-brand-panel">
    <canvas id="starfield" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"></canvas>
    <div class="auth-brand-logo" style="position:relative;z-index:1;">
      <img src="assets/img/logo.png" alt="SIRAJ" style="height:56px;filter:drop-shadow(0 0 14px rgba(126,200,227,0.5));margin-bottom:4px;display:block;margin-left:auto;margin-right:auto;"/>
      <span class="name">SIRAJ</span>
    </div>
    <p class="auth-brand-quote" style="position:relative;z-index:1;">"Where Technology Meets Sustainable Illumination"</p>
    <p class="auth-brand-tagline" style="position:relative;z-index:1;">Smart Street Lighting · Dark Sky Preservation</p>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
function selectRole(role) {
  document.getElementById('role-input').value = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  document.querySelector('.' + role + '-tab').classList.add('active');
}
document.getElementById('login-form').addEventListener('submit', function(e) {
  let v = true;
  clearFieldError('email'); clearFieldError('password');
  const em = document.getElementById('email').value.trim();
  const pw = document.getElementById('password').value;
  if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showFieldError('email','Enter a valid email.'); v=false; }
  if (!pw) { showFieldError('password','Password is required.'); v=false; }
  if (!v) { e.preventDefault(); return; }
  const btn = document.getElementById('login-btn');
  btn.innerHTML = '<span class="spinner"></span> Signing in…'; btn.disabled = true;
});
// Starfield for brand panel
(function(){
  const c=document.getElementById('starfield'); if(!c)return;
  const ctx=c.getContext('2d'); let W,H,stars=[];
  function resize(){W=c.width=c.offsetWidth;H=c.height=c.offsetHeight;stars=[];for(let i=0;i<120;i++)stars.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*1.3+.2,sp:Math.random()*.008+.002,ph:Math.random()*Math.PI*2});}
  window.addEventListener('resize',resize);resize();
  function draw(){ctx.clearRect(0,0,W,H);const t=Date.now()*.001;stars.forEach(s=>{const a=.15+.55*Math.sin(t*s.sp*10+s.ph);ctx.beginPath();ctx.arc(s.x,s.y,s.r,0,Math.PI*2);ctx.fillStyle=`rgba(255,255,255,${a})`;ctx.fill();});requestAnimationFrame(draw);}
  draw();
})();
</script>
</body>
</html>
