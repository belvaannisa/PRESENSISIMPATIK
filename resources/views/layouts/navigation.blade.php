<nav class="navbar navbar-expand-lg navbar-dark shadow" style="background-color: #FA713F;">
    <div class="container">

        {{-- Brand --}}
        <a class="navbar-brand fw-bold">
            PT. Simpatik Borneo Utama
        </a>

        {{-- Toggle Mobile --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menu --}}
        <div class="collapse navbar-collapse" id="navbarNav">

            {{-- Menu Kiri --}}
            <ul class="navbar-nav me-auto">
                 <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                    href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('karyawan.*') ? 'active' : '' }}" 
                       href="{{ route('karyawan.index') }}">
                        Karyawan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('presensi.*') ? 'active' : '' }}" 
                       href="{{ route('presensi.index') }}">
                        Presensi
                    </a>
                </li>
            </ul>

            {{-- Menu Kanan --}}
            <ul class="navbar-nav d-flex align-items-center">

                {{-- Logout (di kiri logo) --}}
                <li class="nav-item me-3">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link text-warning p-0">
                            Logout
                        </button>
                    </form>
                </li>

                {{-- Profile (paling kanan) --}}
                <li class="nav-item d-flex align-items-center">

                    <span class="nav-link text-white mb-0 me-2">
                        {{ auth()->user()->name ?? 'User' }}
                    </span>

                    <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name ?? 'User' }}&background=ffffff&color=FA713F" 
                         alt="profile" 
                         class="rounded-circle"
                         width="35" height="35">
                </li>

            </ul>

        </div>
    </div>
</nav>