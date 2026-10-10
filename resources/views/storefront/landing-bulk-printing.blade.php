<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $companyName ?? 'Okina Craft' }} – Get Bulk Printing for Your Business</title>
<style>
:root{
  --bg:#0c0d10; --panel:#14161b; --line:#2b2f38; --line2:#454b59; --text:#fff; --muted:#a4aab7;
  --orange:#ff6a1a; --orange2:#ff3d5a; --err:#ff7b7b;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:"Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;
  line-height:1.45;-webkit-font-smoothing:antialiased;padding:env(safe-area-inset-top) 0 env(safe-area-inset-bottom)}
.wrap{max-width:520px;margin:0 auto;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;padding:0 20px 24px}
.top{display:flex;align-items:center;justify-content:space-between;height:56px}
.icon-btn{background:none;border:0;color:var(--text);width:48px;height:48px;border-radius:50%;cursor:pointer;display:grid;place-items:center}
.icon-btn[hidden]{display:grid;visibility:hidden}
.icon-btn svg{width:26px;height:26px}
.icon-btn:hover{background:rgba(255,255,255,.07)}
:focus-visible{outline:2px solid var(--orange);outline-offset:2px}
.progress{display:flex;gap:6px;margin:0 -4px 22px}
.progress span{flex:1;height:3px;border-radius:3px;background:var(--line);transition:background .3s}
.progress span.done{background:linear-gradient(90deg,var(--orange),var(--orange2))}
.brand{text-align:center;margin-bottom:20px}
.logo{width:70px;height:70px;border-radius:50%;margin:0 auto 12px;border:1px solid rgba(255,106,26,0.32);display:grid;place-items:center;overflow:hidden;
  background:radial-gradient(circle at 50% 30%,rgba(232,53,53,0.18),#14161b);box-shadow:0 8px 24px rgba(0,0,0,0.5),0 0 16px rgba(255,106,26,0.12);
  font-weight:800;font-size:22px;color:var(--orange)}
.logo img{width:100%;height:100%;object-fit:contain;padding:7px;display:block}
.brand strong{display:block;font-size:1.15rem;font-weight:700;letter-spacing:-0.01em;color:#fff}
.stage{flex:1;display:flex;flex-direction:column;position:relative}
.step{display:none;flex:1;flex-direction:column}
.step.active{display:flex;animation:inR .28s ease both}
.stage.back .step.active{animation-name:inL}
@keyframes inR{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:none}}
@keyframes inL{from{opacity:0;transform:translateX(-18px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.step.active{animation:none!important}.progress span{transition:none}}
h2{font-size:1.45rem;font-weight:650;line-height:1.25;margin-bottom:8px;outline:none}
.support{color:var(--muted);font-size:.95rem;margin-bottom:18px}

/* Resized Step 1 hero image */
.step-hero-media{position:relative;width:100%;height:120px;margin:0 0 16px;border-radius:18px;overflow:hidden;background:#0c0d10}
.step-hero-img{width:100%;height:100%;object-fit:cover;object-position:center 25%;display:block}
.step-hero-media::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,rgba(12,13,16,0) 70%,rgba(12,13,16,.95) 100%);pointer-events:none}

fieldset{border:0;min-width:0}
legend{font-size:1.12rem;font-weight:600;margin-bottom:12px;padding:0}
.group{border:1px solid var(--line);border-radius:22px;padding:18px 14px 6px;margin-bottom:16px;background:rgba(255,255,255,.015)}
.group legend{padding:0 6px;margin-left:-6px}
.opt{position:relative;display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:56px;
  border:1.5px solid var(--line);border-radius:999px;padding:14px 20px;margin-bottom:12px;cursor:pointer;
  background:var(--panel);font-size:1.02rem;transition:border-color .15s,background .15s}
.opt:hover{border-color:var(--line2)}
.opt input{position:absolute;opacity:0;inset:0;width:100%;height:100%;margin:0;cursor:pointer}
.radio{flex:none;width:28px;height:28px;border-radius:50%;border:2px solid #8b92a1;display:grid;place-items:center;transition:.15s}
.radio::after{content:"";width:14px;height:14px;border-radius:50%;background:var(--orange);transform:scale(0);transition:transform .15s}
.opt:has(input:checked){border-color:var(--orange);background:rgba(255,106,26,.09)}
.opt:has(input:checked) .radio{border-color:var(--orange)}
.opt:has(input:checked) .radio::after{transform:scale(1)}
.opt:has(input:focus-visible){outline:2px solid var(--orange);outline-offset:2px}
.invalid-group .opt{border-color:rgba(255,123,123,.55)}
.err{display:none;color:var(--err);font-size:.88rem;margin:-4px 0 12px 6px}
.invalid-group>.err,.field.invalid .err{display:block}
.spacer{flex:1;min-height:16px}
.btn{width:100%;min-height:56px;border:0;border-radius:999px;padding:16px;font-size:1.05rem;font-weight:700;color:#fff;cursor:pointer;
  background:linear-gradient(90deg,var(--orange),var(--orange2));margin-top:12px;display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none}
.btn:disabled{opacity:.6;cursor:progress}
.spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:sp .7s linear infinite;display:none}
.loading .spin{display:inline-block}
@keyframes sp{to{transform:rotate(360deg)}}
.field{margin-bottom:16px}
.field label{display:block;font-weight:600;margin-bottom:8px;font-size:.95rem}
.field input{width:100%;min-height:54px;background:var(--panel);border:1.5px solid var(--line);border-radius:16px;color:#fff;padding:14px 18px;font-size:1rem;font-family:inherit}
.field input::placeholder{color:#6d7485}
.field input:focus{border-color:var(--orange);outline:none}
.field.invalid input{border-color:var(--err)}
.field .err{margin:6px 0 0}
.hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.banner{display:none;border:1px solid var(--err);background:rgba(255,123,123,.08);color:#ffd0d0;border-radius:14px;padding:12px 14px;font-size:.92rem;margin-bottom:8px}
.banner.show{display:block}
.consent{color:var(--muted);font-size:.8rem;margin-top:14px;text-align:center}
.thanks{text-align:center;margin:auto 0;padding:16px 0}
.tick{width:76px;height:76px;border-radius:50%;margin:0 auto 22px;display:grid;place-items:center;background:linear-gradient(135deg,var(--orange),var(--orange2))}
.tick svg{width:38px;height:38px}
.tick path{stroke-dasharray:30;stroke-dashoffset:30;animation:draw .5s .15s ease forwards}
@keyframes draw{to{stroke-dashoffset:0}}
.thanks .support{max-width:380px;margin:0 auto}
.foot{margin-top:28px;padding-top:18px;border-top:1px solid var(--line);color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
@if(!empty($metaPixelId))
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"></noscript>
@endif
<div class="wrap">
  <div class="top">
    <button class="icon-btn" id="back" type="button" aria-label="Go back to previous step" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </button>
    <button class="icon-btn" id="close" type="button" aria-label="Close and clear the form">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <div class="progress" id="progress" role="progressbar" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1" aria-label="Form progress">
    <span></span><span></span><span></span><span></span>
  </div>

  <div class="brand">
    <div class="logo" id="logo" aria-hidden="true">
      @if(!empty($logoUrl))
        <img src="{{ $logoUrl }}" alt="{{ $companyName ?? 'Okina Craft' }}" width="70" height="70">
      @else
        OC
      @endif
    </div>
    <strong id="brandName">{{ $companyName ?? 'Okina Craft' }}</strong>
  </div>

  <form id="form" class="stage" novalidate>
    <!-- Step 1 -->
    <section class="step active" data-step="1" aria-labelledby="h1">
      <div class="step-hero-media">
        <img src="{{ asset('storefront/landing/bulk-hero-apparel.jpg') }}" alt="Custom Bulk Printing Apparel" class="step-hero-img" width="480" height="120" fetchpriority="high">
      </div>
      <h2 id="h1" tabindex="-1">Get Bulk Printing for Your Business</h2>
      <p class="support">Custom T-shirts, clothing, bags, uniforms, and other printed products. Tell us about your business and printing requirements.</p>
      <fieldset class="group-plain" data-group="product">
        <legend>What do you usually need printing for?</legend>
        <div class="opts">
          <label class="opt"><span>T-shirts</span><input type="radio" name="printing_requirement" value="T-shirts"><i class="radio"></i></label>
          <label class="opt"><span>Shirts / Other Clothes</span><input type="radio" name="printing_requirement" value="Shirts / Other Clothes"><i class="radio"></i></label>
          <label class="opt"><span>Bags</span><input type="radio" name="printing_requirement" value="Bags"><i class="radio"></i></label>
          <label class="opt"><span>Caps</span><input type="radio" name="printing_requirement" value="Caps"><i class="radio"></i></label>
          <label class="opt"><span>Uniforms</span><input type="radio" name="printing_requirement" value="Uniforms"><i class="radio"></i></label>
          <label class="opt"><span>Other Products</span><input type="radio" name="printing_requirement" value="Other Products"><i class="radio"></i></label>
        </div>
        <p class="err" role="alert">Please select an option to continue.</p>
      </fieldset>
      <div class="spacer"></div>
      <button type="button" class="btn" data-next>Next →</button>
    </section>

    <!-- Step 2 -->
    <section class="step" data-step="2" aria-labelledby="h2">
      <h2 id="h2" tabindex="-1">What type of business do you have?</h2>
      <p class="support">Select the option that best describes your business or organization.</p>
      <fieldset data-group="business">
        <legend class="sr" style="position:absolute;left:-9999px">Business type</legend>
        <div class="opts">
          <label class="opt"><span>T-shirt / Clothing Business</span><input type="radio" name="business_type" value="T-shirt / Clothing Business"><i class="radio"></i></label>
          <label class="opt"><span>Printing / Customization Business</span><input type="radio" name="business_type" value="Printing / Customization Business"><i class="radio"></i></label>
          <label class="opt"><span>School / College / Institute</span><input type="radio" name="business_type" value="School / College / Institute"><i class="radio"></i></label>
          <label class="opt"><span>Event / Corporate Business</span><input type="radio" name="business_type" value="Event / Corporate Business"><i class="radio"></i></label>
          <label class="opt"><span>Sports Team / Club</span><input type="radio" name="business_type" value="Sports Team / Club"><i class="radio"></i></label>
          <label class="opt"><span>Other Business</span><input type="radio" name="business_type" value="Other Business"><i class="radio"></i></label>
        </div>
        <p class="err" role="alert">Please select an option to continue.</p>
      </fieldset>
      <div class="spacer"></div>
      <button type="button" class="btn" data-next>Continue →</button>
    </section>

    <!-- Step 3 -->
    <section class="step" data-step="3" aria-labelledby="h3">
      <h2 id="h3" tabindex="-1">Tell Us About Your Printing Requirements</h2>
      <p class="support">Help us understand your order so we can recommend the right printing solution.</p>

      <fieldset class="group" data-group="quantity">
        <legend>What quantity do you need?</legend>
        <label class="opt"><span>50–100 pieces</span><input type="radio" name="quantity" value="50-100 pieces"><i class="radio"></i></label>
        <label class="opt"><span>101–250 pieces</span><input type="radio" name="quantity" value="101-250 pieces"><i class="radio"></i></label>
        <label class="opt"><span>251–500 pieces</span><input type="radio" name="quantity" value="251-500 pieces"><i class="radio"></i></label>
        <label class="opt"><span>501–1,000 pieces</span><input type="radio" name="quantity" value="501-1,000 pieces"><i class="radio"></i></label>
        <label class="opt"><span>1,000+ pieces</span><input type="radio" name="quantity" value="1,000+ pieces"><i class="radio"></i></label>
        <p class="err" role="alert">Please choose a quantity.</p>
      </fieldset>

      <fieldset class="group" data-group="design">
        <legend>Do you have a design ready?</legend>
        <label class="opt"><span>Yes, my design is ready</span><input type="radio" name="design_readiness" value="Design ready"><i class="radio"></i></label>
        <label class="opt"><span>I need help with the design</span><input type="radio" name="design_readiness" value="Need help with design"><i class="radio"></i></label>
        <label class="opt"><span>I need design and printing services</span><input type="radio" name="design_readiness" value="Need design and printing services"><i class="radio"></i></label>
        <p class="err" role="alert">Please select an option.</p>
      </fieldset>

      <fieldset class="group" data-group="timeline">
        <legend>When do you need your order?</legend>
        <label class="opt"><span>As soon as possible</span><input type="radio" name="required_timeline" value="As soon as possible"><i class="radio"></i></label>
        <label class="opt"><span>Within 1–2 weeks</span><input type="radio" name="required_timeline" value="Within 1-2 weeks"><i class="radio"></i></label>
        <label class="opt"><span>Within 3–4 weeks</span><input type="radio" name="required_timeline" value="Within 3-4 weeks"><i class="radio"></i></label>
        <label class="opt"><span>Just exploring options</span><input type="radio" name="required_timeline" value="Just exploring options"><i class="radio"></i></label>
        <p class="err" role="alert">Please select an option.</p>
      </fieldset>
      <div class="spacer"></div>
      <button type="button" class="btn" data-next>Continue →</button>
    </section>

    <!-- Step 4 -->
    <section class="step" data-step="4" aria-labelledby="h4">
      <h2 id="h4" tabindex="-1">Get Your Bulk Printing Quote</h2>
      <p class="support">You're almost there! Share your contact details, and our team will contact you to discuss your printing requirements and the next steps.</p>
      <div class="field">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="full_name" placeholder="Enter your full name" autocomplete="name" required aria-describedby="e-name">
        <p class="err" id="e-name" role="alert">Please enter your full name.</p>
      </div>
      <div class="field">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" placeholder="Enter your mobile number" autocomplete="tel" inputmode="tel" required aria-describedby="e-phone">
        <p class="err" id="e-phone" role="alert">Enter a valid 10-digit Indian mobile number (starting 6–9).</p>
      </div>
      <div class="field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email address" autocomplete="email" required aria-describedby="e-email">
        <p class="err" id="e-email" role="alert">Please enter a valid email address.</p>
      </div>
      <div class="hp" aria-hidden="true"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="spacer"></div>
      <div class="banner" id="banner" role="alert"></div>
      <button type="submit" class="btn" id="submit"><span class="spin" aria-hidden="true"></span><span id="submitLabel">Submit</span></button>
      <p class="consent">By submitting this form, you agree to be contacted by {{ $companyName ?? 'Okina Craft' }} regarding your enquiry.</p>
    </section>

    <!-- Thank you -->
    <section class="step" data-step="5" aria-labelledby="h5">
      <div class="thanks">
        <div class="tick"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></div>
        <h2 id="h5" tabindex="-1">Thank You for Your Enquiry!</h2>
        <p class="support">Your bulk printing enquiry has been submitted successfully. Our team will review your requirements and contact you to discuss your order.</p>
        <p class="foot">{{ $companyName ?? 'Okina Craft' }} — Bulk Printing Solutions for Your Business</p>
      </div>
    </section>
  </form>
</div>

<script>
/* ============================================================
   CONFIGURATION — connected to Okina Craft Lead System
   ============================================================ */
var CONFIG = {
  BRAND_NAME: @json($companyName ?? 'Okina Craft'),
  LOGO_URL: @json($logoUrl ?? ''),
  META_PIXEL_ID: @json($metaPixelId ?? '1983890512310174'),
  LEAD_ENDPOINT: @json(route('landing.bulk-printing.quote')),
  THANK_YOU_URL: @json(route('landing.bulk-printing.thank-you')),
  REQUEST_HEADERS: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  },
  EXTRA_FIELDS: {
    utm_source: @json(request('utm_source')),
    utm_medium: @json(request('utm_medium')),
    utm_campaign: @json(request('utm_campaign')),
    utm_content: @json(request('utm_content'))
  },
  TIMEOUT_MS: 15000,
  ANALYTICS: { enabled: true, events: {
    step_view: 'lead_form_step_view',
    step_complete: 'lead_form_step_complete',
    submit_attempt: 'lead_form_submit_attempt',
    submit_success: 'lead_form_submit_success',
    submit_error: 'lead_form_submit_error'
  }}
};

/* Meta Pixel loader (standard base code, driven by CONFIG.META_PIXEL_ID) */
function loadMetaPixel(id){
  if(!id||window.fbq) return;
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
  s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init',id); fbq('track','PageView');
}

(function(){
  loadMetaPixel(CONFIG.META_PIXEL_ID);
  if(typeof window.fbq==='function') window.fbq('track','ViewContent',{content_name:'Bulk Printing Lead Form',content_category:'Lead Generation'});
  var form=document.getElementById('form'), stage=form;
  var steps=[].slice.call(document.querySelectorAll('.step'));
  var bars=[].slice.call(document.querySelectorAll('#progress span'));
  var progress=document.getElementById('progress');
  var back=document.getElementById('back'), banner=document.getElementById('banner');
  var submitBtn=document.getElementById('submit'), submitLabel=document.getElementById('submitLabel');
  var current=1, sending=false, submitted=false;

  document.getElementById('brandName').textContent=CONFIG.BRAND_NAME;
  if(CONFIG.LOGO_URL){
    var l=document.getElementById('logo');
    var existingImg=l.querySelector('img');
    if(existingImg){
      existingImg.src=CONFIG.LOGO_URL;
      existingImg.alt=CONFIG.BRAND_NAME;
    }else{
      l.textContent='';
      var im=new Image(); im.src=CONFIG.LOGO_URL; im.alt=CONFIG.BRAND_NAME; l.appendChild(im);
    }
  }

  /* ---------- analytics ---------- */
  function track(key,params){
    var A=CONFIG.ANALYTICS; if(!A||!A.enabled) return;
    var name=A.events[key]||key; params=params||{};
    try{
      (window.dataLayer=window.dataLayer||[]).push(Object.assign({event:name},params));
      if(typeof window.gtag==='function') window.gtag('event',name,params);
      if(typeof window.fbq==='function') window.fbq('trackCustom',name,params);
      document.dispatchEvent(new CustomEvent('okina:track',{detail:{name:name,params:params}}));
    }catch(e){}
  }

  /* ---------- navigation ---------- */
  var groupsByStep={1:['printing_requirement'],2:['business_type'],3:['quantity','design_readiness','required_timeline']};
  var groupNames={printing_requirement:'product',business_type:'business',quantity:'quantity',design_readiness:'design',required_timeline:'timeline'};

  function show(n,isBack){
    current=n;
    stage.classList.toggle('back',!!isBack);
    steps.forEach(function(s){s.classList.toggle('active',+s.dataset.step===n)});
    bars.forEach(function(b,i){b.classList.toggle('done',i<Math.min(n,4))});
    progress.setAttribute('aria-valuenow',Math.min(n,4));
    back.hidden=(n===1||n===5);
    progress.style.display=n===5?'none':'flex';
    window.scrollTo({top:0,behavior:'smooth'});
    var h=document.querySelector('.step.active h2'); if(h) h.focus({preventScroll:true});
    track('step_view',{step:n});
  }

  function fieldsetFor(name){return form.querySelector('fieldset[data-group="'+groupNames[name]+'"]')}
  function validateGroup(name){
    var ok=!!form.querySelector('input[name="'+name+'"]:checked');
    var fs=fieldsetFor(name); fs.classList.toggle('invalid-group',!ok);
    return ok;
  }
  function validateStep(n){
    var first=null, ok=true;
    (groupsByStep[n]||[]).forEach(function(g){ if(!validateGroup(g)){ ok=false; if(!first) first=fieldsetFor(g);} });
    if(first) first.scrollIntoView({behavior:'smooth',block:'center'});
    return ok;
  }

  form.addEventListener('change',function(e){
    if(e.target.type==='radio') validateGroup(e.target.name);
  });

  document.querySelectorAll('[data-next]').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!validateStep(current)) return;
      track('step_complete',{step:current});
      show(current+1,false);
    });
  });
  back.addEventListener('click',function(){ if(current>1&&!sending) show(current-1,true); });

  document.getElementById('close').addEventListener('click',function(){
    if(sending) return;
    if(!submitted && !confirmReset()) return;
    form.reset(); submitted=false; hideBanner();
    document.querySelectorAll('.invalid,.invalid-group').forEach(function(x){x.classList.remove('invalid','invalid-group')});
    show(1,true);
  });
  function confirmReset(){
    var touched=form.querySelector('input:checked')||document.getElementById('name').value;
    return !touched || window.confirm('Close and clear your answers?');
  }

  /* ---------- validation ---------- */
  function normPhone(v){
    var d=v.replace(/\D/g,'');
    if(d.length===12&&d.indexOf('91')===0) d=d.slice(2);
    else if(d.length===11&&d[0]==='0') d=d.slice(1);
    return d;
  }
  var rules={
    name:function(v){return v.trim().length>=2 && /[A-Za-z\u0900-\u097F\u0A00-\u0A7F]/.test(v)},
    phone:function(v){return /^[6-9]\d{9}$/.test(normPhone(v))},
    email:function(v){return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim())}
  };
  function checkField(id){
    var el=document.getElementById(id), ok=rules[id](el.value);
    el.closest('.field').classList.toggle('invalid',!ok);
    el.setAttribute('aria-invalid',ok?'false':'true');
    return ok;
  }
  ['name','phone','email'].forEach(function(id){
    var el=document.getElementById(id);
    el.addEventListener('blur',function(){ if(el.value) checkField(id); });
    el.addEventListener('input',function(){ if(el.closest('.field').classList.contains('invalid')) checkField(id); });
  });

  /* ---------- submission ---------- */
  function showBanner(msg){banner.textContent=msg;banner.classList.add('show')}
  function hideBanner(){banner.classList.remove('show')}
  function setSending(on){
    sending=on; submitBtn.disabled=on; submitBtn.classList.toggle('loading',on);
    submitLabel.textContent=on?'Submitting…':'Submit';
    back.disabled=on;
  }

  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(sending||submitted) return;
    hideBanner();

    for(var s=1;s<=3;s++){ if(!validateStep(s)){ show(s,true); return; } }
    var res=['name','phone','email'].map(checkField);
    if(res.indexOf(false)>-1){ document.querySelector('.field.invalid input').focus(); return; }

    if(form.elements.website.value){ return; }

    var fd=new FormData(form);
    var payload=Object.assign({
      printing_requirement:fd.get('printing_requirement'),
      business_type:fd.get('business_type'),
      quantity:fd.get('quantity'),
      design_readiness:fd.get('design_readiness'),
      required_timeline:fd.get('required_timeline'),
      full_name:fd.get('full_name').trim(),
      phone:normPhone(fd.get('phone')),
      email:fd.get('email').trim(),
      website:fd.get('website')||'',
      source:'okina-craft-landing-page',
      page_url:location.href,
      submitted_at:new Date().toISOString()
    },CONFIG.EXTRA_FIELDS);

    track('submit_attempt',{step:4});

    if(!CONFIG.LEAD_ENDPOINT){
      track('submit_error',{reason:'not_configured'});
      showBanner('Submission is not set up yet: no lead endpoint is configured.');
      return;
    }

    setSending(true);
    var ctl=('AbortController' in window)?new AbortController():null;
    var timer=setTimeout(function(){ if(ctl) ctl.abort(); },CONFIG.TIMEOUT_MS);

    fetch(CONFIG.LEAD_ENDPOINT,{
      method:'POST',
      headers:CONFIG.REQUEST_HEADERS,
      body:JSON.stringify(payload),
      signal:ctl?ctl.signal:undefined
    }).then(function(r){
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.json();
    }).then(function(data){
      submitted=true;
      track('submit_success',{business_type:payload.business_type,quantity:payload.quantity});
      if(CONFIG.THANK_YOU_URL){
        try{ sessionStorage.setItem('okina_lead_ok','1'); }catch(e){}
        setTimeout(function(){ location.href=CONFIG.THANK_YOU_URL; },500);
      } else {
        if(typeof window.fbq==='function') window.fbq('track','Lead',{content_name:'Bulk printing enquiry'});
        show(5,false);
      }
    }).catch(function(err){
      var timedOut=err&&err.name==='AbortError';
      track('submit_error',{reason:timedOut?'timeout':String(err&&err.message||'network')});
      showBanner(timedOut
        ? 'The request timed out. Your answers are saved on this page — please check your connection and try again.'
        : 'We could not submit your enquiry. Your answers are still here — please try again in a moment.');
    }).then(function(){ clearTimeout(timer); setSending(false); });
  });

  track('step_view',{step:1});
})();
</script>
</body>
</html>
