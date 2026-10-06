<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Custom T-Shirts &amp; Branded Apparel for Businesses · {{ $companyName }}</title>
    <meta name="description" content="Company uniforms, event T-shirts, team apparel and custom merchandise with printing or embroidery. Bulk-order support with Pan-India delivery.">
    <meta name="theme-color" content="#e83535">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Custom T-Shirts &amp; Branded Apparel for Businesses · {{ $companyName }}">
    <meta property="og:description" content="Company uniforms, event T-shirts, team apparel and custom merchandise with printing or embroidery. Bulk-order support with Pan-India delivery.">
    <meta property="og:image" content="{{ asset('storefront/custom/hero-apparel.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Custom T-Shirts &amp; Branded Apparel for Businesses · {{ $companyName }}">
    <meta name="twitter:description" content="Company uniforms, event T-shirts, team apparel and custom merchandise with printing or embroidery. Bulk-order support with Pan-India delivery.">
    <meta name="twitter:image" content="{{ asset('storefront/custom/hero-apparel.jpg') }}">
    @if(request()->has('source') || request()->has('utm_source'))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* Okina B2B CRO Design System & Mobile Density Optimization */
        :root {
            --brand-ink: #111111;
            --brand-red: #e83535;
            --brand-red-hover: #cf2727;
            --brand-bg: #f8f8f6;
            --brand-surface: #ffffff;
            --brand-border: #e2e2de;
            --brand-subtle: #f0f0ed;
            --brand-muted: #555555;
            --brand-light-muted: #666666;
            --brand-wa: #25d366;
            --brand-wa-hover: #20bd5a;
            --font-family: 'Manrope', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-family);
            color: var(--brand-ink);
            background-color: var(--brand-surface);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 88px; /* Safe space to prevent sticky bar obscuring content on mobile */
        }

        @media (min-width: 769px) {
            body {
                padding-bottom: 0;
            }
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* Skip to Content (UI-Skills / B10 Accessibility) */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .sr-only:focus {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 100;
            width: auto;
            height: auto;
            padding: 10px 18px;
            background: var(--brand-ink);
            color: #ffffff;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            clip: auto;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        }

        /* Focus Visibility (UI-Skills Standard) */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, summary:focus-visible {
            outline: 2px solid var(--brand-ink);
            outline-offset: 2px;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1160px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* Distraction-Free Header (Slimmer on Mobile) */
        .lp-header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--brand-border);
            position: sticky;
            top: 0;
            z-index: 40;
            transition: height 160ms ease, box-shadow 160ms ease;
        }

        .lp-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 52px; /* Leaner 52px height on mobile */
        }

        @media (min-width: 769px) {
            .lp-header-inner {
                height: 60px;
            }
        }

        .lp-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.03em;
            color: var(--brand-ink);
        }

        @media (min-width: 769px) {
            .lp-brand {
                font-size: 19px;
            }
        }

        .lp-brand span {
            color: var(--brand-red);
        }

        .lp-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lp-header-phone {
            display: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand-muted);
        }

        @media (min-width: 640px) {
            .lp-header-phone {
                display: flex;
                align-items: center;
                gap: 6px;
            }
        }

        .lp-wa-btn-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #eefbf3;
            color: #178c43;
            border: 1px solid #c2eed2;
            padding: 7px 13px;
            min-height: 36px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            transition: all 160ms var(--ease-out);
        }

        .lp-wa-btn-sm:hover {
            background: #def7e7;
            border-color: #a4e5bb;
        }

        /* Buttons */
        .btn-wa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--brand-wa);
            color: #ffffff;
            font-size: 15px;
            font-weight: 750;
            padding: 13px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.28);
            transition: background-color 160ms var(--ease-out), transform 160ms var(--ease-out);
            min-height: 48px;
            text-align: center;
        }

        .btn-wa:hover {
            background-color: var(--brand-wa-hover);
            transform: translateY(-1px);
        }

        .btn-wa:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--brand-surface);
            color: var(--brand-ink);
            font-size: 14px;
            font-weight: 650;
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid var(--brand-border);
            cursor: pointer;
            transition: all 160ms var(--ease-out);
            min-height: 48px;
            text-align: center;
        }

        .btn-secondary:hover {
            background-color: var(--brand-bg);
            border-color: #cfcfcb;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--brand-red);
            color: #ffffff;
            font-size: 15px;
            font-weight: 750;
            padding: 13px 22px;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            transition: all 160ms var(--ease-out);
            min-height: 46px;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--brand-red-hover);
        }

        /* Hero Section */
        .lp-hero {
            background: var(--brand-bg);
            border-bottom: 1px solid var(--brand-border);
            padding: 30px 0 36px;
        }

        .lp-hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: center;
        }

        @media (min-width: 992px) {
            .lp-hero {
                padding: 44px 0 48px;
            }
            .lp-hero-grid {
                grid-template-columns: 1.15fr 0.85fr;
                gap: 40px;
            }
        }

        .lp-eyebrow {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: var(--brand-red);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .lp-hero-title {
            font-size: clamp(27px, 3.8vw, 44px);
            font-weight: 850;
            line-height: 1.12;
            letter-spacing: -0.035em;
            margin-bottom: 12px;
            color: var(--brand-ink);
            text-wrap: balance;
        }

        .lp-hero-lead {
            font-size: 14.5px;
            line-height: 1.5;
            color: var(--brand-muted);
            margin-bottom: 16px;
            max-width: 540px;
            text-wrap: pretty;
        }

        /* Optimized Compact 2-Row Trust Chips (Low Height Above the Fold) */
        .lp-hero-badges {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }

        .lp-badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 8px;
        }

        .lp-badge-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ffffff;
            border: 1px solid #dcdcd8;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 650;
            color: var(--brand-ink);
            white-space: nowrap;
        }

        .lp-badge-chip svg {
            width: 13px;
            height: 13px;
            color: var(--brand-red);
            flex-shrink: 0;
        }

        .lp-hero-ctas {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-bottom: 12px;
        }

        @media (min-width: 520px) {
            .lp-hero-ctas {
                flex-direction: row;
                align-items: center;
            }
        }

        .lp-hero-note {
            font-size: 12px;
            color: var(--brand-light-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            line-height: 1.35;
        }

        /* Hero Media with Branded Badge Overlay */
        .lp-hero-media-wrapper {
            position: relative;
        }

        .lp-hero-media {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--brand-border);
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .lp-hero-media img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
            aspect-ratio: 4 / 3;
        }

        .lp-hero-floating-tag {
            position: absolute;
            bottom: -12px;
            left: 16px;
            background: rgba(17, 17, 17, 0.94);
            backdrop-filter: blur(6px);
            color: #ffffff;
            border-radius: 6px;
            padding: 7px 12px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11.5px;
            font-weight: 650;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .lp-hero-floating-tag span {
            color: var(--brand-red);
            font-weight: 800;
        }

        /* Section Layout */
        .lp-section {
            padding: 38px 0;
            border-bottom: 1px solid var(--brand-border);
        }

        .lp-section-header {
            text-align: center;
            max-width: 660px;
            margin: 0 auto 22px;
        }

        .lp-section-title {
            font-size: clamp(21px, 2.6vw, 30px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.025em;
            margin-bottom: 6px;
            text-wrap: balance;
        }

        .lp-section-subtitle {
            font-size: 13.5px;
            color: var(--brand-muted);
            text-wrap: pretty;
        }

        /* 1. Finished Work Showcase Cards (Tightened Copy, Proof-First) */
        .lp-showcase-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        @media (min-width: 768px) {
            .lp-showcase-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
            }
        }

        .lp-showcase-card {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 160ms var(--ease-out), box-shadow 160ms var(--ease-out);
        }

        .lp-showcase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .lp-showcase-img-wrap {
            position: relative;
            background: #ebebe7;
            overflow: hidden;
        }

        .lp-showcase-img {
            width: 100%;
            aspect-ratio: 1.12;
            object-fit: cover;
            display: block;
        }

        .lp-showcase-tag {
            position: absolute;
            top: 7px;
            left: 7px;
            background: rgba(17, 17, 17, 0.88);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 750;
            padding: 3px 7px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .lp-showcase-meta {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .lp-showcase-meta strong {
            font-size: 13.5px;
            font-weight: 750;
            color: var(--brand-ink);
            line-height: 1.25;
        }

        .lp-showcase-meta span {
            font-size: 11.5px;
            color: var(--brand-muted);
            line-height: 1.35;
        }

        /* 2. B2B Focus Areas */
        .lp-usecase-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        @media (min-width: 600px) {
            .lp-usecase-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 992px) {
            .lp-usecase-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .lp-usecase-card {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 160ms var(--ease-out), box-shadow 160ms var(--ease-out);
        }

        .lp-usecase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .lp-usecase-img {
            width: 100%;
            aspect-ratio: 1.35;
            object-fit: cover;
            background: #ebebe7;
            display: block;
        }

        .lp-usecase-body {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
        }

        .lp-usecase-body h3 {
            font-size: 14.5px;
            font-weight: 750;
            line-height: 1.25;
            color: var(--brand-ink);
        }

        .lp-usecase-body p {
            font-size: 12px;
            color: var(--brand-muted);
            line-height: 1.45;
        }

        /* 3. Printing Techniques (Full Width on Mobile to Avoid Text Squishing) */
        .lp-methods-grid {
            display: grid;
            grid-template-columns: 1fr; /* Full width on mobile */
            gap: 12px;
        }

        @media (min-width: 600px) {
            .lp-methods-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
        }

        @media (min-width: 992px) {
            .lp-methods-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .lp-method-card {
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            padding: 14px 16px;
            background: var(--brand-surface);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .lp-method-thumb {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--brand-border);
        }

        .lp-method-content {
            flex: 1;
        }

        .lp-method-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .lp-method-header strong {
            font-size: 14.5px;
            font-weight: 750;
            color: var(--brand-ink);
        }

        .lp-method-pill {
            font-size: 9.5px;
            font-weight: 750;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--brand-subtle);
            color: var(--brand-muted);
            border: 1px solid var(--brand-border);
            white-space: nowrap;
        }

        .lp-method-card p {
            font-size: 12px;
            color: var(--brand-muted);
            line-height: 1.4;
        }

        .lp-methods-footnote {
            text-align: center;
            margin-top: 16px;
            font-size: 11.5px;
            color: var(--brand-light-muted);
        }

        /* 4. Compact Fallback Quote Form */
        .lp-form-section {
            background: #fbfbf9;
            padding: 38px 0;
            border-bottom: 1px solid var(--brand-border);
            scroll-margin-top: 65px;
        }

        .lp-form-card {
            max-width: 540px;
            margin: 0 auto;
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 10px;
            padding: 22px 20px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        }

        @media (min-width: 600px) {
            .lp-form-card {
                padding: 26px 26px;
            }
        }

        .lp-form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        @media (min-width: 540px) {
            .lp-form-row-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        .lp-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .lp-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--brand-ink);
        }

        .lp-input, .lp-select {
            width: 100%;
            height: 42px;
            padding: 8px 12px;
            border: 1px solid #dcdcd8;
            border-radius: 6px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--brand-ink);
            background: #ffffff;
            transition: border-color 140ms ease, box-shadow 140ms ease;
        }

        .lp-input:focus, .lp-select:focus {
            outline: none;
            border-color: var(--brand-ink);
            box-shadow: 0 0 0 3px rgba(17, 17, 17, 0.08);
        }

        .lp-file-input {
            font-size: 12px;
            color: var(--brand-muted);
            padding: 4px 0;
        }

        .lp-qty-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 3px;
        }

        .lp-qty-chip {
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #d0d0cc;
            padding: 7px 11px;
            border-radius: 6px;
            background: #ffffff;
            color: var(--brand-ink);
            transition: all 140ms ease;
            user-select: none;
            flex: 1 1 auto;
            text-align: center;
            min-width: 60px;
        }

        .lp-qty-chip:hover {
            border-color: #999999;
            background: var(--brand-subtle);
        }

        .lp-qty-chip.active {
            background: var(--brand-ink);
            color: #ffffff;
            border-color: var(--brand-ink);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .lp-form-privacy {
            text-align: center;
            font-size: 11.5px;
            color: var(--brand-light-muted);
            margin-top: 10px;
        }

        .lp-alert-success {
            background: #edfbf2;
            border: 1px solid #c2eed2;
            color: #178c43;
            padding: 14px 18px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .lp-alert-success strong {
            display: block;
            font-size: 14px;
            margin-bottom: 3px;
        }

        /* 5. Pricing Guidance Banner (Short & Punchy) */
        .lp-pricing-banner {
            background: #111111;
            color: #ffffff;
            border-radius: 10px;
            padding: 28px 18px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        @media (min-width: 768px) {
            .lp-pricing-banner {
                padding: 38px 28px;
                gap: 14px;
            }
        }

        .lp-pricing-banner h2 {
            font-size: clamp(21px, 2.5vw, 29px);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .lp-pricing-banner p {
            font-size: 13.5px;
            color: #b5b5b5;
            max-width: 540px;
            line-height: 1.45;
        }

        /* 6. How Ordering Works (Prominent 01–05 Numbers & Vertical Timeline on Mobile) */
        .lp-steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 600px) {
            .lp-steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 992px) {
            .lp-steps-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 14px;
            }
        }

        .lp-step-card {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        @media (min-width: 992px) {
            .lp-step-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                min-height: 140px;
            }
        }

        .lp-step-num-lg {
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
            color: var(--brand-red);
            letter-spacing: -0.04em;
            flex-shrink: 0;
            padding-top: 2px;
        }

        @media (min-width: 992px) {
            .lp-step-num-lg {
                font-size: 28px;
                margin-bottom: 2px;
            }
        }

        .lp-step-content {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .lp-step-content strong {
            font-size: 14.5px;
            font-weight: 750;
            color: var(--brand-ink);
        }

        .lp-step-content p {
            font-size: 12px;
            color: var(--brand-muted);
            line-height: 1.4;
        }

        /* 7. Why Okina Craft (Concise Scannable Checkmark Badges) */
        .lp-why-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        @media (min-width: 600px) {
            .lp-why-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        @media (min-width: 992px) {
            .lp-why-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .lp-why-badge {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            padding: 13px 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: transform 160ms ease;
        }

        .lp-why-badge:hover {
            transform: translateY(-1px);
        }

        .lp-why-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fdf2f2;
            color: var(--brand-red);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .lp-why-check svg {
            width: 12px;
            height: 12px;
            stroke-width: 2.5;
        }

        .lp-why-text strong {
            display: block;
            font-size: 13.5px;
            font-weight: 750;
            color: var(--brand-ink);
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .lp-why-text p {
            font-size: 12px;
            color: var(--brand-muted);
            line-height: 1.35;
        }

        /* 8. Recent Custom Work (Density: Top 4 by Default + View More Toggle on Mobile) */
        .lp-work-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        @media (min-width: 600px) {
            .lp-work-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 992px) {
            .lp-work-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .lp-work-item {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .lp-work-img {
            width: 100%;
            aspect-ratio: 1.38;
            object-fit: cover;
            background: #ebebe7;
            display: block;
        }

        .lp-work-details {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .lp-work-pill-row {
            display: flex;
            gap: 5px;
            margin-bottom: 2px;
        }

        .lp-work-pill {
            font-size: 9.5px;
            font-weight: 750;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--brand-subtle);
            color: var(--brand-muted);
        }

        .lp-work-details strong {
            font-size: 13px;
            font-weight: 750;
            color: var(--brand-ink);
        }

        .lp-work-details p {
            font-size: 11.5px;
            color: var(--brand-muted);
            line-height: 1.35;
        }

        /* Gallery Toggle on Mobile */
        .lp-work-extra {
            display: none;
        }

        .lp-work-extra.show {
            display: flex;
        }

        @media (min-width: 992px) {
            .lp-work-extra {
                display: flex !important;
            }
            .lp-work-toggle-wrap {
                display: none !important;
            }
        }

        .lp-work-toggle-wrap {
            text-align: center;
            margin-top: 16px;
        }

        .btn-view-more {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            color: var(--brand-ink);
            border: 1px solid #d4d4d0;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 650;
            cursor: pointer;
            transition: all 140ms ease;
        }

        .btn-view-more:hover {
            background: var(--brand-subtle);
            border-color: #b5b5b0;
        }

        /* 9. FAQ Accordion */
        .lp-faq-container {
            max-width: 760px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .lp-faq-item {
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            overflow: hidden;
            background: var(--brand-surface);
        }

        .lp-faq-summary {
            padding: 13px 16px;
            font-size: 14px;
            font-weight: 750;
            cursor: pointer;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .lp-faq-summary::-webkit-details-marker {
            display: none;
        }

        .lp-faq-plus {
            font-size: 16px;
            font-weight: 600;
            color: var(--brand-light-muted);
            transition: transform 160ms ease;
        }

        details[open] .lp-faq-plus {
            transform: rotate(45deg);
        }

        .lp-faq-answer {
            padding: 0 16px 14px;
            font-size: 13px;
            color: var(--brand-muted);
            line-height: 1.5;
            border-top: 1px solid var(--brand-subtle);
            padding-top: 10px;
        }

        /* 10. Final CTA Banner */
        .lp-final-cta {
            background: var(--brand-bg);
            border-top: 1px solid var(--brand-border);
            padding: 40px 0;
            text-align: center;
        }

        .lp-final-cta h2 {
            font-size: clamp(21px, 2.8vw, 32px);
            font-weight: 850;
            letter-spacing: -0.03em;
            margin-bottom: 8px;
        }

        .lp-final-cta p {
            font-size: 14px;
            color: var(--brand-muted);
            margin-bottom: 18px;
            max-width: 540px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.45;
        }

        .lp-final-actions {
            display: flex;
            flex-direction: column;
            gap: 9px;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 500px) {
            .lp-final-actions {
                flex-direction: row;
            }
        }

        /* Footer */
        .lp-footer {
            background: var(--brand-surface);
            border-top: 1px solid var(--brand-border);
            padding: 22px 0 18px;
            font-size: 12px;
            color: var(--brand-light-muted);
        }

        .lp-footer-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            text-align: center;
        }

        @media (min-width: 640px) {
            .lp-footer-inner {
                flex-direction: row;
                text-align: left;
            }
        }

        /* 11. Mobile Persistent Sticky Bar (70-75% WhatsApp / 25-30% Get Callback) */
        .lp-mobile-sticky {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border-top: 1px solid var(--brand-border);
            padding: 9px 12px;
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 -3px 14px rgba(0, 0, 0, 0.08);
        }

        @media (min-width: 769px) {
            .lp-mobile-sticky {
                display: none;
            }
        }

        .lp-sticky-wa {
            flex: 7; /* ~70% share */
            padding: 10px 12px;
            font-size: 13.5px;
            min-height: 44px;
            white-space: nowrap;
        }

        .lp-sticky-callback {
            flex: 3; /* ~30% share */
            padding: 10px 10px;
            font-size: 12.5px;
            min-height: 44px;
            white-space: nowrap;
            text-align: center;
        }

        /* Tagline Section (B11) */
        .lp-tagline-section {
            background: var(--brand-bg);
            border-bottom: 1px solid var(--brand-border);
            padding: 34px 0;
            text-align: center;
        }

        .lp-tagline-text {
            font-size: clamp(19px, 2.4vw, 28px);
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.25;
            color: var(--brand-ink);
            max-width: 680px;
            margin: 0 auto 6px;
            text-wrap: balance;
        }

        .lp-tagline-sub {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand-muted);
            letter-spacing: 0.02em;
            text-wrap: balance;
        }

        /* Footer Legal Links & Privacy Dialog */
        .lp-footer-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .lp-footer-divider {
            color: var(--brand-border);
        }

        .lp-legal-link {
            color: var(--brand-muted);
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 140ms ease;
        }

        .lp-legal-link:hover {
            color: var(--brand-ink);
        }

        .lp-privacy-dialog {
            border: 1px solid var(--brand-border);
            border-radius: 10px;
            padding: 0;
            max-width: 520px;
            margin: auto;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.2);
            background: #ffffff;
        }

        .lp-privacy-dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .lp-privacy-content {
            padding: 22px 24px;
        }

        .lp-privacy-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--brand-subtle);
            padding-bottom: 10px;
        }

        .lp-privacy-header h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--brand-ink);
        }

        .lp-privacy-close {
            background: none;
            border: none;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            color: var(--brand-muted);
        }

        .lp-privacy-body {
            font-size: 13px;
            line-height: 1.5;
            color: var(--brand-muted);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Icons */
        .icon {
            width: 16px;
            height: 16px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- Skip to Content (B10 Accessibility) -->
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <!-- Distraction-Free Header (Slimmed to 52px on mobile) -->
    <header class="lp-header" role="banner">
        <div class="container lp-header-inner">
            <a href="{{ route('landing.custom-t-shirts') }}" class="lp-brand" aria-label="{{ $companyName }} Home">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" fill="#111111"/>
                    <path d="M12 6C12 6 15 9.5 15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 9.5 12 6 12 6Z" fill="#e83535"/>
                </svg>
                <span>{{ $companyName }}</span>
            </a>

            <div class="lp-header-actions">
                @if($supportPhone)
                    <span class="lp-header-phone">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        {{ $supportPhone }}
                    </span>
                @endif
                <a href="{{ $primaryWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="lp-wa-btn-sm" aria-label="Open WhatsApp Consultation">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
    </header>

    <main id="main-content">
        <!-- 1. Hero (Above the Fold with 2-Row Compact Trust Chips) -->
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="container lp-hero-grid">
                <div>
                    <span class="lp-eyebrow">B2B Custom Apparel &amp; Merchandise</span>
                    <h1 id="hero-title" class="lp-hero-title">Custom T-Shirts &amp; Branded Apparel for Businesses</h1>
                    <p class="lp-hero-lead">Company uniforms, event T-shirts, team apparel and custom merchandise with printing or embroidery. Bulk-order support with Pan-India delivery.</p>

                    <!-- Two Rows of Short Labels for Reduced Vertical Height -->
                    <div class="lp-hero-badges" aria-label="Key Service Highlights">
                        <div class="lp-badges-row">
                            <span class="lp-badge-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                                Pan-India Delivery
                            </span>
                            <span class="lp-badge-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                                Low MOQ Options
                            </span>
                        </div>
                        <div class="lp-badges-row">
                            <span class="lp-badge-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                                Multiple Techniques
                            </span>
                            <span class="lp-badge-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                                Artwork Confirmation
                            </span>
                        </div>
                    </div>

                    <div class="lp-hero-ctas">
                        <a href="{{ $primaryWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn-wa">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            Get Quote on WhatsApp
                        </a>
                        <a href="#quote-form" class="btn-secondary">
                            Request a Callback / Quote
                        </a>
                    </div>

                    <p class="lp-hero-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Direct requirement consultation with our apparel team. No online checkout required.
                    </p>
                </div>

                <div class="lp-hero-media-wrapper">
                    <div class="lp-hero-media">
                        <img src="/storefront/custom/hero-apparel.jpg" alt="Okina Craft custom branded apparel production workshop with embroidered polos and printed t-shirts" width="800" height="600" fetchpriority="high">
                    </div>
                    <div class="lp-hero-floating-tag">
                        <span>● Custom Production</span> T-Shirts &bull; Polos &bull; Uniforms
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Real Customer Work / Finished Apparel (First Visual Proof, Minimal Copy) -->
        <section class="lp-section" aria-labelledby="showcase-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="showcase-heading" class="lp-section-title">Actual Finished Apparel &amp; Printing Work</h2>
                    <p class="lp-section-subtitle">Real examples of printed and embroidered orders produced for teams, staff, and events.</p>
                </div>

                <div class="lp-showcase-grid">
                    <div class="lp-showcase-card">
                        <div class="lp-showcase-img-wrap">
                            <span class="lp-showcase-tag">Embroidery</span>
                            <img src="/storefront/custom/polo-embroidery.jpg" alt="Corporate polo with high-density embroidered chest logo" class="lp-showcase-img" loading="lazy">
                        </div>
                        <div class="lp-showcase-meta">
                            <strong>Corporate Polo Branding</strong>
                            <span>Embroidery &amp; logo placement</span>
                        </div>
                    </div>

                    <div class="lp-showcase-card">
                        <div class="lp-showcase-img-wrap">
                            <span class="lp-showcase-tag">Staff Uniforms</span>
                            <img src="/storefront/custom/staff-uniforms.jpg" alt="Coordinated staff uniforms for cafe & retail" class="lp-showcase-img" loading="lazy">
                        </div>
                        <div class="lp-showcase-meta">
                            <strong>Staff Uniform Sets</strong>
                            <span>Coordinated team wear</span>
                        </div>
                    </div>

                    <div class="lp-showcase-card">
                        <div class="lp-showcase-img-wrap">
                            <span class="lp-showcase-tag">Event Print</span>
                            <img src="/storefront/custom/event-bulk.jpg" alt="Neatly stacked custom event t-shirts ready for corporate dispatch" class="lp-showcase-img" loading="lazy">
                        </div>
                        <div class="lp-showcase-meta">
                            <strong>Event &amp; Campaign Tees</strong>
                            <span>Vibrant promotional prints</span>
                        </div>
                    </div>

                    <div class="lp-showcase-card">
                        <div class="lp-showcase-img-wrap">
                            <span class="lp-showcase-tag">Back Branding</span>
                            <img src="/storefront/custom/tee-backprint.jpg" alt="Black custom round-neck with sharp back-print branding" class="lp-showcase-img" loading="lazy">
                        </div>
                        <div class="lp-showcase-meta">
                            <strong>Custom Branded Tees</strong>
                            <span>Front &amp; back logo printing</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. B2B Focus Areas (Corporate Use Cases) -->
        <section class="lp-section" aria-labelledby="focus-areas-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="focus-areas-heading" class="lp-section-title">Built for B2B Apparel Requirements</h2>
                    <p class="lp-section-subtitle">Customization solutions tailored for corporate teams, businesses, and event organizers.</p>
                </div>

                <div class="lp-usecase-grid">
                    <div class="lp-usecase-card">
                        <img src="/storefront/business/uniform.png" alt="Company & Staff Uniforms" class="lp-usecase-img" loading="lazy">
                        <div class="lp-usecase-body">
                            <h3>Company &amp; Staff Uniforms</h3>
                            <p>Coordinated branded apparel for offices, retail staff, cafes, restaurants, and field teams.</p>
                        </div>
                    </div>

                    <div class="lp-usecase-card">
                        <img src="/storefront/business/event.png" alt="Corporate & Promotional Events" class="lp-usecase-img" loading="lazy">
                        <div class="lp-usecase-body">
                            <h3>Corporate &amp; Promotional Events</h3>
                            <p>Bulk event T-shirts for conferences, brand launches, trade fairs, and promotional campaigns.</p>
                        </div>
                    </div>

                    <div class="lp-usecase-card">
                        <img src="/storefront/business/team.png" alt="Team & College Apparel" class="lp-usecase-img" loading="lazy">
                        <div class="lp-usecase-body">
                            <h3>Team &amp; College Apparel</h3>
                            <p>Branded apparel for sports teams, college fests, coaching institutes, and clubs.</p>
                        </div>
                    </div>

                    <div class="lp-usecase-card">
                        <img src="/storefront/business/cafe.png" alt="Branded Merchandise" class="lp-usecase-img" loading="lazy">
                        <div class="lp-usecase-body">
                            <h3>Branded Merchandise</h3>
                            <p>Custom apparel and merchandise (tees, caps, diaries) for businesses and brand promotions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Printing & Customization Techniques (Full Width on Mobile to Avoid Text Squishing) -->
        <section class="lp-section" aria-labelledby="methods-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="methods-heading" class="lp-section-title">Printing &amp; Customization Techniques</h2>
                    <p class="lp-section-subtitle">Multiple printing and embroidery methods to match your artwork, fabric, and order requirements.</p>
                </div>

                <div class="lp-methods-grid">
                    <div class="lp-method-card">
                        <img src="/storefront/custom/tee-backprint.jpg" alt="DTF print sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>DTF Printing</strong>
                                <span class="lp-method-pill">Full Color</span>
                            </div>
                            <p>Custom printing option based on artwork and garment requirements.</p>
                        </div>
                    </div>

                    <div class="lp-method-card">
                        <img src="/storefront/business/tee.png" alt="DTG print sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>DTG Printing</strong>
                                <span class="lp-method-pill">Soft Hand</span>
                            </div>
                            <p>Available depending on product and artwork requirements.</p>
                        </div>
                    </div>

                    <div class="lp-method-card">
                        <img src="/storefront/custom/polo-embroidery.jpg" alt="Embroidery sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>Embroidery</strong>
                                <span class="lp-method-pill">Stitched</span>
                            </div>
                            <p>Stitched branding option for suitable apparel and merchandise.</p>
                        </div>
                    </div>

                    <div class="lp-method-card">
                        <img src="/storefront/custom/puff-detail.jpg" alt="Puff print sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>Puff Printing</strong>
                                <span class="lp-method-pill">3D Raised</span>
                            </div>
                            <p>Raised-print customization option for heavyweight cotton.</p>
                        </div>
                    </div>

                    <div class="lp-method-card">
                        <img src="/storefront/custom/event-bulk.jpg" alt="Sublimation print sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>Sublimation</strong>
                                <span class="lp-method-pill">All-Over</span>
                            </div>
                            <p>Available for suitable polyester fabrics and order requirements.</p>
                        </div>
                    </div>

                    <div class="lp-method-card">
                        <img src="/storefront/custom/hero-apparel.jpg" alt="Custom decoration sample" class="lp-method-thumb" loading="lazy">
                        <div class="lp-method-content">
                            <div class="lp-method-header">
                                <strong>Custom Decoration</strong>
                                <span class="lp-method-pill">Tailored</span>
                            </div>
                            <p>Evaluated based on your specific artwork, fabric, and placement needs.</p>
                        </div>
                    </div>
                </div>

                <p class="lp-methods-footnote">
                    * The appropriate method and MOQ depend on the customer's specific requirements.
                </p>
            </div>
        </section>

        <!-- Editorial Tagline Statement (Standard B11) -->
        <section class="lp-tagline-section" aria-label="Brand Quality Commitment">
            <div class="container">
                <blockquote class="lp-tagline-text">
                    Every stitch and print crafted to represent your team with uncompromising precision.
                </blockquote>
                <span class="lp-tagline-sub">From single team batches to nationwide enterprise rollouts.</span>
            </div>
        </section>

        <!-- 5. Compact Fallback Quote Form (Positioned After Visual Evidence & Techniques) -->
        <section id="quote-form" class="lp-form-section" aria-labelledby="form-heading">
            <div class="container">
                <div class="lp-form-card">
                    <div style="text-align: center; margin-bottom: 18px;">
                        <span class="lp-eyebrow">Direct Quotation Request</span>
                        <h2 id="form-heading" style="font-size: 22px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 5px;">Request a Callback or Quote</h2>
                        <p style="font-size: 13px; color: var(--brand-muted);">Prefer a callback or direct quote? Share your requirement below and our team will connect with options.</p>
                    </div>

                    @if(session('quote_success'))
                        <div class="lp-alert-success" role="status">
                            <strong>Thank you, {{ session('lead_name') }}! Your quote request has been received.</strong>
                            <p style="font-size: 13px; margin-bottom: 10px;">Our apparel specialist is reviewing your requirement. For an immediate response, you can open WhatsApp directly:</p>
                            @if(session('whatsapp_url'))
                                <a href="{{ session('whatsapp_url') }}" target="_blank" rel="noopener noreferrer" class="btn-wa" style="display: inline-flex; padding: 9px 16px; font-size: 13.5px;">
                                    Continue on WhatsApp
                                </a>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('landing.quote-request') }}" method="POST" enctype="multipart/form-data" id="leadForm">
                        @csrf
                        <input type="hidden" name="source" value="{{ $source }}">
                        <input type="hidden" name="utm_source" value="{{ $utm['utm_source'] ?? '' }}">
                        <input type="hidden" name="utm_medium" value="{{ $utm['utm_medium'] ?? '' }}">
                        <input type="hidden" name="utm_campaign" value="{{ $utm['utm_campaign'] ?? '' }}">
                        <input type="hidden" name="utm_content" value="{{ $utm['utm_content'] ?? '' }}">

                        <!-- Row 1: Name & WhatsApp -->
                        <div class="lp-form-row lp-form-row-2">
                            <div class="lp-field">
                                <label for="field-name" class="lp-label">Full Name <span style="color: var(--brand-red);">*</span></label>
                                <input type="text" id="field-name" name="name" class="lp-input" placeholder="e.g. Rahul Sharma" required autocomplete="name" value="{{ old('name') }}">
                                @error('name')<span style="color: var(--brand-red); font-size: 11px;">{{ $message }}</span>@enderror
                            </div>

                            <div class="lp-field">
                                <label for="field-phone" class="lp-label">WhatsApp Number <span style="color: var(--brand-red);">*</span></label>
                                <input type="tel" id="field-phone" name="phone" class="lp-input" placeholder="e.g. 9876543210 (10 digits)" required inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" title="Please enter a valid 10-digit mobile number" autocomplete="tel" value="{{ old('phone') }}">
                                @error('phone')<span style="color: var(--brand-red); font-size: 11px;">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <!-- Row 2: Requirement & Delivery Date -->
                        <div class="lp-form-row lp-form-row-2">
                            <div class="lp-field">
                                <label for="field-product" class="lp-label">Product Requirement</label>
                                <select id="field-product" name="product_type" class="lp-select">
                                    <option value="Custom T-Shirts">Custom T-Shirts (Round Neck / Collar)</option>
                                    <option value="Company Uniforms">Company &amp; Staff Uniforms</option>
                                    <option value="Event Apparel">Event / Campaign T-Shirts</option>
                                    <option value="Team Apparel">Team / Club Apparel</option>
                                    <option value="Caps & Merchandise">Caps / Diaries / Merchandise</option>
                                    <option value="Other">Other Custom Requirement</option>
                                </select>
                            </div>

                            <div class="lp-field">
                                <label for="field-date" class="lp-label">Target Delivery Date (Optional)</label>
                                <input type="text" id="field-date" name="delivery_date" class="lp-input" placeholder="e.g. Nov 15 or Flexible" value="{{ old('delivery_date') }}">
                            </div>
                        </div>

                        <!-- Row 3: Prominent & Tactile Quantity Chips -->
                        <div class="lp-field" style="margin-bottom: 12px;">
                            <label class="lp-label">Approximate Quantity</label>
                            <input type="hidden" name="quantity_range" id="quantityRangeInput" value="{{ old('quantity_range', '26–100') }}">
                            <div class="lp-qty-chips" role="radiogroup" aria-label="Approximate Quantity Selector">
                                <button type="button" class="lp-qty-chip" data-qty="5–25">5–25</button>
                                <button type="button" class="lp-qty-chip active" data-qty="26–100">26–100</button>
                                <button type="button" class="lp-qty-chip" data-qty="101–500">101–500</button>
                                <button type="button" class="lp-qty-chip" data-qty="500+">500+</button>
                                <button type="button" class="lp-qty-chip" data-qty="Not sure">Not sure</button>
                            </div>
                        </div>

                        <!-- Row 4: Artwork File Upload -->
                        <div class="lp-field" style="margin-bottom: 16px;">
                            <label for="field-artwork" class="lp-label">Logo or Artwork File (Optional)</label>
                            <input type="file" id="field-artwork" name="artwork" class="lp-file-input">
                            <span style="font-size: 11px; color: var(--brand-light-muted);">Share your available logo or artwork. We’ll review it and confirm suitability.</span>
                        </div>

                        <button type="submit" class="btn-primary">
                            Request Custom Quote
                        </button>

                        <p class="lp-form-privacy">
                            We review requirements and respond directly on WhatsApp with pricing &amp; options. No spam.
                        </p>
                    </form>
                </div>
            </div>
        </section>

        <!-- 6. Grounded Pricing Guidance Banner (Short Copy & Verified Action) -->
        <section class="lp-section" aria-labelledby="pricing-heading">
            <div class="container">
                <div class="lp-pricing-banner">
                    <h2 id="pricing-heading">Need Pricing? Every Order is Custom.</h2>
                    <p>Pricing depends on apparel, quantity, customization and requirements.</p>
                    <a href="{{ $primaryWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn-wa">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Get Custom Quote on WhatsApp
                    </a>
                </div>
            </div>
        </section>

        <!-- 7. How Ordering Works (5 Verified Steps, Large 01–05 Numbers, Punchy Hierarchy) -->
        <section class="lp-section" aria-labelledby="steps-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="steps-heading" class="lp-section-title">How Ordering Works</h2>
                    <p class="lp-section-subtitle">A straightforward 5-step process from requirement to final delivery.</p>
                </div>

                <div class="lp-steps-grid">
                    <div class="lp-step-card">
                        <span class="lp-step-num-lg">01</span>
                        <div class="lp-step-content">
                            <strong>Share Requirement</strong>
                            <p>Tell us your apparel, quantity and artwork.</p>
                        </div>
                    </div>

                    <div class="lp-step-card">
                        <span class="lp-step-num-lg">02</span>
                        <div class="lp-step-content">
                            <strong>Confirm Details</strong>
                            <p>Artwork, placement and printing method are confirmed.</p>
                        </div>
                    </div>

                    <div class="lp-step-card">
                        <span class="lp-step-num-lg">03</span>
                        <div class="lp-step-content">
                            <strong>Approve Quote</strong>
                            <p>Review pricing and confirm order terms.</p>
                        </div>
                    </div>

                    <div class="lp-step-card">
                        <span class="lp-step-num-lg">04</span>
                        <div class="lp-step-content">
                            <strong>Production &amp; QC</strong>
                            <p>Printing or embroidery with quality check.</p>
                        </div>
                    </div>

                    <div class="lp-step-card">
                        <span class="lp-step-num-lg">05</span>
                        <div class="lp-step-content">
                            <strong>Pan-India Delivery</strong>
                            <p>Packed securely and delivered to your doorstep.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Why Okina Craft (Concise Trust Badges) -->
        <section class="lp-section" aria-labelledby="why-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="why-heading" class="lp-section-title">Why Okina Craft</h2>
                    <p class="lp-section-subtitle">Dedicated B2B apparel customization built around business needs.</p>
                </div>

                <div class="lp-why-grid">
                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>B2B Customization</strong>
                            <p>Made for teams, organizations and events.</p>
                        </div>
                    </div>

                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>Low MOQ Options</strong>
                            <p>Where supported by product and method.</p>
                        </div>
                    </div>

                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>Multiple Techniques</strong>
                            <p>DTF, DTG, embroidery, puff and sublimation.</p>
                        </div>
                    </div>

                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>Pan-India Delivery</strong>
                            <p>Orders delivered safely across India.</p>
                        </div>
                    </div>

                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>Pre-production Confirmation</strong>
                            <p>Artwork and placement approved before printing.</p>
                        </div>
                    </div>

                    <div class="lp-why-badge">
                        <div class="lp-why-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="lp-why-text">
                            <strong>Direct WhatsApp Support</strong>
                            <p>Fast consultation for quotes and updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Recent Completed Orders (Top 4 by Default + View More Toggle to Prevent Image Fatigue) -->
        <section class="lp-section" aria-labelledby="recent-work-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="recent-work-heading" class="lp-section-title">Recent Custom Work</h2>
                    <p class="lp-section-subtitle">Real apparel orders customized and delivered across India.</p>
                </div>

                <div class="lp-work-grid">
                    <!-- Top 4 Visible by Default -->
                    <div class="lp-work-item">
                        <img src="/storefront/custom/polo-embroidery.jpg" alt="100 Embroidered Corporate Polos" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">Polos</span>
                                <span class="lp-work-pill">Embroidery</span>
                            </div>
                            <strong>100 Embroidered Corporate Polos</strong>
                            <p>Clean chest branding for business staff.</p>
                        </div>
                    </div>

                    <div class="lp-work-item">
                        <img src="/storefront/custom/event-bulk.jpg" alt="250 Event T-Shirts" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">Event</span>
                                <span class="lp-work-pill">DTF Print</span>
                            </div>
                            <strong>250 Event T-Shirts</strong>
                            <p>Color-rich promotional tees for annual conference.</p>
                        </div>
                    </div>

                    <div class="lp-work-item">
                        <img src="/storefront/custom/staff-uniforms.jpg" alt="Staff Uniform Batch" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">Uniforms</span>
                                <span class="lp-work-pill">Front &amp; Back</span>
                            </div>
                            <strong>Coordinated Staff Uniforms</strong>
                            <p>Durable daily wear for retail &amp; hospitality.</p>
                        </div>
                    </div>

                    <div class="lp-work-item">
                        <img src="/storefront/custom/puff-detail.jpg" alt="3D Puff Print Batch" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">Streetwear</span>
                                <span class="lp-work-pill">Puff Print</span>
                            </div>
                            <strong>3D Puff Print Batch</strong>
                            <p>Raised tactile graphics on heavyweight cotton tees.</p>
                        </div>
                    </div>

                    <!-- Items 5 & 6 (Hidden on Mobile Initially, Visible on Toggle & Desktop) -->
                    <div class="lp-work-item lp-work-extra" id="extraWork1">
                        <img src="/storefront/custom/tee-backprint.jpg" alt="Branded Black Tees" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">T-Shirts</span>
                                <span class="lp-work-pill">Screen / DTF</span>
                            </div>
                            <strong>Branded Merchandise Run</strong>
                            <p>Custom graphic tee run for growing brand.</p>
                        </div>
                    </div>

                    <div class="lp-work-item lp-work-extra" id="extraWork2">
                        <img src="/storefront/custom/hero-apparel.jpg" alt="Studio Production Batch" class="lp-work-img" loading="lazy">
                        <div class="lp-work-details">
                            <div class="lp-work-pill-row">
                                <span class="lp-work-pill">Production</span>
                                <span class="lp-work-pill">Apparel Run</span>
                            </div>
                            <strong>Corporate Apparel Batch</strong>
                            <p>Coordinated staff tees and custom merchandise.</p>
                        </div>
                    </div>
                </div>

                <!-- Mobile View More Toggle -->
                <div class="lp-work-toggle-wrap">
                    <button type="button" class="btn-view-more" id="toggleWorkBtn" aria-expanded="false">
                        <span>View More Completed Work</span>
                        <svg class="icon" viewBox="0 0 24 24" style="width: 14px; height: 14px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                </div>
            </div>
        </section>

        <!-- 10. Frequently Asked Questions (5 Core Verified Topics) -->
        <section class="lp-section" aria-labelledby="faq-heading">
            <div class="container">
                <div class="lp-section-header">
                    <h2 id="faq-heading" class="lp-section-title">Frequently Asked Questions</h2>
                    <p class="lp-section-subtitle">Common Questions Before You Place a Custom Order</p>
                </div>

                <div class="lp-faq-container">
                    <details class="lp-faq-item" open>
                        <summary class="lp-faq-summary">
                            <span>What is the minimum order quantity (MOQ)?</span>
                            <span class="lp-faq-plus">+</span>
                        </summary>
                        <div class="lp-faq-answer">
                            Smaller quantities are available where the product and printing method support them. Specialized methods may require higher MOQs. Exact MOQ depends on your requirement.
                        </div>
                    </details>

                    <details class="lp-faq-item">
                        <summary class="lp-faq-summary">
                            <span>Can we confirm artwork and placement before printing starts?</span>
                            <span class="lp-faq-plus">+</span>
                        </summary>
                        <div class="lp-faq-answer">
                            Yes. Apparel type, artwork, placement, and printing method are confirmed before production begins.
                        </div>
                    </details>

                    <details class="lp-faq-item">
                        <summary class="lp-faq-summary">
                            <span>What artwork should I send?</span>
                            <span class="lp-faq-plus">+</span>
                        </summary>
                        <div class="lp-faq-answer">
                            Share your available logo or artwork. We’ll review it and confirm whether it is suitable for the selected printing or embroidery method.
                        </div>
                    </details>

                    <details class="lp-faq-item">
                        <summary class="lp-faq-summary">
                            <span>Do you deliver across India?</span>
                            <span class="lp-faq-plus">+</span>
                        </summary>
                        <div class="lp-faq-answer">
                            Yes, orders can be delivered across India.
                        </div>
                    </details>

                    <details class="lp-faq-item">
                        <summary class="lp-faq-summary">
                            <span>What are the payment terms?</span>
                            <span class="lp-faq-plus">+</span>
                        </summary>
                        <div class="lp-faq-answer">
                            Payment terms are confirmed with the quotation based on the order requirement. Bulk orders may require advance payment, and COD is not available for bulk orders.
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- 11. Final CTA Banner -->
        <section class="lp-final-cta" aria-labelledby="final-heading">
            <div class="container">
                <h2 id="final-heading">Ready to Brand Your Team or Upcoming Event?</h2>
                <p>Share your quantity, apparel requirement and artwork. We'll review the details and help you with the next step.</p>

                <div class="lp-final-actions">
                    <a href="{{ $primaryWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn-wa">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Get Quote on WhatsApp
                    </a>
                    <a href="#quote-form" class="btn-secondary">
                        Request Callback
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="lp-footer" role="contentinfo">
        <div class="container lp-footer-inner">
            <div>
                <strong>{{ $companyName }}</strong> — Custom Printed Apparel &amp; Merchandise
            </div>
            <div class="lp-footer-links">
                <span>Pan-India Delivery &bull; B2B Custom Orders</span>
                <span class="lp-footer-divider">|</span>
                <a href="#privacy-notice" class="lp-legal-link" id="privacyLink">Privacy &amp; Data Policy</a>
            </div>
        </div>
    </footer>

    <!-- Accessible Privacy Notice Dialog (Meta Ads Policy Compliance) -->
    <dialog id="privacyModal" class="lp-privacy-dialog" aria-labelledby="privacyTitle">
        <div class="lp-privacy-content">
            <div class="lp-privacy-header">
                <h3 id="privacyTitle">Privacy &amp; Data Handling Policy</h3>
                <button type="button" class="lp-privacy-close" id="closePrivacyBtn" aria-label="Close Privacy Dialog">&times;</button>
            </div>
            <div class="lp-privacy-body">
                <p><strong>Your Privacy:</strong> Details submitted through this quotation form (Name, WhatsApp Phone Number, and Requirement notes) are used solely by the {{ $companyName }} apparel team to review your request, calculate custom bulk pricing, and confirm artwork specifications directly with you.</p>
                <p>We do not sell, rent, or distribute your contact details to third-party marketing brokers. For order inquiries or data removal requests, reach out directly via our WhatsApp consultation channel.</p>
            </div>
        </div>
    </dialog>

    <!-- Mobile Persistent Sticky Bar (70–75% WhatsApp / 25–30% Get Callback) -->
    <aside class="lp-mobile-sticky" aria-label="Quick Mobile Actions">
        <a href="{{ $primaryWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn-wa lp-sticky-wa">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Get Quote on WhatsApp
        </a>
        <a href="#quote-form" class="btn-secondary lp-sticky-callback">
            Get Callback
        </a>
    </aside>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Quantity chip selector interaction
            const qtyChips = document.querySelectorAll('.lp-qty-chip');
            const qtyInput = document.getElementById('quantityRangeInput');

            qtyChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    qtyChips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    if (qtyInput) {
                        qtyInput.value = chip.getAttribute('data-qty');
                    }
                });
            });

            // View More Work toggle on mobile
            const toggleWorkBtn = document.getElementById('toggleWorkBtn');
            const extraWorks = document.querySelectorAll('.lp-work-extra');
            if (toggleWorkBtn && extraWorks.length > 0) {
                toggleWorkBtn.addEventListener('click', function () {
                    const isExpanded = toggleWorkBtn.getAttribute('aria-expanded') === 'true';
                    extraWorks.forEach(el => el.classList.toggle('show', !isExpanded));
                    toggleWorkBtn.setAttribute('aria-expanded', !isExpanded);
                    if (!isExpanded) {
                        toggleWorkBtn.innerHTML = '<span>Show Less Work</span> <svg class="icon" viewBox="0 0 24 24" style="width: 14px; height: 14px;"><polyline points="18 15 12 9 6 15"></polyline></svg>';
                    } else {
                        toggleWorkBtn.innerHTML = '<span>View More Completed Work</span> <svg class="icon" viewBox="0 0 24 24" style="width: 14px; height: 14px;"><polyline points="6 9 12 15 18 9"></polyline></svg>';
                    }
                });
            }

            // Privacy Policy modal handling
            const privacyLink = document.getElementById('privacyLink');
            const privacyModal = document.getElementById('privacyModal');
            const closePrivacyBtn = document.getElementById('closePrivacyBtn');
            if (privacyLink && privacyModal && closePrivacyBtn) {
                privacyLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (typeof privacyModal.showModal === 'function') {
                        privacyModal.showModal();
                    }
                });
                closePrivacyBtn.addEventListener('click', function () {
                    privacyModal.close();
                });
                privacyModal.addEventListener('click', function (e) {
                    if (e.target === privacyModal) {
                        privacyModal.close();
                    }
                });
            }
        });
    </script>
</body>
</html>
