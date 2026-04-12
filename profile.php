<?php

// ================================================================
//  SIRAJ — User Profile Page
//  Allows any logged-in user (Admin or Employee) to view and
//  edit their name and email. The role code is read-only.
//  Access: Any logged-in user.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireLogin();
$activePage = 'profile';
$userId     = currentUserId();
$isAdmin    = ($_SESSION['user_role'] ?? '') === 'admin';
$message    = '';
$messageType = 'success';


// ── Handle Profile Save ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    $name  = trim($_POST['username'] ?? '');
    $email = trim($_POST['email']    ?? '');

    $validationError = validateProfileInput($name, $email);

    if ($validationError) {
        $message     = $validationError;
        $messageType = 'error';
    } elseif (isEmailTakenByAnotherUser($email, $userId, $isAdmin)) {
        $message     = 'This email is already in use by another account.';
        $messageType = 'error';
    } else {
        saveProfile($name, $email, $userId, $isAdmin);
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $message = 'Profile updated successfully!';
    }
}


// ── Load User Data ───────────────────────────────────────────────
$user = fetchUserData($userId, $isAdmin);


// ── Helper Functions ─────────────────────────────────────────────

// Validates the name and email fields.
// Returns an error string, or empty string if valid.
function validateProfileInput(string $name, string $email): string
{
    if (empty($name) || empty($email)) {
        return 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    return '';
}

// Returns true if another user already uses this email.
function isEmailTakenByAnotherUser(string $email, int $userId, bool $isAdmin): bool
{
    $sql = $isAdmin
        ? 'SELECT AdminID    FROM `admin`    WHERE Email = ? AND AdminID    != ?'
        : 'SELECT EmployeeID FROM `employee` WHERE Email = ? AND EmployeeID != ?';

    $stmt = db()->prepare($sql);
    $stmt->execute([$email, $userId]);
    return (bool) $stmt->fetch();
}

// Updates the user's name and email in the correct table.
function saveProfile(string $name, string $email, int $userId, bool $isAdmin): void
{
    $sql = $isAdmin
        ? 'UPDATE `admin`    SET AdminName    = ?, Email = ? WHERE AdminID    = ?'
        : 'UPDATE `employee` SET EmployeeName = ?, Email = ? WHERE EmployeeID = ?';

    db()->prepare($sql)->execute([$name, $email, $userId]);
}

// Fetches all profile data from the correct table, including area name for employees.
function fetchUserData(int $userId, bool $isAdmin): array|false
{
    if ($isAdmin) {
        $stmt = db()->prepare('
            SELECT AdminID      AS UserID,
                   AdminName    AS UserName,
                   Email,
                   Password,
                   AdminCode    AS UserCode,
                   NULL         AS AreaID,
                   NULL         AS AreaName,
                   CreatedAt
            FROM `admin`
            WHERE AdminID = ?
        ');
    } else {
        $stmt = db()->prepare('
            SELECT e.EmployeeID   AS UserID,
                   e.EmployeeName AS UserName,
                   e.Email,
                   e.Password,
                   e.EmployeeCode AS UserCode,
                   e.AreaID,
                   a.AreaName,
                   e.CreatedAt
            FROM `employee` e
            LEFT JOIN `area` a ON e.AreaID = a.AreaID
            WHERE e.EmployeeID = ?
        ');
    }

    $stmt->execute([$userId]);
    return $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Profile — SIRAJ</title>
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">

<?php include 'includes/nav.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
<div class="profile-layout">

    <!-- ── Left Card: Avatar & Summary ──────────────────── -->
    <div class="profile-card">
        <div class="profile-avatar"><?= $isAdmin ? '🛡️' : '👷' ?></div>
        <div class="profile-name"><?= htmlspecialchars($user['UserName']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($user['Email']) ?></div>

        <!-- Role badge -->
        <div style="margin: 14px 0;">
            <span class="nav-role-badge <?= $_SESSION['user_role'] ?>"
                  style="font-size:13px; padding:5px 14px;">
                <?= $isAdmin ? 'Admin' : 'Employee' ?>
            </span>
        </div>

        <!-- Assigned area (employees only) -->
        <?php if (!$isAdmin && $user['AreaName']): ?>
            <div style="background:rgba(74,144,184,.08); padding:10px 14px;
                        font-size:13px; color:var(--text-muted); margin-bottom:12px;">
                Assigned: <strong style="color:var(--glow-soft);">
                    <?= htmlspecialchars($user['AreaName']) ?>
                </strong>
            </div>
        <?php endif; ?>

        <!-- User code (read-only) -->
        <?php if ($user['UserCode']): ?>
            <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">
                <?= $isAdmin ? 'Admin' : 'Employee' ?> Code:
                <strong style="color:var(--text-main);">
                    <?= htmlspecialchars($user['UserCode']) ?>
                </strong>
            </div>
        <?php endif; ?>

        <div style="font-size:12px; color:var(--text-muted); margin-bottom:20px;">
            Member since <?= date('F Y', strtotime($user['CreatedAt'])) ?>
        </div>

        <a href="logout.php" class="btn btn-outline btn-full"
           style="color:var(--danger); border-color:var(--danger);">
            Log Out
        </a>
    </div>

    <!-- ── Right Card: Account Information ──────────────── -->
    <div class="profile-info-card">
        <div class="profile-section-title">Account Information</div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> visible" style="margin-bottom:16px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="profile-form" novalidate>

            <!-- Username -->
            <div class="profile-field">
                <span class="profile-field-label">Username</span>
                <span class="profile-field-value disp-field" id="disp-name">
                    <?= htmlspecialchars($user['UserName']) ?>
                </span>
                <input class="form-input edit-field" type="text"
                       name="username" id="inp-name"
                       value="<?= htmlspecialchars($user['UserName']) ?>"
                       style="display:none;" required/>
            </div>

            <!-- Email -->
            <div class="profile-field">
                <span class="profile-field-label">Email</span>
                <span class="profile-field-value disp-field" id="disp-email">
                    <?= htmlspecialchars($user['Email']) ?>
                </span>
                <input class="form-input edit-field" type="email"
                       name="email" id="inp-email"
                       value="<?= htmlspecialchars($user['Email']) ?>"
                       style="display:none;" required/>
            </div>

            <!-- User Code (read-only — managed by admin) -->
            <div class="profile-field">
                <span class="profile-field-label">
                    <?= $isAdmin ? 'Admin Code' : 'Employee Code' ?>
                </span>
                <span class="profile-field-value">
                    <?php if ($user['UserCode']): ?>
                        <?= htmlspecialchars($user['UserCode']) ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted); font-size:13px;">Not set</span>
                    <?php endif; ?>
                </span>
            </div>

            <!-- Password (link to change-password page) -->
            <div class="profile-field">
                <span class="profile-field-label">Password</span>
                <span class="profile-field-value">••••••••••</span>
            </div>

            <!-- Role (read-only) -->
            <div class="profile-field" style="border:none;">
                <span class="profile-field-label">Role</span>
                <span class="profile-field-value">
                    <span class="nav-role-badge <?= $_SESSION['user_role'] ?>">
                        <?= $isAdmin ? 'Admin' : 'Employee' ?>
                    </span>
                </span>
            </div>

            <!-- View mode actions -->
            <div style="display:flex; gap:12px; margin-top:24px;" id="view-actions">
                <button type="button" class="btn btn-primary" id="edit-btn">
                    Edit Profile
                </button>
                <a href="change-password.php" class="btn btn-outline">
                    Change Password
                </a>
            </div>

            <!-- Edit mode actions -->
            <div style="display:none; gap:12px; margin-top:24px;" id="edit-actions">
                <button type="submit" name="save_profile" class="btn btn-accent">
                    Save Changes
                </button>
                <button type="button" class="btn btn-outline" id="cancel-btn">
                    Cancel
                </button>
            </div>

        </form>
    </div>

</div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>

    // Switch from view mode to edit mode
    document.getElementById('edit-btn')?.addEventListener('click', function () {
        // Hide display values, show input fields
        document.querySelectorAll('.disp-field').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.edit-field').forEach(el => el.style.display = 'block');

        // Swap action buttons
        document.getElementById('view-actions').style.display = 'none';
        document.getElementById('edit-actions').style.display = 'flex';
    });

    // Cancel editing and reload the page to restore original values
    document.getElementById('cancel-btn')?.addEventListener('click', () => location.reload());

</script>
</body>
</html>
