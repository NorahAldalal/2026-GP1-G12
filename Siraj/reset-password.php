<?php
require_once __DIR__ . '/config/db.php';
startSession();
if(empty($_SESSION['reset_verified'])||empty($_SESSION['reset_user_id'])){header('Location: forgot-password.php');exit;}
$error='';$success=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $pw=$_POST['password']??'';$cpw=$_POST['confirm_password']??'';
    if(empty($pw)||empty($cpw)){$error='Password cannot be empty.';}
    elseif(strlen($pw)<8||!preg_match('/[A-Z]/',$pw)||!preg_match('/[a-z]/',$pw)||!preg_match('/[0-9]/',$pw)){$error='Password must be 8+ chars with uppercase, lowercase, and a number.';}
    elseif($pw!==$cpw){$error='Passwords do not match.';}
    else{
        db()->prepare('UPDATE User SET Password=? WHERE UserID=?')->execute([password_hash($pw,PASSWORD_BCRYPT),$_SESSION['reset_user_id']]);
        db()->prepare('UPDATE PasswordReset SET Used=1 WHERE UserID=?')->execute([$_SESSION['reset_user_id']]);
        foreach(['reset_verified','reset_user_id','reset_email','reset_sent_at']as$k)unset($_SESSION[$k]);
        $success=true;
    }
}
?><!DOCTYPE html><html lang="en"><head>
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
</script><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Reset Password — SIRAJ</title><link rel="stylesheet" href="assets/css/global.css"/><link rel="stylesheet" href="assets/css/auth.css"/></head>
<body style="background:var(--dark-bg);min-height:100vh;display:flex;flex-direction:column;">
<nav class="siraj-nav" style="position:relative;"><a href="login.php" class="brand"><span class="brand-star">+</span><span class="brand-text">SIRAJ</span></a></nav>
<div style="flex:1;display:flex;align-items:center;justify-content:center;padding:60px 20px;">
<div style="background:var(--form-bg);border-radius:24px;padding:48px 44px;width:100%;max-width:460px;box-shadow:0 8px 40px rgba(0,0,0,.1);">
<?php if($success):?>
<div style="text-align:center;"><div style="font-size:56px;margin-bottom:16px;">OK</div>
<h2 style="font-family:Cinzel,serif;font-size:22px;color:var(--primary);margin-bottom:10px;">Password Updated!</h2>
<p style="font-size:14px;color:var(--secondary);margin-bottom:28px;">Your password has been reset. You can now log in.</p>
<a href="login.php" class="btn btn-primary">Go to Login</a></div>
<?php else:?>
<h1 style="font-family:Cinzel,serif;font-size:26px;font-weight:700;color:var(--primary);text-align:center;margin-bottom:24px;">New Password</h1>
<?php if($error):?><div class="alert alert-error visible"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="POST">
<div class="form-group"><label class="form-label">New Password</label>
<div class="input-wrapper"><input class="form-input" type="password" id="pw" name="password" placeholder="Min 8 chars, uppercase, number" required/><span class="toggle-pw">o</span></div></div>
<div class="form-group"><label class="form-label">Confirm Password</label>
<div class="input-wrapper"><input class="form-input" type="password" id="cpw" name="confirm_password" placeholder="Repeat password" required/><span class="toggle-pw">o</span></div>
<div id="match-ind" style="font-size:12px;margin-top:4px;display:none;"></div></div>
<button type="submit" class="btn btn-primary btn-full">Confirm Password</button>
</form><?php endif;?>
</div></div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
document.getElementById('cpw')?.addEventListener('input',function(){
  const p=document.getElementById('pw').value,c=this.value,d=document.getElementById('match-ind');
  d.style.display='block';d.style.color=p===c?'var(--success)':'var(--danger)';d.textContent=p===c?'Passwords match':'Do not match';
});
</script></body></html>
