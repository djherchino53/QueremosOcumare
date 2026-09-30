<?php
use Livewire\Volt\Component;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $histories;
    public $patient_id, $diagnosis, $treatment, $notes, $date;
    public $historyId;
    public $isModalOpen = false;
    public $isEditMode = false;
    public $search = '';
    public $patient_id_filter = null;
    public $appointment_id_filter = null;

    public function mount()
    {
        $patientId = request()->query('patient_id');
        if ($patientId) {
            $this->patient_id_filter = $patientId;
            $patient = Patient::find($patientId);
            if ($patient) {
                $this->search = $patient->dni;
            }
        }

        $appointmentId = request()->query('appointment_id');
        if ($appointmentId) {
            $this->appointment_id_filter = $appointmentId;
        }
    }

    public function with()
    {
        $user = Auth::user();
        $query = MedicalHistory::with(['patient', 'doctor'])->orderBy('date', 'desc');

        // Restricción de visibilidad
        // Admins y enfermería consultan todas las historias.
        if (!in_array($user->role, ['super_admin', 'admin', 'nurse'])) {
            // Un médico solo ve las historias que él creó (sus pacientes)
            $query->where('doctor_id', $user->id);
        }

        if ($this->search) {
            $query->whereHas('patient', function ($q) {
                $q->whereLike('name', '%' . $this->search . '%')
                    ->orWhereLike('dni', '%' . $this->search . '%');
            });
        }

        return [
            'historiesList' => $query->get(),
            'patients' => Patient::orderBy('name')->get(),
        ];
    }

    public function rules()
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'diagnosis' => 'required|min:3',
            'treatment' => 'required',
            'date' => 'required|date',
        ];
    }

    public function openModal()
    {
        $this->reset(['patient_id', 'diagnosis', 'treatment', 'notes', 'date', 'historyId', 'isEditMode']);

        // Si venimos de "Atender Paciente", pre-seleccionamos el paciente
        if ($this->patient_id_filter) {
            $this->patient_id = $this->patient_id_filter;
        }

        $this->date = date('Y-m-d'); // Default today
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($id)
    {
        $history = MedicalHistory::find($id);

        // Verificar permisos de edición (solo el autor o admin/super admin)
        if (!in_array(Auth::user()->role, ['super_admin', 'admin']) && $history->doctor_id !== Auth::id()) {
            return;
        }

        $this->historyId = $history->id;
        $this->patient_id = $history->patient_id;
        $this->diagnosis = $history->diagnosis;
        $this->treatment = $history->treatment;
        $this->notes = $history->notes;
        $this->date = \Carbon\Carbon::parse($history->date)->format('Y-m-d');
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'patient_id' => $this->patient_id,
            'diagnosis'  => $this->diagnosis,
            'treatment'  => $this->treatment,
            'notes'      => $this->notes,
            'date'       => $this->date,
        ];

        if ($this->isEditMode) {
            $history = MedicalHistory::find($this->historyId);
            // Verificar permisos antes de actualizar
            if (!in_array(Auth::user()->role, ['super_admin', 'admin']) && $history->doctor_id !== Auth::id()) {
                return;
            }
            $history->update($data);
        } else {
            // Al crear, asignar el doctor actual
            $data['doctor_id'] = Auth::id();
            MedicalHistory::create($data);
        }

        // Las notas de enfermería no cierran la cita: eso le corresponde al médico.
        if (Auth::user()->role === 'nurse') {
            $this->appointment_id_filter = null;
            $this->closeModal();
            return;
        }

        // Marcar la cita como 'atendido' (aplica tanto al crear como al editar una historia médica)
        $appointment = null;
        if ($this->appointment_id_filter) {
            $appointment = Appointment::find($this->appointment_id_filter);
        }
        if (!$appointment && $this->patient_id) {
            $query = Appointment::where('patient_id', $this->patient_id)
                ->whereIn('status', ['attending', 'pending', 'confirmed']);
            if (Auth::user()->role === 'doctor') {
                $query->where('doctor_id', Auth::id());
            }
            $appointment = $query->orderBy('date', 'desc')->first();
        }

        if ($appointment && in_array($appointment->status, ['attending', 'pending', 'confirmed'])) {
            $appointment->update(['status' => 'attended']);
        }
        $this->appointment_id_filter = null;

        $this->closeModal();
    }

    public function delete($id)
    {
        $history = MedicalHistory::find($id);
        if (!in_array(Auth::user()->role, ['super_admin', 'admin']) && $history->doctor_id !== Auth::id()) {
            return;
        }
        $history->delete();
    }
};
?>

<div>
    @if($appointment_id_filter)
        <div class="mb-4 flex items-center gap-3 bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
            <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                </path>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-bold text-orange-700">Consultando paciente — Cita #{{ $appointment_id_filter }}</p>
                <p class="text-xs text-orange-600">Al guardar la historia médica, el estado de la cita cambiará a <strong>Atendido</strong>.</p>
            </div>
            <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-full border border-orange-300">Atendiendo</span>
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Historias Médicas</h1>
        <div class="flex gap-2 items-center">
            @if($patient_id_filter)
                <span
                    class="text-sm bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full border border-indigo-100 flex items-center gap-2">
                    Filtrado por Paciente
                    <button wire:click="$set('patient_id_filter', null)"
                        class="text-indigo-900 hover:text-red-600 font-bold">×</button>
                </span>
            @endif
            <input wire:model.live="search" type="text" placeholder="Buscar paciente..."
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
            <button wire:click="openModal"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-bold shadow-sm">
                + Nueva Historia
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Diagnóstico</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($historiesList as $history)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-bold">{{ $history->patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $history->patient->dni }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            {{ Str::limit($history->diagnosis, 50) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $history->doctor->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if(in_array(Auth::user()->role, ['super_admin', 'admin']) || $history->doctor_id === Auth::id())
                                <button wire:click="edit({{ $history->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $history->id }})" wire:confirm="¿Estás seguro?"
                                    class="text-red-600 hover:text-red-900">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6">
                                        </path>
                                    </svg>
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">Solo lectura</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ $isEditMode ? 'Editar Historia' : 'Nueva Historia Médica' }}
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Paciente</label>
                                    <select wire:model="patient_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 {{ $patient_id_filter ? 'bg-gray-100 cursor-not-allowed pointer-events-none' : '' }}"
                                        {{ $patient_id_filter ? 'tabindex=-1' : '' }}>
                                        <option value="">Seleccione Paciente</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->dni }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($patient_id_filter)
                                        <p class="mt-1 text-xs text-indigo-600 font-medium">Paciente pre-seleccionado desde la cita.</p>
                                    @endif
                                    @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                    <input type="date" wire:model="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Diagnóstico</label>
                                <textarea wire:model="diagnosis" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                @error('diagnosis') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tratamiento</label>
                                <textarea wire:model="treatment" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                @error('treatment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notas Adicionales</label>
                                <textarea wire:model="notes" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="save" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar
                        </button>
                        <button wire:click="closeModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>