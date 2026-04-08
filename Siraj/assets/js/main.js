/* SIRAJ — Main JS */

// ── Toast ─────────────────────────────────────────────────
function showToast(msg, type='success', duration=3500) {
  let t = document.getElementById('siraj-toast');
  if (!t) { t = document.createElement('div'); t.id='siraj-toast'; t.className='toast'; document.body.appendChild(t); }
  t.className = `toast toast-${type}`;
  t.innerHTML = `<span>${type==='success'?'✓':'✕'}</span>${msg}`;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), duration);
}

// ── Password toggle ───────────────────────────────────────
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', function() {
    const inp = this.closest('.input-wrapper').querySelector('input');
    if (!inp) return;
    const hide = inp.type === 'password';
    inp.type = hide ? 'text' : 'password';
    this.textContent = hide ? '🙈' : '👁';
  });
});

// ── Field errors ──────────────────────────────────────────
function showFieldError(id, msg) {
  const f = document.getElementById(id), e = document.getElementById(id+'_error');
  if (f) f.classList.add('error');
  if (e) { e.textContent = msg; e.classList.add('visible'); }
}
function clearFieldError(id) {
  const f = document.getElementById(id), e = document.getElementById(id+'_error');
  if (f) f.classList.remove('error');
  if (e) e.classList.remove('visible');
}

// ── Alert ─────────────────────────────────────────────────
function showAlert(id, msg, type='error') {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = `alert alert-${type} visible`;
  el.textContent = msg;
}

// ── PW strength ───────────────────────────────────────────
function checkPasswordStrength(pw) {
  const rules = {
    length:    pw.length >= 8,
    uppercase: /[A-Z]/.test(pw),
    lowercase: /[a-z]/.test(pw),
    number:    /[0-9]/.test(pw),
    special:   /[^A-Za-z0-9]/.test(pw),
  };
  return { rules, score: Object.values(rules).filter(Boolean).length };
}
function updateStrengthUI(pw, containerId) {
  const c = document.getElementById(containerId);
  if (!c) return;
  c.classList.add('visible');
  const { rules, score } = checkPasswordStrength(pw);
  const fill = c.querySelector('.pw-strength-fill');
  const colors = ['','#e05c5c','#f5c542','#f5c542','#4caf7d','#4caf7d'];
  const widths  = ['0%','20%','40%','65%','85%','100%'];
  if (fill) { fill.style.width = widths[score]; fill.style.background = colors[score]; }
  Object.entries(rules).forEach(([k, met]) => {
    const el = c.querySelector(`[data-rule="${k}"]`);
    if (el) { el.classList.toggle('met', met); el.querySelector('.rule-icon').textContent = met?'✓':'○'; }
  });
}

// ── Modal helpers ─────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); setTimeout(() => m.querySelector('input,textarea')?.focus(), 300); }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
// Close on overlay click or Escape
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});
document.querySelectorAll('.modal-close').forEach(b => {
  b.addEventListener('click', () => b.closest('.modal-overlay').classList.remove('open'));
});

// ── Auto-dismiss flash messages from URL ──────────────────
(function() {
  const p = new URLSearchParams(window.location.search);
  const map = { account_created:['Account created!','success'], logged_out:['Logged out.','success'], pw_updated:['Password updated!','success'] };
  if (p.has('msg') && map[p.get('msg')]) showToast(...map[p.get('msg')]);
})();

// ── Status refresh ────────────────────────────────────────
document.getElementById('refresh-status')?.addEventListener('click', function() {
  this.innerHTML = '<span class="spinner"></span>';
  setTimeout(() => location.reload(), 400);
});

// ── Countdown timer ───────────────────────────────────────
function startCountdown(seconds, displayId) {
  const el = document.getElementById(displayId);
  if (!el) return;
  const iv = setInterval(() => {
    const m = Math.floor(seconds/60), s = seconds%60;
    el.textContent = m+':'+(s<10?'0':'')+s;
    if (seconds <= 0) { el.textContent='Expired'; el.style.color='var(--danger)'; clearInterval(iv); }
    seconds--;
  }, 1000);
}

// ── Code input boxes ──────────────────────────────────────
document.querySelectorAll('.code-box').forEach((box, i, boxes) => {
  box.addEventListener('keydown', function(e) {
    if (!/^\d$/.test(e.key) && !['Backspace','Tab','ArrowLeft','ArrowRight'].includes(e.key)) e.preventDefault();
    if (e.key==='Backspace') { e.preventDefault(); this.value=''; if(i>0) boxes[i-1].focus(); checkCode(); }
  });
  box.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').slice(-1);
    if (this.value && i<boxes.length-1) boxes[i+1].focus();
    checkCode();
  });
});
document.querySelector('.code-box')?.addEventListener('paste', function(e) {
  e.preventDefault();
  const text = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
  document.querySelectorAll('.code-box').forEach((b,i) => b.value=text[i]||'');
  checkCode();
});
function checkCode() {
  const code = Array.from(document.querySelectorAll('.code-box')).map(b=>b.value).join('');
  const btn = document.getElementById('verify-code-btn');
  if (btn) btn.disabled = code.length < 4;
}

// ── Starfield ─────────────────────────────────────────────
function initStarfield() {
  const canvas = document.getElementById('starfield');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, stars=[];
  function resize() { W=canvas.width=canvas.offsetWidth; H=canvas.height=canvas.offsetHeight; }
  window.addEventListener('resize', resize); resize();
  for (let i=0;i<240;i++) stars.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*1.5+.3,speed:Math.random()*.006+.002,phase:Math.random()*Math.PI*2});
  function draw() {
    ctx.clearRect(0,0,W,H);const t=Date.now()*.001;
    stars.forEach(s=>{const a=.3+.5*Math.sin(t*s.speed*10+s.phase);ctx.beginPath();ctx.arc(s.x,s.y,s.r,0,Math.PI*2);ctx.fillStyle=`rgba(255,255,255,${a})`;ctx.fill();});
    requestAnimationFrame(draw);
  }
  draw();
}
initStarfield();


// ── Dark / Light Mode Toggle ─────────────────────────────
(function() {
  const KEY = 'siraj_theme';
  const saved = localStorage.getItem(KEY) || 'dark';

  // Apply saved preference immediately
  if (saved === 'light') {
    document.body.classList.add('light-mode');
  }

  // Wait for DOM
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    // Set initial icon
    updateIcon(btn);

    btn.addEventListener('click', function() {
      const isNowLight = document.body.classList.toggle('light-mode');
      localStorage.setItem(KEY, isNowLight ? 'light' : 'dark');
      updateIcon(this);
    });
  });

  function updateIcon(btn) {
    const isLight = document.body.classList.contains('light-mode');
    const dark  = btn.querySelector('.t-dark');
    const light = btn.querySelector('.t-light');
    if (dark)  dark.style.display  = isLight ? 'none'   : 'inline';
    if (light) light.style.display = isLight ? 'inline' : 'none';
    // Fallback for text-only buttons
    if (!dark && !light) btn.textContent = isLight ? '◯' : '☽';
    btn.title = isLight ? 'Switch to Dark Mode' : 'Switch to Light Mode';
  }
})();
