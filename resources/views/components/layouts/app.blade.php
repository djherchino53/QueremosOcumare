<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Queremos Ocumare' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <span class="text-xl font-bold text-indigo-600">Queremos Ocumare</span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <x-layout.sidebar />
            </nav>
        </aside>

        <!-- Content -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <x-layout.header />
            </header>

            <!-- Main -->
            <main class="w-full grow p-6">
                <x-layout.breadcrumbs />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>