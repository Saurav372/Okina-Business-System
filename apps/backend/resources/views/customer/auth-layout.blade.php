<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a1816">
    <meta name="robots" content="noindex,nofollow">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('title') | Okina Craft</title>
    <style>
        :root{--ink:#1a1816;--muted:#625a54;--paper:#f6f1e8;--surface:#fffaf3;--line:#d9d0c5;--red:#d92d2d;--red-dark:#b82020;--danger:#b42318;--danger-soft:#fdeae7;--serif:"Iowan Old Style","Palatino Linotype",Georgia,serif;--sans:"Aptos","Segoe UI",system-ui,sans-serif}*{box-sizing:border-box}body{margin:0;min-width:320px;background:var(--paper);color:var(--ink);font-family:var(--sans);line-height:1.5}body:before{position:fixed;inset:0;z-index:-1;background-image:radial-gradient(rgba(26,24,22,.06) .55px,transparent .55px);background-size:6px 6px;content:""}.auth-shell{display:grid;min-height:100vh;grid-template-columns:minmax(20rem,.85fr) minmax(26rem,1.15fr)}.auth-story{display:flex;min-height:100%;flex-direction:column;justify-content:space-between;padding:clamp(2rem,6vw,5rem);background:var(--ink);color:white}.brand{display:inline-flex;align-items:center;gap:.7rem;color:white;font-family:var(--serif);font-size:1.35rem;font-weight:700;text-decoration:none}.brand-mark{display:grid;width:2.25rem;aspect-ratio:1;place-items:center;border:1px solid white;background:var(--red)}.story-copy p:first-child{color:#ff7971;font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.story-copy h2{max-width:10ch;margin:.8rem 0 1.2rem;font-family:var(--serif);font-size:clamp(2.7rem,6vw,5.5rem);font-weight:600;letter-spacing:-.045em;line-height:.95}.story-copy>p:last-child{max-width:32rem;color:#c8c0b9;font-size:1.05rem}.trust-list{display:flex;flex-wrap:wrap;gap:.7rem;color:#d7d0ca;font-size:.75rem}.trust-list span{padding:.45rem .65rem;border:1px solid #4a4541;border-radius:999px}.auth-main{display:grid;place-items:center;padding:clamp(1.25rem,6vw,5rem)}.auth-card{width:min(100%,29rem)}.back-link{display:inline-flex;margin-bottom:2rem;color:var(--muted);font-size:.8rem;font-weight:700;text-decoration:none}.back-link:hover{color:var(--red)}.eyebrow{margin:0 0 .7rem;color:var(--red);font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.auth-card h1{margin:0 0 .7rem;font-family:var(--serif);font-size:clamp(2.7rem,7vw,4.5rem);font-weight:600;letter-spacing:-.045em;line-height:.98}.intro{margin:0 0 2rem;color:var(--muted)}.error-summary{padding:1rem;margin-bottom:1.2rem;border-left:3px solid var(--danger);background:var(--danger-soft);color:var(--danger);font-size:.82rem}.error-summary p{margin:.2rem 0}.auth-form{display:grid;gap:1rem}.field{display:grid;gap:.4rem}.field span{font-size:.82rem;font-weight:750}.field input{width:100%;min-height:3.25rem;padding:.75rem .85rem;border:1px solid #aaa096;border-radius:.35rem;background:white;color:var(--ink);font:inherit}.field input:focus{border-color:var(--red);outline:2px solid var(--red);outline-offset:2px}.field small{color:var(--muted);font-size:.7rem}.submit{display:inline-flex;min-height:3.25rem;align-items:center;justify-content:center;margin-top:.5rem;padding:.8rem 1rem;border:1px solid var(--red);border-radius:.35rem;background:var(--red);color:white;cursor:pointer;font:inherit;font-weight:800}.submit:hover{border-color:var(--red-dark);background:var(--red-dark)}.switch{padding-top:1.2rem;border-top:1px solid var(--line);margin-top:1.5rem;color:var(--muted);font-size:.85rem}.switch a{color:var(--red);font-weight:800}@media(max-width:780px){.auth-shell{grid-template-columns:1fr}.auth-story{min-height:auto;padding:1.25rem}.story-copy,.trust-list{display:none}.auth-main{align-items:start;padding-top:2.5rem}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important}}
    </style>
    <style>
        .success-summary{padding:1rem;margin-bottom:1.2rem;border-left:3px solid #217a4b;background:#e6f3eb;color:#217a4b;font-size:.82rem}.field-line{display:flex;align-items:center;justify-content:space-between;gap:1rem}.field-line a{color:var(--red);font-size:.72rem;font-weight:800}
    </style>
    <style>
        :root{--paper:#fff;--surface:#fff;--line:#e1e1e1}body:before{display:none}.brand-mark{position:relative;display:block;flex:0 0 auto;width:3rem;overflow:hidden;border:0;background:#fff}.brand-logo-image{position:absolute;top:-23.5%;left:-25%;width:150%;max-width:none;height:auto}
    </style>
</head>
<body>
@php($storefront = rtrim(env('PUBLIC_SITE_URL', env('FRONTEND_URL', 'http://127.0.0.1:4321')), '/'))
<div class="auth-shell">
    <aside class="auth-story">
        <a class="brand" href="{{ $storefront }}"><span class="brand-mark" aria-hidden="true"><img class="brand-logo-image" src="{{ $storefront }}/brand/okina-logo.svg" alt="" width="1080" height="1350"></span><span>Okina Craft</span></a>
        <div class="story-copy"><p>Your mark, made wearable</p><h2>A calmer custom-order journey.</h2><p>Your saved artwork, addresses, approvals, and order progress stay together in one private account.</p></div>
        <div class="trust-list"><span>Private artwork</span><span>Saved order history</span><span>Production updates</span></div>
    </aside>
    <main class="auth-main"><div class="auth-card"><a class="back-link" href="{{ $storefront }}">← Back to the shop</a>@yield('content')</div></main>
</div>
</body>
</html>
