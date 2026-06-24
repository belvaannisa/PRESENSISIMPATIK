<nav class="navbar navbar-expand-lg navbar-dark shadow" style="background-color: #FA713F;">
    <div class="container"> 

      <a class="navbar-brand fw-bold d-flex align-items-center flex-grow-1"
   href="{{ route('dashboard') }}">

        <img src="{{ asset('images/logo.jpeg') }}"
            alt="Logo"
            width="35"
            height="35"
            class="rounded-circle me-2">

        <span class="small">
            PT. Simpatik Borneo Utama
        </span>

    </a>

    <button class="navbar-toggler d-lg-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMobile">
        <span class="navbar-toggler-icon"></span>
    </button>

        {{-- MENU DESKTOP --}}
        <div class="collapse navbar-collapse" id="navbarNav">

            {{-- MENU KIRI --}}
            <ul class="navbar-nav me-auto">

                {{-- DASHBOARD --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>

                {{-- KARYAWAN --}}
                <li class="nav-item dropdown">

                    <a class="nav-link {{ request()->routeIs('karyawan.*') ? 'active' : '' }}"
                        href="{{ route('karyawan.index') }}">
                        Karyawan
                    </a>
                </li>

                {{-- PRESENSI --}}
                @if(in_array(auth()->user()->role, ['admin','kepala_personalia']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('presensi.*') ? 'active' : '' }}"
                       href="{{ route('presensi.index') }}">
                        Presensi
                    </a>
                </li>
                @endif

                {{-- LOG PRESENSI}}
                <li class="nav-item">
                    <a href="{{ route('presensi-log.index') }}"
                    class="nav-link">
                        Presensi Log
                    </a>
                </li>

                {{-- LAPORAN --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle {{ request()->is('laporan/*') ? 'active' : '' }}"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown">

                        @if(request()->routeIs('laporan.presensi'))
                            Laporan Presensi
                        @elseif(request()->routeIs('laporan.keterlambatan'))
                            Laporan Keterlambatan
                        @elseif(request()->routeIs('laporan.kedisiplinan'))
                            Laporan Kedisiplinan
                        @else
                            Laporan
                        @endif

                    </a>

                    <ul class="dropdown-menu">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.presensi') }}">
                                Presensi
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.keterlambatan') }}">
                                Keterlambatan
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.kedisiplinan') }}">
                                Kedisiplinan
                            </a>
                        </li>

                    </ul>

                </li>

            </ul>

            {{-- MENU KANAN --}}
            <ul class="navbar-nav align-items-center">
               


                {{-- LOGOUT --}}
                <li class="nav-item me-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="btn btn-link nav-link text-warning p-0">
                            Logout
                        </button>
                    </form>
                </li>

                {{-- USER --}}
                <li class="nav-item d-flex align-items-center">

                    <span class="nav-link text-white me-2">
                        {{ auth()->user()->name }}
                    </span>

                    <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}&background=ffffff&color=FA713F"
                         class="rounded-circle"
                         width="35"
                         height="35">

                </li>

            </ul>

        </div>
    </div>
</nav>

{{-- ================= MOBILE MENU ================= --}}


<div class="offcanvas offcanvas-start d-lg-none"
     tabindex="-1"
     id="sidebarMobile">

     
    <div class="offcanvas-header"
         style="background:#FA713F;">

        <h5 class="text-white mb-0">
            Menu
        </h5>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas">
        </button>

    </div>

    <div class="offcanvas-body">

        <ul class="navbar-nav">

            <li class="nav-item mb-2">
                <a class="nav-link"
                   href="{{ route('dashboard') }}">
                    🏠 Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link"
                   href="{{ route('karyawan.index') }}">
                    👥 Data Karyawan
                </a>
            </li>

            @if(in_array(auth()->user()->role, ['admin','kepala_personalia']))
            <li class="nav-item mb-2">
                <a class="nav-link"
                   href="{{ route('presensi.index') }}">
                    📅 Presensi
                </a>
            </li>
            @endif

            <li class="nav-item">
                <a href=""
                class="nav-link">
                    📅 Presensi Log
                </a>
            </li>
            
            <li class="nav-item mt-3">
                <strong>📊 Laporan</strong>
            </li>

            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('laporan.presensi') }}">
                    Presensi
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('laporan.keterlambatan') }}">
                    Keterlambatan
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('laporan.kedisiplinan') }}">
                    Kedisiplinan
                </a>
            </li>


            <li class="nav-item mt-2">
                <span class="nav-link">
                    👤 {{ auth()->user()->name }}
                </span>
            </li>

            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger w-100">
                        Logout
                    </button>
                </form>
            </li>

        </ul>

    </div>
</div>