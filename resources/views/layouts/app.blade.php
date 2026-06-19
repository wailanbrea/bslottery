<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BSLottery') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,500,600,700" rel="stylesheet">
    <link href="{{ asset('css/argon.css') }}" rel="stylesheet">
    <link href="{{ asset('css/icon-fallback.css') }}" rel="stylesheet">
    <style>[x-cloak]{display:none !important}</style>
    <script>
        (function () {
            function enableFallbackIfNeeded() {
                var probe = document.createElement('i');
                probe.className = 'bi bi-receipt';
                probe.style.position = 'absolute';
                probe.style.visibility = 'hidden';
                document.body.appendChild(probe);

                var fontFamily = window.getComputedStyle(probe, '::before').getPropertyValue('font-family') || '';
                document.body.removeChild(probe);

                if (fontFamily.toLowerCase().indexOf('bootstrap-icons') === -1) {
                    document.documentElement.classList.add('icons-fallback');
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', enableFallbackIfNeeded);
            } else {
                enableFallbackIfNeeded();
            }
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    @auth
        @php
            $user = auth()->user();
            $showLotteryMenu = $user->hasPermission('lotteries.view')
                || $user->hasPermission('draws.view')
                || $user->hasPermission('payout_rules.view')
                || $user->hasPermission('limit_rules.view')
                || $user->hasPermission('reports.view');
            $showOperationsMenu = $user->hasPermission('sales.create')
                || $user->hasPermission('tickets.view')
                || $user->hasPermission('results.view')
                || $user->hasPermission('prizes.pay')
                || $user->hasPermission('monitoring.view')
                || $user->hasPermission('cash.view')
                || $user->hasPermission('accounting.view')
                || $user->hasPermission('payroll.view');
            $showSystemMenu = $user->hasPermission('devices.view')
                || $user->hasPermission('audit.view')
                || $user->hasPermission('settings.view')
                || $user->hasPermission('printers.view');
        @endphp
        <nav class="argon-sidebar" id="argonSidebar">
            <div class="argon-sidebar-brand">
                <div class="argon-sidebar-brand-icon">BS</div>
                <div class="argon-sidebar-brand-text">BSLottery</div>
            </div>

            <div class="argon-sidebar-divider"></div>

            <div class="argon-sidebar-heading">Navegación</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @if (auth()->user()->hasPermission('companies.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                            <i class="bi bi-building"></i>
                            <span>Empresas</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('branches.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">
                            <i class="bi bi-shop"></i>
                            <span>Sucursales</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('users.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('roles.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                            <i class="bi bi-shield-check"></i>
                            <span>Roles</span>
                        </a>
                    </li>
                @endif
            </ul>

            @if ($showLotteryMenu)
                <div class="argon-sidebar-divider"></div>
                <div class="argon-sidebar-heading">Lotería</div>
                <ul class="nav flex-column">
                @if (auth()->user()->hasPermission('lotteries.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.lotteries.*') ? 'active' : '' }}" href="{{ route('admin.lotteries.index') }}">
                            <i class="bi bi-ticket-perforated"></i>
                            <span>Loterías</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('draws.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.draws.*') ? 'active' : '' }}" href="{{ route('admin.draws.index') }}">
                            <i class="bi bi-calendar-event"></i>
                            <span>Sorteos</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('lotteries.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.bet-types.*') ? 'active' : '' }}" href="{{ route('admin.bet-types.index') }}">
                            <i class="bi bi-dice-3"></i>
                            <span>Tipos de jugada</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('payout_rules.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.payout-rules.*') ? 'active' : '' }}" href="{{ route('admin.payout-rules.index') }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>Reglas de pago</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('limit_rules.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.limit-rules.*') ? 'active' : '' }}" href="{{ route('admin.limit-rules.index') }}">
                            <i class="bi bi-shield-exclamation"></i>
                            <span>Límites</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('reports.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-graph-up"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                @endif
                </ul>
            @endif

            @if ($showOperationsMenu)
                <div class="argon-sidebar-divider"></div>
                <div class="argon-sidebar-heading">Operaciones</div>
            <ul class="nav flex-column">
                @if (auth()->user()->hasPermission('sales.create'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.tickets.create') ? 'active' : '' }}" href="{{ route('admin.tickets.create') }}">
                            <i class="bi bi-cart-plus"></i>
                            <span>Vender</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('tickets.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.tickets.*') && !request()->routeIs('admin.tickets.create') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">
                            <i class="bi bi-receipt"></i>
                            <span>Tickets</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('results.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.results.*') ? 'active' : '' }}" href="{{ route('admin.results.index') }}">
                            <i class="bi bi-trophy"></i>
                            <span>Resultados</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('prizes.pay'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.prizes.*') ? 'active' : '' }}" href="{{ route('admin.prizes.pending') }}">
                            <i class="bi bi-cash-coin"></i>
                            <span>Premios</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('monitoring.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}" href="{{ route('admin.monitoring.index') }}">
                            <i class="bi bi-activity"></i>
                            <span>Monitoreo</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('notifications.view'))
                    @php
                        $unreadNotifications = \App\Models\SystemNotification::where('company_id', session('active_company_id'))
                            ->where('status', 'UNREAD')
                            ->count();
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}">
                            <i class="bi bi-bell"></i>
                            <span>Notificaciones</span>
                            @if ($unreadNotifications > 0)
                                <span class="badge bg-danger ms-auto">{{ $unreadNotifications }}</span>
                            @endif
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('cash.view'))
                    <li class="nav-item">
                        <div class="argon-sidebar-parent {{ request()->routeIs('admin.cash.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-register"></i>
                            <span>Caja</span>
                        </div>
                        <ul class="nav flex-column argon-sidebar-subnav">
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.cash.current') ? 'active' : '' }}" href="{{ route('admin.cash.current') }}">
                                    <i class="bi bi-cash-register"></i>
                                    <span>Caja actual</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.cash.index') ? 'active' : '' }}" href="{{ route('admin.cash.index') }}">
                                    <i class="bi bi-clock-history"></i>
                                    <span>Historial</span>
                                </a>
                            </li>
                            @if (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('branches.view'))
                                <li class="nav-item">
                                    <a class="nav-link py-1 {{ request()->routeIs('admin.cash.by-branch') ? 'active' : '' }}" href="{{ route('admin.cash.by-branch') }}">
                                        <i class="bi bi-shop"></i>
                                        <span>Cajas por Sucursal</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasPermission('cash.transfers.view'))
                                <li class="nav-item">
                                    <a class="nav-link py-1 {{ request()->routeIs('admin.cash.transfers.*') ? 'active' : '' }}" href="{{ route('admin.cash.transfers.index') }}">
                                        <i class="bi bi-bank"></i>
                                        <span>Transferencias</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasPermission('cash.incidents.view'))
                                <li class="nav-item">
                                    <a class="nav-link py-1 {{ request()->routeIs('admin.cash.incidents.*') ? 'active' : '' }}" href="{{ route('admin.cash.incidents.index') }}">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Incidencias</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('accounting.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}" href="{{ route('admin.accounting.journal') }}">
                            <i class="bi bi-journal-bookmark"></i>
                            <span>Contabilidad</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('payroll.view'))
                    <li class="nav-item">
                        <div class="argon-sidebar-parent {{ request()->routeIs('admin.payroll.*') || request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Nómina</span>
                        </div>
                        <ul class="nav flex-column argon-sidebar-subnav">
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.employees.index') || request()->routeIs('admin.employees.create') || request()->routeIs('admin.employees.edit') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Empleados</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.employees.advances') ? 'active' : '' }}" href="{{ route('admin.employees.advances') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Avances</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.employees.loans') ? 'active' : '' }}" href="{{ route('admin.employees.loans') }}">
                                    <i class="bi bi-bank"></i>
                                    <span>Préstamos</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}" href="{{ route('admin.payroll.index') }}">
                                    <i class="bi bi-calendar2-check"></i>
                                    <span>Períodos</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                </ul>
            @endif

            @if ($showSystemMenu)
                <div class="argon-sidebar-divider"></div>
                <div class="argon-sidebar-heading">Sistema</div>
            <ul class="nav flex-column">
                @if (auth()->user()->hasPermission('devices.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.devices.*') ? 'active' : '' }}" href="{{ route('admin.devices.index') }}">
                            <i class="bi bi-phone"></i>
                            <span>Dispositivos</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('audit.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}">
                            <i class="bi bi-journal-text"></i>
                            <span>Auditoría</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('settings.view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.results') }}">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>Configuración</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('printers.view'))
                    <li class="nav-item">
                        <div class="argon-sidebar-parent {{ request()->routeIs('admin.printers.*') ? 'active' : '' }}">
                            <i class="bi bi-printer"></i>
                            <span>Impresora</span>
                        </div>
                        <ul class="nav flex-column argon-sidebar-subnav">
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.printers.index') ? 'active' : '' }}" href="{{ route('admin.printers.index') }}">
                                    <i class="bi bi-gear"></i>
                                    <span>Configuración</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ request()->routeIs('admin.printers.queue') ? 'active' : '' }}" href="{{ route('admin.printers.queue') }}">
                                    <i class="bi bi-list-task"></i>
                                    <span>Cola de impresión</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                </ul>
            @endif
        </nav>

        <div class="argon-main" id="argonMain">
            <header class="argon-navbar d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="argon-navbar-toggler" id="argonSidebarToggle" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="argon-navbar-brand">
                        <strong>{{ auth()->user()->company?->name ?? 'Plataforma' }}</strong>
                        <span class="mx-2 text-muted">/</span>
                        {{ auth()->user()->branch?->name ?? 'Todas las sucursales' }}
                    </div>
                </div>
                <div class="argon-navbar-user">
                    <div class="argon-navbar-user-name d-none d-md-block">{{ auth()->user()->name }}</div>
                    <div class="argon-navbar-avatar" title="{{ auth()->user()->name }}">
                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <a class="btn btn-sm btn-outline-secondary ms-2" href="{{ route('password.edit') }}" title="Cambiar contrase&ntilde;a">
                        <i class="bi bi-key"></i>
                        <span class="d-none d-lg-inline ms-1">Clave</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="ms-2">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit" title="Cerrar sesión">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </header>

            <div class="argon-page-content">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>

        <script>
            document.getElementById('argonSidebarToggle').addEventListener('click', function () {
                document.getElementById('argonSidebar').classList.toggle('show');
            });
        </script>
        @stack('scripts')
    @else
        <main class="min-vh-100">
            @include('partials.flash')
            @yield('content')
        </main>
    @endauth
</body>
</html>
