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

        .register-card {
            max-width: 420px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }

        .register-header {
            background: #FA713F;
            padding: 25px;
            text-align: center;
        }

        .register-header img {
            width: 70px;
            margin-bottom: 10px;
            border: 1px solid rgba(0,0,0,0.3);
            border-radius: 10px;
        }

        .register-header h5 {
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
        }

        .form-control:focus {
            border-color: #FA713F;
            box-shadow: 0 0 0 0.2rem rgba(250, 113, 63, 0.25);
        }

        @media (max-width: 576px) {
            .register-card {
                margin: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow register-card">

        {{-- HEADER --}}
        <div class="register-header">
            <img src="{{ asset('images/logo.jpeg') }}" alt="logo">
            <h5>PT. Simpatik Borneo Utama</h5>
        </div>

        {{-- BODY --}}
        <div class="card-body p-4">

            {{-- ERROR --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    Data tidak valid, cek kembali input
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- NAMA --}}
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" 
                           name="name" 
                           class="form-control" 
                           value="{{ old('name') }}" 
                           required autofocus>
                </div>

                {{-- EMAIL --}}
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           value="{{ old('email') }}" 
                           required>
                </div>

                {{-- PASSWORD --}}
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>

                {{-- KONFIRMASI PASSWORD --}}
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" 
                           name="password_confirmation" 
                           class="form-control" 
                           required>
                </div>
<div class="mb-3">

    <label class="form-label">
        Role
    </label>

    <select name="role"
            class="form-control"
            required>

        <option value="">
            -- Pilih Role --
        </option>

        <option value="admin">
            ADMIN
        </option>

        <option value="kepala_personalia">
            KEPALA GUDANG & KEPALA PERSONALIA
        </option>

        <option value="pimpinan">
            PIMPINAN
        </option>

        <option value="haf">
            HEAD ACCOUNTING & FINANCE
        </option>
    </select>

</div>
                {{-- BUTTON --}}
                <button type="submit" class="btn btn-orange w-100">
                    Register
                </button>

                {{-- LINK LOGIN --}}
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-decoration-none text-muted">
                        Sudah punya akun? Login
                    </a>
                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>