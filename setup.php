<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>SIRAJ — Create Admin Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Lato',sans-serif;background:#060C18;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
    .card{background:#132030;border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:48px 44px;width:100%;max-width:480px;box-shadow:0 32px 80px rgba(0,0,0,.5);}
    .logo{text-align:center;margin-bottom:32px;}
    .logo-star{font-size:32px;color:#7EC8E3;display:block;margin-bottom:8px;}
    .logo-text{font-family:'Cinzel',serif;font-size:22px;font-weight:700;color:#7EC8E3;letter-spacing:4px;}
    h1{font-family:'Cinzel',serif;font-size:20px;color:white;margin-bottom:6px;text-align:center;}
    .subtitle{font-size:13px;color:rgba(255,255,255,.4);text-align:center;margin-bottom:32px;}
    .warning{background:rgba(245,197,66,.1);border:1px solid rgba(245,197,66,.3);border-radius:10px;padding:12px 16px;font-size:13px;color:#c8a800;margin-bottom:24px;line-height:1.6;}
    .form-group{margin-bottom:18px;}
    label{display:block;font-size:13px;font-weight:700;color:rgba(255,255,255,.7);margin-bottom:7px;}
    input{width:100%;padding:12px 16px;border-radius:10px;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:white;font-size:14px;font-family:'Lato',sans-serif;outline:none;transition:border-color .2s;}
    input::placeholder{color:rgba(255,255,255,.3);}
    input:focus{border-color:#4A90B8;box-shadow:0 0 0 3px rgba(74,144,184,.2);}
    .btn{width:100%;padding:14px;border-radius:12px;border:none;background:#4A90B8;color:white;font-size:16px;font-weight:700;font-family:'Lato',sans-serif;cursor:pointer;transition:all .25s;margin-top:8px;}
    .btn:hover{background:#3a7da8;transform:translateY(-1px);}
    .alert{padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;line-height:1.6;}
    .alert-success{background:rgba(76,175,125,.15);border:1px solid rgba(76,175,125,.35);color:#4CAF7D;}
    .alert-error{background:rgba(224,92,92,.12);border:1px solid rgba(224,92,92,.3);color:#E05C5C;}
    .login-link{display:block;text-align:center;margin-top:20px;color:#4A90B8;font-size:14px;font-weight:700;text-decoration:none;}
    .login-link:hover{color:#7EC8E3;}
    .delete-note{margin-top:24px;padding:12px 16px;background:rgba(224,92,92,.08);border:1px solid rgba(224,92,92,.2);border-radius:10px;font-size:12px;color:rgba(224,92,92,.8);text-align:center;line-height:1.6;}
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/db.php';

$success = false;
$error   = '';
$created = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            // Check if email already exists
            $chk = db()->prepare('SELECT UserID, Role FROM `User` WHERE Email = ?');
            $chk->execute([$email]);
            $existing = $chk->fetch();

            if ($existing) {
                // Update existing account to admin
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                db()->prepare('UPDATE `User` SET UserName=?, Password=?, Role="admin" WHERE Email=?')
                   ->execute([$name, $hashed, $email]);
                $success = true;
                $created = false; // updated
            } else {
                // Create new admin
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                db()->prepare('INSERT INTO `User` (UserName, Email, Password, Role) VALUES (?, ?, ?, "admin")')
                   ->execute([$name, $email, $hashed]);
                $success = true;
                $created = true;
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="card">
  <div class="logo">
    <span class="logo-star">✦</span>
    <span class="logo-text">SIRAJ</span>
  </div>

  <h1>Create Admin Account</h1>
  <p class="subtitle">One-time setup — run this once then delete the file</p>

  <?php if ($success): ?>
    <div class="alert alert-success">
      ✅ <?= $created ? 'Admin account created successfully!' : 'Account updated to Admin successfully!' ?><br><br>
      <strong>Email:</strong> <?= htmlspecialchars($_POST['email']) ?><br>
      <strong>Role:</strong> Admin<br><br>
      You can now log in. <strong>Delete this file immediately!</strong>
    </div>
    <a href="login.php" class="login-link">→ Go to Login Page</a>

  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="warning">
      ⚠️ This page creates an Admin account in your database.<br>
      <strong>Delete this file after use!</strong>
    </div>

    <form method="POST">
      <div class="form-group">
        <label>Your Name</label>
        <input type="text" name="name"
               placeholder="e.g. Norah Al-Dlal"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>Your Email</label>
        <input type="email" name="email"
               placeholder="norahaldlal@gmail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>Choose a Password (min 6 chars)</label>
        <input type="password" name="password" placeholder="••••••••" required/>
      </div>
      <button type="submit" class="btn">✓ Create Admin Account</button>
    </form>

    <div class="delete-note">
      🗑️ Delete <strong>setup.php</strong> from your server immediately after creating your account.
    </div>

  <?php endif; ?>
</div>
</body>
</html>
