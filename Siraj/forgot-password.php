<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mailer.php';
startSession();
if (isLoggedIn()) { header('Location: '.(isAdmin()?'admin-home.php':'employee-home.php')); exit; }

$pageError  = '';
$savedEmail = $_SESSION['reset_email']   ?? '';
$sentAt     = $_SESSION['reset_sent_at'] ?? null;
$modalError = $_SESSION['modal_error']   ?? '';
$wrongCode  = !empty($_SESSION['wrong_code']);
$showModal  = !empty($_SESSION['show_modal']);
unset($_SESSION['show_modal'], $_SESSION['modal_error'], $_SESSION['wrong_code']);

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='send_code') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pageError = 'Please enter a valid email address.';
    } else {
        $stmt = db()->prepare('SELECT UserID,UserName FROM `User` WHERE Email=?');
        $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user) {
            db()->prepare('UPDATE `PasswordReset` SET Used=1 WHERE UserID=? AND Used=0')->execute([$user['UserID']]);
            $code   = str_pad(random_int(0,9999),4,'0',STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', time()+600);
            db()->prepare('INSERT INTO `PasswordReset` (UserID,Code,ExpiresAt) VALUES (?,?,?)')->execute([$user['UserID'],$code,$expiry]);
            $sent = sendResetCodeEmail($email, $user['UserName'], $code);
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_user_id'] = $user['UserID'];
            $_SESSION['reset_sent_at'] = time();
            $_SESSION['show_modal']    = true;
            $_SESSION['modal_error']   = $sent ? '' : 'Could not send email. Check SMTP settings.';
        } else {
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_sent_at'] = time();
            $_SESSION['show_modal']    = true;
            unset($_SESSION['modal_error']);
        }
        header('Location: forgot-password.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='verify_code') {
    $entered = preg_replace('/\D/','', ($_POST['d1']??'').($_POST['d2']??'').($_POST['d3']??'').($_POST['d4']??''));
    $userId  = $_SESSION['reset_user_id'] ?? null;
    if (strlen($entered) < 4) {
        $_SESSION['modal_error'] = 'Enter the complete 4-digit code.'; $_SESSION['show_modal'] = true;
    } elseif (!$userId) {
        $_SESSION['modal_error'] = 'Session expired.'; $_SESSION['show_modal'] = false;
    } else {
        $stmt = db()->prepare('SELECT id,Code,ExpiresAt,Attempts FROM `PasswordReset` WHERE UserID=? AND Used=0 ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]); $reset = $stmt->fetch();
        if (!$reset) {
            $_SESSION['modal_error'] = 'No active code. Request a new one.'; $_SESSION['show_modal'] = false;
            unset($_SESSION['reset_user_id'],$_SESSION['reset_email'],$_SESSION['reset_sent_at']);
        } elseif ((int)$reset['Attempts'] >= 3) {
            db()->prepare('UPDATE `PasswordReset` SET Used=1 WHERE id=?')->execute([$reset['id']]);
            $_SESSION['modal_error'] = 'Too many attempts. Request a new code.'; $_SESSION['show_modal'] = false;
            unset($_SESSION['reset_user_id'],$_SESSION['reset_email'],$_SESSION['reset_sent_at']);
        } elseif (strtotime($reset['ExpiresAt']) < time()) {
            $_SESSION['modal_error'] = 'Code expired. Request a new one.'; $_SESSION['show_modal'] = false;
        } elseif ($reset['Code'] !== $entered) {
            db()->prepare('UPDATE `PasswordReset` SET Attempts=Attempts+1 WHERE id=?')->execute([$reset['id']]);
            $rem = max(0, 3-((int)$reset['Attempts']+1));
            $_SESSION['modal_error'] = "Wrong code — $rem attempt(s) left.";
            $_SESSION['show_modal']  = true; $_SESSION['wrong_code'] = true;
        } else {
            db()->prepare('UPDATE `PasswordReset` SET Used=1 WHERE id=?')->execute([$reset['id']]);
            $_SESSION['reset_verified'] = true;
            unset($_SESSION['show_modal'],$_SESSION['modal_error'],$_SESSION['wrong_code']);
            header('Location: reset-password.php'); exit;
        }
    }
    header('Location: forgot-password.php'); exit;
}

if (isset($_GET['resend'])) {
    foreach (['reset_user_id','reset_email','reset_sent_at','show_modal','modal_error','wrong_code','reset_verified'] as $k) unset($_SESSION[$k]);
    header('Location: forgot-password.php'); exit;
}

// AJAX resend handler
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='resend_code') {
    header('Content-Type: application/json');
    $userId = $_SESSION['reset_user_id'] ?? null;
    $email  = $_SESSION['reset_email']  ?? null;
    if (!$userId || !$email) {
        echo json_encode(['ok'=>false,'msg'=>'Session expired. Please start over.']);
        exit;
    }
    // Invalidate previous codes
    db()->prepare('UPDATE `PasswordReset` SET Used=1 WHERE UserID=? AND Used=0')->execute([$userId]);
    // Generate new code
    $code   = str_pad(random_int(0,9999),4,'0',STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', time()+600);
    db()->prepare('INSERT INTO `PasswordReset` (UserID,Code,ExpiresAt) VALUES (?,?,?)')->execute([$userId,$code,$expiry]);
    // Get user name
    $stmt = db()->prepare('SELECT UserName FROM `User` WHERE UserID=?');
    $stmt->execute([$userId]); $user = $stmt->fetch();
    $sent = sendResetCodeEmail($email, $user['UserName'] ?? 'User', $code);
    $_SESSION['reset_sent_at'] = time();
    echo json_encode(['ok'=>$sent,'msg'=>$sent?'A new code has been sent!':'Could not send email. Check SMTP settings.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>
(function(){
  var t=localStorage.getItem('siraj_theme')||'dark';
  if(t==='light'){ document.documentElement.className='light-mode'; }
})();
</script>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Forgot Password — SIRAJ</title>
<link rel="stylesheet" href="assets/css/global.css"/>
<link rel="stylesheet" href="assets/css/auth.css"/>
<style>
/* ── Modal ── */
.modal-overlay{position:fixed;inset:0;background:rgba(4,8,18,.82);z-index:600;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s;backdrop-filter:blur(8px);}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal-box{
  background:rgba(10,20,40,.96);
  backdrop-filter:blur(28px);
  border:1px solid rgba(126,200,227,.14);
  border-radius:0;
  padding:40px 36px;
  width:calc(100% - 40px);max-width:440px;
  text-align:center;
  transform:scale(.9) translateY(20px);
  transition:transform .35s cubic-bezier(.175,.885,.32,1.275);
  box-shadow:0 32px 80px rgba(0,0,0,.6);
  position:relative;
}
body.light-mode .modal-box{
  background:rgba(255,255,255,.97);
  border-color:rgba(13,27,46,.10);
  box-shadow:0 20px 60px rgba(0,0,0,.18);
}
.modal-overlay.open .modal-box{transform:scale(1) translateY(0);}
.m-close{position:absolute;top:14px;right:16px;width:28px;height:28px;border-radius:0;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);}
body.light-mode .m-close{background:rgba(13,27,46,.06);border-color:rgba(13,27,46,.10);color:rgba(13,27,46,.5);}
.m-title{font-family:'Cinzel',serif;font-size:20px;font-weight:700;color:var(--glow-soft);margin-bottom:6px;}
body.light-mode .m-title{color:#0D1B2E;}
.m-sub{font-size:13px;color:var(--text-muted);line-height:1.7;margin-bottom:14px;}
body.light-mode .m-sub{color:rgba(13,27,46,.55);}
.m-sub strong{color:var(--glow);}
body.light-mode .m-sub strong{color:#2d6d98;}
.cd-wrap{display:inline-flex;align-items:center;gap:7px;background:rgba(245,197,66,.1);border:1px solid rgba(245,197,66,.3);padding:5px 14px;font-size:13px;font-weight:700;color:#9a7200;margin-bottom:18px;}
.hint-b{background:rgba(74,144,184,.07);border:1px solid rgba(74,144,184,.17);padding:10px 13px;margin-bottom:16px;font-size:12px;color:var(--text-muted);line-height:1.7;text-align:left;}
body.light-mode .hint-b{background:rgba(74,144,184,.06);border-color:rgba(74,144,184,.15);color:rgba(13,27,46,.55);}
.m-err{padding:9px 13px;font-size:13px;margin-bottom:16px;background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#ff9494;display:none;text-align:left;}
body.light-mode .m-err{background:rgba(224,92,92,.07);color:#8b1a1a;border-color:rgba(224,92,92,.2);}
.m-err.visible{display:block;}
.code-inputs{display:flex;gap:10px;justify-content:center;margin-bottom:22px;}
.code-box{width:58px;height:66px;border:1.5px solid rgba(126,200,227,.2);background:rgba(255,255,255,.06);font-size:28px;font-weight:700;font-family:'Cinzel',serif;text-align:center;color:var(--text-main);outline:none;transition:all .2s;caret-color:transparent;}
body.light-mode .code-box{background:white;border-color:rgba(13,27,46,.15);color:#0D1B2E;}
.code-box:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(74,144,184,.18);transform:scale(1.05);}
.code-box.filled{border-color:var(--success);}
.code-box.eb{border-color:var(--danger);}
@keyframes shake{0%,100%{transform:translateX(0)}15%{transform:translateX(-8px)}30%{transform:translateX(8px)}60%{transform:translateX(5px)}80%{transform:translateX(-3px)}}
.shake-anim{animation:shake .5s ease;}
.resend-row{text-align:center;margin-top:14px;font-size:13px;color:var(--text-muted);}
body.light-mode .resend-row{color:rgba(13,27,46,.5);}
</style>
</head>
<body class="auth-page">
<div class="auth-wrapper">

  <!-- ── Form Panel ── -->
  <div class="auth-form-panel" style="justify-content:center;">
    <div><a href="login.php" class="back-home-btn">← Back to Login</a></div>

    <div style="width:100%;max-width:400px;">
    <h1 class="auth-form-title">Forgot Password?</h1>
    <p class="auth-form-sub">Enter your registered email to receive the <strong>reset code</strong>.</p>

    <?php if ($pageError): ?>
      <div class="alert alert-error visible"><?= htmlspecialchars($pageError) ?></div>
    <?php endif; ?>

    <form method="POST" id="fp-form" novalidate>
      <input type="hidden" name="action" value="send_code"/>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email"
               placeholder="your@email.com"
               value="<?= htmlspecialchars($savedEmail) ?>"
               autocomplete="email" required/>
        <span class="field-error" id="email_error"></span>
      </div>
      <button type="submit" class="btn btn-accent btn-full" id="fp-btn">
        Send Reset Code
      </button>
      <div class="auth-links" style="margin-top:16px;">
        I remembered it! <a href="login.php">Log in</a>
      </div>
    </form>
  </div><!-- /max-width wrapper -->
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

</div>

<!-- ── CODE MODAL ── -->
<div class="modal-overlay <?= $showModal ? 'open' : '' ?>" id="code-modal">
  <div class="modal-box">
    <button class="m-close" onclick="document.getElementById('code-modal').classList.remove('open')">✕</button>

    <div style="width:60px;height:60px;border:1.5px solid rgba(126,200,227,.25);background:rgba(74,144,184,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--glow)" stroke-width="1.8">
        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8l10 6 10-6"/>
      </svg>
    </div>

    <div class="m-title">Enter Verification Code</div>
    <p class="m-sub">
      We sent a <strong>4-digit code</strong> to<br>
      <strong><?= htmlspecialchars($savedEmail) ?></strong>
    </p>

    <?php if ($sentAt): ?>
    <div class="cd-wrap">Expires in: <span id="countdown">10:00</span></div>
    <?php endif; ?>

    <div class="hint-b">
      Check your <strong>inbox</strong> and <strong>spam/junk</strong> folder.<br>
      Email is from <strong>SIRAJ Lighting</strong>.
    </div>

    <div class="m-err <?= $modalError ? 'visible' : '' ?>" id="modal-alert">
      <?= htmlspecialchars($modalError) ?>
    </div>

    <form method="POST" id="code-form">
      <input type="hidden" name="action" value="verify_code"/>
      <div class="code-inputs" id="code-boxes">
        <input type="text" class="code-box" name="d1" maxlength="1" inputmode="numeric" autocomplete="off"/>
        <input type="text" class="code-box" name="d2" maxlength="1" inputmode="numeric" autocomplete="off"/>
        <input type="text" class="code-box" name="d3" maxlength="1" inputmode="numeric" autocomplete="off"/>
        <input type="text" class="code-box" name="d4" maxlength="1" inputmode="numeric" autocomplete="off"/>
      </div>
      <button type="submit" class="btn btn-accent btn-full" id="verify-code-btn" disabled>
        Verify Code
      </button>
    </form>

    <div class="resend-row">
      Didn't receive it?
      <button type="button" id="resend-btn" onclick="resendCode()" style="background:none;border:none;cursor:pointer;font-weight:700;font-size:13px;color:var(--glow);font-family:inherit;padding:0;">Resend</button>
      &nbsp;·&nbsp;
      <a href="forgot-password.php?resend=1">Different email</a>
    </div>
    <div id="resend-msg" style="text-align:center;font-size:12px;margin-top:8px;display:none;"></div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
// Apply theme to body
(function(){
  if(document.documentElement.classList.contains('light-mode'))
    document.body.classList.add('light-mode');
})();

// Modal
if(<?= $showModal ? 'true' : 'false' ?>) setTimeout(()=>document.getElementById('code-modal').classList.add('open'), 80);
document.getElementById('code-modal').addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });

// Code boxes
const boxes = Array.from(document.querySelectorAll('.code-box'));
const vBtn  = document.getElementById('verify-code-btn');
boxes.forEach((b,i) => {
  b.addEventListener('keydown', function(e) {
    if (!/^\d$/.test(e.key) && !['Backspace','Tab','ArrowLeft','ArrowRight'].includes(e.key)) e.preventDefault();
    if (e.key==='Backspace') { e.preventDefault(); this.value=''; this.classList.remove('filled','eb'); if(i>0) boxes[i-1].focus(); upd(); }
  });
  b.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').slice(-1);
    this.classList.toggle('filled', !!this.value); this.classList.remove('eb');
    if (this.value && i < boxes.length-1) boxes[i+1].focus();
    upd();
  });
});
document.getElementById('code-boxes').addEventListener('paste', function(e) {
  e.preventDefault();
  const d = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,4);
  boxes.forEach((b,i) => { b.value=d[i]||''; b.classList.toggle('filled',!!b.value); });
  boxes[Math.min(d.length,3)].focus(); upd();
});
function upd() { vBtn.disabled = boxes.map(b=>b.value).join('').length < 4; }

// Shake on wrong code
if (<?= $wrongCode ? 'true' : 'false' ?>) {
  setTimeout(() => {
    const w = document.getElementById('code-boxes');
    w.classList.add('shake-anim');
    boxes.forEach(b => { b.value=''; b.classList.remove('filled'); b.classList.add('eb'); });
    boxes[0].focus(); upd();
    setTimeout(() => w.classList.remove('shake-anim'), 600);
  }, 400);
}
document.getElementById('code-modal').addEventListener('transitionend', function() {
  if (this.classList.contains('open')) setTimeout(() => boxes[0].focus(), 100);
});

// Countdown
<?php if ($sentAt): ?>
startCountdown(Math.max(0, 600 - <?= (int)(time()-$sentAt) ?>));
<?php endif; ?>

// Form submit
document.getElementById('fp-form').addEventListener('submit', function(e) {
  clearFieldError('email');
  const em = document.getElementById('email').value.trim();
  if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
    showFieldError('email', 'Please enter a valid email address.');
    e.preventDefault(); return;
  }
  const btn = document.getElementById('fp-btn');
  btn.innerHTML = '<span class="spinner"></span> Sending…'; btn.disabled = true;
});
document.getElementById('code-form').addEventListener('submit', function() {
  if (boxes.map(b=>b.value).join('').length === 4) {
    vBtn.innerHTML = '<span class="spinner"></span> Verifying…'; vBtn.disabled = true;
  }
});

// Resend code via AJAX (stays inside modal)
function resendCode() {
  const btn = document.getElementById('resend-btn');
  const msg = document.getElementById('resend-msg');
  btn.disabled = true;
  btn.textContent = 'Sending…';
  msg.style.display = 'none';

  fetch('forgot-password.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=resend_code'
  })
  .then(r => r.json())
  .then(data => {
    msg.style.display = 'block';
    if (data.ok) {
      msg.style.color = 'var(--success)';
      msg.textContent = data.msg;
      // Reset countdown to 10:00
      startCountdown(600);
      // Clear the code boxes
      boxes.forEach(b => { b.value=''; b.classList.remove('filled','eb'); });
      upd();
      setTimeout(() => boxes[0].focus(), 100);
      // Cooldown: disable resend for 30s
      let cd = 30;
      btn.textContent = 'Resend (' + cd + 's)';
      const t = setInterval(() => {
        cd--;
        if (cd <= 0) { clearInterval(t); btn.disabled=false; btn.textContent='Resend'; }
        else btn.textContent = 'Resend (' + cd + 's)';
      }, 1000);
    } else {
      msg.style.color = 'var(--danger)';
      msg.textContent = data.msg;
      btn.disabled = false;
      btn.textContent = 'Resend';
    }
  })
  .catch(() => {
    msg.style.display = 'block';
    msg.style.color = 'var(--danger)';
    msg.textContent = 'Network error. Please try again.';
    btn.disabled = false;
    btn.textContent = 'Resend';
  });
}

// Countdown function (reusable)
function startCountdown(seconds) {
  const el = document.getElementById('countdown');
  if (!el) return;
  clearInterval(window._cdTimer);
  let s = seconds;
  function tick() {
    const m = Math.floor(s/60), sec = s%60;
    el.textContent = m + ':' + (sec<10?'0':'') + sec;
    if (s <= 0) { el.textContent='Expired'; el.style.color='var(--danger)'; return; }
    s--; window._cdTimer = setTimeout(tick, 1000);
  }
  tick();
}

// Starfield for brand panel
(function(){
  const c=document.getElementById('starfield'); if(!c)return;
  const ctx=c.getContext('2d'); let W,H,stars=[];
  function resize(){W=c.width=c.offsetWidth;H=c.height=c.offsetHeight;stars=[];
    for(let i=0;i<120;i++)stars.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*1.4+.2,sp:Math.random()*.008+.002,ph:Math.random()*Math.PI*2});}
  window.addEventListener('resize',resize);resize();
  function draw(){ctx.clearRect(0,0,W,H);const t=Date.now()*.001;
    stars.forEach(s=>{const a=.15+.55*Math.sin(t*s.sp*10+s.ph);ctx.beginPath();ctx.arc(s.x,s.y,s.r,0,Math.PI*2);ctx.fillStyle=`rgba(255,255,255,${a})`;ctx.fill();});
    requestAnimationFrame(draw);}
  draw();
})();
</script>
</body>
</html>