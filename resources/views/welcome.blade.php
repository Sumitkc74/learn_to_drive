<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learn To Drive</title>
    <style>
        :root {
            --charcoal: #3B3B3B;
            --charcoal-deep: #2a2a2a;
            --yellow: #FFDE17;
            --muted: #b3b3b3;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--charcoal);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        .ring {
            position: absolute;
            top: -220px;
            right: -220px;
            width: 560px;
            height: 560px;
            border-radius: 50%;
            border: 1px solid rgba(255, 222, 23, 0.18);
            pointer-events: none;
        }
        .road {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 14%;
            height: 60px;
            pointer-events: none;
            overflow: hidden;
        }
        .road-line {
            position: absolute;
            left: 0;
            right: 0;
            top: 30px;
            border-top: 2px dashed rgba(255, 255, 255, 0.08);
        }
        .car {
            position: absolute;
            top: 0;
            left: -120px;
            width: 110px;
            animation: drive 9s linear infinite;
        }
        .wheel {
            animation: spin 0.45s linear infinite;
            transform-origin: center;
            transform-box: fill-box;
        }
        @keyframes drive {
            0%   { left: -120px; }
            100% { left: 100%; }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .car { animation: drive 9s linear infinite; }
            .wheel { animation: none; }
        }
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem 3rem;
            position: relative;
            z-index: 2;
        }
        nav .brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        nav .brand svg, nav .brand img {
            width: 28px;
            height: 28px;
        }
        .nav-link {
            color: #fff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            padding-bottom: 2px;
            border-bottom: 2px solid var(--yellow);
            transition: opacity 0.15s ease;
        }
        .nav-link:hover { opacity: 0.8; }
        main {
            position: relative;
            z-index: 2;
            max-width: 640px;
            margin: 0 auto;
            padding: 2.5rem 2rem 10rem;
            text-align: center;
        }
        .eyebrow {
            display: inline-block;
            color: var(--yellow);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 3rem;
            line-height: 1.1;
            font-weight: 800;
            margin: 0 0 1.5rem;
        }
        h1 .accent { color: var(--yellow); }
        p.lede {
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 480px;
            margin: 0 auto 2.5rem;
        }
        .cta {
            display: inline-block;
            background: var(--yellow);
            color: var(--charcoal-deep);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.85rem 2rem;
            border-radius: 999px;
            text-decoration: none;
            transition: transform 0.15s ease;
        }
        .cta:hover { transform: translateY(-2px); }
        @media (max-width: 640px) {
            nav { padding: 1.5rem; }
            main { padding: 1.5rem 1.5rem 8rem; }
            h1 { font-size: 2.2rem; }
        }
    </style>
  </head>
  <body>
    <div class="ring"></div>

    <nav>
        <div class="brand">
            <x-application-logo />
            Learn To Drive
        </div>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/home') }}" class="nav-link">Home</a>
            @else
                <a href="{{ route('login') }}" class="nav-link">Log in</a>
            @endauth
        @endif
    </nav>

    <main>
        <span class="eyebrow">Driving License Prep &middot; Nepal</span>
        <h1>Learn to <span class="accent">Drive</span>, the right way.</h1>
        <p class="lede">
            A comprehensive companion for preparing for the driving license test in Nepal &mdash;
            multiple-choice questions, driving tips, step-by-step instructions, and mock tests
            to help you pass with confidence.
        </p>
        @if (Route::has('login'))
            @guest
                <a href="{{ route('login') }}" class="cta">Get Started</a>
            @endguest
        @endif
    </main>

    <div class="road">
        <div class="road-line"></div>
        <svg class="car" viewBox="0 0 110 50" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 36 L14 20 Q18 14 26 14 L70 14 Q78 14 82 20 L88 36 Z" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/>
            <path d="M26 14 L32 22 L62 22 L68 14" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/>
            <line x1="46" y1="14" x2="46" y2="22" stroke="#fff" stroke-width="2"/>
            <rect x="80" y="24" width="6" height="5" rx="1.5" fill="#FFDE17"/>
            <circle class="wheel" cx="26" cy="38" r="8" fill="none" stroke="#fff" stroke-width="2.5"/>
            <line class="wheel" x1="26" y1="32" x2="26" y2="44" stroke="#fff" stroke-width="1.5"/>
            <circle class="wheel" cx="72" cy="38" r="8" fill="none" stroke="#fff" stroke-width="2.5"/>
            <line class="wheel" x1="72" y1="32" x2="72" y2="44" stroke="#fff" stroke-width="1.5"/>
        </svg>
    </div>
  </body>
</html>
