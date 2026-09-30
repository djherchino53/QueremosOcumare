<?php
use Livewire\Volt\Component;
new class extends Component { };
?>

<div class="flex flex-col h-full bg-white text-gray-700">
    <div class="px-6 py-4">
        <nav class="space-y-1">
            <a href="{{ route('dashboard') }}" wire:navigate
                class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                <span class="ml-2">Inicio</span>
            </a>

            @if(auth()->user()->role === 'doctor')
                <a href="{{ route('medical-doctors.profile') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('medical-doctors.profile') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Mi Perfil</span>
                </a>
            @endif



            {{-- GESTION --}}
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestión</p>
            </div>

            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                <a href="{{ route('users.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('users.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Usuarios</span>
                </a>
                <a href="{{ route('medical-doctors.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('medical-doctors.index') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Médicos</span>
                </a>
            @endif

            <a href="{{ route('patients.index') }}" wire:navigate
                class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('patients.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                <span class="ml-2">Pacientes</span>
            </a>


            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                <a href="{{ route('medical-specialties.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('medical-specialties.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Especialidades Médicas</span>
                </a>
            @endif


            @if(auth()->user()->role !== 'pharmacist')
                <a href="{{ route('appointments.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('appointments.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Citas Médicas</span>
                </a>
            @endif

            @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'doctor']))
                <a href="{{ route('medical-histories.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('medical-histories.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Historias Médicas</span>
                </a>
            @endif

            {{-- FARMACIA --}}
            @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'pharmacist']))
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Farmacia</p>
            </div>
                <a href="{{ route('supplies.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('supplies.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Control de Insumos</span>
                </a>
            @endif

            {{-- CAJA --}}
            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Caja</p>
            </div>
                <a href="{{ route('cash.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('cash.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Caja y Finanzas</span>
                </a>
            @endif

            {{-- REPORTES --}}
            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Reportes</p>
            </div>            
                <a href="{{ route('reports.index') }}" wire:navigate
                    class="flex items-center px-4 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('reports.*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                    <span class="ml-2">Reportes y Estadísticas</span>
                </a>
            @endif
            
        </nav>
    </div>
</div>