
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Credlyrw — Smart Lending Management for Rwanda</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --green:#00C853;--green-dark:#007A32;--green-dim:#00C85322;
    --ink:#0A0F0D;--ink2:#1A2420;--slate:#8A9E96;--white:#F5FAF7;
    --card:#111A15;--border:#1E2E24;--gold:#FFD166;
    --blue:#3B82F6;--blue-dim:#3B82F618;--purple:#8B5CF6;--red:#EF4444;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  body{font-family:'DM Sans',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden;cursor:none}
  .cursor{width:12px;height:12px;background:var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;mix-blend-mode:screen}
  .cursor-ring{width:36px;height:36px;border:1.5px solid var(--green);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;opacity:.5}
  ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:var(--ink)}::-webkit-scrollbar-thumb{background:var(--green-dark);border-radius:2px}

  nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:20px 60px;border-bottom:1px solid transparent;transition:all .4s}
  nav.scrolled{background:rgba(10,15,13,.95);backdrop-filter:blur(20px);border-bottom-color:var(--border)}
  .logo{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-1px}
  .logo span{color:var(--green)}
  .nav-links{display:flex;gap:28px;list-style:none}
  .nav-links a{color:var(--slate);text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
  .nav-links a:hover{color:var(--white)}
  .nav-cta{background:var(--green)!important;color:var(--ink)!important;padding:10px 22px;border-radius:100px;font-weight:600!important;transition:transform .2s,box-shadow .2s!important}
  .nav-cta:hover{transform:translateY(-2px);box-shadow:0 8px 24px #00C85340}

  .hero{min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:120px 24px 80px;position:relative;overflow:hidden}
  .hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:60px 60px;opacity:.4;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 40%,transparent 100%)}
  .orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
  .orb-1{width:600px;height:600px;background:radial-gradient(circle,#00C85314 0%,transparent 70%);top:-100px;left:50%;transform:translateX(-50%);animation:op 7s ease-in-out infinite}
  .orb-2{width:300px;height:300px;background:radial-gradient(circle,#3B82F610 0%,transparent 70%);bottom:80px;right:-50px;animation:op 9s ease-in-out infinite reverse}
  .orb-3{width:200px;height:200px;background:radial-gradient(circle,#8B5CF610 0%,transparent 70%);top:30%;left:-60px;animation:op 11s ease-in-out infinite}
  @keyframes op{0%,100%{opacity:.5}50%{opacity:1}}
  .hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--green-dim);border:1px solid #00C85344;color:var(--green);font-size:12px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;padding:8px 18px;border-radius:100px;margin-bottom:28px;animation:fu .8s ease both}
  .hero-badge::before{content:'';width:7px;height:7px;background:var(--green);border-radius:50%;animation:blink 2s ease-in-out infinite}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
  h1{font-family:'Syne',sans-serif;font-size:clamp(40px,7vw,84px);font-weight:800;line-height:1;letter-spacing:-3px;max-width:920px;animation:fu .8s .2s ease both}
  h1 em{font-style:normal;color:var(--green)}
  .hero-sub{font-size:18px;font-weight:300;color:var(--slate);max-width:580px;line-height:1.7;margin:24px auto 40px;animation:fu .8s .4s ease both}
  .hero-actions{display:flex;gap:16px;flex-wrap:wrap;justify-content:center;animation:fu .8s .6s ease both}
  .btn-primary{background:var(--green);color:var(--ink);border:none;padding:16px 36px;border-radius:100px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:transform .2s,box-shadow .3s;text-decoration:none;display:inline-block}
  .btn-primary:hover{transform:translateY(-3px);box-shadow:0 16px 40px #00C85350}
  .btn-ghost{background:transparent;color:var(--white);border:1px solid var(--border);padding:16px 36px;border-radius:100px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;cursor:pointer;transition:border-color .2s,background .2s;text-decoration:none;display:inline-block}
  .btn-ghost:hover{border-color:var(--green);background:var(--green-dim)}
  .stats-bar{margin-top:70px;display:flex;justify-content:center;border:1px solid var(--border);border-radius:20px;overflow:hidden;animation:fu .8s .8s ease both;max-width:900px;width:100%}
  .stat-item{flex:1;min-width:130px;padding:22px 20px;border-right:1px solid var(--border);text-align:center;background:var(--card)}
  .stat-item:last-child{border-right:none}
  .stat-num{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--green);line-height:1}
  .stat-label{font-size:11px;color:var(--slate);margin-top:5px}
  @keyframes fu{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

  section{padding:100px 60px}
  .section-label{font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--green);margin-bottom:16px}
  .section-title{font-family:'Syne',sans-serif;font-size:clamp(28px,4vw,48px);font-weight:800;letter-spacing:-1.5px;line-height:1.1;max-width:620px}
  .section-sub{color:var(--slate);font-size:16px;font-weight:300;line-height:1.8;max-width:500px;margin-top:16px}

  .problem{background:var(--ink2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  .two-col{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1200px;margin:0 auto}
  .pain-list{list-style:none;margin-top:36px;display:flex;flex-direction:column;gap:12px}
  .pain-item{display:flex;align-items:flex-start;gap:14px;padding:16px;border:1px solid var(--border);border-radius:12px;background:var(--ink);transition:border-color .3s}
  .pain-item:hover{border-color:#E74C3C55}
  .pain-icon{width:34px;height:34px;flex-shrink:0;background:#E74C3C18;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
  .pain-text strong{display:block;font-size:14px;font-weight:600;margin-bottom:2px}
  .pain-text span{font-size:13px;color:var(--slate)}

  .mockup-box{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden}
  .mockup-header{background:var(--ink2);padding:12px 18px;display:flex;align-items:center;gap:7px;border-bottom:1px solid var(--border)}
  .dot{width:9px;height:9px;border-radius:50%}.dot-r{background:#E74C3C}.dot-y{background:var(--gold)}.dot-g{background:var(--green)}
  .mockup-body{padding:20px}
  .mock-kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
  .mock-kpi{padding:12px;border-radius:10px;background:var(--ink2);border:1px solid var(--border);text-align:center}
  .mock-kpi-val{font-family:'Syne',sans-serif;font-size:16px;font-weight:800}
  .mock-kpi-lbl{font-size:9px;color:var(--slate);margin-top:2px}
  .mock-chart{background:var(--ink2);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px;display:flex;align-items:flex-end;gap:5px;height:70px}
  .mock-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;margin-bottom:6px;background:var(--ink2)}
  .mock-avatar{width:28px;height:28px;border-radius:50%;flex-shrink:0}
  .mock-info{flex:1}
  .mock-name{height:7px;background:var(--border);border-radius:4px;width:110px;margin-bottom:4px}
  .mock-id{height:5px;background:#1E2E2490;border-radius:4px;width:70px}
  .mock-amount{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;color:var(--green)}
  .mock-badge{padding:3px 8px;border-radius:100px;font-size:9px;font-weight:600}
  .badge-active{background:#00C85322;color:var(--green)}.badge-watch{background:#F39C1222;color:#F39C12}.badge-loss{background:#E74C3C22;color:#E74C3C}

  .features-wrap{max-width:1200px;margin:0 auto}
  .features-header{text-align:center;margin-bottom:56px}
  .features-header .section-title{max-width:100%}
  .features-header .section-sub{max-width:540px;margin:14px auto 0}
  .features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;border:1px solid var(--border);border-radius:20px;overflow:hidden}
  .feat-card{background:var(--card);padding:34px 30px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);transition:background .3s;position:relative;overflow:hidden}
  .feat-card:hover{background:var(--ink2)}
  .feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--green),transparent);opacity:0;transition:opacity .3s}
  .feat-card:hover::before{opacity:1}
  .feat-icon{width:44px;height:44px;background:var(--green-dim);border:1px solid #00C85333;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:16px}
  .feat-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:8px;letter-spacing:-.3px}
  .feat-desc{font-size:13px;color:var(--slate);line-height:1.7}
  .feat-tag{display:inline-block;margin-top:10px;padding:3px 10px;border-radius:100px;font-size:10px;font-weight:600;letter-spacing:.5px}
  .tag-new{background:#00C85322;color:var(--green);border:1px solid #00C85344}
  .tag-core{background:#3B82F618;color:var(--blue);border:1px solid #3B82F633}
  .tag-compliance{background:#8B5CF618;color:var(--purple);border:1px solid #8B5CF633}

  .bi-section{background:linear-gradient(135deg,#0D1F14,var(--ink2));border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  .bi-cards{display:flex;flex-direction:column;gap:12px;margin-top:32px}
  .bi-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 22px;display:flex;align-items:flex-start;gap:14px;transition:border-color .3s,transform .3s}
  .bi-card:hover{border-color:var(--green);transform:translateX(6px)}
  .bi-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
  .bi-info strong{display:block;font-size:14px;font-weight:600;margin-bottom:3px}
  .bi-info span{font-size:13px;color:var(--slate)}
  .chart-visual{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:26px}
  .chart-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
  .chart-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
  .chart-period{font-size:11px;color:var(--slate)}
  .bar-chart{display:flex;align-items:flex-end;gap:6px;height:110px;margin-bottom:10px}
  .bc-group{flex:1;display:flex;flex-direction:column;align-items:center;height:100%}
  .bc-bars{flex:1;width:100%;display:flex;align-items:flex-end;gap:3px}
  .bc-bar{flex:1;border-radius:4px 4px 0 0;animation:growUp .8s ease both}
  @keyframes growUp{from{transform:scaleY(0);transform-origin:bottom}to{transform:scaleY(1);transform-origin:bottom}}
  .bc-label{font-size:9px;color:var(--slate);margin-top:5px}
  .chart-legend{display:flex;gap:14px;flex-wrap:wrap}
  .legend-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--slate)}
  .legend-dot{width:8px;height:8px;border-radius:50%}
  .mini-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:14px}
  .mini-stat{background:var(--ink2);border:1px solid var(--border);border-radius:10px;padding:14px}
  .mini-stat-val{font-family:'Syne',sans-serif;font-size:19px;font-weight:800}
  .mini-stat-lbl{font-size:11px;color:var(--slate);margin-top:2px}

  .reports-section{background:var(--ink);border-top:1px solid var(--border)}
  .report-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:26px}
  .chip{padding:6px 14px;border:1px solid var(--border);border-radius:100px;font-size:12px;font-weight:500;color:var(--slate);background:var(--card);cursor:pointer;transition:all .2s}
  .chip:hover,.chip.active{border-color:var(--green);color:var(--green);background:var(--green-dim)}
  .report-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:10px;display:flex;align-items:center;gap:14px;transition:transform .3s,border-color .3s}
  .report-card:hover{transform:translateX(8px);border-color:var(--green)}
  .report-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
  .ri-bnr{background:#006B3C22}.ri-crb{background:#1A3C6E22}.ri-ndfsp{background:#8E44AD22}.ri-std{background:#F39C1222}
  .report-info strong{display:block;font-size:14px;font-weight:600;margin-bottom:2px}
  .report-info span{font-size:12px;color:var(--slate)}
  .report-arrow{margin-left:auto;color:var(--slate)}

  .security-section{background:var(--ink2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  .security-visual{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:30px;text-align:center;position:relative;overflow:hidden}
  .security-visual::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 100%,#00C85308 0%,transparent 70%)}
  .auth-phone{width:178px;margin:0 auto 18px;background:var(--ink2);border:1px solid var(--border);border-radius:22px;padding:18px 14px;position:relative;z-index:1}
  .auth-screen{background:var(--ink);border-radius:12px;padding:14px 11px}
  .auth-logo-sm{font-family:'Syne',sans-serif;font-size:12px;font-weight:800;margin-bottom:10px;color:var(--white)}
  .auth-logo-sm span{color:var(--green)}
  .auth-label{font-size:9px;color:var(--slate);text-align:left;margin-bottom:2px}
  .auth-input{background:var(--ink2);border:1px solid var(--border);border-radius:5px;padding:6px 9px;font-size:10px;color:var(--slate);margin-bottom:7px;width:100%;text-align:left}
  .auth-2fa-box{background:var(--green-dim);border:1px solid #00C85333;border-radius:8px;padding:9px;margin:7px 0}
  .auth-2fa-title{font-size:9px;font-weight:700;color:var(--green);margin-bottom:4px}
  .auth-2fa-code{display:flex;gap:4px;justify-content:center}
  .auth-2fa-digit{width:21px;height:25px;background:var(--card);border:1px solid var(--green);border-radius:4px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:12px;font-weight:800;color:var(--green)}
  .auth-btn{background:var(--green);color:var(--ink);border-radius:6px;padding:7px;font-size:10px;font-weight:700;margin-top:6px;width:100%}
  .security-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px;justify-content:center;position:relative;z-index:1}
  .sec-badge{background:var(--ink2);border:1px solid var(--border);border-radius:100px;padding:6px 14px;font-size:12px;color:var(--slate);display:flex;align-items:center;gap:6px}
  .sec-badge-dot{width:6px;height:6px;background:var(--green);border-radius:50%}
  .security-features{list-style:none;margin-top:32px;display:flex;flex-direction:column;gap:12px}
  .sec-feat{display:flex;align-items:flex-start;gap:14px;padding:16px;background:var(--ink);border:1px solid var(--border);border-radius:12px;transition:border-color .3s}
  .sec-feat:hover{border-color:var(--green)}
  .sec-feat-icon{width:36px;height:36px;background:var(--green-dim);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
  .sec-feat strong{display:block;font-size:14px;font-weight:600;margin-bottom:2px}
  .sec-feat span{font-size:13px;color:var(--slate)}

  .pricing-wrap{max-width:1080px;margin:0 auto}
  .pricing-header{text-align:center;margin-bottom:50px}
  .pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
  .price-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px 32px;position:relative;overflow:hidden;transition:transform .3s,border-color .3s}
  .price-card:hover{transform:translateY(-5px)}
  .price-card.featured{border-color:var(--green);background:linear-gradient(135deg,#0D1F14,var(--card))}
  .price-card.featured::before{content:'MOST POPULAR';position:absolute;top:20px;right:-30px;background:var(--green);color:var(--ink);font-size:9px;font-weight:700;letter-spacing:1.5px;padding:5px 38px;transform:rotate(45deg)}
  .plan-name{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--slate);margin-bottom:8px}
  .plan-tagline{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-1px;line-height:1.1;margin-bottom:6px}
  .plan-divider{height:1px;background:var(--border);margin:18px 0}
  .plan-desc{font-size:13px;color:var(--slate);margin-bottom:22px;line-height:1.6}
  .plan-features{list-style:none;margin-bottom:28px}
  .plan-features li{padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;color:var(--slate);display:flex;align-items:center;gap:9px}
  .plan-features li::before{content:'✓';color:var(--green);font-weight:700;flex-shrink:0}
  .plan-features li.no::before{content:'×';color:#333}
  .plan-features li.no{opacity:.4}
  .btn-plan{width:100%;padding:13px;border-radius:11px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
  .btn-plan-ghost{background:transparent;border:1px solid var(--border);color:var(--white)}
  .btn-plan-ghost:hover{border-color:var(--green);color:var(--green)}
  .btn-plan-primary{background:var(--green);color:var(--ink)}
  .btn-plan-primary:hover{box-shadow:0 8px 24px #00C85440;transform:translateY(-2px)}
  .plan-contact{text-align:center;margin-top:12px;font-size:12px;color:var(--slate)}
  .plan-contact a{color:var(--green);text-decoration:none}

  .clients-section{background:var(--ink2);border-top:1px solid var(--border)}
  .clients-inner{max-width:1200px;margin:0 auto}
  .clients-intro{text-align:center;margin-bottom:50px}
  .featured-clients{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:44px}
  .fc-card{background:var(--ink);border:1px solid var(--border);border-radius:16px;padding:22px 20px;display:flex;align-items:center;gap:14px;transition:border-color .3s,transform .3s}
  .fc-card:hover{border-color:var(--green);transform:translateY(-3px)}
  .fc-logo{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:13px;flex-shrink:0;color:var(--ink)}
  .fc-name{font-family:'Syne',sans-serif;font-size:14px;font-weight:700;margin-bottom:2px}
  .fc-type{font-size:12px;color:var(--slate)}
  .fc-status{display:flex;align-items:center;gap:6px;margin-top:5px}
  .fc-dot{width:6px;height:6px;background:var(--green);border-radius:50%;animation:blink 2s infinite}
  .fc-active{font-size:11px;color:var(--green)}
  .clients-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
  .client-card{background:var(--ink);border:1px solid var(--border);border-radius:16px;padding:28px;transition:border-color .3s,transform .3s}
  .client-card:hover{border-color:var(--green);transform:translateY(-4px)}
  .client-logo{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:14px;margin-bottom:16px;color:var(--ink)}
  .client-quote{font-size:14px;color:var(--slate);line-height:1.8;margin-bottom:16px;font-style:italic}
  .client-name{font-size:13px;font-weight:600;color:var(--white)}
  .client-role{font-size:12px;color:var(--slate)}
  .stars{color:var(--gold);font-size:12px;margin-bottom:10px;letter-spacing:2px}
  .logos-row{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:44px;padding-top:44px;border-top:1px solid var(--border)}
  .logo-pill{background:var(--card);border:1px solid var(--border);border-radius:100px;padding:8px 20px;font-size:13px;font-weight:600;color:var(--slate);transition:all .2s}
  .logo-pill:hover{border-color:var(--green);color:var(--white)}

  .cta-section{text-align:center;position:relative;overflow:hidden;border-top:1px solid var(--border)}
  .cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 50% 100%,#00C85310 0%,transparent 70%)}
  .cta-inner{position:relative;max-width:680px;margin:0 auto}
  .cta-inner h2{font-family:'Syne',sans-serif;font-size:clamp(32px,5vw,60px);font-weight:800;letter-spacing:-2px;line-height:1.1;margin-bottom:16px}
  .cta-inner p{color:var(--slate);font-size:17px;margin-bottom:34px;line-height:1.7}

  footer{background:var(--ink2);border-top:1px solid var(--border);padding:56px 60px 34px}
  .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:52px;max-width:1200px;margin:0 auto 40px}
  .footer-brand p{color:var(--slate);font-size:14px;line-height:1.7;margin-top:10px;max-width:230px}
  .footer-col h4{font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--slate);margin-bottom:16px}
  .footer-col ul{list-style:none}
  .footer-col li{margin-bottom:10px}
  .footer-col a{color:var(--slate);text-decoration:none;font-size:14px;transition:color .2s}
  .footer-col a:hover{color:var(--white)}
  .footer-bottom{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding-top:20px;border-top:1px solid var(--border);font-size:13px;color:var(--slate);flex-wrap:wrap;gap:10px}
  .footer-contact a{color:var(--green);text-decoration:none;font-weight:600}

  .reveal{opacity:0;transform:translateY(36px);transition:opacity .8s ease,transform .8s ease}
  .reveal.visible{opacity:1;transform:translateY(0)}
  .reveal-delay-1{transition-delay:.1s}.reveal-delay-2{transition-delay:.2s}.reveal-delay-3{transition-delay:.3s}.reveal-delay-4{transition-delay:.4s}

  @media(max-width:900px){
    nav{padding:16px 24px}.nav-links{display:none}
    section{padding:60px 24px}
    .two-col,.features-grid,.pricing-grid,.clients-grid,.featured-clients{grid-template-columns:1fr}
    .footer-grid{grid-template-columns:1fr 1fr;gap:28px}
    .footer-bottom{flex-direction:column;text-align:center}
    .stats-bar{flex-wrap:wrap}
  }
</style>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- NAV -->
<nav id="nav">
  <div class="logo">Credly<span>rw</span></div>
  <ul class="nav-links">
    <li><a href="#features">Features</a></li>
    <li><a href="#bi">Analytics</a></li>
    <li><a href="#reports">Reports</a></li>
    <li><a href="#security">Security</a></li>
    <li><a href="#pricing">Pricing</a></li>
     <li><a href="team">Our Team</a></li>
    <li><a href="#clients">Clients</a></li>
    <li>
    <a href="{{ route('contact') }}" class="nav-cta">Get Started</a>
     <a href="{{ url('admin') }}" class="nav-cta">Login</a>
</li>
  </ul>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
  <div class="hero-badge">Rwanda's #1 Lending Platform</div>
  <h1>The <em>complete</em> system for<br>modern money lending</h1>
  <p class="hero-sub">Loans, repayments, income, expenses, penalties, visual analytics, BNR compliance, CRB reports — everything your NDFSP, MFI, or SACCO needs. One platform. Zero spreadsheets.</p>
  <div class="hero-actions">
    <a href="#contact" class="btn-primary">Start Free Trial →</a>
    <a href="#features" class="btn-ghost">Explore Features</a>
  </div>
  <div class="stats-bar">
    <div class="stat-item"><div class="stat-num" data-target="500">0</div><div class="stat-label">Loans Managed</div></div>
    <div class="stat-item"><div class="stat-num" data-target="12">0</div><div class="stat-label">Institutions</div></div>
    <div class="stat-item"><div class="stat-num" data-target="100">0</div><div class="stat-label">% BNR Compliant</div></div>
    <div class="stat-item"><div class="stat-num" data-target="99">0</div><div class="stat-label">% Uptime</div></div>
    <div class="stat-item"><div class="stat-num" data-target="4">0</div><div class="stat-label">Export Formats</div></div>
  </div>
</section>

<!-- PROBLEM -->
<section class="problem" id="about">
  <div class="two-col">
    <div>
      <p class="section-label reveal">The Problem</p>
      <h2 class="section-title reveal reveal-delay-1">Manual lending is costing you time and money</h2>
      <p class="section-sub reveal reveal-delay-2">Most lending businesses in Rwanda still rely on spreadsheets — exposed to errors, compliance failures, and zero business visibility.</p>
      <ul class="pain-list">
        <li class="pain-item reveal reveal-delay-1"><div class="pain-icon">📋</div><div class="pain-text"><strong>No centralized loan tracking</strong><span>Loans scattered across notebooks with no audit trail or history</span></div></li>
        <li class="pain-item reveal reveal-delay-2"><div class="pain-icon">⚠️</div><div class="pain-text"><strong>BNR reporting takes days</strong><span>Quarterly reports done manually, prone to costly classification errors</span></div></li>
        <li class="pain-item reveal reveal-delay-3"><div class="pain-icon">💸</div><div class="pain-text"><strong>No visibility into business health</strong><span>No charts, no expense tracking, no income reports — flying blind</span></div></li>
        <li class="pain-item reveal reveal-delay-4"><div class="pain-icon">🔓</div><div class="pain-text"><strong>Weak security</strong><span>Single password login with no protection against unauthorized access</span></div></li>
      </ul>
    </div>
    <div class="mockup-box reveal">
      <div class="mockup-header">
        <div class="dot dot-r"></div><div class="dot dot-y"></div><div class="dot dot-g"></div>
        <span style="font-size:11px;color:var(--slate);margin-left:8px">Credlyrw — Live Dashboard</span>
      </div>
      <div class="mockup-body">
        <div class="mock-kpi-row">
          <div class="mock-kpi"><div class="mock-kpi-val" style="color:var(--green)">45.2M</div><div class="mock-kpi-lbl">GROSS LOANS</div></div>
          <div class="mock-kpi"><div class="mock-kpi-val" style="color:#F39C12">2.4%</div><div class="mock-kpi-lbl">NPL RATIO</div></div>
          <div class="mock-kpi"><div class="mock-kpi-val" style="color:var(--blue)">8.1M</div><div class="mock-kpi-lbl">NET INCOME</div></div>
        </div>
        <div class="mock-chart">
          <div style="flex:1;height:40%;background:var(--green-dim);border-radius:3px 3px 0 0;border:1px solid #00C85344"></div>
          <div style="flex:1;height:65%;background:var(--green-dim);border-radius:3px 3px 0 0;border:1px solid #00C85344"></div>
          <div style="flex:1;height:55%;background:var(--green-dim);border-radius:3px 3px 0 0;border:1px solid #00C85344"></div>
          <div style="flex:1;height:85%;background:var(--green);border-radius:3px 3px 0 0;opacity:.85"></div>
          <div style="flex:1;height:70%;background:var(--green-dim);border-radius:3px 3px 0 0;border:1px solid #00C85344"></div>
          <div style="flex:1;height:95%;background:var(--green);border-radius:3px 3px 0 0"></div>
        </div>
        <div class="mock-row"><div class="mock-avatar" style="background:linear-gradient(135deg,var(--green-dark),var(--green))"></div><div class="mock-info"><div class="mock-name"></div><div class="mock-id"></div></div><div class="mock-amount">1,200,000</div><div class="mock-badge badge-active">Active</div></div>
        <div class="mock-row"><div class="mock-avatar" style="background:linear-gradient(135deg,#B7770D,#F39C12)"></div><div class="mock-info"><div class="mock-name"></div><div class="mock-id"></div></div><div class="mock-amount">850,000</div><div class="mock-badge badge-watch">Watch</div></div>
        <div style="margin-top:10px;padding:10px 14px;background:var(--green-dim);border:1px solid #00C85333;border-radius:10px;display:flex;align-items:center;gap:8px">
          <span>🛡️</span><span style="font-size:11px;color:var(--green)">2FA enabled — login secured successfully</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features">
  <div class="features-wrap">
    <div class="features-header">
      <p class="section-label reveal">Platform Features</p>
      <h2 class="section-title reveal reveal-delay-1">Everything your lending business needs</h2>
      <p class="section-sub reveal reveal-delay-2">Built for Rwanda's regulations, local workflows, and complete business financial management — not just loans.</p>
    </div>
    <div class="features-grid reveal">
      <div class="feat-card"><div class="feat-icon">🏦</div><div class="feat-title">Loan Lifecycle Management</div><p class="feat-desc">From application to final repayment. Automated EMI calculations, payment scheduling, declining balance and flat rate support.</p><span class="feat-tag tag-core">CORE</span></div>
      <div class="feat-card"><div class="feat-icon">💰</div><div class="feat-title">Income & Revenue Tracking</div><p class="feat-desc">Track all interest income, fees, commissions, and recoveries. See exactly how much your institution earns per period with visual breakdowns.</p><span class="feat-tag tag-new">NEW</span></div>
      <div class="feat-card"><div class="feat-icon">🧾</div><div class="feat-title">Expense Management</div><p class="feat-desc">Record and categorize operational expenses — staff costs, admin fees, bank charges. Full P&L visibility from a single dashboard.</p><span class="feat-tag tag-new">NEW</span></div>
      <div class="feat-card"><div class="feat-icon">⚡</div><div class="feat-title">Penalty Recording</div><p class="feat-desc">Automatically calculate and record penalties on overdue loans. Track penalty income separately with full audit history per loan.</p><span class="feat-tag tag-new">NEW</span></div>
      <div class="feat-card"><div class="feat-icon">📈</div><div class="feat-title">Visual Business Analytics</div><p class="feat-desc">Beautiful charts showing loan trends, income vs expenses, NPL ratios, and portfolio health — updated in real time. Know your business at a glance.</p><span class="feat-tag tag-new">NEW</span></div>
      <div class="feat-card"><div class="feat-icon">📊</div><div class="feat-title">Internal Business Reports</div><p class="feat-desc">Monthly and quarterly internal reports — portfolio performance, income statements, expense summaries, and balance sheets.</p><span class="feat-tag tag-new">NEW</span></div>
      <div class="feat-card"><div class="feat-icon">👤</div><div class="feat-title">Smart Customer Registry</div><p class="feat-desc">National ID-based lookup with cross-institution detection. Auto-fills info, detects duplicate borrowers, alerts on unpaid loans elsewhere.</p><span class="feat-tag tag-core">CORE</span></div>
      <div class="feat-card"><div class="feat-icon">📱</div><div class="feat-title">SMS Notifications</div><p class="feat-desc">Automatic SMS on disbursement with amount, company name, and due date. Payment reminders keep default rates low.</p><span class="feat-tag tag-core">CORE</span></div>
      <div class="feat-card"><div class="feat-icon">🔍</div><div class="feat-title">BNR Classification Engine</div><p class="feat-desc">Auto-classifies loans: Normal, Watch, Substandard, Doubtful, Loss. Provisions at 1%, 3%, 20%, 50%, 100% per regulation.</p><span class="feat-tag tag-compliance">COMPLIANCE</span></div>
      <div class="feat-card"><div class="feat-icon">📋</div><div class="feat-title">BNR Compliance Reports</div><p class="feat-desc">One-click BNR credit classification reports with 9 color-coded sheets. NDFSP quarterly template auto-populated from your data.</p><span class="feat-tag tag-compliance">COMPLIANCE</span></div>
      <div class="feat-card"><div class="feat-icon">🛡️</div><div class="feat-title">TransUnion CRB Export</div><p class="feat-desc">Full 74-column TransUnion Rwanda submission file — borrower, loan, collateral, and guarantor data pre-filled and ready.</p><span class="feat-tag tag-compliance">COMPLIANCE</span></div>
      <div class="feat-card"><div class="feat-icon">🏢</div><div class="feat-title">Multi-Institution Support</div><p class="feat-desc">Manage multiple NDFSPs from one super-admin account. Each institution's data is fully isolated and secure.</p><span class="feat-tag tag-core">CORE</span></div>
    </div>
  </div>
</section>

<!-- BUSINESS INTELLIGENCE -->
<section class="bi-section" id="bi">
  <div class="two-col" style="max-width:1200px;margin:0 auto">
    <div>
      <p class="section-label reveal">Business Intelligence</p>
      <h2 class="section-title reveal reveal-delay-1">See your business clearly, not on paper</h2>
      <p class="section-sub reveal reveal-delay-2">Real-time visual insights into every corner of your lending business — from income earned to expenses spent to portfolio health.</p>
      <div class="bi-cards">
        <div class="bi-card reveal reveal-delay-1"><div class="bi-icon" style="background:#00C85318">📈</div><div class="bi-info"><strong>Income vs Expense Charts</strong><span>See monthly profit and loss at a glance with interactive bar and line charts</span></div></div>
        <div class="bi-card reveal reveal-delay-2"><div class="bi-icon" style="background:#3B82F618">🥧</div><div class="bi-info"><strong>Portfolio Breakdown</strong><span>Visual split of loans by class, gender, sector, and repayment status</span></div></div>
        <div class="bi-card reveal reveal-delay-3"><div class="bi-icon" style="background:#F39C1218">⚡</div><div class="bi-info"><strong>Penalty & Arrears Tracking</strong><span>Dashboard of overdue loans, penalty amounts earned, and arrears by classification</span></div></div>
        <div class="bi-card reveal reveal-delay-4"><div class="bi-icon" style="background:#8B5CF618">🧾</div><div class="bi-info"><strong>Expense Categories</strong><span>Track staff costs, bank charges, admin expenses with category summaries and trends</span></div></div>
      </div>
    </div>
    <div class="chart-visual reveal">
      <div class="chart-header"><div class="chart-title">Business Performance</div><div class="chart-period">Last 6 Months</div></div>
      <div class="bar-chart">
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:55%;background:var(--green);opacity:.7;animation-delay:.1s"></div><div class="bc-bar" style="height:30%;background:#EF444488;animation-delay:.12s"></div></div><div class="bc-label">Jul</div></div>
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:72%;background:var(--green);opacity:.75;animation-delay:.2s"></div><div class="bc-bar" style="height:36%;background:#EF444488;animation-delay:.22s"></div></div><div class="bc-label">Aug</div></div>
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:62%;background:var(--green);opacity:.8;animation-delay:.3s"></div><div class="bc-bar" style="height:28%;background:#EF444488;animation-delay:.32s"></div></div><div class="bc-label">Sep</div></div>
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:88%;background:var(--green);animation-delay:.4s"></div><div class="bc-bar" style="height:33%;background:#EF444488;animation-delay:.42s"></div></div><div class="bc-label">Oct</div></div>
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:78%;background:var(--green);opacity:.9;animation-delay:.5s"></div><div class="bc-bar" style="height:40%;background:#EF444488;animation-delay:.52s"></div></div><div class="bc-label">Nov</div></div>
        <div class="bc-group"><div class="bc-bars"><div class="bc-bar" style="height:97%;background:var(--green);animation-delay:.6s"></div><div class="bc-bar" style="height:38%;background:#EF444488;animation-delay:.62s"></div></div><div class="bc-label">Dec</div></div>
      </div>
      <div class="chart-legend">
        <div class="legend-item"><div class="legend-dot" style="background:var(--green)"></div>Income</div>
        <div class="legend-item"><div class="legend-dot" style="background:#EF4444"></div>Expenses</div>
      </div>
      <div class="mini-stats">
        <div class="mini-stat"><div class="mini-stat-val" style="color:var(--green)">12.4M</div><div class="mini-stat-lbl">Total Income (RWF)</div></div>
        <div class="mini-stat"><div class="mini-stat-val" style="color:#EF4444">3.8M</div><div class="mini-stat-lbl">Total Expenses (RWF)</div></div>
        <div class="mini-stat"><div class="mini-stat-val" style="color:var(--gold)">840K</div><div class="mini-stat-lbl">Penalties Earned</div></div>
        <div class="mini-stat"><div class="mini-stat-val" style="color:var(--blue)">8.6M</div><div class="mini-stat-lbl">Net Profit (RWF)</div></div>
      </div>
    </div>
  </div>
</section>

<!-- REPORTS -->
<section class="reports-section" id="reports">
  <div class="two-col" style="max-width:1200px;margin:0 auto">
    <div>
      <p class="section-label reveal">Regulatory & Internal Exports</p>
      <h2 class="section-title reveal reveal-delay-1">Every report ready in one click</h2>
      <p class="section-sub reveal reveal-delay-2">From BNR submissions to internal P&L reports — fully formatted, regulation-compliant Excel files generated from your live data.</p>
      <div class="report-chips reveal reveal-delay-3">
        <div class="chip active">BNR Classification</div>
        <div class="chip">TransUnion CRB</div>
        <div class="chip">NDFSP Quarterly</div>
        <div class="chip">Income Report</div>
        <div class="chip">Expense Report</div>
        <div class="chip">Standard Export</div>
      </div>
    </div>
    <div class="reveal">
      <div class="report-card"><div class="report-icon ri-bnr">🟢</div><div class="report-info"><strong>BNR Credit Classification Report</strong><span>9 sheets — Summary + 7 loan classes + NDFSP Quarterly template</span></div><div class="report-arrow">→</div></div>
      <div class="report-card"><div class="report-icon ri-crb">🔵</div><div class="report-info"><strong>TransUnion CRB Submission</strong><span>74-column format — Individual, Guarantors, Collateral sheets</span></div><div class="report-arrow">→</div></div>
      <div class="report-card"><div class="report-icon ri-ndfsp">🟣</div><div class="report-info"><strong>Internal Business Report</strong><span>Income, expenses, penalties, profit — monthly & quarterly views</span></div><div class="report-arrow">→</div></div>
      <div class="report-card"><div class="report-icon ri-std">🟡</div><div class="report-info"><strong>Standard Loan Export</strong><span>34 columns — full loan data for internal analysis and audits</span></div><div class="report-arrow">→</div></div>
    </div>
  </div>
</section>

<!-- SECURITY -->
<section class="security-section" id="security">
  <div class="two-col" style="max-width:1200px;margin:0 auto">
    <div>
      <p class="section-label reveal">Enterprise Security</p>
      <h2 class="section-title reveal reveal-delay-1">Bank-grade protection for your financial data</h2>
      <p class="section-sub reveal reveal-delay-2">Every login is protected with two-factor authentication. Your customer data, loan records, and financial reports are secured at every level.</p>
      <ul class="security-features">
        <li class="sec-feat reveal reveal-delay-1"><div class="sec-feat-icon">🔐</div><div><strong>Two-Factor Authentication (2FA)</strong><span>Every login requires a second verification step — OTP via SMS or authenticator app. No unauthorized access, ever.</span></div></li>
        <li class="sec-feat reveal reveal-delay-2"><div class="sec-feat-icon">🏢</div><div><strong>Institution Data Isolation</strong><span>Each company's data is completely isolated. Users only see their own institution's loans, customers, and reports.</span></div></li>
        <li class="sec-feat reveal reveal-delay-3"><div class="sec-feat-icon">👁️</div><div><strong>Role-Based Access Control</strong><span>Super admins, managers, and staff have separate permissions. Control exactly who sees and does what.</span></div></li>
        <li class="sec-feat reveal reveal-delay-4"><div class="sec-feat-icon">📝</div><div><strong>Full Audit Trail</strong><span>Every action — loan creation, payment, export — is logged with user, timestamp, and details.</span></div></li>
      </ul>
    </div>
    <div class="security-visual reveal">
      <div style="font-size:12px;color:var(--slate);margin-bottom:14px;position:relative;z-index:1">Secure Login Flow</div>
      <div class="auth-phone">
        <div class="auth-screen">
          <div class="auth-logo-sm">Credly<span>rw</span></div>
          <div class="auth-label">Email Address</div>
          <div class="auth-input">admin@prospera.rw</div>
          <div class="auth-label">Password</div>
          <div class="auth-input">••••••••••••</div>
          <div class="auth-2fa-box">
            <div class="auth-2fa-title">🛡️ 2-FACTOR AUTH REQUIRED</div>
            <div style="font-size:8px;color:var(--slate);margin-bottom:5px">Enter code sent to your phone</div>
            <div class="auth-2fa-code">
              <div class="auth-2fa-digit">4</div><div class="auth-2fa-digit">8</div>
              <div class="auth-2fa-digit">3</div><div class="auth-2fa-digit">7</div>
              <div class="auth-2fa-digit">2</div><div class="auth-2fa-digit">1</div>
            </div>
          </div>
          <div class="auth-btn">✓ Verify & Login</div>
        </div>
      </div>
      <div class="security-badges">
        <div class="sec-badge"><div class="sec-badge-dot"></div>2FA Protected</div>
        <div class="sec-badge"><div class="sec-badge-dot"></div>Data Isolated</div>
        <div class="sec-badge"><div class="sec-badge-dot"></div>Audit Logged</div>
        <div class="sec-badge"><div class="sec-badge-dot"></div>RBAC Enabled</div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section id="pricing">
  <div class="pricing-wrap">
    <div class="pricing-header">
      <p class="section-label reveal">Subscription Plans</p>
      <h2 class="section-title reveal reveal-delay-1" style="max-width:100%;text-align:center">Simple, transparent pricing</h2>
      <p class="section-sub reveal reveal-delay-2" style="margin:12px auto 0;text-align:center;max-width:460px">Contact us to get a plan tailored to your institution's size and needs. No hidden fees, no surprises.</p>
    </div>
    <div class="pricing-grid">
      <div class="price-card reveal">
        <div class="plan-name">Starter</div>
        <div class="plan-tagline">For Small Lenders</div>
        <div class="plan-divider"></div>
        <p class="plan-desc">Perfect for small lending groups and individual money lenders getting organized.</p>
        <ul class="plan-features">
          <li>Up to 100 active loans</li>
          <li>1 institution</li>
          <li>BNR Credit Report</li>
          <li>Income & expense tracking</li>
          <li>SMS notifications</li>
          <li>Standard Excel export</li>
          <li class="no">TransUnion CRB export</li>
          <li class="no">Visual analytics charts</li>
          <li class="no">Multi-institution</li>
        </ul>
        <button class="btn-plan btn-plan-ghost" onclick="location.href='tel:0786748001'">Contact Us</button>
        <div class="plan-contact">Call <a href="tel:0786748001">0786 748 001</a></div>
      </div>
      <div class="price-card featured reveal reveal-delay-1">
        <div class="plan-name">Professional</div>
        <div class="plan-tagline">For Growing NDFSPs</div>
        <div class="plan-divider"></div>
        <p class="plan-desc">For NDFSPs, SACCOs, and MFIs needing full compliance, analytics, and business management.</p>
        <ul class="plan-features">
          <li>Unlimited active loans</li>
          <li>Up to 3 institutions</li>
          <li>All BNR + NDFSP Reports</li>
          <li>TransUnion CRB export</li>
          <li>Visual analytics charts</li>
          <li>Expense & income tracking</li>
          <li>Penalty management</li>
          <li>2FA security</li>
          <li>Priority support</li>
        </ul>
        <button class="btn-plan btn-plan-primary" onclick="location.href='tel:0786748001'">Get Started →</button>
        <div class="plan-contact">Call <a href="tel:0786748001">0786 748 001</a></div>
      </div>
      <div class="price-card reveal reveal-delay-2">
        <div class="plan-name">Enterprise</div>
        <div class="plan-tagline">For Large Networks</div>
        <div class="plan-divider"></div>
        <p class="plan-desc">For large institution networks needing custom integrations, branding, and dedicated support.</p>
        <ul class="plan-features">
          <li>Unlimited everything</li>
          <li>Unlimited institutions</li>
          <li>All report formats</li>
          <li>Custom branding</li>
          <li>Full analytics suite</li>
          <li>API access</li>
          <li>Dedicated account manager</li>
          <li>On-site training</li>
          <li>SLA guarantee</li>
        </ul>
        <button class="btn-plan btn-plan-ghost" onclick="location.href='tel:0786748001'">Contact Us</button>
        <div class="plan-contact">Call <a href="tel:0786748001">0786 748 001</a></div>
      </div>
    </div>
  </div>
</section>

<!-- CLIENTS -->
<section class="clients-section" id="clients">
  <div class="clients-inner">
    <div class="clients-intro">
      <p class="section-label reveal">Our Clients</p>
      <h2 class="section-title reveal reveal-delay-1" style="max-width:100%;text-align:center">Trusted by Rwanda's leading lending institutions</h2>
      <p class="section-sub reveal reveal-delay-2" style="margin:14px auto 0;text-align:center">From capital firms to community finance — Credlyrw powers their daily operations.</p>
    </div>
    <div class="featured-clients reveal">
      <div class="fc-card">
        <div class="fc-logo" style="background:linear-gradient(135deg,#1A3C6E,#3B82F6)">PC</div>
        <div><div class="fc-name">PROSPERA CAPITAL</div><div class="fc-type">Capital & Investment Finance</div><div class="fc-status"><div class="fc-dot"></div><span class="fc-active">Active Client</span></div></div>
      </div>
      <div class="fc-card">
        <div class="fc-logo" style="background:linear-gradient(135deg,#006B3C,#00C853)">CC</div>
        <div><div class="fc-name">CASHCARE Finance</div><div class="fc-type">Consumer Lending & Credit</div><div class="fc-status"><div class="fc-dot"></div><span class="fc-active">Active Client</span></div></div>
      </div>
      <div class="fc-card">
        <div class="fc-logo" style="background:linear-gradient(135deg,#7C3AED,#8B5CF6)">GF</div>
        <div><div class="fc-name">GROWTH FINANCE</div><div class="fc-type">SME & Business Finance</div><div class="fc-status"><div class="fc-dot"></div><span class="fc-active">Active Client</span></div></div>
      </div>
    </div>
    <div class="clients-grid">
      <div class="client-card reveal">
        <div class="client-logo" style="background:linear-gradient(135deg,#1A3C6E,#3B82F6)">PC</div>
        <div class="stars">★★★★★</div>
        <p class="client-quote">"Credlyrw transformed how we manage our portfolio. BNR reports that took 3 days now take 10 minutes. The income tracking and visual analytics give us clarity we never had before."</p>
        <div class="client-name">Finance Director</div><div class="client-role">PROSPERA CAPITAL — Kigali</div>
      </div>
      <div class="client-card reveal reveal-delay-1">
        <div class="client-logo" style="background:linear-gradient(135deg,#006B3C,#00C853)">CC</div>
        <div class="stars">★★★★★</div>
        <p class="client-quote">"The 2FA security and expense management are exactly what we needed. Customers get SMS confirmations on every disbursement. Default rates dropped by 30% in 3 months."</p>
        <div class="client-name">Operations Manager</div><div class="client-role">CASHCARE Finance — Kigali</div>
      </div>
      <div class="client-card reveal reveal-delay-2">
        <div class="client-logo" style="background:linear-gradient(135deg,#7C3AED,#8B5CF6)">GF</div>
        <div class="stars">★★★★★</div>
        <p class="client-quote">"Penalty recording and cross-customer detection have been game changers. We caught 6 customers with unpaid loans elsewhere before disbursing. The analytics show our business in a new way."</p>
        <div class="client-name">CEO</div><div class="client-role">GROWTH FINANCE — Rwanda</div>
      </div>
    </div>
    <div class="logos-row reveal">
      <div class="logo-pill">PROSPERA CAPITAL</div>
      <div class="logo-pill">CASHCARE Finance</div>
      <div class="logo-pill">GROWTH FINANCE</div>
      <div class="logo-pill">Urumuri NDFSP</div>
      <div class="logo-pill">Amahoro SACCO</div>
      <div class="logo-pill">Ejo Heza Finance</div>
      <div class="logo-pill">+ More Joining</div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section" id="contact">
  <div class="cta-inner">
    <p class="section-label reveal" style="text-align:center">Ready to transform your business?</p>
    <h2 class="reveal reveal-delay-1">Stop managing loans.<br>Start <em style="color:var(--green);font-style:normal">growing</em> them.</h2>
    <p class="reveal reveal-delay-2">Join Rwanda's fastest-growing lending platform. Loans, analytics, compliance, expenses — all in one secure place. Get set up in under a day.</p>
    <div class="hero-actions reveal reveal-delay-3">
      <a href="tel:0786748001" class="btn-primary">📞 Call 0786 748 001</a>
      <a href="#pricing" class="btn-ghost">View Plans</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="logo">Credly<span>rw</span></div>
      <p>Rwanda's complete lending management system. Loans, compliance, analytics, expenses — all in one secure platform.</p>
    </div>
    <div class="footer-col">
      <h4>Product</h4>
      <ul>
        <li><a href="#features">Features</a></li>
        <li><a href="#bi">Analytics</a></li>
        <li><a href="#reports">Reports</a></li>
        <li><a href="#security">Security</a></li>
        <li><a href="#pricing">Pricing</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Clients</h4>
      <ul>
        <li><a href="#clients">PROSPERA CAPITAL</a></li>
        <li><a href="#clients">CASHCARE Finance</a></li>
        <li><a href="#clients">GROWTH FINANCE</a></li>
        <li><a href="#clients">Join Us</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <ul>
        <li><a href="tel:0786748001">0786 748 001</a></li>
        <li><a href="#">Kigali, Rwanda</a></li>
        <li><a href="#">info@credlyrw.rw</a></li>
        <li><a href="#">Support</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2025 Credlyrw. All rights reserved. Powered by <strong style="color:var(--green)">E-STORE FASITA</strong></span>
    <div class="footer-contact"><span>📞</span><a href="tel:0786748001">0786 748 001</a></div>
  </div>
</footer>

<script>
  // Cursor
  const cursor=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
  let mx=0,my=0,rx=0,ry=0;
  document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.transform=`translate(${mx-6}px,${my-6}px)`});
  (function tick(){rx+=(mx-rx-18)*.1;ry+=(my-ry-18)*.1;ring.style.transform=`translate(${rx}px,${ry}px)`;requestAnimationFrame(tick)})();
  document.querySelectorAll('a,button').forEach(el=>{el.addEventListener('mouseenter',()=>ring.style.opacity='.1');el.addEventListener('mouseleave',()=>ring.style.opacity='.5')});

  // Nav
  window.addEventListener('scroll',()=>document.getElementById('nav').classList.toggle('scrolled',scrollY>40));

  // Reveal
  const obs=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible')})},{threshold:.12});
  document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));

  // Counters
  const sObs=new IntersectionObserver(entries=>{
    if(!entries[0].isIntersecting)return;
    document.querySelectorAll('[data-target]').forEach(el=>{
      const t=+el.dataset.target,step=t/55;
      let c=0;
      const int=setInterval(()=>{
        c=Math.min(c+step,t);
        el.textContent=Math.floor(c)+(t===100||t===99?'%':'+');
        if(c>=t)clearInterval(int);
      },22);
    });
    sObs.disconnect();
  },{threshold:.5});
  const sb=document.querySelector('.stats-bar');
  if(sb)sObs.observe(sb);

  // Chips
  document.querySelectorAll('.chip').forEach(c=>{
    c.addEventListener('click',()=>{
      c.closest('.report-chips').querySelectorAll('.chip').forEach(x=>x.classList.remove('active'));
      c.classList.add('active');
    });
  });
</script>
</body>
</html>
