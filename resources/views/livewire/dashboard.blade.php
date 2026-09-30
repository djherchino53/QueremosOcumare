<?php
use Livewire\Volt\Component;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Supply;
use App\Models\User;
use App\Models\MedicalHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use App\Models\CashMovement;

new class extends Component {
    public $patient_id, $doctor_id, $date, $time, $status = 'pending', $specialty, $notes, $price = 15;
    public $isModalOpen = false;

    // Propiedades para Estudios Médicos
    public $availableStudies = [];
    public $selectedStudyIds = [];
    public $includeConsultation = true;

    public function with()
    {
        $user = Auth::user();
        $isDoctor = $user->role === 'doctor';

        // Estats Base
        $stats = [
            'total_patients' => Patient::count(),
            'critical_supplies' => Supply::where('quantity', '<', 10)->orWhereDate('expiration_date', '<', now())->count(),
        ];

        // Query Base para Citas de Hoy
        $appointmentsQuery = Appointment::with(['patient', 'doctor'])
            ->whereDate('date', now())
            ->orderBy('time', 'asc');

        if ($isDoctor) {
            $stats['appointments_today'] = Appointment::where('doctor_id', $user->id)->where('date', now()->toDateString())->count();
            $stats['appointments_pending'] = Appointment::where('doctor_id', $user->id)->where('status', 'pending')->count();
            $stats['patients_attended'] = MedicalHistory::where('doctor_id', $user->id)->count();
            $appointmentsQuery->where('doctor_id', $user->id);
        } elseif ($user->role === 'receptionist') {
            $stats['appointments_today'] = Appointment::where('date', now()->toDateString())->where('status', 'pending')->count();
            $stats['appointments_pending'] = Appointment::where('status', 'pending')->count();
            $appointmentsQuery->where('status', 'pending');
        } else {
            $stats['appointments_today'] = Appointment::where('date', now()->toDateString())->count();
            $stats['appointments_pending'] = Appointment::where('status', 'pending')->count();
        }

        return [
            'stats' => $stats,
            'todayAppointments' => $appointmentsQuery->get(),
            'isDoctor' => $isDoctor,
            'patients' => Patient::orderBy('name')->get(),
            'doctors' => User::where('role', 'doctor')
                ->active()
                ->with(['specialties.estudios', 'doctorProfile'])
                ->orderBy('name')
                ->get(),
        ];
    }

    public function updatedDoctorId($value)
    {
        if ($value) {
            $doctor = User::with('specialties.estudios')->find($value);
            $this->availableStudies = $doctor->specialties->flatMap->estudios;
            $this->selectedStudyIds = [];
            $this->includeConsultation = true;
            $this->calculateTotal();
        } else {
            $this->availableStudies = [];
            $this->selectedStudyIds = [];
        }
    }

    public function updatedSelectedStudyIds()
    {
        $this->calculateTotal();
    }

    public function updatedIncludeConsultation()
    {
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $total = $this->includeConsultation ? 15 : 0;
        $names = [];

        if ($this->includeConsultation) {
            $names[] = "Consulta";
        }

        $selectedItems = \App\Models\EspecialidadEstudio::whereIn('id', $this->selectedStudyIds)->get();
        foreach ($selectedItems as $study) {
            $total += $study->costo;
            $names[] = $study->estudio;
        }

        $this->price = $total;
        $this->specialty = implode(', ', $names);
    }

    public function openModal()
    {
        $this->reset(['patient_id', 'doctor_id', 'date', 'time', 'status', 'specialty', 'notes', 'isModalOpen', 'selectedStudyIds', 'availableStudies']);
        $this->includeConsultation = true;
        $this->price = 15;
        $this->date = now()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('role', 'doctor')->where('is_active', true)],
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
            'status' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'date' => $this->date,
            'time' => $this->time,
            'status' => $this->status,
            'specialty' => $this->specialty,
            'notes' => $this->notes,
            'price' => $this->price,
        ]);

        $this->closeModal();
        session()->flash('message', 'Cita programada exitosamente desde el tablero.');
    }
};
?>

