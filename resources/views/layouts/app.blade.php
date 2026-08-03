<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Permissões do usuário atual para componentes Alpine.js (real-time) --}}
    <meta name="can-products-update" content="{{ auth()->user()?->can('products.update') ? 'true' : 'false' }}">
    <meta name="can-products-delete" content="{{ auth()->user()?->can('products.delete') ? 'true' : 'false' }}">
    <meta name="can-sectors-update" content="{{ auth()->user()?->can('sectors.update') ? 'true' : 'false' }}">
    <meta name="can-sectors-delete" content="{{ auth()->user()?->can('sectors.delete') ? 'true' : 'false' }}">
    <meta name="can-warehouses-update" content="{{ auth()->user()?->can('warehouses.update') ? 'true' : 'false' }}">
    <meta name="can-warehouses-delete" content="{{ auth()->user()?->can('warehouses.delete') ? 'true' : 'false' }}">
    <title>@yield('title', 'WMS — Gestão de Almoxarifado')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-dvh">
        {{-- Overlay (mobile) --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/40 lg:hidden"
             x-transition.opacity></div>

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-border bg-surface transition-transform duration-300 lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center gap-2 border-b border-border px-6">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4v10l-9 4-9-4V7z M3 7l9 4 9-4 M12 11v10" />
                    </svg>
                </span>
                <div class="leading-tight">
                    <span class="block text-base font-bold text-foreground">WMS</span>
                    <span class="block text-[10px] uppercase tracking-wide text-muted-foreground">Almoxarifado</span>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
                {{-- Painel (link direto) --}}
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors duration-200
                          {{ request()->routeIs('dashboard') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Painel
                </a>

                {{-- Cadastros --}}
                @canany(['products.view', 'warehouses.view', 'warehouse-types.view', 'sectors.view'])
                <div x-data="{ open: {{ request()->routeIs('products.*', 'warehouses.*', 'warehouse-types.*', 'sectors.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Cadastros
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        @can('products.view')
                        <a href="{{ route('products.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('products.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Produtos
                        </a>
                        @endcan
                        @can('warehouses.view')
                        <a href="{{ route('warehouses.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('warehouses.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V8l9-5 9 5v13M3 21h18M9 21v-6h6v6" />
                            </svg>
                            Almoxarifados
                        </a>
                        @endcan
                        @can('sectors.view')
                        <a href="{{ route('sectors.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('sectors.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M4 10h16 M9 6v12" />
                            </svg>
                            Setores
                        </a>
                        @endcan
                        @can('warehouse-types.view')
                        <a href="{{ route('warehouse-types.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('warehouse-types.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4v10l-9 4-9-4V7z M3 7l9 4 9-4 M12 11v10" />
                            </svg>
                            Tipos de Almoxarifado
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                {{-- Estoque --}}
                @canany(['stock.view', 'requests.view'])
                <div x-data="{ open: {{ request()->routeIs('stock.*', 'material-requests.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm4 0v12m6-12v12" />
                            </svg>
                            Estoque
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        @can('stock.view')
                        <a href="{{ route('stock.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('stock.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
                            </svg>
                            Posição de estoque
                        </a>
                        @endcan
                        @can('requests.view')
                        <a href="{{ route('material-requests.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('material-requests.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Solicitações
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                {{-- Movimentações --}}
                @can('movements.view')
                <div x-data="{ open: {{ request()->routeIs('movements.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                            Movimentações
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('movements.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('movements.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Histórico
                        </a>
                    </div>
                </div>
                @endcan

                {{-- Compras --}}
                @canany(['suppliers.view', 'purchase-orders.view', 'nfe.view'])
                <div x-data="{ open: {{ request()->routeIs('suppliers.*', 'purchase-orders.*', 'nfe.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V5a4 4 0 00-8 0v6m8 0l2.293 2.293c.63.63.184 1.707-.707 1.707H6.414c-.89 0-1.337-1.077-.707-1.707L16 11z M16 11V9a4 4 0 00-8 0v2m8 0v2a2 2 0 11-8 0v-2m8 0h2.586a1 1 0 01.707 1.707l-1.293 1.293a1 1 0 01-1.414 0L15 15.414V15a2 2 0 012-2z" />
                            </svg>
                            Compras
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        @can('suppliers.view')
                        <a href="{{ route('suppliers.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('suppliers.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Fornecedores
                        </a>
                        @endcan
                        @can('nfe.view')
                        <a href="{{ route('nfe.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('nfe.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3l1 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            NF-E
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                {{-- Catálogos --}}
                @canany(['categories.view', 'brands.view', 'manufacturers.view', 'units.view', 'subcategories.view', 'attributes.view'])
                <div x-data="{ open: {{ request()->routeIs('categories.*', 'brands.*', 'manufacturers.*', 'units.*', 'subcategories.*', 'attributes.*', 'catalog.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Catálogos
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        @can('categories.view')
                        <a href="{{ route('categories.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('categories.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Categorias
                        </a>
                        @endcan
                        @can('brands.view')
                        <a href="{{ route('brands.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('brands.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            Marcas
                        </a>
                        @endcan
                        @can('manufacturers.view')
                        <a href="{{ route('manufacturers.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('manufacturers.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z" />
                            </svg>
                            Fabricantes
                        </a>
                        @endcan
                        @can('units.view')
                        <a href="{{ route('units.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('units.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18M7 3v18M17 3v18" />
                            </svg>
                            Unidades
                        </a>
                        @endcan
                        @can('subcategories.view')
                        <a href="{{ route('subcategories.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('subcategories.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16M9 3v18" />
                            </svg>
                            Subcategorias
                        </a>
                        @endcan
                        @can('attributes.view')
                        <a href="{{ route('attributes.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('attributes.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-4-4-4 4m8 0l4-4 4 4" />
                            </svg>
                            Atributos
                        </a>
                        @endcan
                    </div>
                </div>
                @endcan

                {{-- Administração (somente quem tem acesso a algum item) --}}

                {{-- Administração (somente quem tem acesso a algum item) --}}
                @canany(['users.view', 'roles.view', 'roles.assign'])
                <div x-data="{ open: {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.permissions.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground">
                        <span class="flex items-center gap-3">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 7a2 2 0 100 4 2 2 0 000-4zm10 4a2 2 0 100 4 2 2 0 000-4z" />
                            </svg>
                            Administração
                        </span>
                        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1">
                        @can('users.view')
                        <a href="{{ route('admin.users.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('admin.users.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Usuários
                        </a>
                        @endcan
                        @can('roles.view')
                        <a href="{{ route('admin.roles.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('admin.roles.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Papéis
                        </a>
                        @endcan
                        @can('roles.assign')
                        <a href="{{ route('admin.permissions.index') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('admin.permissions.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-6 6h-1a2 2 0 01-2-2V9a2 2 0 012-2h1a6 6 0 016 6v1a6 6 0 01-6 6h-1a2 2 0 01-2-2m4 0a2 2 0 01-2-2" />
                            </svg>
                            Permissões
                        </a>
                        @endcan
                        @can('roles.assign')
                        <a href="{{ route('admin.entities.create') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 pl-9 font-medium transition-colors duration-200
                                  {{ request()->routeIs('admin.entities.*') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Criar Entidade
                        </a>
                        @endcan
                    </div>
                </div>
                @endcan
            </nav>

            <div class="border-t border-border p-4">
                <div class="mb-3 flex items-center justify-between text-sm">
                    <div>
                        <p class="font-medium text-foreground">{{ Auth::user()?->name ?? 'Usuário' }}</p>
                        <p class="text-xs text-muted-foreground">{{ Auth::user()?->roles?->first()?->name ?? '—' }}</p>
                    </div>
                    <button type="button" onclick="window.toggleTheme()"
                        class="rounded-lg border border-border p-2 text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground"
                        title="Alternar tema claro/escuro" aria-label="Alternar tema">
                        {{-- Sol (mostra no dark) --}}
                        <svg x-show="document.documentElement.classList.contains('dark')" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
                        </svg>
                        {{-- Lua (mostra no claro) --}}
                        <svg x-show="!document.documentElement.classList.contains('dark')" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="btn-secondary w-full">
                        Sair
                    </button>
                </form>
            </div>
        </aside>

        {{-- Conteúdo --}}
        <main class="flex-1 overflow-x-hidden">
            <header class="flex h-16 items-center justify-between gap-4 border-b border-border bg-surface px-4 lg:px-8">
                <div class="flex items-center gap-3">
                    {{-- Hamburguer (mobile) --}}
                    <button type="button" @click="sidebarOpen = true" x-cloak
                        class="rounded-lg border border-border p-2 text-muted-foreground transition-colors duration-200 hover:bg-muted hover:text-foreground lg:hidden"
                        aria-label="Abrir menu" title="Abrir menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-foreground">@yield('page_title', 'Painel')</h1>
                </div>
            </header>
            <div class="animate-fade-in p-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
