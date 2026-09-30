<?php
use Livewire\Volt\Component;
use App\Models\Patient;
use App\Models\MedicalHistory;

new class extends Component {
    public Patient $patient;
    public ?MedicalHistory $selectedHistory = null;
    public bool $isHistoryModalOpen = false;

    public function mount(Patient $patient)
    {
        $this->patient = $patient->load(['medicalHistories.doctor']);
    }

    public function getHistoriesProperty()
    {
        return $this->patient->medicalHistories()->with('doctor')->orderBy('date', 'desc')->get();
    }

    public function showHistory(MedicalHistory $history)
    {
        $this->selectedHistory = $history->load('doctor');
        $this->isHistoryModalOpen = true;
    }

    public function closeHistoryModal()
    {
        $this->isHistoryModalOpen = false;
        $this->selectedHistory = null;
    }
};
?>

<div>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Expediente del Paciente</h1>
            <p class="text-gray-500 text-sm">Gestiona la información y el historial clínico completo</p>
        </div>
        <a href="{{ route('patients.index') }}" wire:navigate
            class="flex items-center text-indigo-600 hover:text-indigo-800 font-bold transition bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver
        </a>
    </div>

    <!-- Patient Header Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="bg-indigo-600 h-24 relative">
            <div class="absolute -bottom-12 left-8">
                {{-- <div
                    class="h-24 w-24 rounded-2xl bg-white shadow-xl border-4 border-white flex items-center justify-center text-indigo-600">
                    {{ substr($patient->name, 0, 1) }}
                </div> --}}
                <div
                    class="h-24 w-24 rounded-full bg-indigo-600 flex items-center justify-center text-white text-4xl font-black uppercase mx-auto mb-4 shadow-lg ring-4 ring-indigo-50">
                    {{ substr($patient->name, 0, 1) }}
                </div>                
            </div>
        </div>
        <div class="pt-16 pb-8 px-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="md:pl-40">
                <h2 class="text-xl md:text-2xl font-black text-gray-800 uppercase tracking-tight">{{ $patient->name }}</h2>
                <div class="flex flex-wrap gap-4 mt-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                        {{ $patient->dni }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $patient->email ?: 'Sin correo' }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $patient->phone ?: 'Sin teléfono' }}
                    </span>
                </div>
                <p class="mt-4 text-gray-500 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ $patient->address ?: 'No registrada' }}
                </p>
            </div>
            <div class="flex gap-4">
                <div class="text-center px-6 py-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-2xl font-black text-indigo-600">{{ $this->histories->count() }}</p>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Número de Consultas</p>
                </div>
                <div class="text-center px-6 py-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-lg font-black text-gray-700 leading-tight pt-1">
                        {{ $patient->created_at->format('d/m/Y') }}
                    </p>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fecha de Registro</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Histories Full Width Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-gray-800 font-black uppercase text-sm tracking-widest flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Historial Clínico del Paciente
            </h3>
        </div>

        <div class="p-8">
            <div class="space-y-0">
                @forelse($this->histories as $history)
                    <div class="relative pl-10 pb-12 border-l-2 border-indigo-100 last:border-0 last:pb-0">
                        <!-- Timeline Dot -->
                        <div
                            class="absolute -left-[11px] top-0 w-5 h-5 rounded-full bg-white border-4 border-indigo-600 shadow-sm z-10 transition-transform duration-200 group-hover:scale-125">
                        </div>

                        <div wire:click="showHistory({{ $history->id }})"
                            class="cursor-pointer group bg-white rounded-2xl p-6 border border-gray-100 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="bg-indigo-50 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span
                                            class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-widest border border-indigo-100">
                                            {{ \Carbon\Carbon::parse($history->date)->format('d-m-Y') }}
                                        </span>
                                        <h4 class="text-xl font-bold text-gray-800 mt-1 capitalize">
                                            {{ $history->diagnosis }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Médico
                                        Tratante</p>
                                    <p
                                        class="text-sm font-bold text-gray-700 bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">
                                        Dr. {{ $history->doctor->name }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <p class="line-clamp-2 text-sm text-gray-600 leading-relaxed pr-4">
                                        <span class="font-bold text-gray-700">Tratamiento:</span> {{ $history->treatment }}
                                    </p>
                                </div>
                                <div class="flex justify-end items-center">
                                    <span
                                        class="text-indigo-600 text-xs font-bold uppercase tracking-widest flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                        Ver detalles completos
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                        <div
                            class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="text-gray-500 font-bold text-lg">No hay historias médicas registradas</h4>
                        <p class="text-gray-400 text-sm mt-1">Todas las consultas futuras aparecerán en esta línea de
                            tiempo.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- History Detail Modal -->
    @if($isHistoryModalOpen && $selectedHistory)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-6">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

            <div
                class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col border border-slate-200 ring-1 ring-black/5 transition-all">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-indigo-600 to-blue-500 px-10 py-8 flex justify-between items-center relative">
                    <div>
                        <p class="uppercase tracking-widest text-[10px] font-semibold opacity-80">Expediente Clínico</p>
                        <h3 class="text-2xl font-bold mt-1">Historia Médica de Paciente</h3>
                        <p class="text-[11px] font-medium opacity-90">
                            {{-- {{ \Carbon\Carbon::parse($selectedHistory->date)->isoFormat('LL') }} --}}
                            {{ \Carbon\Carbon::parse($selectedHistory->date)->format('d-m-Y') }}
                        </p>
                    </div>
                    <button wire:click="closeHistoryModal"
                        class="p-3 bg-white/10 rounded-full hover:bg-white/20 transition-all" title="Cerrar Historia">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-10 space-y-10 bg-white">
                    <!-- Diagnóstico -->
                    <section>
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Diagnóstico Principal
                        </h4>
                        <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100">
                            <p class="text-gray-800 font-semibold text-sm leading-relaxed">
                                {{ $selectedHistory->diagnosis }}
                            </p>
                        </div>
                    </section>

                    <!-- Tratamiento -->
                    <section>
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-600"></span>
                            Tratamiento e Indicaciones
                        </h4>
                        <div class="bg-green-50 p-6 rounded-xl border border-green-100">
                            <p class="text-gray-700 leading-relaxed text-sm">
                                {{ $selectedHistory->treatment }}
                            </p>
                        </div>
                    </section>

                    <!-- Notas -->
                    <section>
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            Notas del Especialista
                        </h4>
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                            <p class="text-gray-600 italic text-sm leading-relaxed whitespace-pre-line">
                                {{ $selectedHistory->notes ?: 'Sin observaciones adicionales.' }}
                            </p>
                        </div>
                    </section>

                    <!-- Info Footer -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 flex items-center gap-4">
                            <div class="bg-white p-3 rounded-xl shadow border border-slate-200">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">Atendido por</p>
                                <p class="text-sm font-bold text-gray-800">Dr. {{ $selectedHistory->doctor->name }}</p>
                            </div>
                        </div>

                        @if(!empty($selectedHistory->prescriptions))
                            <div class="bg-indigo-600 p-5 rounded-xl flex items-center gap-4 text-white shadow-md">
                                <div class="bg-white/20 p-3 rounded-lg border border-white/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a2 2 0 00-1.96 1.414l-.477 2.387a2 2 0 00.547 1.022l1.428 1.428a2 2 0 001.022.547l2.387.477a2 2 0 001.96-1.414l.477-2.387a2 2 0 00-.547-1.022l-1.428-1.428z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18.428 10.428L15 7.001M12 15L7 20M5 18L10 13M9 11L4 6M7 4L12 9" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-200">
                                        Prescripciones</p>
                                    <p class="text-sm font-bold">Medicinas asignadas</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 border-t border-gray-200 px-10 py-6 flex justify-center gap-6">
                    <button wire:click="closeHistoryModal"
                        class="px-10 py-3 bg-red-600 text-white text-[11px] uppercase tracking-widest font-semibold rounded-xl shadow hover:bg-red-700 transition">
                        Cerrar
                    </button>
                    <button
                        class="px-10 py-3 bg-indigo-600 text-white text-[11px] uppercase tracking-widest font-semibold rounded-xl shadow hover:bg-indigo-700 transition">
                        Imprimir
                    </button>
                </div>
            </div>
        </div>

    @endif
</div>