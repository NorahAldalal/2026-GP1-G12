<?php

// ================================================================
//  SIRAJ — Change Password Page
//  Allows a logged-in user to update their password.
//  Requires the current password for verification.
//  Access: Any logged-in user.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireLogin();
$activePage = 'profile';
$isAdmin    = ($_SESSION['user_role'] ?? '') === 'admin';
$userId     = currentUserId();
$error      = '';
$success    = false;


// ── Handle Password Change ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']      ?? '';
    $confirmPassword = $_POST['confirm_password']  ?? '';

    $error = validatePasswordChange($currentPassword, $newPassword, $confirmPassword, $userId, $isAdmin);

    if (empty($error)) {
        updatePassword($newPassword, $userId, $isAdmin);
        $success = true;
    }
}


// ── Helper Functions ─────────────────────────────────────────────

// Validates all three password fields.
// Returns an error string, or empty string if everything is valid.
function validatePasswordChange(
    string $current,
    string $new,
    string $confirm,
    int    $userId,
    bool   $isAdmin
): string {
    if (empty($current) || empty($new) || empty($confirm)) {
        return 'All fields are required.';
    }
    if (!isCurrentPasswordCorrect($current, $userId, $isAdmin)) {
        return 'Current password is incorrect.';
    }
    if (strlen($new) < 8) {
        return 'New password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $new)) {
        return 'New password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $new)) {
        return 'New password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $new)) {
        return 'New password must contain at least one number.';
    }
    if ($new !== $confirm) {
        return 'New passwords do not match.';
    }
    if ($current === $new) {
        return 'New password must be different from the current password.';
    }
    return '';
}

// Verifies the provided password against the stored hash.
function isCurrentPasswordCorrect(string $password, int $userId, bool $isAdmin): bool
{
    $sql = $isAdmin
        ? 'SELECT Password FROM `admin`    WHERE AdminID    = ?'
        : 'SELECT Password FROM `employee` WHERE EmployeeID = ?';

    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    $row  = $stmt->fetch();

    return $row && password_verify($password, $row['Password']);
}

// Saves the new hashed password to the database.
function updatePassword(string $newPassword, int $userId, bool $isAdmin): void
{
    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

    $sql = $isAdmin
        ? 'UPDATE `admin`    SET Password = ? WHERE AdminID    = ?'
        : 'UPDATE `employee` SET Password = ? WHERE EmployeeID = ?';

    db()->prepare($sql)->execute([$hashed, $userId]);
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
        /* Password requirement indicators */
        .req-list  { display:flex; flex-direction:column; gap:6px; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); padding:14px 16px; margin-top:8px; }
        .req-item  { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-muted); transition:color .2s; }
        .req-dot   { width:7px; height:7px; border-radius:50%; background:rgba(255,255,255,.2); flex-shrink:0; transition:background .2s; }
        .req-item.met            { color:var(--success); }
        .req-item.met .req-dot   { background:var(--success); }
    </style>
</head>
<body class="dashboard-page">

<?php include 'includes/nav.php'; ?>

<div class="change-pw-wrap">
<div class="change-pw-card">

    <?php if ($success): ?>

        <!-- ── Success State ─────────────────────────────── -->
        <div class="success-state" style="text-align:center; padding:20px 0;">
            <span style="font-size:56px; display:block; margin-bottom:18px;">✅</span>
            <h2 style="font-family:'Cinzel',serif; font-size:22px; color:var(--glow-soft); margin-bottom:10px;">
                Password Changed!
            </h2>
            <p style="font-size:14px; color:var(--text-muted); margin-bottom:28px; line-height:1.7;">
                Your password has been updated. Use the new password next time you log in.
            </p>
            <a href="profile.php" class="btn btn-primary">← Back to Profile</a>
        </div>

    <?php else: ?>

        <!-- ── Change Password Form ──────────────────────── -->
        <div style="text-align:center; margin-bottom:24px;">
            <div style="width:64px; height:64px; border-radius:50%;
                        background:rgba(74,144,184,.1); border:1.5px solid rgba(74,144,184,.25);
                        display:inline-flex; align-items:center; justify-content:center;
                        font-size:26px;">🔑</div>
        </div>

        <h1 style="font-family:'Cinzel',serif; font-size:24px; font-weight:700;
                   color:var(--glow-soft); text-align:center; margin-bottom:8px;">
            Change Password
        </h1>
        <p style="font-size:13px; color:var(--text-muted); text-align:center; margin-bottom:28px;">
            Verify your current password, then choose a new one.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error visible"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <!-- Current Password -->
            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <div class="input-wrapper">
                    <input class="form-input" type="password"
                           id="current_password" name="current_password"
                           placeholder="Enter your current password" required/>
                    <span class="toggle-pw">👁</span>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid rgba(255,255,255,.07); margin:22px 0;"/>

            <!-- New Password with live requirements -->
            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <div class="input-wrapper">
                    <input class="form-input" type="password"
                           id="new_password" name="new_password"
                           placeholder="Create a strong new password" required/>
                    <span class="toggle-pw">👁</span>
                </div>

                <!-- Live requirement indicators -->
                <div class="req-list" id="req-list">
                    <div class="req-item" id="req-length">
                        <div class="req-dot"></div> At least 8 characters
                    </div>
                    <div class="req-item" id="req-upper">
                        <div class="req-dot"></div> One uppercase letter (A–Z)
                    </div>
                    <div class="req-item" id="req-lower">
                        <div class="req-dot"></div> One lowercase letter (a–z)
                    </div>
                    <div class="req-item" id="req-number">
                        <div class="req-dot"></div> One number (0–9)
                    </div>
                    <div class="req-item" id="req-diff">
                        <div class="req-dot"></div> Different from current password
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
                <div id="match-indicator" style="font-size:12px; margin-top:6px; display:none;"></div>
            </div>

            <!-- Form actions -->
            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    Update Password
                </button>
                <a href="profile.php" class="btn btn-outline" style="flex:1; text-align:center;">
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

    const newPwInput     = document.getElementById('new_password');
    const currentPwInput = document.getElementById('current_password');
    const confirmInput   = document.getElementById('confirm_password');
    const matchIndicator = document.getElementById('match-indicator');

    // Check requirements as the user types the new password
    newPwInput?.addEventListener('input', function () {
        const pw      = this.value;
        const current = currentPwInput?.value || '';

        checkRequirement('req-length', pw.length >= 8);
        checkRequirement('req-upper',  /[A-Z]/.test(pw));
        checkRequirement('req-lower',  /[a-z]/.test(pw));
        checkRequirement('req-number', /[0-9]/.test(pw));
        checkRequirement('req-diff',   pw.length > 0 && pw !== current);

        checkPasswordMatch();
    });

    // Re-evaluate the "different from current" requirement when the current field changes
    currentPwInput?.addEventListener('input', () => newPwInput?.dispatchEvent(new Event('input')));

    // Show a match/mismatch indicator under the confirm field
    confirmInput?.addEventListener('input', checkPasswordMatch);

    function checkRequirement(id, isMet) {
        document.getElementById(id)?.classList.toggle('met', isMet);
    }

    function checkPasswordMatch() {
        const pw      = newPwInput?.value     || '';
        const confirm = confirmInput?.value   || '';

        if (!matchIndicator || !confirm) return;

        matchIndicator.style.display = 'block';
        matchIndicator.style.color   = (pw === confirm) ? 'var(--success)' : 'var(--danger)';
        matchIndicator.textContent   = (pw === confirm) ? '✓ Passwords match' : '✕ Passwords do not match';
    }

</script>
</body>
</html>
