<?php
use Livewire\Volt\Component;
new class extends Component { };
?>

<div class="flex items-center justify-between w-full">
    <div class="flex items-center min-w-0">
        <button type="button" @click="$dispatch('toggle-sidebar')" aria-controls="sidebar" aria-label="Abrir menú"
            class="md:hidden -ml-2 p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="ml-2 text-base font-semibold text-gray-800 md:hidden truncate">Queremos Ocumare</span>
    </div>

    <div class="flex items-center space-x-4 shrink-0">
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-sm font-medium text-gray-700">{{ auth()->user()->name ?? 'Usuario' }}</span>
            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="ml-2 pl-4 border-l border-gray-200">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition"
                title="Cerrar Sesión">
                <span class="hidden sm:inline">Salir</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
            </button>
        </form>
    </div>
</div>