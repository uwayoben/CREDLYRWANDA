
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Our Team — Credlyrw</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --green:#00C853;--green-dark:#007A32;--green-dim:#00C85322;
    --ink:#0A0F0D;--ink2:#1A2420;--slate:#8A9E96;--white:#F5FAF7;
    --card:#111A15;--border:#1E2E24;--gold:#FFD166;
    --blue:#3B82F6;--purple:#8B5CF6;--orange:#F59E0B;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  body{font-family:'DM Sans',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden;cursor:none}
  .cursor{width:12px;height:12px;background:var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;mix-blend-mode:screen;transition:transform .1s}
  .cursor-ring{width:40px;height:40px;border:1.5px solid var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;opacity:.4}
  ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:var(--ink)}::-webkit-scrollbar-thumb{background:var(--green-dark);border-radius:2px}

  /* ── Nav ── */
  nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:20px 60px;background:rgba(10,15,13,.95);backdrop-filter:blur(20px);border-bottom:1px solid var(--border)}
  .logo{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-1px;text-decoration:none;color:var(--white)}
  .logo span{color:var(--green)}
  .nav-links{display:flex;gap:28px;list-style:none;align-items:center}
  .nav-links a{color:var(--slate);text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
  .nav-links a:hover{color:var(--white)}
  .nav-links a.active{color:var(--green)}
  .nav-cta{background:var(--green)!important;color:var(--ink)!important;padding:10px 22px;border-radius:100px;font-weight:600!important}
  .nav-cta:hover{box-shadow:0 6px 20px #00C85340;transform:translateY(-1px)}

  /* ── Hero ── */
  .hero{
    min-height:60vh;display:flex;flex-direction:column;
    justify-content:center;align-items:center;
    text-align:center;padding:140px 24px 80px;
    position:relative;overflow:hidden;
  }
  .hero::before{
    content:'';position:absolute;inset:0;
    background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
    background-size:55px 55px;opacity:.3;
    mask-image:radial-gradient(ellipse 70% 80% at 50% 50%,black 30%,transparent 100%);
  }
  .orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
  .orb-1{width:500px;height:500px;background:radial-gradient(circle,#00C85312 0%,transparent 70%);top:-100px;left:50%;transform:translateX(-50%);animation:op 7s ease-in-out infinite}
  .orb-2{width:250px;height:250px;background:radial-gradient(circle,#3B82F60E 0%,transparent 70%);bottom:0;right:10%;animation:op 9s ease-in-out infinite reverse}
  .orb-3{width:200px;height:200px;background:radial-gradient(circle,#8B5CF60E 0%,transparent 70%);top:30%;left:5%;animation:op 11s ease-in-out infinite}
  @keyframes op{0%,100%{opacity:.4}50%{opacity:1}}

  .hero-label{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--green-dim);border:1px solid #00C85344;
    color:var(--green);font-size:11px;font-weight:600;
    letter-spacing:2px;text-transform:uppercase;
    padding:7px 18px;border-radius:100px;margin-bottom:24px;
    animation:fu .7s ease both;
  }
  .hero-label::before{content:'';width:6px;height:6px;background:var(--green);border-radius:50%;animation:blink 2s infinite}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

  .hero h1{
    font-family:'Syne',sans-serif;
    font-size:clamp(44px,8vw,88px);
    font-weight:800;letter-spacing:-4px;line-height:.95;
    animation:fu .7s .15s ease both;
  }
  .hero h1 .line2{color:var(--green)}
  .hero-sub{
    font-size:17px;font-weight:300;color:var(--slate);
    max-width:520px;line-height:1.8;margin:24px auto 0;
    animation:fu .7s .3s ease both;
  }
  @keyframes fu{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}

  /* ── Count strip ── */
  .count-strip{
    display:flex;justify-content:center;gap:0;
    border-top:1px solid var(--border);border-bottom:1px solid var(--border);
    background:var(--ink2);
  }
  .count-item{flex:1;max-width:200px;padding:28px 20px;text-align:center;border-right:1px solid var(--border)}
  .count-item:last-child{border-right:none}
  .count-num{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;color:var(--green);line-height:1}
  .count-lbl{font-size:12px;color:var(--slate);margin-top:4px;letter-spacing:.5px}

  /* ── Team section ── */
  .team-section{padding:100px 60px;max-width:1300px;margin:0 auto}

  /* Featured member — Managing Director */
  .featured-member{
    display:grid;grid-template-columns:1fr 1fr;
    gap:0;border:1px solid var(--border);border-radius:28px;
    overflow:hidden;margin-bottom:32px;
    position:relative;
  }
  .featured-avatar-side{
    background:linear-gradient(135deg,#0D1F14 0%,#1A2420 100%);
    padding:60px 50px;display:flex;flex-direction:column;
    justify-content:flex-end;position:relative;overflow:hidden;
    min-height:480px;
  }
  .featured-avatar-side::before{
    content:'';position:absolute;inset:0;
    background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
    background-size:40px 40px;opacity:.3;
  }
  .avatar-circle-lg{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);
    width:200px;height:200px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-family:'Syne',sans-serif;font-weight:800;font-size:56px;
    color:var(--ink);z-index:1;
    box-shadow:0 0 0 8px #00C85318, 0 0 0 16px #00C85310, 0 0 60px #00C85330;
  }
  .ring-anim{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);
    width:220px;height:220px;border-radius:50%;
    border:1px solid #00C85333;z-index:0;
    animation:ringPulse 3s ease-in-out infinite;
  }
  .ring-anim-2{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);
    width:260px;height:260px;border-radius:50%;
    border:1px solid #00C85322;z-index:0;
    animation:ringPulse 3s 1s ease-in-out infinite;
  }
  @keyframes ringPulse{0%,100%{opacity:.4;transform:translate(-50%,-60%) scale(1)}50%{opacity:1;transform:translate(-50%,-60%) scale(1.03)}}

  .featured-tag{
    position:relative;z-index:2;
    display:inline-flex;align-items:center;gap:6px;
    background:var(--green);color:var(--ink);
    font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
    padding:6px 14px;border-radius:100px;margin-bottom:14px;
    width:fit-content;
  }
  .featured-name-lg{
    font-family:'Syne',sans-serif;font-size:32px;font-weight:800;
    letter-spacing:-1.5px;line-height:1;position:relative;z-index:2;
  }
  .featured-role-lg{color:var(--slate);font-size:15px;margin-top:6px;position:relative;z-index:2}

  .featured-info-side{
    background:var(--card);padding:60px 50px;
    display:flex;flex-direction:column;justify-content:center;
    border-left:1px solid var(--border);
  }
  .featured-quote{
    font-size:22px;font-weight:300;font-style:italic;
    line-height:1.5;color:var(--white);margin-bottom:32px;
    position:relative;padding-left:24px;
  }
  .featured-quote::before{
    content:'"';position:absolute;left:0;top:-8px;
    font-family:'Syne',sans-serif;font-size:48px;font-weight:800;
    color:var(--green);line-height:1;
  }
  .featured-bio{font-size:14px;color:var(--slate);line-height:1.8;margin-bottom:32px}
  .skills-wrap{display:flex;flex-wrap:wrap;gap:8px}
  .skill-chip{
    padding:6px 14px;background:var(--green-dim);
    border:1px solid #00C85333;border-radius:100px;
    font-size:12px;color:var(--green);font-weight:500;
  }
  .social-row{display:flex;gap:10px;margin-top:28px}
  .social-btn{
    width:38px;height:38px;border:1px solid var(--border);
    border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-size:16px;cursor:pointer;transition:border-color .2s,background .2s;
    text-decoration:none;
  }
  .social-btn:hover{border-color:var(--green);background:var(--green-dim)}

  /* ── Team grid (remaining 3) ── */
  .team-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}

  .member-card{
    background:var(--card);border:1px solid var(--border);
    border-radius:22px;overflow:hidden;
    transition:transform .4s cubic-bezier(.23,1,.32,1),border-color .3s;
    position:relative;
  }
  .member-card:hover{transform:translateY(-8px);border-color:var(--green)}
  .member-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--green),transparent);
    opacity:0;transition:opacity .3s;
  }
  .member-card:hover::before{opacity:1}

  .card-top{
    padding:40px 32px 28px;text-align:center;
    position:relative;overflow:hidden;
  }
  .card-top-bg{
    position:absolute;inset:0;
    background:linear-gradient(135deg,#0D1F14 0%,var(--ink2) 100%);
  }
  .card-top-grid{
    position:absolute;inset:0;
    background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
    background-size:30px 30px;opacity:.3;
  }
  .avatar-circle{
    width:100px;height:100px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-family:'Syne',sans-serif;font-weight:800;font-size:30px;
    color:var(--ink);margin:0 auto 16px;
    position:relative;z-index:1;
    box-shadow:0 0 0 4px var(--ink2),0 0 0 6px #00C85322;
    transition:box-shadow .3s;
  }
  .member-card:hover .avatar-circle{box-shadow:0 0 0 4px var(--ink2),0 0 0 8px #00C85344,0 0 24px #00C85322}

  .member-name{
    font-family:'Syne',sans-serif;font-size:20px;font-weight:800;
    letter-spacing:-.5px;position:relative;z-index:1;
  }
  .member-role{
    font-size:13px;color:var(--slate);margin-top:4px;
    position:relative;z-index:1;line-height:1.4;
  }
  .role-badge{
    display:inline-flex;align-items:center;gap:6px;
    margin-top:12px;padding:5px 12px;border-radius:100px;
    font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
    position:relative;z-index:1;
  }
  .rb-dev{background:#3B82F618;border:1px solid #3B82F633;color:var(--blue)}
  .rb-finance{background:#F59E0B18;border:1px solid #F59E0B33;color:var(--orange)}
  .rb-security{background:#8B5CF618;border:1px solid #8B5CF633;color:var(--purple)}
  .rb-director{background:var(--green-dim);border:1px solid #00C85333;color:var(--green)}

  .card-body{padding:24px 28px 28px}
  .member-bio{font-size:13px;color:var(--slate);line-height:1.75;margin-bottom:18px}
  .member-skills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px}
  .ms-tag{
    padding:4px 10px;background:var(--ink2);border:1px solid var(--border);
    border-radius:100px;font-size:11px;color:var(--slate);
    transition:border-color .2s,color .2s;
  }
  .member-card:hover .ms-tag{border-color:#1E3A28;color:var(--white)}
  .card-footer{
    display:flex;justify-content:space-between;align-items:center;
    padding-top:16px;border-top:1px solid var(--border);
  }
  .member-number{
    font-family:'Syne',sans-serif;font-size:36px;font-weight:800;
    color:var(--border);line-height:1;
    transition:color .3s;
  }
  .member-card:hover .member-number{color:#1E3A28}
  .card-socials{display:flex;gap:8px}
  .cs-btn{
    width:32px;height:32px;border:1px solid var(--border);
    border-radius:8px;display:flex;align-items:center;justify-content:center;
    font-size:13px;cursor:pointer;transition:all .2s;
    text-decoration:none;
  }
  .cs-btn:hover{border-color:var(--green);background:var(--green-dim)}

  /* ── Values strip ── */
  .values-strip{
    background:var(--ink2);border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
    padding:80px 60px;
  }
  .values-inner{max-width:1200px;margin:0 auto}
  .values-header{text-align:center;margin-bottom:56px}
  .values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
  .value-card{
    background:var(--ink);border:1px solid var(--border);
    border-radius:18px;padding:28px 24px;
    transition:border-color .3s,transform .3s;text-align:center;
  }
  .value-card:hover{border-color:var(--green);transform:translateY(-4px)}
  .value-icon{font-size:32px;margin-bottom:14px}
  .value-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:8px;letter-spacing:-.3px}
  .value-desc{font-size:13px;color:var(--slate);line-height:1.7}

  /* ── CTA ── */
  .cta-band{
    padding:100px 60px;text-align:center;
    position:relative;overflow:hidden;
    border-top:1px solid var(--border);
  }
  .cta-band::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 50% 100%,#00C85310 0%,transparent 70%)}
  .cta-band h2{font-family:'Syne',sans-serif;font-size:clamp(32px,5vw,56px);font-weight:800;letter-spacing:-2px;line-height:1.1;margin-bottom:16px;position:relative}
  .cta-band p{color:var(--slate);font-size:16px;margin-bottom:36px;max-width:500px;margin-left:auto;margin-right:auto;line-height:1.7;position:relative}
  .cta-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;position:relative}
  .btn-primary{background:var(--green);color:var(--ink);border:none;padding:15px 34px;border-radius:100px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:transform .2s,box-shadow .3s;text-decoration:none;display:inline-block}
  .btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 32px #00C85440}
  .btn-ghost{background:transparent;color:var(--white);border:1px solid var(--border);padding:15px 34px;border-radius:100px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
  .btn-ghost:hover{border-color:var(--green);background:var(--green-dim)}

  /* ── Footer ── */
  footer{background:var(--ink2);border-top:1px solid var(--border);padding:50px 60px 32px}
  .footer-bottom{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--slate);flex-wrap:wrap;gap:10px}
  .footer-bottom a{color:var(--green);text-decoration:none;font-weight:600}

  /* ── Reveal ── */
  .reveal{opacity:0;transform:translateY(36px);transition:opacity .8s ease,transform .8s ease}
  .reveal.visible{opacity:1;transform:translateY(0)}
  .rd1{transition-delay:.1s}.rd2{transition-delay:.2s}.rd3{transition-delay:.3s}.rd4{transition-delay:.4s}

  @media(max-width:900px){
    nav{padding:16px 24px}.nav-links{display:none}
    .hero{padding:110px 24px 60px}
    .team-section{padding:60px 24px}
    .featured-member{grid-template-columns:1fr}
    .featured-avatar-side{min-height:320px}
    .team-grid{grid-template-columns:1fr}
    .values-grid{grid-template-columns:1fr 1fr}
    .values-strip,.cta-band{padding:60px 24px}
    footer{padding:40px 24px 28px}
    .footer-bottom{flex-direction:column;text-align:center}
    .count-strip{flex-wrap:wrap}
    .count-item{min-width:140px}
  }
</style>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- Nav -->
<nav>
  <a href="/"
   class="logo">Credly<span>rw</span></a>
  <ul class="nav-links">
    <li><a href="/">Home</a></li>
    <li><a href="credlyrw.html#features">Features</a></li>
    <li><a href="credlyrw.html#reports">Reports</a></li>
    <li><a href="team.html" class="active">Our Team</a></li>
    <li><a href="credlyrw.html#clients">Clients</a></li>
    <a href="{{ route('contact') }}" class="nav-cta">Get Started</a>
    <a href="{{ url('admin') }}" class="nav-cta">Login</a>
  </ul>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
  <div class="hero-label">The People Behind Credlyrw</div>
  <h1>Built by<br><span class="line2">passionate people</span></h1>
  <p class="hero-sub">A small, dedicated team of Rwandan professionals who believe lending businesses deserve better tools — and built them.</p>
</section>

<!-- Count strip -->
<div class="count-strip">
  <div class="count-item"><div class="count-num">4</div><div class="count-lbl">Team Members</div></div>
  <div class="count-item"><div class="count-num">3+</div><div class="count-lbl">Active Clients</div></div>
  <div class="count-item"><div class="count-num">500+</div><div class="count-lbl">Loans Managed</div></div>
  <div class="count-item"><div class="count-num">100%</div><div class="count-lbl">Made in Rwanda</div></div>
</div>

<!-- Team section -->
<div class="team-section">

  <!-- Featured: Managing Director -->
  <div class="featured-member reveal">
    <div class="featured-avatar-side">
      <div class="ring-anim"></div>
      <div class="ring-anim-2"></div>
      <div class="avatar-circle-lg" style="background:linear-gradient(135deg,#007A32,#00C853)">IM</div>
      <div class="featured-tag">⭐ Managing Director</div>
      <div class="featured-name-lg">ISHIMWE Moses</div>
      <div class="featured-role-lg">Founder & Managing Director — Credlyrw</div>
    </div>
    <div class="featured-info-side">
      <div class="featured-quote">
        We built Credlyrw because we saw Rwandan lenders drowning in paperwork while their businesses had so much potential to grow.
      </div>
      <p class="featured-bio">
        ISHIMWE Moses leads Credlyrw with a clear vision — to make professional lending management accessible to every NDFSP, SACCO, and MFI in Rwanda. Under his leadership, Credlyrw has grown from an idea into a platform trusted by multiple institutions across the country, with a full suite of BNR compliance tools, business analytics, and customer management capabilities.
      </p>
      <div class="skills-wrap">
        <span class="skill-chip">Strategic Leadership</span>
        <span class="skill-chip">Business Development</span>
        <span class="skill-chip">Financial Systems</span>
        <span class="skill-chip">Rwanda Fintech</span>
        <span class="skill-chip">BNR Regulations</span>
        <span class="skill-chip">Team Management</span>
      </div>
      <div class="social-row">
        <a href="#" class="social-btn" title="LinkedIn">💼</a>
        <a href="tel:0786748001" class="social-btn" title="Phone">📞</a>
        <a href="mailto:info@credly.co.rw" class="social-btn" title="Email">✉️</a>
      </div>
    </div>
  </div>

  <!-- Team grid: 3 members -->
  <div class="team-grid">

    <!-- UWAYO Benjamin -->
    <div class="member-card reveal rd1">
      <div class="card-top">
        <div class="card-top-bg"></div>
        <div class="card-top-grid"></div>
        <div class="avatar-circle" style="background:linear-gradient(135deg,#1A3C6E,#3B82F6)">UB</div>
        <div class="member-name">UWAYO Benjamin</div>
        <div class="member-role">Software Developer</div>
        <div class="role-badge rb-dev">💻 Developer</div>
      </div>
      <div class="card-body">
        <p class="member-bio">
          Benjamin is the lead developer behind Credlyrw's core platform. He architects and builds the loan management engine, BNR export system, and all backend features that make the platform powerful and reliable for lending institutions.
        </p>
        <div class="member-skills">
          <span class="ms-tag">Laravel / PHP</span>
          <span class="ms-tag">Filament v5</span>
          <span class="ms-tag">MySQL</span>
          <span class="ms-tag">REST APIs</span>
          <span class="ms-tag">Excel Exports</span>
          <span class="ms-tag">Vue.js</span>
        </div>
        <div class="card-footer">
          <div class="member-number">02</div>
          <div class="card-socials">
            <a href="#" class="cs-btn" title="LinkedIn">💼</a>
            <a href="#" class="cs-btn" title="GitHub">🐙</a>
            <a href="mailto:info@credlyrw.rw" class="cs-btn" title="Email">✉️</a>
          </div>
        </div>
      </div>
    </div>

    <!-- ISHIMWE Landrada -->
    <div class="member-card reveal rd2">
      <div class="card-top">
        <div class="card-top-bg"></div>
        <div class="card-top-grid"></div>
        <div class="avatar-circle" style="background:linear-gradient(135deg,#B7770D,#F59E0B)">IL</div>
        <div class="member-name">ISHIMWE Landrada</div>
        <div class="member-role">Finance Manager</div>
        <div class="role-badge rb-finance">💰 Finance</div>
      </div>
      <div class="card-body">
        <p class="member-bio">
          Landrada oversees all financial operations at Credlyrw and plays a key role in shaping the platform's financial features. Her deep understanding of NDFSP regulations and BNR requirements ensures that every report and calculation in the system is accurate and compliant.
        </p>
        <div class="member-skills">
          <span class="ms-tag">BNR Compliance</span>
          <span class="ms-tag">Financial Reporting</span>
          <span class="ms-tag">NDFSP Regulations</span>
          <span class="ms-tag">Loan Classification</span>
          <span class="ms-tag">Accounting</span>
        </div>
        <div class="card-footer">
          <div class="member-number">03</div>
          <div class="card-socials">
            <a href="#" class="cs-btn" title="LinkedIn">💼</a>
            <a href="mailto:info@credlyrw.rw" class="cs-btn" title="Email">✉️</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Bienvenue FIKIRI -->
    <div class="member-card reveal rd3">
      <div class="card-top">
        <div class="card-top-bg"></div>
        <div class="card-top-grid"></div>
        <div class="avatar-circle" style="background:linear-gradient(135deg,#6C3483,#8B5CF6)">BF</div>
        <div class="member-name">Bienvenue FIKIRI</div>
        <div class="member-role">Software Developer &<br>Cyber Security Officer</div>
        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1;margin-top:10px">
          <div class="role-badge rb-dev" style="margin-top:0">💻 Developer</div>
          <div class="role-badge rb-security" style="margin-top:0">🛡️ Security</div>
        </div>
      </div>
      <div class="card-body">
        <p class="member-bio">
          Bienvenue wears two hats — as a developer he builds and maintains key platform features, and as Cyber Security Officer he ensures that all client data, login systems, and infrastructure meet the highest security standards including 2FA implementation and audit logging.
        </p>
        <div class="member-skills">
          <span class="ms-tag">Cyber Security</span>
          <span class="ms-tag">2FA Systems</span>
          <span class="ms-tag">PHP / Laravel</span>
          <span class="ms-tag">Penetration Testing</span>
          <span class="ms-tag">Audit & Compliance</span>
          <span class="ms-tag">Network Security</span>
        </div>
        <div class="card-footer">
          <div class="member-number">04</div>
          <div class="card-socials">
            <a href="#" class="cs-btn" title="LinkedIn">💼</a>
            <a href="#" class="cs-btn" title="GitHub">🐙</a>
            <a href="mailto:info@credly.co.rw" class="cs-btn" title="Email">✉️</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Values -->
<div class="values-strip">
  <div class="values-inner">
    <div class="values-header">
      <p style="font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--green);margin-bottom:14px" class="reveal">What Drives Us</p>
      <h2 style="font-family:'Syne',sans-serif;font-size:clamp(28px,4vw,44px);font-weight:800;letter-spacing:-1.5px;max-width:100%" class="reveal rd1">Our values, built into every line of code</h2>
    </div>
    <div class="values-grid">
      <div class="value-card reveal rd1">
        <div class="value-icon">🇷🇼</div>
        <div class="value-title">Built for Rwanda</div>
        <p class="value-desc">Every feature is designed around Rwanda's BNR regulations, NDFSP requirements, and local lending realities.</p>
      </div>
      <div class="value-card reveal rd2">
        <div class="value-icon">🔒</div>
        <div class="value-title">Security First</div>
        <p class="value-desc">Financial data deserves bank-grade protection. We build security into the foundation, not as an afterthought.</p>
      </div>
      <div class="value-card reveal rd3">
        <div class="value-icon">⚡</div>
        <div class="value-title">Simplicity Wins</div>
        <p class="value-desc">Powerful tools should be easy to use. We make complex financial management feel simple and intuitive.</p>
      </div>
      <div class="value-card reveal rd4">
        <div class="value-icon">🤝</div>
        <div class="value-title">Client Success</div>
        <p class="value-desc">Our clients' growth is our growth. We obsess over their results, not just their subscription.</p>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="cta-band">
  <h2 class="reveal">Want to work with our team?</h2>
  <p class="reveal rd1">Whether you want to get your institution on Credlyrw or just have a conversation about what we can do for you — we would love to hear from you.</p>
  <div class="cta-actions reveal rd2">
    <a href="get-started.html" class="btn-primary">🚀 Get Started Today</a>
    <a href="tel:0786748001" class="btn-ghost">📞 Call 0786 748 001</a>
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="footer-bottom">
    <span>© 2025 Credlyrw. All rights reserved. Powered by <strong style="color:var(--green)">E-STORE FASITA</strong></span>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <a href="credlyrw.html">Home</a>
      <a href="team.html" style="color:var(--green)">Team</a>
      <a href="get-started.html">Get Started</a>
      <a href="mailto:info@credlyrw.rw" style="color:var(--green)">info@credlyrw.rw</a>
      <a href="tel:0786748001" style="color:var(--green)">0786 748 001</a>
    </div>
  </div>
</footer>

<script>
  // Cursor
  const cursor=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
  let mx=0,my=0,rx=0,ry=0;
  document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.transform=`translate(${mx-6}px,${my-6}px)`});
  (function tick(){rx+=(mx-rx-18)*.1;ry+=(my-ry-18)*.1;ring.style.transform=`translate(${rx}px,${ry}px)`;requestAnimationFrame(tick)})();
  document.querySelectorAll('a,button').forEach(el=>{el.addEventListener('mouseenter',()=>ring.style.opacity='.1');el.addEventListener('mouseleave',()=>ring.style.opacity='.4')});

  // Reveal on scroll
  const obs=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible')})},{threshold:.1});
  document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));
</script>
</body>
</html>
