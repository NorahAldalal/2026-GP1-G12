<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireLogin();
$activePage = 'profile';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPw  = $_POST['current_password']  ?? '';
    $newPw      = $_POST['new_password']       ?? '';
    $confirmPw  = $_POST['confirm_password']   ?? '';

    if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
        $error = 'All fields are required.';
    } else {
        // Verify current password
        $stmt = db()->prepare('SELECT Password FROM `User` WHERE UserID = ?');
        $stmt->execute([currentUserId()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPw, $user['Password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPw) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPw)) {
            $error = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $newPw)) {
            $error = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $newPw)) {
            $error = 'New password must contain at least one number.';
        } elseif ($newPw !== $confirmPw) {
            $error = 'New passwords do not match.';
        } elseif ($currentPw === $newPw) {
            $error = 'New password must be different from the current password.';
        } else {
            $hashed = password_hash($newPw, PASSWORD_BCRYPT);
            db()->prepare('UPDATE `User` SET Password = ? WHERE UserID = ?')
               ->execute([$hashed, currentUserId()]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Change Password — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <style>
    .change-pw-wrap {
      max-width: 520px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .change-pw-card {
      background: white;
      border-radius: var(--radius-xl);
      padding: 44px 40px;
      box-shadow: var(--shadow-md);
    }
    .card-icon {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: rgba(74,144,184,.1);
      border: 2px solid rgba(74,144,184,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 30px;
      margin: 0 auto 20px;
    }
    .card-title {
      font-family: 'Cinzel', serif;
      font-size: 24px; font-weight: 700;
      color: var(--primary);
      text-align: center; margin-bottom: 6px;
    }
    .card-sub {
      font-size: 13px; color: var(--secondary);
      text-align: center; margin-bottom: 32px;
      line-height: 1.6;
    }
    /* Strength bar */
    .pw-req-list {
      display: flex; flex-direction: column; gap: 6px;
      background: rgba(33,43,58,.04);
      border: 1px solid rgba(33,43,58,.08);
      border-radius: var(--radius-sm);
      padding: 14px 16px;
      margin-top: 8px;
    }
    .pw-req {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; color: var(--secondary);
      transition: color .2s;
    }
    .pw-req .req-icon {
      width: 16px; height: 16px; border-radius: 50%;
      background: rgba(33,43,58,.1);
      display: flex; align-items: center; justify-content: center;
      font-size: 9px; flex-shrink: 0;
      transition: all .2s;
    }
    .pw-req.met { color: var(--success); }
    .pw-req.met .req-icon { background: rgba(76,175,125,.2); color: var(--success); }

    .success-state { text-align: center; padding: 20px 0; }
    .success-icon  { font-size: 60px; margin-bottom: 18px; display: block; }
    .success-title {
      font-family: 'Cinzel', serif; font-size: 22px;
      color: var(--primary); margin-bottom: 10px;
    }
    .success-text  { font-size: 14px; color: var(--secondary); line-height: 1.7; margin-bottom: 28px; }
    .back-btn { margin-top: 24px; text-align: center; }
  </style>
</head>
<body class="dashboard-page">

<?php include 'includes/nav.php'; ?>

<div class="change-pw-wrap">

  <div class="change-pw-card">

    <?php if ($success): ?>
    <!-- ── Success State ── -->
    <div class="success-state">
      <span class="success-icon">✅</span>
      <div class="success-title">Password Changed!</div>
      <p class="success-text">
        Your password has been updated successfully.<br>
        Use your new password next time you log in.
      </p>
      <a href="profile.php" class="btn btn-primary" style="display:inline-flex;">← Back to Profile</a>
    </div>

    <?php else: ?>
    <!-- ── Change Password Form ── -->
    <div class="card-icon">🔑</div>
    <div class="card-title">Change Password</div>
    <p class="card-sub">Enter your current password, then choose a new one.</p>

    <?php if ($error): ?>
      <div class="alert alert-error visible" style="margin-bottom:20px;">
        ❌ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="change-pw-form" novalidate>

      <!-- Current Password -->
      <div class="form-group">
        <label class="form-label" for="current_password">Current Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password"
                 id="current_password" name="current_password"
                 placeholder="Enter your current password" required/>
          <span class="toggle-pw">👁</span>
        </div>
        <span class="field-error" id="current_password_error"></span>
      </div>

      <div style="height:1px;background:#f0f0f0;margin:24px 0;"></div>

      <!-- New Password -->
      <div class="form-group">
        <label class="form-label" for="new_password">New Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password"
                 id="new_password" name="new_password"
                 placeholder="Create a strong new password" required/>
          <span class="toggle-pw">👁</span>
        </div>
        <span class="field-error" id="new_password_error"></span>

        <!-- Requirements list -->
        <div class="pw-req-list" id="pw-reqs">
          <div class="pw-req" id="req-length">
            <span class="req-icon">○</span> At least 8 characters
          </div>
          <div class="pw-req" id="req-upper">
            <span class="req-icon">○</span> One uppercase letter (A-Z)
          </div>
          <div class="pw-req" id="req-lower">
            <span class="req-icon">○</span> One lowercase letter (a-z)
          </div>
          <div class="pw-req" id="req-number">
            <span class="req-icon">○</span> One number (0-9)
          </div>
          <div class="pw-req" id="req-diff">
            <span class="req-icon">○</span> Different from current password
          </div>
        </div>
      </div>

      <!-- Confirm New Password -->
      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm New Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password"
                 id="confirm_password" name="confirm_password"
                 placeholder="Repeat your new password" required/>
          <span class="toggle-pw">👁</span>
        </div>
        <span class="field-error" id="confirm_password_error"></span>
        <div id="match-indicator" style="font-size:12px;margin-top:6px;display:none;"></div>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:12px;margin-top:8px;">
        <button type="submit" class="btn btn-primary" style="flex:1;" id="submit-btn">
          🔒 Update Password
        </button>
        <a href="profile.php" class="btn btn-outline" style="flex:1;text-align:center;">
          Cancel
        </a>
      </div>

    </form>
    <?php endif; ?>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
const newPwInput  = document.getElementById('new_password');
const confPwInput = document.getElementById('confirm_password');
const currPwInput = document.getElementById('current_password');
const matchInd    = document.getElementById('match-indicator');

// Toggle requirements visibility on focus
newPwInput?.addEventListener('focus', () => {
  document.getElementById('pw-reqs').style.display = 'flex';
});

// Live requirement check
newPwInput?.addEventListener('input', function() {
  const pw   = this.value;
  const curr = currPwInput?.value || '';

  const checks = {
    'req-length': pw.length >= 8,
    'req-upper':  /[A-Z]/.test(pw),
    'req-lower':  /[a-z]/.test(pw),
    'req-number': /[0-9]/.test(pw),
    'req-diff':   pw.length > 0 && pw !== curr,
  };

  Object.entries(checks).forEach(([id, met]) => {
    const el   = document.getElementById(id);
    const icon = el?.querySelector('.req-icon');
    if (el)   el.classList.toggle('met', met);
    if (icon) icon.textContent = met ? '✓' : '○';
  });

  checkMatch();
});

// Re-check "different" when current password changes
currPwInput?.addEventListener('input', function() {
  newPwInput?.dispatchEvent(new Event('input'));
});

// Match indicator
confPwInput?.addEventListener('input', checkMatch);

function checkMatch() {
  const pw   = newPwInput?.value  || '';
  const conf = confPwInput?.value || '';
  if (!matchInd || !conf) return;
  matchInd.style.display = 'block';
  if (pw === conf) {
    matchInd.style.color   = 'var(--success)';
    matchInd.textContent   = '✓ Passwords match';
  } else {
    matchInd.style.color   = 'var(--danger)';
    matchInd.textContent   = '✕ Passwords do not match';
  }
}

// Form submit validation
document.getElementById('change-pw-form')?.addEventListener('submit', function(e) {
  let valid = true;
  clearFieldError('current_password');
  clearFieldError('new_password');
  clearFieldError('confirm_password');

  const curr = currPwInput?.value || '';
  const pw   = newPwInput?.value  || '';
  const conf = confPwInput?.value || '';

  if (!curr) { showFieldError('current_password', 'Please enter your current password.'); valid = false; }
  if (!pw)   { showFieldError('new_password', 'Please enter a new password.');           valid = false; }
  else if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw)) {
    showFieldError('new_password', 'Password does not meet all requirements.'); valid = false;
  }
  if (pw && conf && pw !== conf) {
    showFieldError('confirm_password', 'Passwords do not match.'); valid = false;
  }

  if (!valid) { e.preventDefault(); return; }

  const btn = document.getElementById('submit-btn');
  btn.innerHTML = '<span class="spinner"></span> Updating…';
  btn.disabled  = true;
});
</script>
</body>
</html>
