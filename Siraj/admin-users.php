<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mailer.php';
requireAdmin();
$activePage = 'users';
$adminId    = currentUserId();
$msg = ''; $msgType = 'success';

// ── CREATE EMPLOYEE ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='create') {
    $empCode  = trim($_POST['employee_code'] ?? '');
    $username = trim($_POST['username']      ?? '');
    $email    = trim($_POST['email']         ?? '');
    $password = trim($_POST['password']      ?? '');
    $areaId   = (int)($_POST['area_id']      ?? 0);

    if (empty($username)||empty($email)||empty($password)||!$areaId) {
        $msg = 'All fields are required.'; $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email address.'; $msgType = 'error';
    } elseif (strlen($password) < 8) {
        $msg = 'Password must be at least 8 characters.'; $msgType = 'error';
    } else {
        $chk = db()->prepare('SELECT UserID FROM `User` WHERE Email=?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $msg = 'This email is already registered.'; $msgType = 'error';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            db()->prepare('INSERT INTO `User` (EmployeeCode,UserName,Email,Password,Role,AreaID,AdminID) VALUES (?,?,?,?,?,?,?)')
               ->execute([$empCode ?: null, $username, $email, $hashed, 'employee', $areaId, $adminId]);

            // Send welcome email
            sendWelcomeEmail($email, $username, $password);

            $msg = "Employee account created and welcome email sent to $email.";
        }
    }
}

// ── UPDATE EMPLOYEE ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update') {
    $uid      = (int)$_POST['user_id'];
    $empCode  = trim($_POST['employee_code'] ?? '');
    $username = trim($_POST['username']      ?? '');
    $email    = trim($_POST['email']         ?? '');
    $areaId   = (int)($_POST['area_id']      ?? 0);
    $newPass  = trim($_POST['new_password']  ?? '');

    if (empty($username)||empty($email)||!$areaId) {
        $msg = 'Username, email, and area are required.'; $msgType = 'error';
    } else {
        $chk = db()->prepare('SELECT UserID FROM `User` WHERE Email=? AND UserID!=?');
        $chk->execute([$email, $uid]);
        if ($chk->fetch()) {
            $msg = 'That email is already used by another account.'; $msgType = 'error';
        } else {
            if ($newPass) {
                db()->prepare('UPDATE `User` SET EmployeeCode=?,UserName=?,Email=?,Password=?,AreaID=? WHERE UserID=? AND AdminID=?')
                   ->execute([$empCode?:null, $username, $email, password_hash($newPass,PASSWORD_BCRYPT), $areaId, $uid, $adminId]);
            } else {
                db()->prepare('UPDATE `User` SET EmployeeCode=?,UserName=?,Email=?,AreaID=? WHERE UserID=? AND AdminID=?')
                   ->execute([$empCode?:null, $username, $email, $areaId, $uid, $adminId]);
            }
            $msg = 'Employee account updated successfully.';
        }
    }
}

// ── DELETE EMPLOYEE ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='delete') {
    $uid = (int)$_POST['user_id'];
    db()->prepare('DELETE FROM `User` WHERE UserID=? AND AdminID=? AND Role="employee"')
       ->execute([$uid, $adminId]);
    $msg = 'Employee account deleted permanently.';
}

// ── Fetch employees ───────────────────────────────────────
$employees = db()->prepare('SELECT u.*, a.AreaName FROM `User` u LEFT JOIN Area a ON u.AreaID=a.AreaID WHERE u.Role="employee" AND u.AdminID=? ORDER BY u.CreatedAt DESC');
$employees->execute([$adminId]);
$employees = $employees->fetchAll();

// ── Fetch areas for dropdown ──────────────────────────────
$areas = db()->query('SELECT AreaID, AreaName FROM Area ORDER BY AreaName')->fetchAll();

