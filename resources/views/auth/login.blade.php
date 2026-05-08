<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Login — MyProject') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; padding: 2.5rem; width: 360px; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
        h1 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
        p { color: #6b7280; font-size: 13px; margin-bottom: 1.75rem; }
        label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 0.3rem; }
        input { width: 100%; padding: 0.55rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px #bfdbfe; }
        .remember { display: flex; align-items: center; gap: 0.5rem; font-size: 13px; color: #374151; margin-bottom: 1.25rem; }
        .remember input { width: auto; margin: 0; }
        button[type=submit] { width: 100%; padding: 0.6rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button[type=submit]:hover { background: #1d4ed8; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 13px; margin-bottom: 1rem; }
        .lang-row { display: flex; justify-content: flex-end; gap: .35rem; margin-bottom: 1.25rem; }
        .lang-btn { background: none; border: 1px solid #d1d5db; color: #6b7280; padding: .15rem .45rem; border-radius: 3px; font-size: 11px; font-weight: 700; cursor: pointer; }
        .lang-btn.active { background: #1e293b; color: #fff; border-color: #1e293b; }
    </style>
</head>
<body>
<div class="login-box">
    <h1>MyProject</h1>
    <p>{{ __('Sign in to your account') }}</p>

    <div class="lang-row">
        @foreach(['en' => 'EN', 'cs' => 'CS'] as $code => $label)
            <form method="POST" action="{{ route('locale.switch', $code) }}" style="display:inline">
                @csrf
                <button type="submit" class="lang-btn {{ app()->getLocale() === $code ? 'active' : '' }}">{{ $label }}</button>
            </form>
        @endforeach
    </div>

    @error('username')
        <div class="error">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="username">{{ __('Username') }}</label>
        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus>

        <label for="password">{{ __('Password') }}</label>
        <input id="password" type="password" name="password" required>

        <div class="remember">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember" style="margin:0">{{ __('Remember me') }}</label>
        </div>

        <button type="submit">{{ __('Sign in') }}</button>
    </form>
</div>
</body>
</html>
