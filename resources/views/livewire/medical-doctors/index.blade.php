<?php
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\MedicalSpecialty;
use App\Models\DoctorProfile;

new class extends Component {
    public $search = '';
    public $isModalOpen = false;
    public $selectedDoctorId;
    public $selectedSpecialties = [];
    public $activeTab = 'list';

    public function with()
    {
        return [
            'doctors' => User::where('role', 'doctor')
                ->where('name', 'like', '%' . $this->search . '%')
                ->orwhere('email', 'like', '%' . $this->search . '%')
                ->with(['specialties', 'doctorProfile'])
                ->get(),
            'allSpecialties' => MedicalSpecialty::orderBy('name')->get(),
        ];
    }

    public function openSpecialtyModal($doctorId)
    {
        $this->selectedDoctorId = $doctorId;
        $doctor = User::find($doctorId);
        $this->selectedSpecialties = $doctor->specialties->pluck('id')->toArray();
        $this->isModalOpen = true;
    }

    public function saveSpecialties()
    {
        $doctor = User::find($this->selectedDoctorId);
        $doctor->specialties()->sync($this->selectedSpecialties);

        // Asegurar que tenga un perfil creado
        DoctorProfile::firstOrCreate(['user_id' => $doctor->id]);

        $this->isModalOpen = false;
        session()->flash('message', 'Especialidades vinculadas correctamente.');
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Médicos</h1>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 flex gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <input wire:model.live="search" type="text" placeholder="Buscar Médico"
            class="w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($doctors as $doctor)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="p-5">
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xl uppercase">
                            {{ substr($doctor->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $doctor->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $doctor->email }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <span
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Especialidades</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($doctor->specialties as $s)
                                    <span
                                        class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase transition hover:bg-indigo-100">
                                        {{ $s->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 italic">Sin especialidades asignadas</span>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Registro
                                Prof.</span>
                            <p class="text-sm text-gray-700 font-medium">
                                {{ $doctor->doctorProfile->professional_id ?? 'No registrado' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex justify-between items-center">
                    <button wire:click="openSpecialtyModal({{ $doctor->id }})"
                        class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                            </path>
                        </svg>
                        Gestionar Especialidades
                    </button>
                    <a href="{{ route('medical-doctors.profile', $doctor->id) }}" wire:navigate
                        class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        Ver Perfil
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Vincular Especialidades -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    wire:click="$set('isModalOpen', false)"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                        <h3 class="text-xl font-bold text-gray-900">Vincular Especialidades</h3>
                        <p class="text-sm text-gray-500">Médico: {{ User::find($selectedDoctorId)->name }}</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($allSpecialties as $spec)
                                <label
                                    class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-indigo-50 transition cursor-pointer group {{ in_array($spec->id, $selectedSpecialties) ? 'bg-indigo-50 border-indigo-200' : '' }}">
                                    <input type="checkbox" wire:model="selectedSpecialties" value="{{ $spec->id }}"
                                        class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    <span
                                        class="text-sm font-medium text-gray-700 group-hover:text-indigo-900 transition">{{ $spec->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="saveSpecialties"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition sm:ml-3 sm:w-auto">
                            Guardar Cambios
                        </button>
                        <button wire:click="$set('isModalOpen', false)"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>