// ── Welcome email function ────────────────────────────────
function sendWelcomeEmail(string $toEmail, string $toName, string $plainPassword): bool {
    $mailer = new SirajMailer(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, FROM_EMAIL, FROM_NAME);
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#0A1428;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#0A1428;padding:40px 20px;">
    <tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">
    <tr><td align="center" style="padding:0 0 28px;">
      <span style="font-size:28px;color:#7EC8E3;">✦</span>
      <span style="font-family:Georgia,serif;font-size:22px;font-weight:700;color:#7EC8E3;letter-spacing:4px;margin-left:8px;vertical-align:middle;">SIRAJ</span>
    </td></tr>
    <tr><td style="background:#132030;border-radius:18px;padding:40px;border:1px solid rgba(255,255,255,.08);">
      <h2 style="font-family:Georgia,serif;font-size:22px;color:white;margin:0 0 16px;text-align:center;">Welcome to SIRAJ! 👋</h2>
      <p style="color:rgba(255,255,255,.6);font-size:15px;line-height:1.7;margin:0 0 24px;">
        Hi <strong style="color:#7EC8E3;">' . htmlspecialchars($toName) . '</strong>, your employee account has been created. Here are your login credentials:
      </p>
      <div style="background:rgba(74,144,184,.1);border:1px solid rgba(74,144,184,.3);border-radius:12px;padding:24px;margin-bottom:24px;">
        <div style="margin-bottom:12px;"><span style="color:rgba(255,255,255,.45);font-size:12px;text-transform:uppercase;letter-spacing:1px;">Email</span><br><strong style="color:#7EC8E3;font-size:16px;">' . htmlspecialchars($toEmail) . '</strong></div>
        <div><span style="color:rgba(255,255,255,.45);font-size:12px;text-transform:uppercase;letter-spacing:1px;">Password</span><br><strong style="color:#7EC8E3;font-size:16px;font-family:monospace;">' . htmlspecialchars($plainPassword) . '</strong></div>
      </div>
      <p style="color:rgba(255,255,255,.4);font-size:13px;line-height:1.6;">
        🔒 Please change your password after your first login.<br>
        🌐 Login at: <a href="' . SITE_URL . '/login.php" style="color:#7EC8E3;">' . SITE_URL . '/login.php</a>
      </p>
    </td></tr>
    <tr><td style="padding:20px 0;text-align:center;color:rgba(255,255,255,.2);font-size:12px;">© 2026 Siraj Lighting</td></tr>
    </table></td></tr></table></body></html>';
    return $mailer->send($toEmail, $toName, 'SIRAJ — Your Account Has Been Created', $html);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Employees — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="dashboard-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Manage Employees</h1>
      <p>Create, edit, and delete employee accounts</p>
    </div>
    <button class="btn btn-accent" onclick="openModal('create-modal')">+ Create Employee</button>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> visible" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Employees Table -->
  <div class="users-table-wrap">
    <?php if (empty($employees)): ?>
      <div style="padding:60px;text-align:center;color:var(--secondary);">
        <div style="font-size:40px;margin-bottom:12px;">👥</div>
        <p>No employees yet. Click <strong>Create Employee</strong> to add one.</p>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Emp. Code</th>
            <th>Name</th>
            <th>Email</th>
            <th>Assigned Area</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $emp): ?>
          <tr>
            <td data-label="ID" style="color:var(--secondary);font-size:13px;">#<?= $emp['UserID'] ?></td>
            <td data-label="Code"><span style="font-family:monospace;font-size:13px;background:rgba(0,0,0,.05);padding:2px 8px;border-radius:6px;"><?= htmlspecialchars($emp['EmployeeCode'] ?? '—') ?></span></td>
            <td data-label="Name"><strong><?= htmlspecialchars($emp['UserName']) ?></strong></td>
            <td data-label="Email" style="font-size:13px;color:var(--secondary);"><?= htmlspecialchars($emp['Email']) ?></td>
            <td data-label="Area">
              <?php if ($emp['AreaName']): ?>
                <span class="report-badge in_progress" style="font-size:12px;">📍 <?= htmlspecialchars($emp['AreaName']) ?></span>
              <?php else: ?>
                <span style="color:var(--danger);font-size:12px;">No area</span>
              <?php endif; ?>
            </td>
            <td data-label="Created" style="font-size:12px;color:var(--secondary);"><?= date('d/m/Y', strtotime($emp['CreatedAt'])) ?></td>
            <td data-label="Actions">
              <div style="display:flex;gap:6px;">
                <button class="btn btn-outline btn-sm"
                        onclick='openEditModal(<?= json_encode($emp) ?>)'>✏️ Edit</button>
                <button class="btn btn-danger btn-sm"
                        onclick="confirmDelete(<?= $emp['UserID'] ?>, '<?= htmlspecialchars($emp['UserName']) ?>')">🗑️</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── CREATE EMPLOYEE MODAL ── -->
