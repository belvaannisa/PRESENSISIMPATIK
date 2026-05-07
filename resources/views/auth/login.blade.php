<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
        <title>PT. Simpatik Borneo Utama</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">

    {{-- BOOTSTRAP --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f5f5;
        }

        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }

        .login-header {
            background: #FA713F;
            padding: 25px;
            text-align: center;
        }

            .login-header img {
                width: 70px;
                margin-bottom: 10px;
                border: 1px solid rgba(0,0,0,0.4); 
                border-radius: 10px;
            
        }

        .login-header h5 {
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .btn-orange {
            background: #FA713F;
            color: white;
            border: none;
        }

        .btn-orange:hover {
            background: #e65c2e;
            color: white;
        }

        .form-control:focus {
            border-color: #FA713F;
            box-shadow: 0 0 0 0.2rem rgba(250, 113, 63, 0.25);
        }

        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow login-card">

        {{-- HEADER --}}
        <div class="login-header">
            <img src="{{ asset('images/logo.jpeg') }}" alt="logo">
            <h5>PT. Simpatik Borneo Utama</h5>
        </div>

        {{-- BODY --}}
        <div class="card-body p-4">

            {{-- ERROR --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    Email atau password salah
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- EMAIL --}}
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           required autofocus>
                </div>

                {{-- PASSWORD --}}
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>

                {{-- REMEMBER --}}
                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>

                {{-- BUTTON --}}
                <button type="submit" class="btn btn-orange w-100">
                    Login
                </button>

                {{-- LUPA PASSWORD --}}
                @if (Route::has('password.request'))
                    <div class="text-center mt-3">
                        <a href="{{ route('password.request') }}" class="text-decoration-none text-muted">
                            Lupa Password?
                        </a>
                    </div>
                @endif

            </form>

        </div>

    </div>

</div>

</body>
</html>