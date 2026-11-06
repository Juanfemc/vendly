<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear tu cuenta | Vendly</title>
    @include('landing.partials.meta-pixel')
    <style>
        :root {
            --bg: #f7f8fa;
            --card: #ffffff;
            --ink: #111111;
            --muted: #667085;
            --line: #d9dee7;
            --soft: #f8fafc;
            --brand: #ff6b00;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .signup-shell {
            width: min(560px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px 0;
        }

        .signup-copy {
            display: grid;
            justify-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .signup-brand {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 9px;
            color: var(--ink);
            font-size: 25px;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .signup-brand img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
        }

        .signup-badge {
            width: fit-content;
            min-height: 0;
            padding: 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .signup-copy h1 {
            max-width: 520px;
            margin: 0;
            color: var(--ink);
            font-size: 34px;
            line-height: 1.08;
            text-align: center;
            letter-spacing: -0.04em;
        }

        .signup-copy h1 span {
            color: var(--ink);
        }

        .signup-copy p {
            display: none;
            margin: 0;
        }

        .signup-benefits {
            display: none;
        }

        .signup-benefits li {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 0 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.84);
            font-size: 13px;
            font-weight: 700;
        }

        .signup-benefits li::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 5px rgba(255, 107, 0, 0.14);
        }

        .signup-card {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--card);
            color: var(--ink);
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
            overflow: hidden;
        }

        .signup-form {
            display: grid;
            gap: 16px;
            padding: 18px 38px 32px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field label {
            color: #232323;
            font-size: 14px;
            font-weight: 700;
        }

        .field input {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--soft);
            color: #111111;
            padding: 0 14px;
            font: inherit;
            font-size: 16px;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field input:focus {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.12);
        }

        .field small {
            color: #777777;
            font-size: 12px;
            line-height: 1.35;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 92px;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 8px;
            height: 32px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--muted);
            padding: 0 8px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .consent-field {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .consent-field input {
            width: 17px;
            height: 17px;
            margin: 1px 0 0;
            accent-color: var(--brand);
            flex: 0 0 auto;
        }

        .signup-submit {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 9px;
            background: var(--brand);
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 18px 38px rgba(255, 107, 0, 0.28);
        }

        .signup-submit:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .signup-note {
            margin: -2px 0 0;
            color: #777777;
            font-size: 12px;
            text-align: center;
        }

        .signup-login {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        .signup-login a {
            color: var(--brand);
            font-weight: 900;
        }

        .error {
            color: #b42318;
            font-size: 12px;
            line-height: 1.4;
        }

        .turnstile-box {
            display: grid;
            gap: 8px;
        }

        .turnstile-box .cf-turnstile {
            max-width: 100%;
            overflow: hidden;
        }

        .security-warning {
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.45;
        }

        @media (max-width: 560px) {
            .signup-shell {
                width: min(100% - 20px, 1080px);
                padding: 16px 0;
            }

            .signup-brand {
                font-size: 22px;
            }

            .signup-brand img {
                width: 38px;
                height: 38px;
            }

            .signup-copy h1 {
                font-size: 30px;
            }

            .signup-card {
                border-radius: 14px;
            }

            .signup-form {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
    @if($turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>

<body>
    @include('landing.partials.meta-pixel-noscript')
    <main class="signup-shell">
        <section class="signup-copy" aria-label="Beneficios">
            <a href="{{ url('/') }}" class="signup-brand">
                <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="">
                <span>vendly</span>
            </a>

            <span class="signup-badge">Prueba de {{ $trialDays }} días · Sin tarjeta</span>
            <h1>Crea tu cuenta</h1>
        </section>

        <section class="signup-card" aria-label="Formulario de registro">
            <form class="signup-form" method="POST" action="{{ route('trial-signup.store') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $selectedPlan }}">
                <input type="hidden" name="billing_cycle" value="{{ $selectedCycle }}">

                <div class="field">
                    <label for="user_name">Tu nombre</label>
                    <input id="user_name" name="user_name" value="{{ old('user_name') }}" required autocomplete="name" placeholder="Ej. Juan">
                    @error('user_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="store_name">Nombre de tu tienda <span aria-hidden="true">(opcional)</span></label>
                    <input id="store_name" name="store_name" value="{{ old('store_name') }}" autocomplete="organization" placeholder="Ej. Mi tienda">
                    @error('store_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required inputmode="tel" autocomplete="tel" placeholder="Ej. 300 123 4567">
                    @error('whatsapp')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="user_email">Correo</label>
                    <input id="user_email" type="email" name="user_email" value="{{ old('user_email') }}" required autocomplete="email" placeholder="tu@email.com">
                    @error('user_email')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="password-field">
                        <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-pressed="false">Mostrar</button>
                    </div>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                @if($turnstileSiteKey)
                    <div class="turnstile-box">
                        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                        @error('turnstile_token')<div class="error">{{ $message }}</div>@enderror
                    </div>
                @elseif($requiresTurnstile && ! $turnstileReady)
                    <div class="security-warning" role="alert">
                        La protección anti abuso no está configurada. Intenta nuevamente más tarde.
                    </div>
                @endif

                <label class="consent-field" for="whatsapp_consent">
                    <input id="whatsapp_consent" name="whatsapp_consent" type="checkbox" value="1" @checked(old('whatsapp_consent')) required>
                    <span>{{ \App\Http\Requests\TrialSignupRequest::WHATSAPP_CONSENT_TEXT }}</span>
                </label>
                @error('whatsapp_consent')<div class="error">{{ $message }}</div>@enderror

                <button class="signup-submit" type="submit" @disabled($requiresTurnstile && ! $turnstileReady)>Crear mi tienda</button>
                <p class="signup-note">Gratis {{ $trialDays }} días. Sin tarjeta.</p>
                <p class="signup-login">¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
            </form>
        </section>
    </main>

    <script>
        document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const input = document.getElementById('password');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Ocultar' : 'Mostrar';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        });

        document.querySelector('.signup-form')?.addEventListener('submit', (event) => {
            const token = document.querySelector('[name="cf-turnstile-response"]')?.value;

            if (! token) {
                return;
            }

            let input = document.querySelector('[name="turnstile_token"]');

            if (! input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'turnstile_token';
                event.currentTarget.appendChild(input);
            }

            input.value = token;
        });
    </script>
</body>

</html>
