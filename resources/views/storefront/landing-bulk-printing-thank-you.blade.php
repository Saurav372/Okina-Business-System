<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
<title>Thank You – {{ $companyName ?? 'Okina Craft' }}</title>
<style>
:root{--bg:#0c0d10;--line:#2b2f38;--muted:#a4aab7;--orange:#ff6a1a;--orange2:#ff3d5a}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:#fff;font-family:"Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;line-height:1.45;
  -webkit-font-smoothing:antialiased;padding:env(safe-area-inset-top) 0 env(safe-area-inset-bottom)}
.wrap{max-width:520px;margin:0 auto;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;justify-content:center;padding:32px 20px;text-align:center}
.logo{width:70px;height:70px;border-radius:50%;margin:0 auto 12px;border:1px solid rgba(255,106,26,0.32);display:grid;place-items:center;overflow:hidden;
  background:radial-gradient(circle at 50% 30%,rgba(232,53,53,0.18),#14161b);box-shadow:0 8px 24px rgba(0,0,0,0.5),0 0 16px rgba(255,106,26,0.12);
  font-weight:800;font-size:22px;color:var(--orange)}
.logo img{width:100%;height:100%;object-fit:contain;padding:7px;display:block}
.brand{font-weight:700;font-size:1.15rem;letter-spacing:-0.01em;margin-bottom:32px;color:#fff}
.tick{width:76px;height:76px;border-radius:50%;margin:0 auto 22px;display:grid;place-items:center;background:linear-gradient(135deg,var(--orange),var(--orange2))}
.tick svg{width:38px;height:38px}
.tick path{stroke-dasharray:30;stroke-dashoffset:30;animation:draw .5s .15s ease forwards}
@keyframes draw{to{stroke-dashoffset:0}}
@media (prefers-reduced-motion:reduce){.tick path{animation:none;stroke-dashoffset:0}}
h1{font-size:1.6rem;font-weight:650;line-height:1.25;margin-bottom:10px}
p.msg{color:var(--muted);font-size:1rem;max-width:400px;margin:0 auto}
.actions{margin-top:28px;display:flex;flex-direction:column;gap:10px}
.btn{width:100%;min-height:50px;border:0;border-radius:999px;padding:14px 20px;font-size:1rem;font-weight:700;color:#fff;cursor:pointer;
  background:linear-gradient(90deg,var(--orange),var(--orange2));display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;transition:transform .15s}
.btn:hover{transform:scale(1.02)}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid var(--line)}
.foot{margin-top:36px;padding-top:18px;border-top:1px solid var(--line);color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
@if(!empty($metaPixelId))
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"></noscript>
@endif
<main class="wrap">
  <div class="logo" id="logo" aria-hidden="true">
    @if(!empty($logoUrl))
      <img src="{{ $logoUrl }}" alt="{{ $companyName ?? 'Okina Craft' }}" width="70" height="70">
    @else
      OC
    @endif
  </div>
  <div class="brand">{{ $companyName ?? 'Okina Craft' }}</div>
  <div class="tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></div>
  <h1>Thank You for Your Enquiry!</h1>
  <p class="msg">Your bulk printing enquiry has been submitted successfully. Our team will review your requirements and contact you promptly with quotes & samples.</p>

  @if(!empty($whatsappUrl))
    <div class="actions">
      <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.04 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.16 12.04 20.16C10.66 20.16 9.3 19.8 8.1 19.1L7.81 18.93L4.69 19.75L5.52 16.71L5.34 16.42C4.55 15.17 4.13 13.56 4.13 11.91C4.13 7.37 7.84 3.67 12.05 3.67M9.53 7.42C9.36 7.42 9.08 7.48 8.85 7.73C8.62 7.98 7.97 8.59 7.97 9.84C7.97 11.09 8.88 12.3 9.01 12.47C9.14 12.64 10.79 15.19 13.33 16.29C15.44 17.2 15.87 17.02 16.33 16.98C16.79 16.94 17.81 16.38 18.02 15.79C18.23 15.2 18.23 14.7 18.17 14.59C18.11 14.48 17.94 14.42 17.69 14.3C17.44 14.18 16.21 13.57 15.98 13.49C15.75 13.41 15.58 13.37 15.41 13.62C15.24 13.87 14.76 14.42 14.61 14.59C14.46 14.76 14.31 14.78 14.06 14.66C13.81 14.54 13.01 14.27 12.06 13.42C11.32 12.76 10.82 11.95 10.67 11.7C10.52 11.45 10.65 11.32 10.78 11.19C10.89 11.08 11.03 10.9 11.16 10.75C11.29 10.6 11.33 10.49 11.41 10.33C11.49 10.17 11.45 10.03 11.39 9.91C11.33 9.79 10.83 8.56 10.62 8.06C10.42 7.57 10.21 7.64 10.05 7.63C9.9 7.62 9.72 7.42 9.53 7.42Z"/></svg>
        Need Instant Quote? Chat on WhatsApp
      </a>
      <a href="{{ route('storefront.home') }}" class="btn btn-secondary">
        Browse Okina Catalog
      </a>
    </div>
  @endif

  <p class="foot">{{ $companyName ?? 'Okina Craft' }} — Bulk Printing Solutions for Your Business</p>
</main>
<script>
var CONFIG={
  META_PIXEL_ID:@json($metaPixelId ?? '1983890512310174'),
  LOGO_URL:@json($logoUrl ?? ''),
  REQUIRE_SUBMISSION_FLAG:true,
  ANALYTICS:{enabled:true,event:'lead_form_thank_you_view'}
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
  try{
    var ok=!CONFIG.REQUIRE_SUBMISSION_FLAG||sessionStorage.getItem('okina_lead_ok');
    if(ok&&typeof window.fbq==='function'){
      window.fbq('track','Lead',{content_name:'Bulk printing enquiry'});
      sessionStorage.removeItem('okina_lead_ok');
    }
  }catch(e){}
  if(CONFIG.LOGO_URL){
    var l=document.getElementById('logo');
    var existingImg=l.querySelector('img');
    if(existingImg){
      existingImg.src=CONFIG.LOGO_URL;
    }else{
      l.textContent='';
      var i=new Image();i.src=CONFIG.LOGO_URL;i.alt='';l.appendChild(i);
    }
  }
  try{
    if(!CONFIG.ANALYTICS.enabled||sessionStorage.getItem('ty_tracked')) return;
    sessionStorage.setItem('ty_tracked','1');
    var n=CONFIG.ANALYTICS.event;
    (window.dataLayer=window.dataLayer||[]).push({event:n});
    if(typeof window.gtag==='function') window.gtag('event',n);
    if(typeof window.fbq==='function') window.fbq('trackCustom',n);
  }catch(e){}
})();
</script>
</body>
</html>