<div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Hola, {{ auth()->user()->name }}</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Citas Hoy -->
        <a href="{{ route('appointments.index') }}" wire:navigate
            class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer group">
            <div>
                <h3 class="text-gray-500 text-sm font-medium group-hover:text-indigo-600 transition">Citas Hoy</h3>
                <p class="text-2xl md:text-3xl font-bold text-indigo-600 mt-2">{{ $stats['appointments_today'] }}</p>
            </div>
            <div class="p-3 bg-indigo-50 rounded-full text-indigo-600 group-hover:bg-indigo-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
        </a>

        <!-- Card 2: Citas Pendientes -->
        <a href="{{ route('appointments.index') }}" wire:navigate
            class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer group">
            <div>
                <h3 class="text-gray-500 text-sm font-medium group-hover:text-orange-600 transition">Citas Pendientes
                </h3>
                <p class="text-2xl md:text-3xl font-bold text-orange-500 mt-2">{{ $stats['appointments_pending'] }}</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-full text-orange-600 group-hover:bg-orange-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </a>

        <!-- Card 3: Pacientes -->
        <a href="{{ $isDoctor ? route('medical-histories.index') : route('patients.index') }}" wire:navigate
            class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer group">
            <div>
                <h3 class="text-gray-500 text-sm font-medium group-hover:text-green-600 transition">
                    {{ $isDoctor ? 'Pacientes Atendidos' : 'Pacientes Totales' }}
                </h3>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-2">
                    {{ $isDoctor ? $stats['patients_attended'] : $stats['total_patients'] }}
                </p>
            </div>
            <div class="p-3 bg-green-50 rounded-full text-green-600 group-hover:bg-green-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
            </div>
        </a>

        @if (in_array(auth()->user()->role, ['super_admin', 'admin', 'pharmacist', 'nurse']))
            <!-- Card 4: Insumos -->
            <a href="{{ route('supplies.index') }}" wire:navigate
                class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer group">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium group-hover:text-red-600 transition">Insumos Críticos</h3>
                    <p class="text-2xl md:text-3xl font-bold text-red-600 mt-2">{{ $stats['critical_supplies'] }}</p>
                </div>
                <div class="p-3 bg-red-50 rounded-full text-red-600 group-hover:bg-red-100 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                </div>
            </a>
        @endif
    </div>

    <!-- Today's Schedule -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Agenda de Hoy
            </h3>
            <button wire:click="openModal"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-bold text-sm shadow-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Cita
            </button>
        </div>

        @if (session()->has('message'))
            <div class="m-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('message') }}
            </div>
        @endif

        @if($todayAppointments->count() > 0)
            <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médico
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($todayAppointments as $appointment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        {{ Carbon\Carbon::parse($appointment->time)->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $appointment->patient->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $appointment->doctor->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                                                                                                                                                            {{ match ($appointment->status) {
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'confirmed' => 'bg-blue-100 text-blue-800',
                            'attending' => 'bg-orange-100 text-orange-800',
                            'attended' => 'bg-teal-100 text-teal-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        } }}">
                                            @php
                                                $labels = [
                                                    'pending' => 'Pendiente',
                                                    'confirmed' => 'Confirmada',
                                                    'attending' => 'Atendiendo',
                                                    'attended' => 'Atendido',
                                                    'completed' => 'Completada',
                                                    'cancelled' => 'Cancelada',
                                                ];
                                            @endphp
                                            {{ $labels[$appointment->status] ?? ucfirst($appointment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('appointments.index') }}"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded transition">Ver
                                            en Agenda</a>
                                    </td>
                                </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="p-12 text-center text-gray-500 flex flex-col items-center gap-3">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg">No hay citas programadas para hoy.</p>
                @if(auth()->user()->role !== 'doctor')
                    <button wire:click="openModal" class="text-indigo-600 font-bold hover:underline">¿Deseas programar una
                        ahora?</button>
                @endif
            </div>
        @endif
    </div>

    <!-- Calendar: Availability of Doctors -->
    <div x-data="{ isOpen: false }" class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <button @click="isOpen = !isOpen"
            class="w-full px-6 py-4 flex justify-between items-center bg-indigo-600 hover:bg-indigo-700 transition">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Disponibilidad Semanal de Médicos
            </h3>
            <svg :class="{'rotate-180': isOpen}" class="w-5 h-5 text-white transition-transform duration-200"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="grid grid-cols-1 md:grid-cols-7 divide-y md:divide-y-0 md:divide-x divide-gray-100 overflow-x-auto">
            @php
                $weekDays = [
                    'monday' => 'Lunes',
                    'tuesday' => 'Martes',
                    'wednesday' => 'Miércoles',
                    'thursday' => 'Jueves',
                    'friday' => 'Viernes',
                    'saturday' => 'Sábado',
                    // 'sunday' => 'Domingo'
                ];
            @endphp

            @foreach($weekDays as $key => $label)
                <div class="flex-1 min-w-[150px]">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 text-center">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ $label }}</span>
                    </div>
                    <div class="p-2 flex flex-wrap gap-2 min-h-[150px] content-start">
                        @php
                            $availableToday = $doctors->filter(
                                fn($d) =>
                                $d->doctorProfile &&
                                is_array($d->doctorProfile->working_days) &&
                                in_array($key, $d->doctorProfile->working_days)
                            );
                        @endphp

                        @forelse($availableToday as $doc)
                            <div
                                class="flex-1 min-w-[120px] max-w-[150px] p-2 rounded-lg bg-indigo-50 border border-indigo-100 hover:shadow-sm transition text-center shadow-sm">
                                <p class="text-[10px] font-bold text-indigo-700 uppercase truncate mb-1"
                                    title="{{ $doc->name }}">
                                    {{ $doc->name }}
                                </p>
                                <div
                                    class="flex items-center justify-center gap-1 text-[9px] text-gray-500 font-bold bg-white/60 rounded py-0.5 border border-indigo-50">
                                    <svg class="w-2.5 h-2.5 text-indigo-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>
                                        {{ $doc->doctorProfile->working_hours['start'] }} -
                                        {{ $doc->doctorProfile->working_hours['end'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="w-full flex flex-col items-center justify-center opacity-30 py-8">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                    </path>
                                </svg>
                                <span class="text-[9px] font-bold text-gray-400 uppercase mt-2">Sin Médicos</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Contactos de Médicos -->
    <div x-data="{ isOpen: false }" class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 mt-6">
        <button @click="isOpen = !isOpen"
            class="w-full px-6 py-4 flex justify-between items-center bg-teal-600 hover:bg-teal-700 transition">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                    </path>
                </svg>
                Contactos de Médicos
            </h3>
            <svg :class="{'rotate-180': isOpen}" class="w-5 h-5 text-white transition-transform duration-200"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="bg-white border-t border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Médico</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Correo Electrónico</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($doctors as $doctor)
                            <tr class="hover:bg-gray-50 transition">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold text-xs uppercase shadow-sm">
                                        {{ substr($doctor->name, 0, 2) }}
                                    </div>
                                    {{ $doctor->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    {{ $doctor->phone ?? 'Sin registro' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="mailto:{{ $doctor->email }}"
                                        class="text-teal-600 hover:text-teal-800 hover:underline transition">{{ $doctor->email }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Cita -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                        <h3 class="text-xl leading-6 font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">
                            Programar Nueva Cita (Rápido)
                        </h3>
                    </div>
                    <div class="bg-white p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Paciente</label>
                            <select wire:model="patient_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccione Paciente</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->dni }})</option>
                                @endforeach
                            </select>
                            @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Médico</label>
                            <select wire:model.live="doctor_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccione Médico</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">
                                        {{ $doctor->name }}
                                        @if($doctor->specialties->count() > 0)
                                            ({{ $doctor->specialties->pluck('name')->implode(', ') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if(count($availableStudies) > 0)
                            <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                                <label class="block text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">Estudios
                                    Disponibles (Opcional)</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <label
                                        class="flex items-center gap-2 p-1.5 bg-white rounded border border-indigo-200 cursor-pointer hover:bg-white/80 transition">
                                        <input type="checkbox" wire:model.live="includeConsultation"
                                            class="text-indigo-600 focus:ring-indigo-500 rounded">
                                        <span class="text-xs font-bold text-gray-700">Consulta (General)</span>
                                        <span class="ml-auto text-xs font-bold text-indigo-600">$15.00</span>
                                    </label>
                                    @foreach($availableStudies as $study)
                                        <label
                                            class="flex items-center gap-2 p-1.5 bg-white rounded border border-indigo-200 cursor-pointer hover:bg-white/80 transition">
                                            <input type="checkbox" wire:model.live="selectedStudyIds" value="{{ $study->id }}"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-xs font-bold text-gray-900 leading-none">{{ $study->estudio }}</span>
                                                <span
                                                    class="text-[10px] text-gray-500 leading-tight">{{ $study->specialty->name }}</span>
                                            </div>
                                            <span
                                                class="ml-auto text-xs font-bold text-indigo-600">${{ number_format($study->costo, 2) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mt-2 pt-2 border-t border-indigo-200 flex justify-between items-center px-1">
                                    <span class="text-xs font-bold text-indigo-800">TOTAL:</span>
                                    <span class="text-sm font-black text-indigo-600">${{ number_format($price, 2) }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
                                <input type="date" wire:model="date" min="{{ date('Y-m-d') }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Hora</label>
                                <input type="time" wire:model="time"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Monto ($)</label>
                                <input type="number" step="0.01" wire:model="price"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Notas</label>
                            <textarea wire:model="notes" rows="2"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Motivo de la consulta..."></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="save" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-indigo-600 text-base font-bold text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm transition">
                            Crear Cita
                        </button>
                        <button wire:click="closeModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>