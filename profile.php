<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireLogin();
$activePage = 'profile';
$userId  = currentUserId();
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$msg     = '';
$msgType = 'success';

// ── Save profile ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name  = trim($_POST['username']  ?? '');
    $email = trim($_POST['email']     ?? '');
    if (empty($name) || empty($email)) {
        $msg = 'All fields are required.'; $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email address.'; $msgType = 'error';
    } else {
        $chk = db()->prepare($isAdmin
            ? 'SELECT AdminID FROM `admin` WHERE Email=? AND AdminID!=?'
            : 'SELECT EmployeeID FROM `employee` WHERE Email=? AND EmployeeID!=?'
        );
        $chk->execute([$email, $userId]);
        if ($chk->fetch()) {
            $msg = 'This email is already in use.'; $msgType = 'error';
        } else {
            if ($isAdmin) {
                db()->prepare('UPDATE `admin` SET AdminName=?, Email=? WHERE AdminID=?')
                   ->execute([$name, $email, $userId]);
            } else {
                db()->prepare('UPDATE `employee` SET EmployeeName=?, Email=? WHERE EmployeeID=?')
                   ->execute([$name, $email, $userId]);
            }
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $msg = 'Profile updated successfully!';
        }
    }
}

// ── Fetch user ────────────────────────────────────────────
if ($isAdmin) {
    $stmt = db()->prepare('
        SELECT AdminID AS UserID, AdminName AS UserName, Email, Password,
               AdminCode AS UserCode, NULL AS AreaID, NULL AS AreaName, CreatedAt
        FROM `admin` WHERE AdminID = ?
    ');
} else {
    $stmt = db()->prepare('
        SELECT e.EmployeeID AS UserID, e.EmployeeName AS UserName, e.Email, e.Password,
               e.EmployeeCode AS UserCode, e.AreaID, a.AreaName, e.CreatedAt
        FROM `employee` e
        LEFT JOIN `area` a ON e.AreaID = a.AreaID
        WHERE e.EmployeeID = ?
    ');
}
$stmt->execute([$userId]);
$user = $stmt->fetch();
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
<div style="flex:1;display:flex;flex-direction:column;">
<div class="profile-layout">

  <!-- Left card -->
  <div class="profile-card">
    <div class="profile-avatar"><?= $isAdmin ? '🛡️' : '👷' ?></div>
    <div class="profile-name"><?= htmlspecialchars($user['UserName']) ?></div>
    <div class="profile-email"><?= htmlspecialchars($user['Email']) ?></div>
    <div style="margin:14px 0;">
      <span class="nav-role-badge <?= $_SESSION['user_role'] ?>" style="font-size:13px;padding:5px 14px;">
        <?= $isAdmin ? 'Admin' : 'Employee' ?>
      </span>
    </div>
    <?php if (!$isAdmin && $user['AreaName']): ?>
      <div style="background:rgba(74,144,184,.08);padding:10px 14px;font-size:13px;color:var(--text-muted);margin-bottom:12px;">
        Assigned: <strong style="color:var(--glow-soft);"><?= htmlspecialchars($user['AreaName']) ?></strong>
      </div>
    <?php endif; ?>
    <?php if ($user['UserCode']): ?>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">
        <?= $isAdmin ? 'Admin' : 'Employee' ?> Code: <strong style="color:var(--text-main);"><?= htmlspecialchars($user['UserCode']) ?></strong>
      </div>
    <?php endif; ?>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">
      Member since <?= date('F Y', strtotime($user['CreatedAt'])) ?>
    </div>
    <a href="logout.php" class="btn btn-outline btn-full" style="color:var(--danger);border-color:var(--danger);">Log Out</a>
  </div>

  <!-- Right card -->
  <div class="profile-info-card">
    <div class="profile-section-title">Account Information</div>
    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?> visible" style="margin-bottom:16px;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST" id="profile-form" novalidate>

      <div class="profile-field">
        <span class="profile-field-label">Username</span>
        <span class="profile-field-value disp-field" id="disp-name"><?= htmlspecialchars($user['UserName']) ?></span>
        <input class="form-input edit-field" type="text" name="username" id="inp-name" value="<?= htmlspecialchars($user['UserName']) ?>" style="display:none;" required/>
      </div>

      <div class="profile-field">
        <span class="profile-field-label">Email</span>
        <span class="profile-field-value disp-field" id="disp-email"><?= htmlspecialchars($user['Email']) ?></span>
        <input class="form-input edit-field" type="email" name="email" id="inp-email" value="<?= htmlspecialchars($user['Email']) ?>" style="display:none;" required/>
      </div>

      <div class="profile-field">
        <span class="profile-field-label"><?= $isAdmin ? 'Admin Code' : 'Employee Code' ?></span>
        <span class="profile-field-value">
          <?= $user['UserCode'] ? htmlspecialchars($user['UserCode']) : '<span style="color:var(--text-muted);font-size:13px;">Not set</span>' ?>
        </span>
      </div>

      <div class="profile-field">
        <span class="profile-field-label">Password</span>
        <span class="profile-field-value">••••••••••</span>
      </div>

      <div class="profile-field" style="border:none;">
        <span class="profile-field-label">Role</span>
        <span class="profile-field-value">
          <span class="nav-role-badge <?= $_SESSION['user_role'] ?>"><?= $isAdmin ? 'Admin' : 'Employee' ?></span>
        </span>
      </div>

      <div style="display:flex;gap:12px;margin-top:24px;" id="view-actions">
        <button type="button" class="btn btn-primary" id="edit-btn">Edit Profile</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('pw-modal').classList.add('open')">Change Password</button>
      </div>
      <div style="display:none;gap:12px;margin-top:24px;" id="edit-actions">
        <button type="submit" name="save_profile" class="btn btn-accent">Save Changes</button>
        <button type="button" class="btn btn-outline" id="cancel-btn">Cancel</button>
      </div>

    </form>
  </div>

</div>
</div>

<!-- ── Change Password Modal ─────────────────────────────── -->
<div class="modal-overlay" id="pw-modal">
  <div class="modal-box" style="max-width:460px;">
    <button class="modal-close" onclick="closePwModal()">✕</button>
    <div class="modal-title">Change Password</div>
    <p class="modal-sub">Verify your current password, then choose a new one.</p>

    <div id="pw-success" style="display:none;text-align:center;padding:16px 0;">
      <div style="font-size:48px;margin-bottom:12px;">✅</div>
      <div style="font-family:Cinzel,serif;font-size:18px;color:var(--glow-soft);margin-bottom:8px;">Password Changed!</div>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">Your password has been updated.</p>
      <button class="btn btn-primary" onclick="closePwModal()">Close</button>
    </div>

    <div id="pw-form-wrap">
      <div class="alert alert-error" id="pw-error" style="margin-bottom:16px;"></div>

      <div class="form-group">
        <label class="form-label">Current Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password" id="cpw-current" placeholder="Enter current password"/>
          <span class="toggle-pw">👁</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password" id="cpw-new" placeholder="Min 8 chars, uppercase, number"/>
          <span class="toggle-pw">👁</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <div class="input-wrapper">
          <input class="form-input" type="password" id="cpw-confirm" placeholder="Repeat new password"/>
          <span class="toggle-pw">👁</span>
        </div>
        <div id="cpw-match" style="font-size:12px;margin-top:5px;display:none;"></div>
      </div>

      <div style="display:flex;gap:10px;margin-top:8px;">
        <button class="btn btn-accent" style="flex:1;" id="cpw-submit-btn" onclick="submitPasswordChange()">Update Password</button>
        <button class="btn btn-outline" onclick="closePwModal()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
function closePwModal() {
  document.getElementById('pw-modal').classList.remove('open');
  // Reset form
  ['cpw-current','cpw-new','cpw-confirm'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('pw-error').style.display = 'none';
  document.getElementById('pw-error').textContent   = '';
  document.getElementById('pw-success').style.display   = 'none';
  document.getElementById('pw-form-wrap').style.display = 'block';
  document.getElementById('cpw-match').style.display    = 'none';
}

document.getElementById('pw-modal').addEventListener('click', function(e) {
  if (e.target === this) closePwModal();
});

document.getElementById('cpw-confirm').addEventListener('input', function() {
  const match = document.getElementById('cpw-match');
  const pw    = document.getElementById('cpw-new').value;
  match.style.display = this.value ? 'block' : 'none';
  match.style.color   = pw === this.value ? 'var(--success)' : 'var(--danger)';
  match.textContent   = pw === this.value ? '✓ Passwords match' : '✕ Do not match';
});

function submitPasswordChange() {
  const current = document.getElementById('cpw-current').value;
  const newPw   = document.getElementById('cpw-new').value;
  const confirm = document.getElementById('cpw-confirm').value;
  const errEl   = document.getElementById('pw-error');
  const btn     = document.getElementById('cpw-submit-btn');

  errEl.style.display = 'none';

  // Client-side validation
  if (!current || !newPw || !confirm) { showPwError('All fields are required.'); return; }
  if (newPw.length < 8)               { showPwError('Password must be at least 8 characters.'); return; }
  if (!/[A-Z]/.test(newPw))           { showPwError('Password must contain an uppercase letter.'); return; }
  if (!/[0-9]/.test(newPw))           { showPwError('Password must contain a number.'); return; }
  if (newPw !== confirm)              { showPwError('Passwords do not match.'); return; }

  btn.innerHTML = '<span class="spinner"></span>';
  btn.disabled  = true;

  // Submit via AJAX to change-password.php
  const form = new FormData();
  form.append('current_password',  current);
  form.append('new_password',      newPw);
  form.append('confirm_password',  confirm);
  form.append('ajax', '1');

  fetch('change-password.php', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('pw-form-wrap').style.display = 'none';
        document.getElementById('pw-success').style.display   = 'block';
      } else {
        showPwError(data.error || 'An error occurred.');
      }
    })
    .catch(() => showPwError('Network error. Please try again.'))
    .finally(() => { btn.innerHTML = 'Update Password'; btn.disabled = false; });
}

function showPwError(msg) {
  const el = document.getElementById('pw-error');
  el.textContent    = msg;
  el.style.display  = 'block';
  el.classList.add('visible');
}
</script>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
document.getElementById('edit-btn')?.addEventListener('click', function() {
  document.querySelectorAll('.disp-field').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.edit-field').forEach(el => el.style.display = 'block');
  document.getElementById('view-actions').style.display = 'none';
  document.getElementById('edit-actions').style.display = 'flex';
});
document.getElementById('cancel-btn')?.addEventListener('click', () => location.reload());
</script>
</body>
</html>
