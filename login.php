<?php

// ================================================================
//  SIRAJ — Login Page
//  Handles authentication for both Admin and Employee accounts.
//  On success: redirects to the appropriate dashboard.
//  On failure: shows an error message and keeps the form.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

startSession();

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $destination = (($_SESSION['user_role'] ?? '') === 'admin')
        ? 'admin-home.php'
        : 'employee-home.php';
    header('Location: ' . $destination);
    exit;
}

$error        = '';
$selectedRole = $_POST['role'] ?? 'admin';


// ── Handle Login Form Submission ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role     = $_POST['role']     ?? '';
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $error = validateLoginInput($role, $email, $password);

    if (empty($error)) {
        $user = findUserByEmailAndRole($email, $role);

        if ($user && password_verify($password, $user['Password'])) {
            startUserSession($user, $email, $role);
            header('Location: ' . ($role === 'admin' ? 'admin-home.php' : 'employee-home.php'));
            exit;
        }

        $error = 'Incorrect email or password.';
    }
}


// ── Validation Helper ────────────────────────────────────────────
// Returns an error message string, or empty string if input is valid.
function validateLoginInput(string $role, string $email, string $password): string
{
    if (!in_array($role, ['admin', 'employee'])) {
        return 'Please select a login type.';
    }
    if (empty($email) || empty($password)) {
        return 'Please fill in all fields.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    return '';
}


// ── Database Lookup ──────────────────────────────────────────────
// Queries the correct table (admin or employee) based on the selected role.
// Returns the user row or false if not found.
function findUserByEmailAndRole(string $email, string $role): array|false
{
    if ($role === 'admin') {
        $stmt = db()->prepare('
            SELECT AdminID    AS UserID,
                   AdminName  AS UserName,
                   Password,
                   NULL       AS AreaID
            FROM `admin`
            WHERE Email = ?
        ');
    } else {
        $stmt = db()->prepare('
            SELECT EmployeeID  AS UserID,
                   EmployeeName AS UserName,
                   Password,
                   AreaID
            FROM `employee`
            WHERE Email = ?
        ');
    }

    $stmt->execute([$email]);
    return $stmt->fetch();
}


// ── Session Initialisation ───────────────────────────────────────
// Regenerates the session ID (prevents session fixation attacks),
// then stores the user's data in the session.
function startUserSession(array $user, string $email, string $role): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['UserID'];
    $_SESSION['user_name']  = $user['UserName'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;
    $_SESSION['user_area']  = $user['AreaID'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        // Apply the saved theme before the page renders to prevent a visual flash
        (function () {
            if (localStorage.getItem('siraj_theme') === 'light') {
                document.documentElement.classList.add('light-mode');
                document.addEventListener('DOMContentLoaded', function () {
                    document.body.classList.add('light-mode');
                });
            }
        })();
    </script>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Log In — SIRAJ</title>
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="assets/css/auth.css"/>
</head>
<body class="auth-page">
<div class="auth-wrapper">

    <!-- ── Form Panel ───────────────────────────────────────── -->
    <div class="auth-form-panel">

        <a href="index.php" class="back-home-btn">← Back to Home</a>
        <h1 class="auth-form-title">Log In</h1>
        <p class="auth-form-sub">Select your role and sign in to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-error visible"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" id="login-form" novalidate>

            <!-- Role selector (Admin / Employee) -->
            <div class="role-tabs">
                <button type="button"
                        class="role-tab admin-tab <?= $selectedRole === 'admin' ? 'active' : '' ?>"
                        onclick="selectRole('admin')">Admin</button>
                <button type="button"
                        class="role-tab employee-tab <?= $selectedRole === 'employee' ? 'active' : '' ?>"
                        onclick="selectRole('employee')">Employee</button>
            </div>
            <input type="hidden" name="role" id="role-input"
                   value="<?= htmlspecialchars($selectedRole) ?>"/>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-input" type="email" id="email" name="email"
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
                <span class="field-error" id="email_error"></span>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <input class="form-input" type="password" id="password"
                           name="password" placeholder="Your password" required/>
                    <span class="toggle-pw">👁</span>
                </div>
                <span class="field-error" id="password_error"></span>
            </div>

            <button type="submit" class="btn btn-accent btn-full" id="login-btn">Log In</button>

            <div class="auth-links" style="margin-top:14px;">
                <a href="forgot-password.php">Forgot your password?</a>
            </div>

        </form>
    </div>

    <!-- ── Brand Panel ──────────────────────────────────────── -->
    <div class="auth-brand-panel">
        <canvas id="starfield" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"></canvas>
        <div class="auth-brand-logo" style="position:relative;z-index:1;">
            <img src="assets/img/logo.png" alt="SIRAJ"
                 style="height:56px;filter:drop-shadow(0 0 14px rgba(126,200,227,0.5));display:block;margin:0 auto 4px;"/>
            <span class="name">SIRAJ</span>
        </div>
        <p class="auth-brand-quote" style="position:relative;z-index:1;">
            "Where Technology Meets Sustainable Illumination"
        </p>
        <p class="auth-brand-tagline" style="position:relative;z-index:1;">
            Smart Street Lighting · Dark Sky Preservation
        </p>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>

    // Switch the active role tab and update the hidden input
    function selectRole(role) {
        document.getElementById('role-input').value = role;
        document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
        document.querySelector('.' + role + '-tab').classList.add('active');
    }

    // Client-side validation before submitting the form
    document.getElementById('login-form').addEventListener('submit', function (e) {
        clearFieldError('email');
        clearFieldError('password');

        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        let   isValid  = true;

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFieldError('email', 'Enter a valid email address.');
            isValid = false;
        }
        if (!password) {
            showFieldError('password', 'Password is required.');
            isValid = false;
        }
        if (!isValid) {
            e.preventDefault();
            return;
        }

        // Show loading state on the button
        const btn     = document.getElementById('login-btn');
        btn.innerHTML = '<span class="spinner"></span> Signing in…';
        btn.disabled  = true;
    });

    // Animated starfield for the brand panel
    (function () {
        const canvas = document.getElementById('starfield');
        if (!canvas) return;

        const ctx  = canvas.getContext('2d');
        let W, H, stars = [];

        function resize() {
            W      = canvas.width  = canvas.offsetWidth;
            H      = canvas.height = canvas.offsetHeight;
            stars  = [];

            for (let i = 0; i < 120; i++) {
                stars.push({
                    x:     Math.random() * W,
                    y:     Math.random() * H,
                    r:     Math.random() * 1.3 + 0.2,
                    speed: Math.random() * 0.008 + 0.002,
                    phase: Math.random() * Math.PI * 2,
                });
            }
        }

        function draw() {
            ctx.clearRect(0, 0, W, H);
            const t = Date.now() * 0.001;

            stars.forEach(s => {
                const alpha = 0.15 + 0.55 * Math.sin(t * s.speed * 10 + s.phase);
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255,255,255,${alpha})`;
                ctx.fill();
            });

            requestAnimationFrame(draw);
        }

        window.addEventListener('resize', resize);
        resize();
        draw();
    })();

</script>
</body>
</html>
