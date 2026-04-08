<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireLogin();
$activePage = 'profile';
$userId = currentUserId();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_profile'])) {
    $name  = trim($_POST['username'] ?? '');
    $email = trim($_POST['email']    ?? '');
    if (empty($name)||empty($email)) { $msg='All fields required.'; $msgType='error'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $msg='Invalid email.'; $msgType='error'; }
    else {
        $chk=db()->prepare('SELECT UserID FROM `User` WHERE Email=? AND UserID!=?'); $chk->execute([$email,$userId]);
        if ($chk->fetch()) { $msg='Email already used.'; $msgType='error'; }
        else {
            db()->prepare('UPDATE `User` SET UserName=?,Email=? WHERE UserID=?')->execute([$name,$email,$userId]);
            $_SESSION['user_name']=$name; $_SESSION['user_email']=$email;
            $msg='Profile updated!';
        }
    }
}

$stmt=db()->prepare('SELECT u.*, a.AreaName FROM `User` u LEFT JOIN Area a ON u.AreaID=a.AreaID WHERE u.UserID=?');
$stmt->execute([$userId]); $user=$stmt->fetch();
$isAdmin = $_SESSION['user_role']==='admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Profile — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>
<div class="profile-layout">
  <div class="profile-card">
    <div class="profile-avatar"><?= $isAdmin ? '🛡️' : '👷' ?></div>
    <div class="profile-name"><?= htmlspecialchars($user['UserName']) ?></div>
    <div class="profile-email"><?= htmlspecialchars($user['Email']) ?></div>
    <div style="margin:16px 0;"><span class="nav-role-badge <?= $user['Role'] ?>" style="font-size:13px;padding:5px 14px;"><?= ucfirst($user['Role']) ?></span></div>
    <?php if (!$isAdmin && $user['AreaName']): ?>
      <div style="background:rgba(74,144,184,.08);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:var(--secondary);margin-bottom:16px;">
        📍 Assigned: <strong style="color:var(--primary);"><?= htmlspecialchars($user['AreaName']) ?></strong>
      </div>
    <?php endif; ?>
    <?php if ($user['EmployeeCode']): ?>
      <div style="font-size:12px;color:var(--secondary);margin-bottom:16px;">Employee Code: <strong><?= htmlspecialchars($user['EmployeeCode']) ?></strong></div>
    <?php endif; ?>
    <div style="font-size:12px;color:var(--secondary);">Member since <?= date('F Y',strtotime($user['CreatedAt'])) ?></div>
    <div style="margin-top:20px;width:100%;"><a href="logout.php" class="btn btn-outline btn-full" style="color:var(--danger);border-color:var(--danger);">⏻ Log Out</a></div>
  </div>

  <div class="profile-info-card">
    <div class="profile-section-title">Account Information</div>
    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?> visible" style="margin-bottom:16px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="POST" id="profile-form">
      <div class="profile-field"><span class="profile-field-label">Username</span><span class="profile-field-value" id="disp-name"><?= htmlspecialchars($user['UserName']) ?></span><input class="form-input" type="text" name="username" id="inp-name" value="<?= htmlspecialchars($user['UserName']) ?>" style="display:none;"/></div>
      <div class="profile-field"><span class="profile-field-label">Email</span><span class="profile-field-value" id="disp-email"><?= htmlspecialchars($user['Email']) ?></span><input class="form-input" type="email" name="email" id="inp-email" value="<?= htmlspecialchars($user['Email']) ?>" style="display:none;"/></div>
      <div class="profile-field"><span class="profile-field-label">Password</span><span class="profile-field-value">••••••••••</span></div>
      <div class="profile-field" style="border:none;"><span class="profile-field-label">Role</span><span class="profile-field-value"><span class="nav-role-badge <?= $user['Role'] ?>"><?= ucfirst($user['Role']) ?></span></span></div>
      <div style="display:flex;gap:10px;margin-top:20px;" id="view-actions">
        <button type="button" class="btn btn-primary" id="edit-btn">✏️ Edit Profile</button>
        <a href="change-password.php" class="btn btn-outline">🔑 Change Password</a>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px;display:none;" id="edit-actions">
        <button type="submit" name="save_profile" class="btn btn-accent">Save Changes</button>
        <button type="button" class="btn btn-outline" id="cancel-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
document.getElementById('edit-btn')?.addEventListener('click',function(){
  ['disp-name','disp-email'].forEach(id=>document.getElementById(id).style.display='none');
  ['inp-name','inp-email'].forEach(id=>document.getElementById(id).style.display='block');
  document.getElementById('view-actions').style.display='none';
  document.getElementById('edit-actions').style.display='flex';
});
document.getElementById('cancel-btn')?.addEventListener('click',()=>location.reload());
</script>
</body>
</html>
