<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Custom Style --}}
    <style>
        .navbar-custom {
            background-color: #FA713F;
        }

        .table-orange {
            background-color: #FA713F;
            color: white;
        }

        .btn-custom {
            background-color: #FEECC8;
            color: #333;
            border: none;
        }

        .btn-custom:hover {
            background-color: #fbdca0;
        }
    </style>
</head>

<body class="bg-light">

    {{-- Navbar --}}
    @include('layouts.navigation')

    {{-- Header --}}
    @yield('header')

    {{-- Content --}}
    <main class="py-4">
        <div class="container">

            {{-- Notifikasi --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Isi Halaman --}}
            @yield('content')

        </div>
    </main>

    <!-- Bootstrap JS (HARUS di bawah) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>