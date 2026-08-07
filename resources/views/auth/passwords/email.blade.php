<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password &middot; Learn To Drive</title>
    <style>
        :root {
            --charcoal: #3B3B3B;
            --charcoal-deep: #2a2a2a;
            --charcoal-field: #4a4a4a;
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
            left: -220px;
            width: 560px;
            height: 560px;
            border-radius: 50%;
            border: 1px solid rgba(255, 222, 23, 0.18);
            pointer-events: none;
        }
        .road-line {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 10%;
            border-top: 2px dashed rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }
        nav {
            display: flex;
            align-items: center;
            padding: 2rem 3rem;
            position: relative;
            z-index: 2;
        }
        nav .brand {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        main {
            position: relative;
            z-index: 2;
            max-width: 380px;
            margin: 0 auto;
            padding: 3rem 2rem 8rem;
        }
        .eyebrow {
            display: block;
            text-align: center;
            color: var(--yellow);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        h1 {
            text-align: center;
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0 0 1rem;
        }
        .subtext {
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0 0 2.5rem;
        }
        .status {
            background: rgba(255, 222, 23, 0.1);
            border: 1px solid rgba(255, 222, 23, 0.3);
            color: var(--yellow);
            font-size: 0.85rem;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 0.4rem;
        }
        .field {
            margin-bottom: 1.4rem;
        }
        input[type="email"] {
            width: 100%;
            background: var(--charcoal-field);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: #fff;
            font-family: inherit;
            transition: border-color 0.15s ease;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: var(--yellow);
        }
        input.is-invalid {
            border-color: #e85d5d;
        }
        .invalid-feedback {
            display: block;
            color: #ff8a8a;
            font-size: 0.8rem;
            margin-top: 0.4rem;
        }
        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }
        .cta {
            background: var(--yellow);
            color: var(--charcoal-deep);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.8rem 2.2rem;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            transition: transform 0.15s ease;
        }
        .cta:hover { transform: translateY(-2px); }
        .back-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.85rem;
            border-bottom: 1px solid transparent;
            transition: border-color 0.15s ease;
        }
        .back-link:hover { border-color: var(--muted); }
        @media (max-width: 460px) {
            nav { padding: 1.5rem; }
            main { padding: 2rem 1.5rem 6rem; }
        }
    </style>
  </head>
  <body>
    <div class="ring"></div>
    <div class="road-line"></div>

    <nav>
        <a href="{{ url('/') }}" class="brand">&larr; Learn To Drive</a>
    </nav>

    <main>
        <span class="eyebrow">Reset access</span>
        <h1>Forgot your password?</h1>
        <p class="subtext">{{ __('No worries — enter your email and we\'ll send you a link to reset it.') }}</p>

        @if (session('status'))
            <div class="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="field">
                <label for="email">{{ __('Email Address') }}</label>
                <input id="email" type="email" class="@error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="submit-row">
                <button type="submit" class="cta">{{ __('Send Reset Link') }}</button>
                <a class="back-link" href="{{ route('login') }}">{{ __('Back to login') }}</a>
            </div>
        </form>
    </main>
  </body>
</html>