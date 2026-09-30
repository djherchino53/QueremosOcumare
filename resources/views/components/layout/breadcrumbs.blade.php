@php
    $routeName = request()->route() ? request()->route()->getName() : '';
    $breadcrumbs = [
        ['label' => 'Inicio', 'url' => route('dashboard'), 'icon' => true],
    ];

    if ($routeName && $routeName !== 'dashboard') {
        $labels = [
            'patients' => 'Pacientes',
            'appointments' => 'Citas',
            'medical-histories' => 'Historias Médicas',
            'users' => 'Usuarios',
            'medical-specialties' => 'Especialidades',
            'medical-doctors' => 'Médicos',
            'cash' => 'Caja',
            'reports' => 'Reportes',
            'supplies' => 'Insumos',
            'profile' => 'Mi Cuenta',
        ];

        $segments = explode('.', $routeName);
        $module = $segments[0] ?? '';

        $label = $labels[$module] ?? ucfirst($module);

        if ($module === 'medical-doctors' && ($segments[1] ?? '') === 'profile') {
            $label = 'Perfil Médico';

            // Add "Médicos" as intermediate if admin
            if (in_array(auth()->user()->role, ['super_admin', 'admin'])) {
                $breadcrumbs[] = ['label' => 'Médicos', 'url' => route('medical-doctors.index')];
            }
        }

        if ($module === 'patients' && ($segments[1] ?? '') === 'show') {
            $label = 'Detalles del Paciente';
            $breadcrumbs[] = ['label' => 'Pacientes', 'url' => route('patients.index')];
        }

        $breadcrumbs[] = ['label' => $label, 'url' => '#'];
    }
@endphp

<nav class="flex mb-2" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2">
        @foreach($breadcrumbs as $breadcrumb)
            <li class="inline-flex items-center">
                @if(!$loop->first)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-300 mx-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                @endif

                @if($loop->last)
                    <span class="text-sm font-bold text-indigo-600 truncate">{{ $breadcrumb['label'] }}</span>
                @else
                    <a href="{{ $breadcrumb['url'] }}" wire:navigate
                        class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors">
                        @if(isset($breadcrumb['icon']) && $breadcrumb['icon'])
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        @endif
                        {{ $breadcrumb['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>