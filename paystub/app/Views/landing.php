<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Instant Pay Stub Generator | createpaystubdocs.com</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Create professional pay stubs instantly. Accurate multi-period generator, custom earnings, deductions & taxes, secure PDF delivery.">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="site-header">
    <div class="logo">createpaystubdocs<span style="opacity:.55;font-weight:400">.com</span></div>
    <nav class="nav">
      <a href="/">Home</a>
      <a href="/create">Create</a>
      <a href="#features">Features</a>
      <a href="#faq">FAQ</a>
      <button type="button" id="themeToggle" style="margin-left:1.25rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;padding:.45rem .75rem;font-size:.65rem;border-radius:6px;cursor:pointer;">🌙</button>
    </nav>
  </header>
  <main>
    <section class="hero" style="padding-top:4.5rem;">
      <p class="tagline">FAST • ACCURATE • SECURE</p>
      <h1 style="max-width:780px;">Generate Professional Pay Stubs in Seconds</h1>
      <p style="max-width:760px;">Accurate multi‑period pay stub generation with customizable earnings, deductions and tax lines. Preview with watermark, pay securely, download final PDF and receive instant email delivery.</p>
      <div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
        <a href="/create" class="btn-primary" style="text-decoration:none;">Create Pay Stub →</a>
        <a href="#features" class="btn-secondary" style="text-decoration:none;">Explore Features</a>
      </div>
      <div style="margin-top:2.25rem;font-size:.7rem;letter-spacing:.08em;color:#94a3b8;">Base price from $<?= number_format($pricingBase,2) ?> per stub • Volume discounts auto‑apply</div>
    </section>
    <section id="features" class="container" style="margin-top:2rem;">
      <h2 style="font-size:1.5rem;margin:0 0 1.25rem;">Features</h2>
      <ul style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem 1.75rem;font-size:.8rem;line-height:1.4;color:#cbd5e1;">
        <li><strong style="color:#fff;">Multi-Period Generator</strong><br>Automatically builds accurate pay periods for weekly, biweekly, semi‑monthly or monthly schedules.</li>
        <li><strong style="color:#fff;">Custom Line Items</strong><br>Add unlimited earnings, deductions and tax lines with instant totals.</li>
        <li><strong style="color:#fff;">Live Price Estimate</strong><br>Price shown before checkout; volume discounts & template surcharge handled.</li>
        <li><strong style="color:#fff;">Secure PDF Delivery</strong><br>Watermarked preview then final PDF + email receipt and secure token link.</li>
        <li><strong style="color:#fff;">Regeneration & Versioning</strong><br>Create corrected versions with preserved audit trail.</li>
        <li><strong style="color:#fff;">Template Selection</strong><br>Choose among multiple professional layouts (black, horizontal blue...).</li>
      </ul>
    </section>
    <section id="how-it-works" class="container" style="margin-top:3rem;">
      <h2 style="font-size:1.5rem;margin:0 0 1rem;">How It Works</h2>
      <ol style="margin:0;padding-left:1.2rem;font-size:.8rem;line-height:1.5;color:#cbd5e1;max-width:760px;">
        <li>Enter employer, employee and pay schedule details.</li>
        <li>Add or adjust earnings, deductions and taxes (we total instantly).</li>
        <li>Select a template and number of pay periods (1–12).</li>
        <li>Preview a watermarked version and verify totals + price breakdown.</li>
        <li>Checkout securely. Upon success, download final PDF + receive email.</li>
        <li>Regenerate corrected versions if needed (history retained).</li>
      </ol>
    </section>
    <section id="faq" class="container" style="margin-top:3rem;">
      <h2 style="font-size:1.5rem;margin:0 0 1.25rem;">FAQ</h2>
      <div style="display:grid;gap:1.1rem;max-width:920px;font-size:.8rem;line-height:1.45;">
        <div><strong style="color:#fff;">Do you store my generated stubs?</strong><br>Temporarily for secure delivery + regeneration; deletion can be requested.</div>
        <div><strong style="color:#fff;">Can I create multiple stubs?</strong><br>Yes, select 1–12 and each period is calculated automatically.</div>
        <div><strong style="color:#fff;">Are taxes automatic?</strong><br>You provide tax lines for precision; automated tax engine optional per tenant later.</div>
        <div><strong style="color:#fff;">What payment methods?</strong><br>Stripe (cards, wallets). Architecture allows adding more providers.</div>
        <div><strong style="color:#fff;">Refund policy?</strong><br>If there's a technical issue with generation, contact support for review.</div>
      </div>
    </section>
    <section class="container" style="margin:3.5rem auto 4rem;text-align:center;">
      <h2 style="font-size:1.4rem;margin:0 0 .75rem;">Ready to Build Your Pay Stub?</h2>
      <p style="margin:0 0 1.25rem;font-size:.85rem;color:#cbd5e1;">Start free previewing now – pay only when you're satisfied.</p>
      <a href="/create" class="btn-primary" style="text-decoration:none;">Start Creating →</a>
    </section>
  </main>
  <footer class="footer">&copy; <?= date('Y') ?> createpaystubdocs.com – Instant Pay Stub Generator.</footer>
  <script>
    // Theme toggle
    const root=document.documentElement;const pref=localStorage.getItem('theme');if(pref)root.setAttribute('data-theme',pref);const btn=document.getElementById('themeToggle');const setIcon=()=>btn.textContent=root.getAttribute('data-theme')==='light'?'🌙':'☀️';setIcon();btn.addEventListener('click',()=>{const next=root.getAttribute('data-theme')==='light'?'dark':'light';if(next==='dark')root.removeAttribute('data-theme');else root.setAttribute('data-theme','light');localStorage.setItem('theme',next==='dark'?'':'light');setIcon();});
  </script>
</body>
</html>
