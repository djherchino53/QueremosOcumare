<?php
use Livewire\Volt\Component;
use App\Models\MedicalSpecialty;
use Illuminate\Validation\Rule;

new class extends Component {
    public $name, $description, $specialtyId;
    public $isModalOpen = false;
    public $isEditMode = false;
    public $search = '';

    // Propiedades para Estudios (especialidad_estudios)
    public $estudio, $costo, $studyId;
    public $studiesList = [];

    public function with()
    {
        return [
            'specialties' => MedicalSpecialty::with('estudios')
                ->whereLike('name', '%' . $this->search . '%')
                ->orderBy('name')
                ->get(),
        ];
    }

    public function openModal()
    {
        $this->reset(['name', 'description', 'specialtyId', 'isEditMode', 'estudio', 'costo', 'studyId', 'studiesList']);
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetStudyForm()
    {
        $this->reset(['estudio', 'costo', 'studyId']);
    }

    public function edit($id)
    {
        $specialty = MedicalSpecialty::with('estudios')->find($id);
        $this->specialtyId = $specialty->id;
        $this->name = $specialty->name;
        $this->description = $specialty->description;
        $this->studiesList = $specialty->estudios;
        $this->isEditMode = true;
        $this->isModalOpen = true;
        $this->resetStudyForm();
    }

    public function saveStudy()
    {
        $this->validate([
            'estudio' => 'required|min:3',
            'costo' => 'required|numeric|min:0',
        ]);

        if ($this->studyId) {
            \App\Models\EspecialidadEstudio::find($this->studyId)->update([
                'estudio' => $this->estudio,
                'costo' => $this->costo,
            ]);
        } else {
            \App\Models\EspecialidadEstudio::create([
                'especialidad_id' => $this->specialtyId,
                'estudio' => $this->estudio,
                'costo' => $this->costo,
            ]);
        }

        $this->studiesList = MedicalSpecialty::find($this->specialtyId)->estudios;
        $this->resetStudyForm();
    }

    public function editStudy($id)
    {
        $study = \App\Models\EspecialidadEstudio::find($id);
        $this->studyId = $study->id;
        $this->estudio = $study->estudio;
        $this->costo = $study->costo;
    }

    public function deleteStudy($id)
    {
        \App\Models\EspecialidadEstudio::find($id)->delete();
        $this->studiesList = MedicalSpecialty::find($this->specialtyId)->estudios;
    }

    public function save()
    {
        $this->validate([
            'name' => [
                'required',
                'min:3',
                Rule::unique('medical_specialties', 'name')->ignore($this->specialtyId)
            ],
        ]);

        if ($this->isEditMode) {
            MedicalSpecialty::find($this->specialtyId)->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            session()->flash('message', 'Especialidad actualizada exitosamente.');
        } else {
            MedicalSpecialty::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            session()->flash('message', 'Especialidad creada exitosamente.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        MedicalSpecialty::find($id)->delete();
        session()->flash('message', 'Especialidad eliminada correctamente.');
    }
};
?>

<div>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-xl md:text-3xl font-bold text-gray-800">Especialidades Médicas</h1>
        <button wire:click="openModal"
            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-bold shadow-sm">
            + Nueva Especialidad
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 flex gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <input wire:model.live="search" type="text" placeholder="Buscar especialidad..."
            class="w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto border border-gray-200">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estudios
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($specialties as $specialty)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $specialty->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $specialty->description ?: 'Sin descripción' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <ul class="space-y-1">
                                @forelse($specialty->estudios as $estudio)
                                    <li
                                        class="flex justify-between items-center text-[11px] bg-slate-50 px-2 py-1 rounded border border-slate-100">
                                        <span class="font-medium text-slate-700 truncate mr-2"
                                            title="{{ $estudio->estudio }}">{{ $estudio->estudio }}</span>
                                        <span
                                            class="font-bold text-indigo-600 whitespace-nowrap">${{ number_format($estudio->costo, 2) }}</span>
                                    </li>
                                @empty
                                    <span class="text-xs italic text-gray-400">Sin estudios registrados</span>
                                @endforelse
                            </ul>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-3">
                                <button wire:click="edit({{ $specialty->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $specialty->id }})"
                                    wire:confirm="¿Estás seguro de eliminar esta especialidad?"
                                    class="text-red-600 hover:text-red-900 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">No se encontraron especialidades
                            médicas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Principal -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <!-- Ajuste de ancho si es edición para acomodar estudios -->
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {{ $isEditMode ? 'sm:max-w-4xl' : 'sm:max-w-lg' }} sm:w-full">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ $isEditMode ? 'Editar Especialidad: ' . $this->name : 'Nueva Especialidad' }}
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 {{ $isEditMode ? 'lg:grid-cols-2' : '' }} gap-8">
                            <!-- Columna 1: Datos de la Especialidad -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Información
                                    General</h4>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                    <input type="text" wire:model="name"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ej: Cardiología">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                                    <textarea wire:model="description" rows="4"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Descripción opcional..."></textarea>
                                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="pt-4 border-t">
                                    <button wire:click="save"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition">
                                        {{ $isEditMode ? 'Actualizar Información' : 'Guardar Especialidad' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Columna 2: Gestión de Estudios (Solo en modo edición) -->
                            @if($isEditMode)
                                <div class="border-t lg:border-t-0 lg:border-l lg:pl-8 pt-6 lg:pt-0">
                                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Estudios y Costos
                                    </h4>

                                    <!-- Formulario pequeño para Añadir/Editar Estudio -->
                                    <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                                        <div class="grid grid-cols-2 gap-3 mb-3">
                                            <div class="col-span-2">
                                                <label class="block text-xs font-bold text-gray-600 mb-1">Nombre del
                                                    Estudio</label>
                                                <input type="text" wire:model="estudio"
                                                    class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="Ej: Electrocardiograma">
                                                @error('estudio') <span class="text-red-500 text-[10px]">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-600 mb-1">Costo ($)</label>
                                                <input type="number" step="0.01" wire:model="costo"
                                                    class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="0.00">
                                                @error('costo') <span class="text-red-500 text-[10px]">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="flex items-end space-x-2">
                                                <button wire:click="saveStudy"
                                                    class="flex-1 bg-indigo-600 text-white px-3 py-2 rounded-md hover:bg-indigo-700 font-bold text-xs shadow-sm">
                                                    {{ $studyId ? 'OK' : 'Añadir' }}
                                                </button>
                                                @if($studyId)
                                                    <button wire:click="resetStudyForm"
                                                        class="bg-gray-200 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-300 font-bold text-xs">
                                                        X
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabla de Estudios Existentes -->
                                    <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                                        <table class="w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50 sticky top-0">
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase">
                                                        Estudio</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase">
                                                        Costo</th>
                                                    <th
                                                        class="px-3 py-2 text-right text-[10px] font-bold text-gray-500 uppercase">
                                                        Acc.</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($studiesList as $item)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-xs text-gray-900">{{ $item->estudio }}</td>
                                                        <td class="px-3 py-2 text-xs text-gray-700 font-bold">
                                                            ${{ number_format($item->costo, 2) }}</td>
                                                        <td class="px-3 py-2 text-right text-xs whitespace-nowrap">
                                                            <button wire:click="editStudy({{ $item->id }})"
                                                                class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                                    </path>
                                                                </svg>
                                                            </button>
                                                            <button wire:click="deleteStudy({{ $item->id }})"
                                                                wire:confirm="¿Borrar estudio?"
                                                                class="text-red-600 hover:text-red-900">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6">
                                                                    </path>
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-3 py-4 text-center text-gray-400 italic text-xs">
                                                            Sin estudios.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                        <button type="button" wire:click="closeModal"
                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 transition">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>