<nav class="navbar navbar-expand-lg navbar-dark shadow" style="background-color: #FA713F;">
    <div class="container d-flex align-items-center">

        {{-- Toggle Mobile (kiri) --}}
        <button class="navbar-toggler d-lg-none me-2" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Brand --}}
        <a class="navbar-brand fw-bold ms-auto ms-lg-0">
            PT. Simpatik Borneo Utama
        </a>

        {{-- Menu Desktop --}}
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

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->is('laporan/*') ? 'active' : '' }}" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        Laporan
                    </a>

                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('laporan.presensi') ? 'active' : '' }}"
                               href="{{ route('laporan.presensi') }}">
                                Presensi
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('laporan.keterlambatan') ? 'active' : '' }}"
                               href="{{ route('laporan.keterlambatan') }}">
                                Keterlambatan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('laporan.kedisiplinan') ? 'active' : '' }}"
                               href="{{ route('laporan.kedisiplinan') }}">
                                Kedisiplinan
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>

            {{-- Menu Kanan --}}
            <ul class="navbar-nav d-flex align-items-center">

                <li class="nav-item me-3">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link text-warning p-0">
                            Logout
                        </button>
                    </form>
                </li>

                <li class="nav-item d-flex align-items-center">
                    <span class="nav-link text-white mb-0 me-2">
                        {{ auth()->user()->name ?? 'User' }}
                    </span>

                    <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name ?? 'User' }}&background=ffffff&color=FA713F" 
                         class="rounded-circle"
                         width="35" height="35">
                </li>

            </ul>

        </div>
    </div>
</nav>

{{-- ================= MOBILE SIDEBAR ================= --}}
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMobile">

    <div class="offcanvas-header" style="background:#FA713F;">
        <h5 class="text-white mb-0">Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        <ul class="navbar-nav">

            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-bold' : '' }}" 
                   href="{{ route('dashboard') }}">
                    🏠 Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('karyawan.*') ? 'active fw-bold' : '' }}" 
                   href="{{ route('karyawan.index') }}">
                    👥 Karyawan
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('presensi.*') ? 'active fw-bold' : '' }}" 
                   href="{{ route('presensi.index') }}">
                    📅 Presensi
                </a>
            </li>

            <li class="nav-item mt-3">
                <strong>📊 Laporan</strong>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('laporan.presensi') ? 'active fw-bold' : '' }}"
                   href="{{ route('laporan.presensi') }}">
                    Presensi
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('laporan.keterlambatan') ? 'active fw-bold' : '' }}"
                   href="{{ route('laporan.keterlambatan') }}">
                    Keterlambatan
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('laporan.kedisiplinan') ? 'active fw-bold' : '' }}"
                   href="{{ route('laporan.kedisiplinan') }}">
                    Kedisiplinan
                </a>
            </li>

            <hr>

            <li class="nav-item">
                <span class="nav-link">
                    👤 {{ auth()->user()->name ?? 'User' }}
                </span>
            </li>

            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger w-100 mt-2">
                        Logout
                    </button>
                </form>
            </li>

        </ul>

    </div>
</div>