<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SIRAJ — Smart Street Lighting for a Darker, Smarter Sky</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --primary:#212B3A; --secondary:#677581; --accent:#4A90B8;
      --glow:#7EC8E3; --dark:#060C18; --success:#4CAF7D;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    html{scroll-behavior:smooth;}
    body{font-family:'Lato',sans-serif;background:var(--dark);color:white;overflow-x:hidden;}

    /* ── NAV ── */
    .welcome-nav{
      position:fixed;top:0;left:0;right:0;z-index:200;
      padding:0 48px;height:72px;
      display:flex;align-items:center;justify-content:space-between;
      transition:all .35s ease;
    }
    .welcome-nav.scrolled{
      background:rgba(6,12,24,.97);
      backdrop-filter:blur(18px);
      border-bottom:1px solid rgba(255,255,255,.06);
      box-shadow:0 4px 28px rgba(0,0,0,.5);
    }
    .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;}
    .nav-brand-star{font-size:22px;color:var(--glow);}
    .nav-brand-text{font-family:'Cinzel',serif;font-size:20px;font-weight:700;color:var(--glow);letter-spacing:3px;}
    .nav-links{display:flex;gap:36px;list-style:none;}
    .nav-links a{font-size:14px;color:rgba(255,255,255,.5);text-decoration:none;transition:color .2s;letter-spacing:.5px;}
    .nav-links a:hover{color:var(--glow);}
    .btn-login{
      padding:10px 26px;border-radius:0;
      background:var(--accent);color:white;border:none;
      font-size:14px;font-weight:700;font-family:'Lato',sans-serif;
      cursor:pointer;text-decoration:none;
      box-shadow:0 4px 18px rgba(74,144,184,.45);
      transition:all .25s;
    }
    .btn-login:hover{background:#3a7da8;transform:translateY(-1px);box-shadow:0 6px 24px rgba(74,144,184,.55);color:white;}

    /* ── HERO ── */
    .hero{
      min-height:100vh;
      background:
        radial-gradient(ellipse at 18% 22%,rgba(74,144,184,.1) 0%,transparent 52%),
        radial-gradient(ellipse at 82% 78%,rgba(126,200,227,.07) 0%,transparent 52%),
        linear-gradient(180deg,#020810 0%,#060C18 55%,#0A1428 100%);
      display:flex;flex-direction:column;align-items:center;justify-content:center;
      text-align:center;padding:120px 40px 100px;
      position:relative;overflow:hidden;
    }
    #starfield{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;}

    .lamp-left{position:absolute;bottom:0;left:5%;opacity:.35;pointer-events:none;}
    .lamp-right{position:absolute;bottom:0;right:5%;opacity:.35;pointer-events:none;transform:scaleX(-1);}

    .hero-eyebrow{
      font-size:11px;letter-spacing:5px;text-transform:uppercase;
      color:var(--accent);margin-bottom:22px;z-index:1;
    }
    .hero-title{
      font-family:'Cinzel',serif;
      font-size:clamp(32px,6.5vw,68px);
      font-weight:900;line-height:1.17;color:white;
      margin-bottom:24px;z-index:1;
      text-shadow:0 0 80px rgba(126,200,227,.15);
    }
    .hero-title span{color:var(--glow);}
    .hero-desc{
      font-size:clamp(15px,2vw,18px);color:rgba(255,255,255,.52);
      max-width:560px;line-height:1.85;margin-bottom:46px;z-index:1;
    }
    .hero-cta{display:flex;gap:16px;z-index:1;flex-wrap:wrap;justify-content:center;}
    .btn-cta-primary{
      padding:16px 44px;border-radius:0;
      background:var(--accent);color:white;border:none;
      font-size:16px;font-weight:700;font-family:'Cinzel',serif;
      cursor:pointer;text-decoration:none;
      box-shadow:0 6px 28px rgba(74,144,184,.5);
      transition:all .25s;
    }
    .btn-cta-primary:hover{background:#3a7da8;transform:translateY(-2px);box-shadow:0 10px 36px rgba(74,144,184,.55);color:white;}
    .btn-cta-outline{
      padding:16px 44px;border-radius:0;
      background:transparent;color:white;
      border:2px solid rgba(255,255,255,.28);
      font-size:16px;font-weight:700;font-family:'Cinzel',serif;
      cursor:pointer;text-decoration:none;
      transition:all .25s;
    }
    .btn-cta-outline:hover{border-color:white;background:rgba(255,255,255,.06);color:white;}

    .scroll-hint{
      position:absolute;bottom:32px;left:50%;transform:translateX(-50%);
      display:flex;flex-direction:column;align-items:center;gap:8px;
      font-size:10px;letter-spacing:3px;text-transform:uppercase;
      color:rgba(255,255,255,.22);z-index:1;
      animation:bounce 2.5s infinite;
    }
    @keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(10px)}}

    /* ── SECTION NAV BAR ── */
    .section-bar{
      background:rgba(6,12,24,.93);
      backdrop-filter:blur(14px);
      border-top:1px solid rgba(255,255,255,.05);
      border-bottom:1px solid rgba(255,255,255,.05);
      display:flex;justify-content:center;gap:52px;padding:0 40px;
    }
    .section-bar a{
      display:inline-block;padding:16px 0;font-size:13px;
      color:rgba(255,255,255,.4);text-decoration:none;letter-spacing:.5px;
      border-bottom:2px solid transparent;transition:all .22s;
    }
    .section-bar a:hover{color:var(--glow);border-bottom-color:var(--glow);}

    /* ── SECTIONS ── */
    .section{padding:110px 40px;}
    .sec-dark{background:linear-gradient(180deg,#060C18 0%,#0A1428 100%);}
    .sec-mid{background:#0D1625;}

    .sec-eyebrow{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:var(--accent);text-align:center;margin-bottom:16px;}
    .sec-title{font-family:'Cinzel',serif;font-size:clamp(22px,4vw,38px);font-weight:700;text-align:center;color:white;margin-bottom:14px;}
    .sec-sub{text-align:center;color:rgba(255,255,255,.38);font-size:15px;margin-bottom:64px;max-width:500px;margin-left:auto;margin-right:auto;}

    /* ── CARDS ── */
    .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;max-width:1000px;margin:0 auto;}
    .glass-card{
      background:rgba(255,255,255,.03);
      border:1px solid rgba(255,255,255,.07);
      border-radius:0;padding:38px 28px;text-align:center;
      transition:all .3s;position:relative;overflow:hidden;
    }
    .glass-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 50% 0%,rgba(74,144,184,.1) 0%,transparent 70%);opacity:0;transition:opacity .3s;}
    .glass-card:hover{transform:translateY(-7px);border-color:rgba(74,144,184,.3);box-shadow:0 24px 60px rgba(0,0,0,.4);}
    .glass-card:hover::before{opacity:1;}
    .card-icon{font-size:42px;margin-bottom:20px;display:block;}
    .card-title{font-family:'Cinzel',serif;font-size:17px;color:white;margin-bottom:14px;}
    .card-text{font-size:14px;color:rgba(255,255,255,.46);line-height:1.75;}

    /* ── ABOUT ── */
    .about-text{max-width:680px;margin:0 auto;text-align:center;font-size:16px;color:rgba(255,255,255,.55);line-height:2;}
    .about-stats{display:flex;justify-content:center;gap:70px;margin-top:60px;flex-wrap:wrap;}
    .stat-item{text-align:center;}
    .stat-number{font-family:'Cinzel',serif;font-size:44px;font-weight:700;color:var(--glow);display:block;line-height:1;margin-bottom:10px;}
    .stat-label{font-size:12px;color:rgba(255,255,255,.32);letter-spacing:2px;text-transform:uppercase;}

    /* ── CTA BAND ── */
    .cta-band{
      background:linear-gradient(135deg,#0A1428 0%,#0C1B32 100%);
      padding:100px 40px;text-align:center;
      border-top:1px solid rgba(255,255,255,.05);
    }
    .cta-band h2{font-family:'Cinzel',serif;font-size:clamp(22px,4vw,36px);margin-bottom:14px;}
    .cta-band p{color:rgba(255,255,255,.42);font-size:15px;margin-bottom:40px;}

    /* ── FOOTER ── */
    .footer{background:#020810;border-top:1px solid rgba(255,255,255,.05);padding:60px 48px 28px;}
    .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:52px;max-width:1000px;margin:0 auto 48px;}
    .footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
    .footer-brand-star{font-size:22px;color:var(--glow);}
    .footer-brand-name{font-family:'Cinzel',serif;font-size:19px;font-weight:700;color:var(--glow);letter-spacing:3px;}
    .footer-brand-text{font-size:14px;color:rgba(255,255,255,.32);line-height:1.8;}
    .footer-col-title{font-family:'Cinzel',serif;font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--glow);margin-bottom:18px;}
    .footer-links{list-style:none;display:flex;flex-direction:column;gap:12px;}
    .footer-links a{font-size:14px;color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
    .footer-links a:hover{color:var(--glow);}
    .footer-bottom{border-top:1px solid rgba(255,255,255,.05);padding-top:24px;text-align:center;font-size:13px;color:rgba(255,255,255,.18);}

    /* ── RESPONSIVE ── */

    
    /* ── LIGHT MODE for welcome page ── */
    body.light-mode { background: #EEF3F8; }
    body.light-mode #starfield { opacity: 0.25; }
    body.light-mode .hero {
      background:
        radial-gradient(ellipse at 18% 22%, rgba(74,144,184,0.12) 0%, transparent 52%),
        linear-gradient(180deg, #C8DCF0 0%, #D8E8F4 50%, #E4EFF8 100%);
    }
    body.light-mode .hero-eyebrow { color: #2d6d98; }
    body.light-mode .hero-title   { color: #0D1B2E; text-shadow: none; }
    body.light-mode .hero-title span { color: #2d6d98; }
    body.light-mode .hero-desc    { color: rgba(13,27,46,0.6); }
    body.light-mode .scroll-hint  { color: rgba(13,27,46,0.3); }
    body.light-mode .btn-cta-outline { border-color: rgba(13,27,46,0.3); color: #0D1B2E; }
    body.light-mode .btn-cta-outline:hover { border-color: #0D1B2E; background: rgba(13,27,46,0.06); }
    body.light-mode .section-bar  { background: rgba(255,255,255,0.88); border-color: rgba(13,27,46,0.08); }
    body.light-mode .section-bar a { color: rgba(13,27,46,0.45); }
    body.light-mode .section-bar a:hover { color: #2d6d98; border-bottom-color: #2d6d98; }
    body.light-mode .sec-dark  { background: linear-gradient(180deg, #E0ECF6 0%, #E8F0F8 100%); }
    body.light-mode .sec-mid   { background: #EBF2F9; }
    body.light-mode .sec-eyebrow { color: #2d6d98; }
    body.light-mode .sec-title    { color: #0D1B2E; }
    body.light-mode .sec-sub      { color: rgba(13,27,46,0.5); }
    body.light-mode .about-text   { color: rgba(13,27,46,0.6); }
    body.light-mode .stat-number  { color: #2d6d98; }
    body.light-mode .stat-label   { color: rgba(13,27,46,0.4); }
    body.light-mode .glass-card {
      background: rgba(255,255,255,0.75);
      border-color: rgba(13,27,46,0.10);
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    body.light-mode .glass-card:hover { border-color: rgba(74,144,184,0.3); }
    body.light-mode .card-title { color: #0D1B2E; }
    body.light-mode .card-text  { color: rgba(13,27,46,0.55); }
    body.light-mode .cta-band   { background: #D8E8F4; border-top-color: rgba(13,27,46,0.08); }
    body.light-mode .cta-band h2 { color: #0D1B2E; }
    body.light-mode .cta-band p  { color: rgba(13,27,46,0.55); }
    body.light-mode .footer {
      background: #C8D8E8;
      border-top-color: rgba(13,27,46,0.08);
    }
    body.light-mode .footer-brand-text  { color: rgba(13,27,46,0.45); }
    body.light-mode .footer-col-title   { color: #2d6d98; }
    body.light-mode .footer-links a     { color: rgba(13,27,46,0.4); }
    body.light-mode .footer-links a:hover { color: #2d6d98; }
    body.light-mode .footer-bottom      { color: rgba(13,27,46,0.3); border-top-color: rgba(13,27,46,0.08); }
    body.light-mode .welcome-nav .nav-brand-text { color: #0D1B2E; text-shadow: none; }
    body.light-mode .welcome-nav .nav-links a { color: rgba(13,27,46,0.5); }
    body.light-mode .welcome-nav .nav-links a:hover { color: #2d6d98; }
    body.light-mode .welcome-nav.scrolled { background: rgba(255,255,255,0.95); }
    body.light-mode .theme-toggle-welcome { background: rgba(13,27,46,0.06); border-color: rgba(13,27,46,0.15); color: rgba(13,27,46,0.5); }


    /* ── Welcome page responsive ── */
    @media (max-width: 1024px) {
      .hero { padding: 110px 32px 80px; }
      .footer-grid { gap: 36px; }
    }
    @media (max-width: 768px) {
      .welcome-nav { padding: 0 20px; height: 60px; }
      .nav-anchors { display: none; }
      .theme-toggle-welcome { display: none; }
      .hero { padding: 96px 24px 70px; }
      .hero-title { font-size: clamp(26px, 7vw, 48px); }
      .hero-desc  { font-size: 15px; margin-bottom: 32px; }
      .btn-hero   { padding: 13px 28px; font-size: 14px; }
      .section    { padding: 72px 24px; }
      .section-subtitle { font-size: 14px; margin-bottom: 44px; }
      .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; padding: 0 8px; }
      .footer { padding: 44px 28px 20px; }
      .section-bar { gap: 20px; padding: 0 16px; overflow-x: auto; }
      .about-stats { gap: 40px; margin-top: 44px; }
      .stat-number { font-size: 36px; }
      .cards-grid { grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; }
    }
    @media (max-width: 640px) {
      .welcome-nav { padding: 0 14px; height: 56px; }
      .nav-brand-text { font-size: 15px; letter-spacing: 2px; }
      .theme-toggle-welcome { display: none; }
      .btn-login { padding: 8px 18px; font-size: 13px; }
      .hero { padding: 88px 20px 64px; }
      .hero-eyebrow { font-size: 10px; letter-spacing: 3px; margin-bottom: 14px; }
      .hero-cta { gap: 12px; }
      .btn-hero { padding: 12px 24px; font-size: 13px; }
      .section { padding: 60px 20px; }
      .cards-grid { grid-template-columns: 1fr; gap: 12px; }
      .glass-card { padding: 28px 22px; }
      .footer-grid { grid-template-columns: 1fr; gap: 24px; }
      .about-stats { gap: 32px; flex-wrap: wrap; justify-content: center; }
      .stat-number { font-size: 32px; }
      .stat-label  { font-size: 11px; }
      .lamp-left, .lamp-right { display: none; }
      .section-bar { display: none; }
    }
    @media (max-width: 480px) {
      .hero-title { font-size: clamp(22px, 8vw, 36px); }
      .section-title { font-size: clamp(18px, 5vw, 26px); }
      .hero-desc { font-size: 14px; }
      .btn-hero { padding: 11px 20px; font-size: 13px; width: 100%; justify-content: center; }
      .hero-cta { flex-direction: column; width: 100%; max-width: 280px; }
      .footer-brand-name { font-size: 16px; }
      .footer { padding: 36px 20px 16px; }
    }
  </style>
</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<nav class="welcome-nav" id="welcome-nav">
  <a href="index.php" class="nav-brand">
    <img src="assets/img/logo.png" alt="SIRAJ" style="height:30px;filter:drop-shadow(0 0 8px rgba(126,200,227,0.5));"/>
    <span class="nav-brand-text">SIRAJ</span>
  </a>
  <ul class="nav-links">
    <li><a href="#mission">Mission &amp; Vision</a></li>
    <li><a href="#about">About</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div style="display:flex;align-items:center;gap:10px;">
    <button id="theme-toggle" class="theme-toggle-welcome" title="Toggle theme">&#10022; Dark</button>
    <a href="login.php" class="btn-login">Log In</a>
  </div>
</nav>

<!-- ══════════ HERO ══════════ -->
<section class="hero" id="hero">
  <canvas id="starfield"></canvas>

  <!-- Street lamp left -->
  <svg class="lamp-left" width="90" height="340" viewBox="0 0 90 340">
    <rect x="42" y="80" width="6" height="260" fill="rgba(255,255,255,0.55)"/>
    <rect x="42" y="80" width="48" height="5" fill="rgba(255,255,255,0.55)"/>
    <ellipse cx="90" cy="83" rx="15" ry="8" fill="rgba(126,200,227,0.7)"/>
    <ellipse cx="90" cy="83" rx="30" ry="20" fill="rgba(126,200,227,0.18)"/>
    <ellipse cx="90" cy="83" rx="55" ry="40" fill="rgba(126,200,227,0.06)"/>
  </svg>

  <!-- Street lamp right -->
  <svg class="lamp-right" width="90" height="340" viewBox="0 0 90 340">
    <rect x="42" y="80" width="6" height="260" fill="rgba(255,255,255,0.55)"/>
    <rect x="42" y="80" width="48" height="5" fill="rgba(255,255,255,0.55)"/>
    <ellipse cx="90" cy="83" rx="15" ry="8" fill="rgba(126,200,227,0.7)"/>
    <ellipse cx="90" cy="83" rx="30" ry="20" fill="rgba(126,200,227,0.18)"/>
    <ellipse cx="90" cy="83" rx="55" ry="40" fill="rgba(126,200,227,0.06)"/>
  </svg>

  <p class="hero-eyebrow">Smart City &nbsp;·&nbsp; Dark Sky &nbsp;·&nbsp; Clean Energy</p>

  <h1 class="hero-title">
    Smart Street Lighting<br>
    for a <span>Darker</span>, Smarter Sky
  </h1>

  <p class="hero-desc">
    Siraj is a smart monitoring platform designed to help cities understand
    and reduce light pollution while improving energy efficiency and
    supporting dark sky environments.
  </p>

  <div class="hero-cta">
    <a href="login.php" class="btn-cta-primary">Log In to Dashboard →</a>
    <a href="#about"    class="btn-cta-outline">Learn More</a>
  </div>

  <div class="scroll-hint">
    <span>Explore</span>
    <svg width="14" height="22" viewBox="0 0 14 22" fill="none">
      <rect x="5" y="1" width="4" height="9" rx="2" stroke="currentColor" stroke-width="1.5"/>
      <path d="M7 16L7 21M7 21L4 18M7 21L10 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
  </div>
</section>

<!-- ══════════ SECTION NAV ══════════ -->
<div class="section-bar">
  <a href="#mission">Mission &amp; Vision</a>
  <a href="#about">About</a>
  <a href="#features">Features</a>
  <a href="#contact">Contact</a>
</div>

<!-- ══════════ MISSION & VISION ══════════ -->
<section class="section sec-dark" id="mission">
  <p class="sec-eyebrow">Why We Exist</p>
  <h2 class="sec-title">Mission &amp; Vision</h2>
  <p class="sec-sub">What drives us forward every day</p>
  <div class="cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <div class="glass-card">
      <span class="card-icon">💡</span>
      <h3 class="card-title">Our Mission</h3>
      <p class="card-text">Our mission is to promote smarter urban lighting by providing real-time insights that help cities reduce unnecessary light pollution and create more sustainable nighttime environments.</p>
    </div>
    <div class="glass-card">
      <span class="card-icon">🛡️</span>
      <h3 class="card-title">Our Vision</h3>
      <p class="card-text">We envision cities where technology and environmental awareness work together to protect the beauty of the night sky while maintaining safe and efficient public lighting.</p>
    </div>
  </div>
</section>

<!-- ══════════ ABOUT ══════════ -->
<section class="section sec-mid" id="about">
  <p class="sec-eyebrow">The Platform</p>
  <h2 class="sec-title">About Siraj</h2>
  <p class="sec-sub">Intelligent monitoring for the cities of tomorrow</p>
  <p class="about-text">
    Siraj is an intelligent street lighting monitoring platform that collects environmental
    lighting data through connected sensors. The system analyzes this data to provide
    actionable insights about lighting conditions, helping city administrators and
    field employees understand patterns of light pollution and improve
    lighting efficiency across the entire city network.
  </p>
  <div class="about-stats">
    <div class="stat-item">
      <span class="stat-number">24/7</span>
      <span class="stat-label">Live Monitoring</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">100%</span>
      <span class="stat-label">Real-time Data</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">∞</span>
      <span class="stat-label">Scalable Areas</span>
    </div>
  </div>
</section>

<!-- ══════════ FEATURES ══════════ -->
<section class="section sec-dark" id="features">
  <p class="sec-eyebrow">Platform Capabilities</p>
  <h2 class="sec-title">Platform Features</h2>
  <p class="sec-sub">Everything you need to manage city lighting intelligently</p>
  <div class="cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));max-width:1100px;">
    <div class="glass-card">
      <span class="card-icon">✦</span>
      <h3 class="card-title">Real-time Status</h3>
      <p class="card-text">Live monitoring of every streetlight's on/off status and lux performance across all city areas.</p>
    </div>
    <div class="glass-card">
      <span class="card-icon">✦</span>
      <h3 class="card-title">Lux Visualization</h3>
      <p class="card-text">Interactive light intensity data and historical trend analysis for smarter city decisions.</p>
    </div>
    <div class="glass-card">
      <span class="card-icon">✦️</span>
      <h3 class="card-title">City Map</h3>
      <p class="card-text">Interactive map displaying every lamp location, area boundaries, and real-time status markers.</p>
    </div>
    <div class="glass-card">
      <span class="card-icon">✦</span>
      <h3 class="card-title">Fault Reporting</h3>
      <p class="card-text">Employees report faults instantly. Admins track and resolve issues with a full audit trail.</p>
    </div>
    <div class="glass-card">
      <span class="card-icon">✦</span>
      <h3 class="card-title">Team Management</h3>
      <p class="card-text">Admins create employee accounts and assign each one to a specific city area.</p>
    </div>
  </div>
</section>

<!-- ══════════ CTA BAND ══════════ -->
<div class="cta-band">
  <h2>Ready to Monitor Smarter?</h2>
  <p>Join city administrators already using Siraj to build better nighttime environments.</p>
  <a href="login.php" class="btn-cta-primary" style="font-size:17px;padding:17px 48px;display:inline-block;">
    Log In to Dashboard →
  </a>
</div>

<!-- ══════════ FOOTER ══════════ -->
<footer class="footer" id="contact">
  <div class="footer-grid">
    <div>
      <div class="footer-brand">
        <span class="footer-brand-star">✦</span>
        <span class="footer-brand-name">SIRAJ</span>
      </div>
      <p class="footer-brand-text">
        Smart Street Lighting Monitoring System.<br>
        Protecting the night sky, one city at a time.
      </p>
    </div>
    <div>
      <h4 class="footer-col-title">Navigation</h4>
      <ul class="footer-links">
        <li><a href="#mission">Mission &amp; Vision</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="login.php">Log In</a></li>
      </ul>
    </div>
    <div>
      <h4 class="footer-col-title">Contact</h4>
      <ul class="footer-links">
        <li><a href="mailto:Sirajteam.official@gmail.com">📧 Sirajteam.official@gmail.com</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    © 2026 Siraj Lighting. All rights reserved | Saudi Made 
  </div>
</footer>

<script>
// ── LIVE ANIMATED STARFIELD ───────────────────────────────
(function () {
  const canvas = document.getElementById('starfield');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, stars = [], shooters = [];

  // ── Init ──
  function resize() {
    W = canvas.width  = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
    initStars();
  }

  function initStars() {
    stars = [];
    // Layer 1: tiny dim background stars (far away)
    for (let i = 0; i < 200; i++) {
      stars.push({
        x:      Math.random() * W,
        y:      Math.random() * H,
        r:      Math.random() * 0.7 + 0.1,
        baseAlpha: Math.random() * 0.3 + 0.05,
        twinkleSpeed: Math.random() * 0.4 + 0.1,
        twinklePhase: Math.random() * Math.PI * 2,
        drift: (Math.random() - 0.5) * 0.04,   // very slow drift
        vy:    (Math.random() - 0.5) * 0.01,
      });
    }
    // Layer 2: medium stars
    for (let i = 0; i < 100; i++) {
      stars.push({
        x:      Math.random() * W,
        y:      Math.random() * H,
        r:      Math.random() * 1.2 + 0.5,
        baseAlpha: Math.random() * 0.5 + 0.2,
        twinkleSpeed: Math.random() * 0.8 + 0.2,
        twinklePhase: Math.random() * Math.PI * 2,
        drift: (Math.random() - 0.5) * 0.06,
        vy:    (Math.random() - 0.5) * 0.02,
      });
    }
    // Layer 3: bright foreground stars
    for (let i = 0; i < 40; i++) {
      stars.push({
        x:      Math.random() * W,
        y:      Math.random() * H,
        r:      Math.random() * 1.8 + 1.0,
        baseAlpha: Math.random() * 0.5 + 0.45,
        twinkleSpeed: Math.random() * 1.5 + 0.5,
        twinklePhase: Math.random() * Math.PI * 2,
        drift: (Math.random() - 0.5) * 0.09,
        vy:    (Math.random() - 0.5) * 0.025,
        glow: true,
      });
    }
  }

  window.addEventListener('resize', resize);
  resize();

  // ── Shooting star factory ──
  function spawnShooter() {
    const side = Math.random() > 0.5;
    shooters.push({
      x:     side ? -20 : W + 20,
      y:     Math.random() * H * 0.6,
      vx:    side ? (Math.random() * 5 + 4) : -(Math.random() * 5 + 4),
      vy:    Math.random() * 3 + 1,
      len:   Math.random() * 120 + 60,
      alpha: 1,
      fade:  Math.random() * 0.012 + 0.008,
    });
  }
  // Random shooting stars
  setInterval(() => { if (Math.random() < 0.55) spawnShooter(); }, 2200);

  // ── Draw loop ──
  function draw() {
    ctx.clearRect(0, 0, W, H);
    const t = Date.now() * 0.001;

    // Draw regular stars
    stars.forEach(s => {
      // Twinkle: sine wave on alpha
      const alpha = s.baseAlpha * (0.5 + 0.5 * Math.sin(t * s.twinkleSpeed + s.twinklePhase));

      // Slow drift
      s.x += s.drift;
      s.y += s.vy;

      // Wrap around edges
      if (s.x < -5)  s.x = W + 5;
      if (s.x > W+5) s.x = -5;
      if (s.y < -5)  s.y = H + 5;
      if (s.y > H+5) s.y = -5;

      if (s.glow) {
        // Glowing halo for bright stars
        const grd = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.r * 4);
        grd.addColorStop(0,   `rgba(200,235,255,${alpha})`);
        grd.addColorStop(0.4, `rgba(150,210,255,${alpha * 0.4})`);
        grd.addColorStop(1,   `rgba(100,180,255,0)`);
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r * 4, 0, Math.PI * 2);
        ctx.fillStyle = grd;
        ctx.fill();
      }

      // Star core
      ctx.beginPath();
      ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
      ctx.fillStyle = s.glow
        ? `rgba(210,240,255,${alpha})`
        : `rgba(255,255,255,${alpha})`;
      ctx.fill();

      // Cross sparkle for brightest stars
      if (s.glow && alpha > 0.55) {
        ctx.save();
        ctx.globalAlpha = alpha * 0.35;
        ctx.strokeStyle = 'rgba(200,235,255,1)';
        ctx.lineWidth = 0.5;
        ctx.beginPath();
        ctx.moveTo(s.x - s.r*3, s.y); ctx.lineTo(s.x + s.r*3, s.y);
        ctx.moveTo(s.x, s.y - s.r*3); ctx.lineTo(s.x, s.y + s.r*3);
        ctx.stroke();
        ctx.restore();
      }
    });

    // Draw shooting stars
    for (let i = shooters.length - 1; i >= 0; i--) {
      const sh = shooters[i];
      sh.x += sh.vx;
      sh.y += sh.vy;
      sh.alpha -= sh.fade;
      if (sh.alpha <= 0) { shooters.splice(i, 1); continue; }

      const tailX = sh.x - sh.vx * (sh.len / Math.abs(sh.vx));
      const tailY = sh.y - sh.vy * (sh.len / Math.abs(sh.vx));

      const grd = ctx.createLinearGradient(sh.x, sh.y, tailX, tailY);
      grd.addColorStop(0,   `rgba(255,255,255,${sh.alpha})`);
      grd.addColorStop(0.3, `rgba(180,225,255,${sh.alpha * 0.6})`);
      grd.addColorStop(1,   `rgba(100,180,255,0)`);

      ctx.beginPath();
      ctx.moveTo(sh.x, sh.y);
      ctx.lineTo(tailX, tailY);
      ctx.strokeStyle = grd;
      ctx.lineWidth   = 1.5;
      ctx.lineCap     = 'round';
      ctx.stroke();

      // Bright head
      ctx.beginPath();
      ctx.arc(sh.x, sh.y, 1.5, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255,255,255,${sh.alpha})`;
      ctx.fill();
    }

    requestAnimationFrame(draw);
  }
  draw();
})();

// ── Navbar scroll effect ──────────────────────────────────
window.addEventListener('scroll', function() {
  document.getElementById('welcome-nav')
    .classList.toggle('scrolled', window.scrollY > 60);
});


// ── Theme Toggle ─────────────────────────────────────────
(function() {
  const KEY = 'siraj_theme';
  const saved = localStorage.getItem(KEY) || 'dark';
  if (saved === 'light') applyLight();

  const btn = document.getElementById('theme-toggle');
  if (btn) {
    btn.textContent = saved === 'light' ? '☀' : '✦';
    btn.addEventListener('click', function() {
      if (document.body.classList.contains('light-mode')) {
        document.body.classList.remove('light-mode');
        localStorage.setItem(KEY, 'dark');
        this.textContent = '✦';
      } else {
        applyLight();
        this.textContent = '☀';
      }
    });
  }
  function updateWelcomeBtn(btn, mode) {
    btn.innerHTML = mode === 'light'
      ? '&#9728; Light'
      : '&#10022; Dark';
  }
  function applyLight() {
    document.body.classList.add('light-mode');
    localStorage.setItem(KEY, 'light');
  }
})();
</script>
</body>
</html>
