<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Queremos Ocumare' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }"
        @toggle-sidebar.window="sidebarOpen = !sidebarOpen" @keydown.escape.window="sidebarOpen = false">
        <!-- Fondo oscuro detrás del menú en móvil -->
        <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 md:hidden" aria-hidden="true"></div>

        <!-- Sidebar: panel deslizable en móvil, fijo desde md -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-200 ease-in-out md:static md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 flex items-center justify-between md:justify-center px-4 border-b border-gray-200">
                <span class="text-xl font-bold text-indigo-600">Queremos Ocumare</span>
                <button type="button" @click="sidebarOpen = false"
                    class="md:hidden p-2 text-gray-500 hover:text-gray-700" aria-label="Cerrar menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <x-layout.sidebar />
            </nav>
        </aside>

        <!-- Content -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 md:px-6">
                <x-layout.header />
            </header>

            <!-- Main -->
            <main class="w-full grow p-4 md:p-6">
                <x-layout.breadcrumbs />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>