<div class="modal-overlay" id="create-modal">
  <div class="modal-box">
    <button class="modal-close">✕</button>
    <div class="modal-title">Create Employee Account</div>
    <div class="modal-sub">Fill in the details below. A welcome email with credentials will be sent automatically.</div>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="action" value="create"/>
      <!-- Dummy fields to fool browser autofill -->
      <input type="text"     name="fake_user" style="display:none;" autocomplete="username"/>
      <input type="password" name="fake_pass" style="display:none;" autocomplete="current-password"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label class="form-label">Employee Code (optional)</label>
          <input class="form-input" type="text" name="employee_code" placeholder="e.g. EMP-001" autocomplete="off"/>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input class="form-input" type="text" name="username" placeholder="Full name" required autocomplete="off"/>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input class="form-input" type="email" name="email" placeholder="employee@example.com" required autocomplete="off"/>
      </div>

      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="input-wrapper">
          <input class="form-input" type="password" id="create-pw" name="password" placeholder="Min 8 characters" required autocomplete="new-password"/>
          <span class="toggle-pw">👁</span>
        </div>
        <div class="pw-strength" id="create-pw-strength">
          <div class="pw-strength-bar"><div class="pw-strength-fill"></div></div>
          <div class="pw-rules">
            <span class="pw-rule" data-rule="length"><span class="rule-icon">○</span> 8+ chars</span>
            <span class="pw-rule" data-rule="uppercase"><span class="rule-icon">○</span> Uppercase</span>
            <span class="pw-rule" data-rule="lowercase"><span class="rule-icon">○</span> Lowercase</span>
            <span class="pw-rule" data-rule="number"><span class="rule-icon">○</span> Number</span>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Assigned Area *</label>
        <select class="form-select" name="area_id" required>
          <option value="">— Select Area —</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['AreaID'] ?>"><?= htmlspecialchars($a['AreaName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="background:rgba(74,144,184,.07);border:1px solid rgba(74,144,184,.2);border-radius:var(--radius-sm);padding:12px;font-size:12px;color:var(--secondary);margin-bottom:18px;">
        📧 A welcome email with the login credentials will be automatically sent to the employee's email address.
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-accent" style="flex:1;">✓ Create Account</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('create-modal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT EMPLOYEE MODAL ── -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal-box">
    <button class="modal-close">✕</button>
    <div class="modal-title">Edit Employee Account</div>
    <div class="modal-sub">Update the employee's information below.</div>

    <form method="POST" id="edit-form">
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="user_id" id="edit-user-id"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label class="form-label">Employee Code</label>
          <input class="form-input" type="text" name="employee_code" id="edit-emp-code" autocomplete="off"/>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input class="form-input" type="text" name="username" id="edit-username" required autocomplete="off"/>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input class="form-input" type="email" name="email" id="edit-email" required autocomplete="off"/>
      </div>

      <div class="form-group">
        <label class="form-label">New Password <span style="color:var(--secondary);font-weight:400;">(leave blank to keep current)</span></label>
        <div class="input-wrapper">
          <input class="form-input" type="password" name="new_password" id="edit-pw" placeholder="Leave blank to keep current" autocomplete="new-password"/>
          <span class="toggle-pw">👁</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Assigned Area *</label>
        <select class="form-select" name="area_id" id="edit-area" required>
          <option value="">— Select Area —</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['AreaID'] ?>"><?= htmlspecialchars($a['AreaName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-modal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── DELETE CONFIRM MODAL ── -->
<div class="modal-overlay" id="delete-modal">
  <div class="modal-box" style="max-width:400px;text-align:center;">
    <button class="modal-close">✕</button>
    <div style="font-size:52px;margin-bottom:16px;">⚠️</div>
    <div class="modal-title">Delete Employee?</div>
    <p class="modal-sub" id="delete-confirm-text">This will permanently delete this account from the database.</p>
    <form method="POST" id="delete-form">
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="user_id" id="delete-user-id"/>
      <div style="display:flex;gap:10px;justify-content:center;">
        <button type="submit" class="btn btn-danger">🗑️ Delete Permanently</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('delete-modal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
// Clear create form every time modal opens
document.querySelector('[onclick="openModal(\'create-modal\')"]')?.addEventListener('click', function() {
  setTimeout(() => {
    const form = document.querySelector('#create-modal form');
    if (form) {
      form.reset();
      // Extra clear for autofill
      form.querySelectorAll('input:not([type=hidden])').forEach(inp => {
        inp.value = '';
        inp.setAttribute('value', '');
      });
    }
  }, 50);
});

// Clear create modal form on open to prevent autofill bleeding
document.addEventListener('DOMContentLoaded', function() {
  // Watch for modal open
  const createBtn = document.querySelector('[onclick*="create-modal"]');
  if (createBtn) {
    createBtn.addEventListener('click', () => {
      setTimeout(clearCreateForm, 100);
    });
  }
});
function clearCreateForm() {
  const fields = ['employee_code','username','email','password'];
  fields.forEach(name => {
    const el = document.querySelector('#create-modal [name="' + name + '"]');
    if (el) { el.value = ''; }
  });
  const sel = document.querySelector('#create-modal [name="area_id"]');
  if (sel) sel.selectedIndex = 0;
  // Reset strength bar
  const fill = document.querySelector('#create-pw-strength .pw-strength-fill');
  if (fill) fill.style.width = '0%';
  document.querySelectorAll('#create-pw-strength .pw-rule').forEach(r => {
    r.classList.remove('met');
    const icon = r.querySelector('.rule-icon');
    if (icon) icon.textContent = '○';
  });
}

document.getElementById('create-pw')?.addEventListener('input', function() {
  updateStrengthUI(this.value, 'create-pw-strength');
});

function openEditModal(emp) {
  document.getElementById('edit-user-id').value   = emp.UserID;
  document.getElementById('edit-emp-code').value  = emp.EmployeeCode || '';
  document.getElementById('edit-username').value  = emp.UserName;
  document.getElementById('edit-email').value     = emp.Email;
  document.getElementById('edit-area').value      = emp.AreaID || '';
  document.getElementById('edit-pw').value        = '';
  openModal('edit-modal');
}

function confirmDelete(uid, name) {
  document.getElementById('delete-user-id').value  = uid;
  document.getElementById('delete-confirm-text').textContent =
    `This will permanently delete "${name}"'s account from the database. This cannot be undone.`;
  openModal('delete-modal');
}
</script>
</body>
</html>
