<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk | Kedai Kopi Senja</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #34251d;
            --muted: #78685b;
            --line: #dfcdb9;
            --paper: #fffaf4;
            --coffee: #70452d;
            --coffee-dark: #4c2f20;
            --coffee-pale: #f3e8dc;
            --error: #a34a3d;
        }

        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            place-items: center;
            padding: 32px 18px;
            background: #f5eee6;
            color: var(--ink);
            font-family: "DM Sans", "Segoe UI", sans-serif;
        }
        button, input { font: inherit; }
        .login-card {
            width: min(610px, 100%);
            min-height: 608px;
            padding: 48px 47px 28px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: var(--paper);
        }
        .login-header { margin-bottom: 44px; text-align: center; }
        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -.6px;
        }
        .login-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 15px;
        }
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 500;
        }
        .field input {
            width: 100%;
            min-height: 47px;
            padding: 10px 13px;
            border: 1px solid #c6b6a6;
            border-radius: 4px;
            outline: none;
            background: var(--paper);
            color: var(--ink);
        }
        .field input:focus {
            border-color: #a9682f;
            box-shadow: 0 0 0 3px rgb(169 104 47 / 15%);
        }
        .field input[aria-invalid="true"] { border-color: var(--error); }
        .submit-button {
            width: 100%;
            min-height: 59px;
            margin-top: 9px;
            border: 0;
            border-radius: 4px;
            background: var(--coffee);
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }
        .submit-button:hover { background: var(--coffee-dark); }
        .error-slot {
            min-height: 77px;
            margin-top: 23px;
        }
        .error-message {
            margin: 0;
            padding: 17px 12px;
            border: 1px dashed var(--error);
            background: #fff2ec;
            color: var(--error);
            font-size: 14px;
            text-align: center;
        }
        .helper {
            margin: 70px 0 0;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }
        @media (max-width: 560px) {
            body { padding: 20px 14px; }
            .login-card {
                min-height: auto;
                padding: 38px 24px 26px;
            }
            .login-header { margin-bottom: 36px; }
            .helper { margin-top: 48px; }
        }
    </style>
</head>
<body>
    <main class="login-card">
        <header class="login-header">
            <h1>Kedai Kopi Senja</h1>
            <p>Masuk sebagai Kasir atau Manajer</p>
        </header>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    autocomplete="username"
                    maxlength="255"
                    required
                    autofocus
                    aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                >
            </div>

            <button class="submit-button" type="submit">Masuk</button>

            <div class="error-slot">
                @if ($errors->any())
                    <p class="error-message" role="alert">{{ $errors->first() }}</p>
                @endif
            </div>
        </form>

        <p class="helper">Pesan galat di atas tampil hanya bila kredensial salah</p>
    </main>
</body>
</html>
