
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Get Started — Credlyrw</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --green:#00C853;--green-dark:#007A32;--green-dim:#00C85322;
    --ink:#0A0F0D;--ink2:#1A2420;--slate:#8A9E96;--white:#F5FAF7;
    --card:#111A15;--border:#1E2E24;--gold:#FFD166;
    --blue:#3B82F6;--purple:#8B5CF6;--red:#EF4444;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  body{font-family:'DM Sans',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden;cursor:none;min-height:100vh}
  .cursor{width:12px;height:12px;background:var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;mix-blend-mode:screen}
  .cursor-ring{width:36px;height:36px;border:1.5px solid var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;opacity:.5}
  ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:var(--ink)}::-webkit-scrollbar-thumb{background:var(--green-dark);border-radius:2px}

  /* Nav */
  nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:20px 60px;background:rgba(10,15,13,.95);backdrop-filter:blur(20px);border-bottom:1px solid var(--border)}
  .logo{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-1px;text-decoration:none;color:var(--white)}
  .logo span{color:var(--green)}
  .nav-back{display:flex;align-items:center;gap:8px;color:var(--slate);text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
  .nav-back:hover{color:var(--green)}
  .nav-back svg{width:16px;height:16px}

  /* Page layout */
  .page{min-height:100vh;display:grid;grid-template-columns:1fr 1fr;padding-top:80px}

  /* Left — info panel */
  .info-panel{
    background:linear-gradient(135deg,#0D1F14 0%,var(--ink2) 100%);
    border-right:1px solid var(--border);
    padding:70px 60px;
    display:flex;flex-direction:column;justify-content:center;
    position:relative;overflow:hidden;
  }
  .info-panel::before{
    content:'';position:absolute;inset:0;
    background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
    background-size:50px 50px;opacity:.25;
    mask-image:radial-gradient(ellipse 90% 80% at 20% 50%,black 30%,transparent 100%);
  }
  .orb-left{position:absolute;width:400px;height:400px;background:radial-gradient(circle,#00C85312 0%,transparent 70%);top:-80px;left:-80px;border-radius:50%;filter:blur(60px);pointer-events:none}

  .info-content{position:relative;z-index:1}
  .info-badge{display:inline-flex;align-items:center;gap:8px;background:var(--green-dim);border:1px solid #00C85344;color:var(--green);font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;padding:7px 16px;border-radius:100px;margin-bottom:24px}
  .info-badge::before{content:'';width:6px;height:6px;background:var(--green);border-radius:50%;animation:blink 2s ease-in-out infinite}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

  .info-panel h1{font-family:'Syne',sans-serif;font-size:clamp(32px,4vw,48px);font-weight:800;letter-spacing:-2px;line-height:1.1;margin-bottom:16px}
  .info-panel h1 span{color:var(--green)}
  .info-panel p{color:var(--slate);font-size:16px;font-weight:300;line-height:1.8;margin-bottom:40px;max-width:420px}

  /* Contact info cards */
  .contact-cards{display:flex;flex-direction:column;gap:12px;margin-bottom:40px}
  .contact-card{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;background:var(--card);border:1px solid var(--border);border-radius:14px;transition:border-color .3s}
  .contact-card:hover{border-color:var(--green)}
  .contact-card a{text-decoration:none;color:inherit;display:flex;align-items:flex-start;gap:14px;width:100%}
  .cc-icon{width:38px;height:38px;background:var(--green-dim);border:1px solid #00C85333;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;margin-top:1px}
  .cc-label{font-size:11px;color:var(--slate);font-weight:500;letter-spacing:.5px;text-transform:uppercase;margin-bottom:3px}
  .cc-value{font-size:14px;font-weight:600;color:var(--white)}
  .cc-sub{font-size:12px;color:var(--slate);margin-top:2px}

  /* Plan selector */
  .plan-selector{margin-top:4px}
  .plan-selector-label{font-size:11px;color:var(--slate);font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px}
  .plan-pills{display:flex;gap:8px;flex-wrap:wrap}
  .plan-pill{padding:8px 18px;border:1px solid var(--border);border-radius:100px;font-size:13px;color:var(--slate);background:var(--card);cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif}
  .plan-pill:hover{border-color:var(--green);color:var(--green)}
  .plan-pill.active{border-color:var(--green);color:var(--green);background:var(--green-dim)}

  /* Clients preview */
  .trusted-by{margin-top:40px;padding-top:32px;border-top:1px solid var(--border)}
  .trusted-label{font-size:11px;color:var(--slate);letter-spacing:1px;text-transform:uppercase;margin-bottom:14px}
  .trusted-logos{display:flex;gap:10px;flex-wrap:wrap}
  .trusted-pill{background:var(--ink);border:1px solid var(--border);border-radius:100px;padding:6px 16px;font-size:12px;font-weight:600;color:var(--slate)}

  /* Right — form panel */
  .form-panel{background:var(--ink);padding:70px 60px;display:flex;flex-direction:column;justify-content:center;overflow-y:auto}
  .form-header{margin-bottom:36px}
  .form-header h2{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:8px}
  .form-header p{color:var(--slate);font-size:14px;line-height:1.6}

  form{display:flex;flex-direction:column;gap:18px;max-width:480px}

  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

  .field{display:flex;flex-direction:column;gap:6px}
  .field label{font-size:12px;font-weight:600;color:var(--slate);letter-spacing:.5px;text-transform:uppercase}
  .field input,.field select,.field textarea{
    background:var(--card);border:1px solid var(--border);
    border-radius:12px;padding:14px 16px;
    font-family:'DM Sans',sans-serif;font-size:14px;color:var(--white);
    outline:none;transition:border-color .2s,box-shadow .2s;
    width:100%;
  }
  .field input::placeholder,.field textarea::placeholder{color:#3A4A42}
  .field input:focus,.field select:focus,.field textarea:focus{
    border-color:var(--green);
    box-shadow:0 0 0 3px var(--green-dim);
  }
  .field select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238A9E96' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;cursor:pointer}
  .field select option{background:var(--ink2);color:var(--white)}
  .field textarea{resize:vertical;min-height:100px}

  /* Plan cards in form */
  .plan-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .plan-card-opt{
    border:1.5px solid var(--border);border-radius:12px;
    padding:14px 12px;cursor:pointer;
    background:var(--card);transition:all .2s;
    text-align:center;
  }
  .plan-card-opt:hover{border-color:var(--green);background:var(--green-dim)}
  .plan-card-opt.selected{border-color:var(--green);background:var(--green-dim)}
  .plan-card-opt input[type=radio]{display:none}
  .pco-name{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;margin-bottom:3px}
  .pco-desc{font-size:11px;color:var(--slate)}
  .pco-check{width:18px;height:18px;border:1.5px solid var(--border);border-radius:50%;margin:8px auto 0;display:flex;align-items:center;justify-content:center;transition:all .2s}
  .plan-card-opt.selected .pco-check{background:var(--green);border-color:var(--green)}
  .plan-card-opt.selected .pco-check::after{content:'✓';font-size:10px;color:var(--ink);font-weight:700}

  /* Agreement */
  .agreement{display:flex;align-items:flex-start;gap:10px;cursor:pointer}
  .agreement input[type=checkbox]{width:16px;height:16px;accent-color:var(--green);margin-top:2px;flex-shrink:0;cursor:pointer}
  .agreement span{font-size:13px;color:var(--slate);line-height:1.5}
  .agreement a{color:var(--green);text-decoration:none}

  /* Submit button */
  .btn-submit{
    background:var(--green);color:var(--ink);
    border:none;border-radius:12px;padding:16px;
    font-family:'DM Sans',sans-serif;font-size:15px;font-weight:700;
    cursor:pointer;transition:transform .2s,box-shadow .3s;
    display:flex;align-items:center;justify-content:center;gap:10px;
    width:100%;
  }
  .btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 32px #00C85440}
  .btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
  .btn-submit .spinner{width:18px;height:18px;border:2px solid var(--ink);border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;display:none}
  @keyframes spin{to{transform:rotate(360deg)}}

  /* Success state */
  .success-screen{
    display:none;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;padding:40px;max-width:480px;
  }
  .success-icon{
    width:80px;height:80px;background:var(--green-dim);border:2px solid var(--green);
    border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:36px;margin:0 auto 24px;
    animation:popIn .5s cubic-bezier(.175,.885,.32,1.275) both;
  }
  @keyframes popIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
  .success-screen h3{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:12px}
  .success-screen p{color:var(--slate);font-size:15px;line-height:1.7;margin-bottom:28px}
  .success-details{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;width:100%;margin-bottom:24px;text-align:left}
  .success-detail-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;color:var(--slate)}
  .success-detail-item:last-child{border-bottom:none}
  .success-detail-item span{color:var(--white);font-weight:600}
  .btn-back-home{background:transparent;border:1px solid var(--border);color:var(--white);border-radius:12px;padding:14px 28px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
  .btn-back-home:hover{border-color:var(--green);color:var(--green)}

  /* Field error */
  .field.error input,.field.error select,.field.error textarea{border-color:var(--red)}
  .field-error{font-size:12px;color:var(--red);margin-top:-2px}

  /* Progress steps */
  .progress-steps{display:flex;align-items:center;gap:0;margin-bottom:32px}
  .step{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--slate);font-weight:500}
  .step-num{width:24px;height:24px;border-radius:50%;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;transition:all .3s}
  .step.active .step-num{background:var(--green);border-color:var(--green);color:var(--ink)}
  .step.done .step-num{background:var(--green-dark);border-color:var(--green-dark);color:var(--white)}
  .step.active{color:var(--white)}
  .step-line{flex:1;height:1px;background:var(--border);margin:0 8px;min-width:20px}

  @media(max-width:900px){
    nav{padding:16px 24px}
    .page{grid-template-columns:1fr;padding-top:72px}
    .info-panel{padding:50px 24px;border-right:none;border-bottom:1px solid var(--border)}
    .form-panel{padding:50px 24px}
    .form-row{grid-template-columns:1fr}
    .plan-cards{grid-template-columns:1fr}
    form{max-width:100%}
  }
</style>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- Nav -->
<nav>
  <a href="/" class="logo">Credly<span>rw</span></a>
  <a href="/" class="nav-back">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Back to Home
  </a>
</nav>

<div class="page">

  <!-- Left Info Panel -->
  <div class="info-panel">
    <div class="orb-left"></div>
    <div class="info-content">

      <div class="info-badge">Talk to Sales</div>

      <h1>Let's get your<br>institution <span>started</span></h1>
      <p>Fill in the form and one of our team members will contact you within 24 hours to set up your institution on Credlyrw — fully guided, no technical knowledge needed.</p>

      <!-- Contact details -->
      <div class="contact-cards">
        <div class="contact-card">
          <a href="tel:0786748001">
            <div class="cc-icon">📞</div>
            <div>
              <div class="cc-label">Phone / WhatsApp</div>
              <div class="cc-value">0786 748 001</div>
              <div class="cc-sub">Available Mon–Sat, 8am–6pm</div>
            </div>
          </a>
        </div>
        <div class="contact-card">
          <a href="mailto:info@credlyrw.rw">
            <div class="cc-icon">✉️</div>
            <div>
              <div class="cc-label">Email</div>
              <div class="cc-value">info@credlyrw.rw</div>
              <div class="cc-sub">We reply within 24 hours</div>
            </div>
          </a>
        </div>
        <div class="contact-card">
          <div class="cc-icon">📍</div>
          <div>
            <div class="cc-label">Office Address</div>
            <div class="cc-value">Ingenzi Building, 2nd Floor</div>
            <div class="cc-sub">Huye District, Southern Province, Rwanda</div>
          </div>
        </div>
      </div>

      <!-- Trusted by -->
      <div class="trusted-by">
        <div class="trusted-label">Trusted by</div>
        <div class="trusted-logos">
          <div class="trusted-pill">PROSPERA CAPITAL</div>
          <div class="trusted-pill">CASHCARE Finance</div>
          <div class="trusted-pill">GROWTH FINANCE</div>
          <div class="trusted-pill">+ More</div>
        </div>
      </div>

    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="form-panel">

    <!-- Progress -->
    <div class="progress-steps">
      <div class="step active" id="step1">
        <div class="step-num">1</div>
        <span>Your Info</span>
      </div>
      <div class="step-line"></div>
      <div class="step" id="step2">
        <div class="step-num">2</div>
        <span>Institution</span>
      </div>
      <div class="step-line"></div>
      <div class="step" id="step3">
        <div class="step-num">3</div>
        <span>Plan</span>
      </div>
    </div>

    <div class="form-header">
      <h2>Request a Demo</h2>
      <p>Tell us about your institution and we'll set up the perfect plan for you.</p>
    </div>

    <!-- Form -->
    <form id="contactForm" novalidate>

      <!-- Step 1: Personal Info -->
      <div id="formStep1">
        <div style="display:flex;flex-direction:column;gap:18px">
          <div class="form-row">
            <div class="field" id="f-firstname">
              <label>First Name *</label>
              <input type="text" name="firstname" placeholder="Jean" autocomplete="given-name">
              <span class="field-error" id="err-firstname"></span>
            </div>
            <div class="field" id="f-lastname">
              <label>Last Name *</label>
              <input type="text" name="lastname" placeholder="Nsabimana" autocomplete="family-name">
              <span class="field-error" id="err-lastname"></span>
            </div>
          </div>
          <div class="field" id="f-email">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="you@yourcompany.rw" autocomplete="email">
            <span class="field-error" id="err-email"></span>
          </div>
          <div class="field" id="f-phone">
            <label>Phone / WhatsApp *</label>
            <input type="tel" name="phone" placeholder="+250 7XX XXX XXX" autocomplete="tel">
            <span class="field-error" id="err-phone"></span>
          </div>
          <div class="field" id="f-role">
            <label>Your Role *</label>
            <select name="role">
              <option value="">Select your role...</option>
              <option>Director / CEO</option>
              <option>Finance Manager</option>
              <option>Accountant</option>
              <option>Operations Manager</option>
              <option>IT Manager</option>
              <option>Other</option>
            </select>
            <span class="field-error" id="err-role"></span>
          </div>
          <button type="button" class="btn-submit" onclick="nextStep(1)">
            Continue <span>→</span>
          </button>
        </div>
      </div>

      <!-- Step 2: Institution Info -->
      <div id="formStep2" style="display:none">
        <div style="display:flex;flex-direction:column;gap:18px">
          <div class="field" id="f-institution">
            <label>Institution Name *</label>
            <input type="text" name="institution" placeholder="e.g., PROSPERA CAPITAL">
            <span class="field-error" id="err-institution"></span>
          </div>
          <div class="field" id="f-type">
            <label>Institution Type *</label>
            <select name="type">
              <option value="">Select type...</option>
              <option>NDFSP</option>
              <option>SACCO</option>
              <option>MFI (Microfinance Institution)</option>
              <option>Money Lending Business</option>
              <option>Capital / Investment Firm</option>
              <option>Cooperative</option>
              <option>Other</option>
            </select>
            <span class="field-error" id="err-type"></span>
          </div>
          <div class="form-row">
            <div class="field" id="f-district">
              <label>District</label>
              <select name="district">
                <option value="">Select district...</option>
                <option>Gasabo</option><option>Kicukiro</option><option>Nyarugenge</option>
                <option>Bugesera</option><option>Gatsibo</option><option>Kayonza</option>
                <option>Kirehe</option><option>Ngoma</option><option>Nyagatare</option>
                <option>Rwamagana</option><option>Burera</option><option>Gakenke</option>
                <option>Gicumbi</option><option>Musanze</option><option>Rulindo</option>
                <option>Gisagara</option><option>Huye</option><option>Kamonyi</option>
                <option>Muhanga</option><option>Nyamagabe</option><option>Nyanza</option>
                <option>Nyaruguru</option><option>Ruhango</option>
                <option>Karongi</option><option>Ngororero</option><option>Nyabihu</option>
                <option>Nyamasheke</option><option>Rubavu</option><option>Rusizi</option>
                <option>Rutsiro</option>
              </select>
            </div>
            <div class="field" id="f-loans">
              <label>Active Loans (approx.)</label>
              <select name="loans">
                <option value="">Select range...</option>
                <option>Less than 50</option>
                <option>50 – 100</option>
                <option>100 – 300</option>
                <option>300 – 500</option>
                <option>500 – 1,000</option>
                <option>Over 1,000</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>How did you hear about us?</label>
            <select name="source">
              <option value="">Select...</option>
              <option>Referral from another institution</option>
              <option>Social Media</option>
              <option>Google Search</option>
              <option>WhatsApp</option>
              <option>Word of mouth</option>
              <option>Other</option>
            </select>
          </div>
          <div style="display:flex;gap:12px">
            <button type="button" class="btn-submit" style="background:transparent;border:1px solid var(--border);color:var(--white);flex:0 0 auto;padding:16px 24px" onclick="prevStep(2)">← Back</button>
            <button type="button" class="btn-submit" style="flex:1" onclick="nextStep(2)">Continue →</button>
          </div>
        </div>
      </div>

      <!-- Step 3: Plan + Message -->
      <div id="formStep3" style="display:none">
        <div style="display:flex;flex-direction:column;gap:18px">
          <div class="field">
            <label>Interested Plan</label>
            <div class="plan-cards">
              <label class="plan-card-opt" id="pc-starter" onclick="selectPlan('starter')">
                <input type="radio" name="plan" value="Starter">
                <div class="pco-name">Starter</div>
                <div class="pco-desc">Small lenders, 1 institution</div>
                <div class="pco-check"></div>
              </label>
              <label class="plan-card-opt selected" id="pc-professional" onclick="selectPlan('professional')">
                <input type="radio" name="plan" value="Professional" checked>
                <div class="pco-name">Professional</div>
                <div class="pco-desc">Full features, up to 3 branches</div>
                <div class="pco-check" style="background:var(--green);border-color:var(--green)"><span style="font-size:10px;color:var(--ink);font-weight:700">✓</span></div>
              </label>
              <label class="plan-card-opt" id="pc-enterprise" onclick="selectPlan('enterprise')">
                <input type="radio" name="plan" value="Enterprise">
                <div class="pco-name">Enterprise</div>
                <div class="pco-desc">Unlimited institutions + API</div>
                <div class="pco-check"></div>
              </label>
            </div>
          </div>
          <div class="field">
            <label>Message / Special Requirements</label>
            <textarea name="message" placeholder="Tell us anything specific about your institution, current challenges, or questions you have..."></textarea>
          </div>
          <div class="field">
            <label>Preferred Contact Time</label>
            <select name="contact_time">
              <option value="">Any time</option>
              <option>Morning (8am – 12pm)</option>
              <option>Afternoon (12pm – 5pm)</option>
              <option>Evening (5pm – 7pm)</option>
            </select>
          </div>
          <label class="agreement">
            <input type="checkbox" id="agree" required>
            <span>I agree that Credlyrw may contact me via phone or email to discuss my request. <a href="#">Privacy Policy</a></span>
          </label>
          <div style="display:flex;gap:12px">
            <button type="button" class="btn-submit" style="background:transparent;border:1px solid var(--border);color:var(--white);flex:0 0 auto;padding:16px 24px" onclick="prevStep(3)">← Back</button>
            <button type="submit" class="btn-submit" id="submitBtn" style="flex:1">
              <div class="spinner" id="spinner"></div>
              <span id="btnText">🚀 Submit Request</span>
            </button>
          </div>
        </div>
      </div>

    </form>

    <!-- Success screen -->
    <div class="success-screen" id="successScreen">
      <div class="success-icon">🎉</div>
      <h3>Request Received!</h3>
      <p>Thank you for your interest in Credlyrw. Our sales team will contact you within <strong style="color:var(--green)">24 hours</strong> to get you set up.</p>
      <div class="success-details">
        <div class="success-detail-item">📞 <span>0786 748 001</span> — We'll call you</div>
        <div class="success-detail-item">✉️ <span>info@credlyrw.co.rw</span> — Confirmation sent</div>
        <div class="success-detail-item">📍 <span>Ingenzi Building, 2nd Floor, Huye</span></div>
      </div>
      <a href="credlyrw.html" class="btn-back-home">← Back to Homepage</a>
    </div>

  </div><!-- /form-panel -->

</div><!-- /page -->

<script>
  // Cursor
  const cursor=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
  let mx=0,my=0,rx=0,ry=0;
  document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.transform=`translate(${mx-6}px,${my-6}px)`});
  (function tick(){rx+=(mx-rx-18)*.1;ry+=(my-ry-18)*.1;ring.style.transform=`translate(${rx}px,${ry}px)`;requestAnimationFrame(tick)})();
  document.querySelectorAll('a,button,label,select,input,textarea').forEach(el=>{el.addEventListener('mouseenter',()=>ring.style.opacity='.1');el.addEventListener('mouseleave',()=>ring.style.opacity='.5')});

  // Step management
  let currentStep = 1;

  function showStep(n) {
    [1,2,3].forEach(i => {
      document.getElementById('formStep'+i).style.display = i===n ? 'block' : 'none';
      const s = document.getElementById('step'+i);
      s.classList.remove('active','done');
      if(i===n) s.classList.add('active');
      if(i<n) s.classList.add('done');
    });
    currentStep = n;
  }

  function nextStep(from) {
    if(from===1 && !validateStep1()) return;
    if(from===2 && !validateStep2()) return;
    showStep(from+1);
    document.querySelector('.form-panel').scrollTop = 0;
  }

  function prevStep(from) { showStep(from-1); }

  function validateStep1() {
    let ok = true;
    const fields = [
      {id:'f-firstname', name:'firstname', msg:'Please enter your first name'},
      {id:'f-lastname',  name:'lastname',  msg:'Please enter your last name'},
      {id:'f-email',     name:'email',     msg:'Please enter a valid email'},
      {id:'f-phone',     name:'phone',     msg:'Please enter your phone number'},
      {id:'f-role',      name:'role',      msg:'Please select your role'},
    ];
    fields.forEach(f => {
      const el = document.querySelector(`[name="${f.name}"]`);
      const wrap = document.getElementById(f.id);
      const err = document.getElementById('err-'+f.name);
      const val = el.value.trim();
      let valid = val.length > 0;
      if(f.name==='email') valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      if(!valid) { wrap.classList.add('error'); err.textContent = f.msg; ok=false; }
      else { wrap.classList.remove('error'); err.textContent = ''; }
    });
    return ok;
  }

  function validateStep2() {
    let ok = true;
    [
      {id:'f-institution', name:'institution', msg:'Please enter your institution name'},
      {id:'f-type',        name:'type',        msg:'Please select your institution type'},
    ].forEach(f => {
      const el = document.querySelector(`[name="${f.name}"]`);
      const wrap = document.getElementById(f.id);
      const err = document.getElementById('err-'+f.name);
      if(!el.value.trim()) { wrap.classList.add('error'); err.textContent=f.msg; ok=false; }
      else { wrap.classList.remove('error'); err.textContent=''; }
    });
    return ok;
  }

  // Plan selector
  function selectPlan(plan) {
    ['starter','professional','enterprise'].forEach(p => {
      const el = document.getElementById('pc-'+p);
      el.classList.remove('selected');
      el.querySelector('.pco-check').style.background = '';
      el.querySelector('.pco-check').style.borderColor = '';
      el.querySelector('.pco-check').innerHTML = '';
    });
    const sel = document.getElementById('pc-'+plan);
    sel.classList.add('selected');
    const chk = sel.querySelector('.pco-check');
    chk.style.background = 'var(--green)';
    chk.style.borderColor = 'var(--green)';
    chk.innerHTML = '<span style="font-size:10px;color:var(--ink);font-weight:700">✓</span>';
    sel.querySelector('input[type=radio]').checked = true;
  }

  // Form submit
  document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if(!document.getElementById('agree').checked) {
      alert('Please agree to the terms to continue.');
      return;
    }

    const btn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');

    btn.disabled = true;
    spinner.style.display = 'block';
    btnText.textContent = 'Sending...';

    // Gather form data
    const fd = new FormData(this);
    const data = Object.fromEntries(fd.entries());

    // Send via mailto as fallback (since no backend)
    const body = `
New Credlyrw Sales Request
===========================
Name: ${data.firstname} ${data.lastname}
Email: ${data.email}
Phone: ${data.phone}
Role: ${data.role}

Institution: ${data.institution}
Type: ${data.type}
District: ${data.district || 'Not specified'}
Active Loans: ${data.loans || 'Not specified'}
Source: ${data.source || 'Not specified'}

Plan Interested: ${data.plan || 'Professional'}
Contact Time: ${data.contact_time || 'Any time'}
Message: ${data.message || 'None'}
    `.trim();

    // Simulate sending (replace with actual API/backend call)
    await new Promise(r => setTimeout(r, 1800));

    // Open email client as fallback
    const mailtoLink = `mailto:info@credlyrw.rw?subject=New%20Sales%20Request%20-%20${encodeURIComponent(data.institution || 'Unknown')}&body=${encodeURIComponent(body)}`;
    window.location.href = mailtoLink;

    // Show success
    setTimeout(() => {
      document.getElementById('contactForm').style.display = 'none';
      document.querySelector('.form-header').style.display = 'none';
      document.querySelector('.progress-steps').style.display = 'none';
      const ss = document.getElementById('successScreen');
      ss.style.display = 'flex';
      ss.style.animation = 'fu .6s ease both';
    }, 500);
  });

  // Input real-time validation
  document.querySelectorAll('input,select,textarea').forEach(el => {
    el.addEventListener('input', () => {
      const wrap = el.closest('.field');
      if(wrap && wrap.classList.contains('error') && el.value.trim()) {
        wrap.classList.remove('error');
        const id = wrap.id;
        if(id) {
          const err = document.getElementById('err-'+id.replace('f-',''));
          if(err) err.textContent = '';
        }
      }
    });
  });
</script>
</body>
</html>
