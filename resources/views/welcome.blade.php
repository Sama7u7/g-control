<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Varo | Control Financiero</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;1,9..40,300&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --ink: #0D0D0F;
            --muted: #6B6B78;
            --surface: #F7F6F3;
            --white: #ffffff;
            --accent: #4F3FF0;
            --accent-light: #EAE8FF;
            --green: #16A34A;
            --green-light: #DCFCE7;
            --amber: #D97706;
            --amber-light: #FEF3C7;
            --rose: #E11D48;
            --rose-light: #FFE4E6;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #FAFAF8;
            color: var(--ink);
            overflow-x: hidden;
        }

        /* NAV */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            background: rgba(250, 250, 248, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--ink);
            letter-spacing: -0.03em;
            text-decoration: none;
        }

        .logo span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--ink);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--ink);
            color: var(--white);
            padding: 0.6rem 1.25rem;
            border-radius: 100px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            letter-spacing: 0.01em;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-primary:hover {
            background: var(--accent);
            transform: translateY(-1px);
        }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 7rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 60% 40% at 20% 30%, rgba(79, 63, 240, 0.08) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 80% 60%, rgba(22, 163, 74, 0.07) 0%, transparent 70%);
        }

        .hero-noise {
            position: absolute;
            inset: 0;
            z-index: 0;
            opacity: 0.025;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            background-size: 200px 200px;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 780px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent-light);
            color: var(--accent);
            padding: 0.35rem 0.9rem;
            border-radius: 100px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 2rem;
            animation: fadeUp 0.6s ease both;
        }

        .hero-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(2.8rem, 6vw, 5.5rem);
            line-height: 1.0;
            letter-spacing: -0.04em;
            color: var(--ink);
            margin-bottom: 1.5rem;
            animation: fadeUp 0.7s 0.1s ease both;
        }

        h1 .accent {
            color: var(--accent);
        }

        h1 .underline-word {
            position: relative;
            display: inline-block;
        }

        h1 .underline-word::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 100%;
            height: 3px;
            background: var(--accent);
            border-radius: 2px;
        }

        .hero-sub {
            font-size: 1.1rem;
            font-weight: 300;
            color: var(--muted);
            line-height: 1.7;
            max-width: 520px;
            margin: 0 auto 2.5rem;
            animation: fadeUp 0.7s 0.2s ease both;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeUp 0.7s 0.3s ease both;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: var(--white);
            padding: 0.9rem 2rem;
            border-radius: 100px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            letter-spacing: -0.01em;
            box-shadow: 0 8px 32px rgba(79, 63, 240, 0.3);
            transition: all 0.2s;
        }

        .btn-hero:hover {
            background: #3B2FD9;
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(79, 63, 240, 0.4);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 400;
            text-decoration: none;
            transition: color 0.2s;
        }

        .btn-ghost:hover {
            color: var(--ink);
        }

        .btn-ghost svg {
            transition: transform 0.2s;
        }

        .btn-ghost:hover svg {
            transform: translateX(3px);
        }

        /* DASHBOARD PREVIEW */
        .dashboard-preview {
            position: relative;
            z-index: 1;
            margin: 3rem auto 0;
            max-width: 740px;
            width: 100%;
            animation: fadeUp 0.8s 0.4s ease both;
        }

        .preview-frame {
            background: var(--white);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1), 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .frame-bar {
            background: #F2F1EE;
            padding: 0.6rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot.r {
            background: #FF5F57;
        }

        .dot.y {
            background: #FEBC2E;
        }

        .dot.g {
            background: #28C840;
        }

        .frame-content {
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.75rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 0.9rem 1rem;
        }

        .stat-card.accent-card {
            background: var(--accent);
            color: white;
        }

        .stat-label {
            font-size: 0.68rem;
            color: var(--muted);
            font-weight: 400;
            margin-bottom: 0.3rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .accent-card .stat-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.03em;
            color: var(--ink);
        }

        .accent-card .stat-value {
            color: white;
        }

        .stat-delta {
            font-size: 0.7rem;
            color: var(--green);
            font-weight: 400;
            margin-top: 0.2rem;
        }

        .accent-card .stat-delta {
            color: rgba(255, 255, 255, 0.8);
        }

        .mini-bar {
            grid-column: 1 / -1;
            background: var(--surface);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bar-track {
            flex: 1;
            height: 6px;
            background: rgba(0, 0, 0, 0.08);
            border-radius: 3px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--accent), #8B7FFF);
        }

        .bar-label {
            font-size: 0.72rem;
            color: var(--muted);
            white-space: nowrap;
        }

        /* FEATURES */
        section.features {
            max-width: 1000px;
            margin: 0 auto;
            padding: 5rem 2rem;
        }

        .section-label {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(2rem, 4vw, 3rem);
            letter-spacing: -0.04em;
            color: var(--ink);
            margin-bottom: 3.5rem;
            line-height: 1.1;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        .feature-card {
            background: var(--white);
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 20px;
            padding: 2rem;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
        }

        .feature-card.c-accent:hover {
            border-color: rgba(79, 63, 240, 0.3);
        }

        .feature-card.c-green:hover {
            border-color: rgba(22, 163, 74, 0.3);
        }

        .feature-card.c-amber:hover {
            border-color: rgba(217, 119, 6, 0.3);
        }

        .feature-card.c-rose:hover {
            border-color: rgba(225, 29, 72, 0.3);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 20px;
        }

        .c-accent .feature-icon {
            background: var(--accent-light);
        }

        .c-green .feature-icon {
            background: var(--green-light);
        }

        .c-amber .feature-icon {
            background: var(--amber-light);
        }

        .c-rose .feature-icon {
            background: var(--rose-light);
        }

        .feature-card h3 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
            color: var(--ink);
            margin-bottom: 0.6rem;
        }

        .feature-card p {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.65;
            font-weight: 300;
        }

        .feature-corner {
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.07;
        }

        .c-accent .feature-corner {
            background: var(--accent);
        }

        .c-green .feature-corner {
            background: var(--green);
        }

        .c-amber .feature-corner {
            background: var(--amber);
        }

        .c-rose .feature-corner {
            background: var(--rose);
        }

        /* PLATFORMS */
        .platforms {
            background: var(--ink);
            color: var(--white);
            padding: 5rem 2rem;
            text-align: center;
        }

        .platforms-inner {
            max-width: 600px;
            margin: 0 auto;
        }

        .platforms .section-label {
            color: rgba(255, 255, 255, 0.4);
        }

        .platforms .section-title {
            color: var(--white);
            margin-bottom: 1.5rem;
        }

        .platforms p {
            color: rgba(255, 255, 255, 0.55);
            font-weight: 300;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .platform-pills {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 100px;
            padding: 0.55rem 1.1rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .pill.active {
            background: rgba(79, 63, 240, 0.4);
            border-color: rgba(79, 63, 240, 0.6);
            color: white;
        }

        .pill .badge {
            background: rgba(79, 63, 240, 0.6);
            color: white;
            font-size: 0.6rem;
            padding: 0.15rem 0.45rem;
            border-radius: 100px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .pill .badge.soon {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-hero-white {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--white);
            color: var(--ink);
            padding: 0.9rem 2rem;
            border-radius: 100px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-hero-white:hover {
            background: var(--accent-light);
            color: var(--accent);
            transform: translateY(-2px);
        }

        /* FOOTER */
        footer {
            border-top: 1px solid rgba(0, 0, 0, 0.07);
            padding: 2.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--ink);
            letter-spacing: -0.03em;
        }

        .footer-logo span {
            color: var(--accent);
        }

        footer p {
            font-size: 0.78rem;
            color: var(--muted);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    {{-- Navbar --}}
    <nav>
        <a href="{{ url('/') }}" class="logo">Mi <span>Varo.</span></a>
        <div class="nav-links">
            <a href="#funciones">Funciones</a>
            <a href="#seguridad">Seguridad</a>
            @auth
                <a class="btn-primary" href="{{ url('/dashboard') }}">Panel de Control →</a>
            @else
                <a class="btn-primary" href="{{ route('login') }}">Entrar →</a>
            @endauth
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-noise"></div>

        <div class="hero-inner">
            <div class="hero-badge">Finanzas personales inteligentes</div>
            <h1>Tu <span class="accent underline-word">varo</span>,<br>bajo control.</h1>
            <p class="hero-sub">
                Gestiona cuentas, tarjetas y ahorros en tiempo real — todo en un solo lugar, sin complicaciones.
            </p>
            <div class="hero-cta">
                @auth
                    <a class="btn-hero" href="{{ url('/dashboard') }}">Ir a mi panel</a>
                @else
                    <a class="btn-hero" href="{{ route('register') }}">Crear mi cuenta gratis</a>
                    <a class="btn-ghost" href="{{ route('login') }}">
                        Ya tengo cuenta
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Dashboard Preview --}}
        <div class="dashboard-preview">
            <div class="preview-frame">
                <div class="frame-bar">
                    <div class="dot r"></div>
                    <div class="dot y"></div>
                    <div class="dot g"></div>
                </div>
                <div class="frame-content">
                    <div class="stat-card accent-card">
                        <div class="stat-label">Balance total</div>
                        <div class="stat-value">$87,450</div>
                        <div class="stat-delta">↑ +$1,230 este mes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Efectivo</div>
                        <div class="stat-value">$24,200</div>
                        <div class="stat-delta" style="color: var(--muted)">3 cuentas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Rendimiento</div>
                        <div class="stat-value">$415</div>
                        <div class="stat-delta">↑ 5.8% anual</div>
                    </div>
                    <div class="mini-bar">
                        <div style="font-size: 0.75rem; color: var(--muted); min-width: 90px">Meta de ahorro</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 72%"></div>
                        </div>
                        <div class="bar-label">72% completado</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="features" id="funciones">
        <div class="section-label">¿Por qué Mi Varo?</div>
        <div class="section-title">Todo lo que necesitas,<br>nada que no necesitas.</div>
        <div class="features-grid">
            <div class="feature-card c-accent">
                <div class="feature-icon">💳</div>
                <h3>Gestión de cuentas</h3>
                <p>Controla efectivo y tarjetas de débito desde una sola interfaz clara. Sin hojas de cálculo, sin caos.
                </p>
                <div class="feature-corner"></div>
            </div>
            <div class="feature-card c-green">
                <div class="feature-icon">📈</div>
                <h3>Rendimientos automáticos</h3>
                <p>Calcula ganancias mensuales de tus cuentas con tasa de interés de forma automática. Ve tu dinero
                    crecer.</p>
                <div class="feature-corner"></div>
            </div>
            <div class="feature-card c-amber" id="seguridad">
                <div class="feature-icon">🌍</div>
                <h3>Multi-zona horaria</h3>
                <p>Tus registros siempre al día, estés en Chiapas, Ciudad de México o al otro lado del mundo.</p>
                <div class="feature-corner"></div>
            </div>
            <div class="feature-card c-rose">
                <div class="feature-icon">🛡️</div>
                <h3>Privacidad primero</h3>
                <p>Infraestructura robusta sobre MariaDB. Tu información financiera, solo tuya — nunca compartida.</p>
                <div class="feature-corner"></div>
            </div>
        </div>
    </section>

    {{-- Platforms --}}
    <section class="platforms">
        <div class="platforms-inner">
            <div class="section-label">Dónde encontrarnos</div>
            <h2 class="section-title">Disponible hoy.<br>En más plataformas pronto.</h2>
            <p>Empieza desde tu navegador ahora mismo. La app móvil está en camino para que lleves tu varo a todas
                partes.</p>
            <div class="platform-pills">
                <div class="pill active">
                    <span>🌐</span> Web App <span class="badge">Disponible</span>
                </div>
                <div class="pill">
                    <span>🍏</span> iOS <span class="badge soon">Próximamente</span>
                </div>
                <div class="pill">
                    <span>🤖</span> Android <span class="badge soon">Próximamente</span>
                </div>
            </div>
            @auth
                <a class="btn-hero-white" href="{{ url('/dashboard') }}">Ir a mi panel →</a>
            @else
                <a class="btn-hero-white" href="{{ route('register') }}">Empezar gratis ahora →</a>
            @endauth
        </div>
    </section>

    {{-- Footer --}}
    <footer>
        <div class="footer-logo">Mi <span>Varo.</span></div>
        <p>v1.0 — Un proyecto de Alberto Samayoa Ramos</p>
        <p style="font-size: 0.7rem">
            Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
        </p>
    </footer>

</body>

</html>
