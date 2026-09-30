<?php
use Livewire\Volt\Component;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Validation\Rule;

use App\Models\CashMovement;

new class extends Component {
    public $appointments;
    public $patient_id, $doctor_id, $date, $time, $status = 'pending', $specialty, $notes, $price = 15;
    public $appointmentId;
    public $isModalOpen = false;
    public $isEditMode = false;
    public $filterStatus = '';
    public $filterPatientId = '';
    public $filterDoctorId = '';
    public $filterDate = '';
    public $filterMonth = '';
    public $filterYear = '';

    // Propiedades para Estudios Médicos
    public $availableStudies = [];
    public $selectedStudyIds = [];
    public $includeConsultation = true;

    // Propiedades para Modal de Cobro
    public $isPaymentModalOpen = false;
    public $payAppointmentId = null;
    public $payPatientName = '';
    public $payDoctorName = '';
    public $payAmount = 0;
    public $payMethod = 'Efectivo';
    public $payReference = '';

    public function mount()
    {
        $this->filterDate = date('Y-m-d');
        $this->filterYear = date('Y');
        $this->filterMonth = date('m');
    }

    public function with()
    {
        $query = Appointment::with(['patient', 'doctor'])->orderBy('date', 'desc');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPatientId) {
            $query->where('patient_id', $this->filterPatientId);
        }

        if ($this->filterDoctorId) {
            $query->where('doctor_id', $this->filterDoctorId);
        }

        if ($this->filterDate) {
            $query->where('date', $this->filterDate);
        } elseif ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('date', $this->filterMonth)
                ->whereYear('date', $this->filterYear);
        } elseif ($this->filterYear) {
            $query->whereYear('date', $this->filterYear);
        }

        // Si el usuario es médico, solo ve sus propias citas
        if (auth()->user()->role === 'doctor') {
            $query->where('doctor_id', auth()->id());
        }

        $patientsQuery = Patient::orderBy('name');
        if (auth()->user()->role === 'doctor') {
            $patientsQuery->whereHas('appointments', function ($q) {
                $q->where('doctor_id', auth()->id());
            });
        }

        return [
            'appointmentsList' => $query->get(),
            'patients' => $patientsQuery->get(),
            'doctors' => User::where('role', 'doctor')->orderBy('name')->get(),
            // Para asignar citas: solo médicos activos, más el de la cita que se está editando.
            'assignableDoctors' => User::where('role', 'doctor')
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $this->doctor_id ?: 0))
                ->with('specialties.estudios')
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

    public function rules()
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'doctor')
                    ->where(fn ($q) => $q->where('is_active', true)
                        ->orWhere('id', $this->appointmentId ? Appointment::find($this->appointmentId)?->doctor_id : 0))),
            ],
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
            'status' => 'required',
            'price' => 'required|numeric|min:0',
        ];
    }

    public function openModal()
    {
        $this->reset(['patient_id', 'doctor_id', 'date', 'time', 'status', 'specialty', 'notes', 'appointmentId', 'isEditMode', 'selectedStudyIds', 'availableStudies']);
        $this->includeConsultation = true;
        $this->price = 15;
        $this->date = now()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) return;

        if (auth()->user()->role === 'receptionist' && in_array($appointment->status, ['attending', 'attended'])) {
            return;
        }

        $this->appointmentId = $appointment->id;
        $this->patient_id = $appointment->patient_id;
        $this->doctor_id = $appointment->doctor_id;
        $this->date = \Carbon\Carbon::parse($appointment->date)->format('Y-m-d');
        $this->time = $appointment->time;
        $this->status = $appointment->status;
        $this->specialty = $appointment->specialty;
        $this->notes = $appointment->notes;
        $this->price = $appointment->price;

        // Cargar estudios disponibles para el médico seleccionado
        $doctor = User::with('specialties.estudios')->find($this->doctor_id);
        if ($doctor) {
            $this->availableStudies = $doctor->specialties->flatMap->estudios;

            // Intentar reconstruir selección
            $this->includeConsultation = str_contains($this->specialty, 'Consulta');
            $this->selectedStudyIds = [];
            foreach ($this->availableStudies as $study) {
                if (str_contains($this->specialty, $study->estudio)) {
                    $this->selectedStudyIds[] = $study->id;
                }
            }
        }

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'date' => $this->date,
            'time' => $this->time,
            'status' => $this->status,
            'specialty' => $this->specialty,
            'notes' => $this->notes,
            'price' => $this->price,
        ];

        if ($this->isEditMode) {
            $appointment = Appointment::find($this->appointmentId);
            if ($appointment && auth()->user()->role === 'receptionist' && in_array($appointment->status, ['attending', 'attended'])) {
                return;
            }
            if ($appointment) {
                $appointment->update($data);
            }
        } else {
            Appointment::create($data);
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) return;

        if (auth()->user()->role === 'receptionist' && in_array($appointment->status, ['attending', 'attended'])) {
            return;
        }

        $appointment->delete();
    }

    public function attendPatient($appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
        if ($appointment && in_array($appointment->status, ['pending', 'confirmed'])) {
            $appointment->update(['status' => 'attending']);
        }
        return redirect()->route('medical-histories.index', [
            'patient_id'     => $appointment->patient_id,
            'appointment_id' => $appointmentId,
        ]);
    }

    public function openPaymentModal($id)
    {
        $appointment = Appointment::with(['patient', 'doctor'])->find($id);
        if (!$appointment) return;

        // El cobro solo se habilita cuando la cita cambia a estado 'attended' o 'completed'
        if (!in_array($appointment->status, ['attended', 'completed'])) {
            return;
        }

        $this->payAppointmentId = $appointment->id;
        $this->payPatientName = $appointment->patient ? $appointment->patient->name : 'N/A';
        $this->payDoctorName = $appointment->doctor ? $appointment->doctor->name : 'N/A';
        $this->payAmount = $appointment->price;
        $this->payMethod = 'Efectivo';
        $this->payReference = '';
        $this->isPaymentModalOpen = true;
    }

    public function closePaymentModal()
    {
        $this->isPaymentModalOpen = false;
    }

    public function processPayment()
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:0',
            'payMethod' => 'required|in:Efectivo,Pago Móvil,Transferencia',
        ]);

        $appointment = Appointment::with(['patient', 'doctor'])->find($this->payAppointmentId);
        if (!$appointment) return;

        $methodLabel = $this->payMethod . ($this->payReference ? " (Ref: {$this->payReference})" : "");

        $appointment->update([
            'price'          => $this->payAmount,
            'payment_status' => 'paid',
            'payment_method' => $methodLabel,
        ]);

        $patientName = $appointment->patient ? $appointment->patient->name : '';
        $doctorName = $appointment->doctor ? $appointment->doctor->name : '';
        $desc = "Cita Médica ({$this->payMethod}): {$patientName} - Dr. {$doctorName}" . ($this->payReference ? " [Ref: {$this->payReference}]" : "");

        $cashMovement = CashMovement::where('appointment_id', $appointment->id)->first();
        if ($cashMovement) {
            $cashMovement->update([
                'amount'      => $this->payAmount,
                'description' => $desc,
                'date'        => date('Y-m-d'),
            ]);
        } else {
            CashMovement::create([
                'amount'         => $this->payAmount,
                'type'           => 'in',
                'category'       => 'Cita Médica',
                'description'    => $desc,
                'appointment_id' => $appointment->id,
                'doctor_id'      => $appointment->doctor_id,
                'user_id'        => auth()->id(),
                'date'           => date('Y-m-d'),
            ]);
        }

        $this->closePaymentModal();
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6 print:hidden">
        <h1 class="text-3xl font-bold text-gray-800">Citas Médicas</h1>
        <button wire:click="openModal"
            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-bold shadow-sm">
            + Nueva Cita
        </button>
    </div>

    <!-- Print Header -->
    <div class="hidden print:block mb-6 border-b pb-4">
        <h1 class="text-3xl font-bold text-gray-800">Reporte de Citas - Queremos Ocumare</h1>
        <p class="text-gray-600">
            @if($filterDate)
                Fecha: {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }}
            @elseif($filterMonth)
                Periodo: {{ $filterMonth }}/{{ $filterYear }}
            @else
                Año: {{ $filterYear }}
            @endif
            @if($filterStatus)
                | Estado: {{ ucfirst($filterStatus) }}
            @endif
        </p>
    </div>

    <div
        class="mb-6 flex flex-wrap items-center gap-3 bg-white p-3 rounded-lg shadow-sm border border-gray-100 print:hidden">
        <div class="flex items-center gap-2 border-l pl-3">
            <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Día:</span>
            <input type="date" wire:model.live="filterDate"
                class="rounded-md border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100 text-xs py-1 px-2">
        </div>

        @if(!$filterDate)
            <div class="flex items-center gap-2 border-l pl-3">
                <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Mes:</span>
                <select wire:model.live="filterMonth" class="rounded-md border-gray-200 shadow-sm text-xs py-1">
                    <option value="01">Enero</option>
                    <option value="02">Febrero</option>
                    <option value="03">Marzo</option>
                    <option value="04">Abril</option>
                    <option value="05">Mayo</option>
                    <option value="06">Junio</option>
                    <option value="07">Julio</option>
                    <option value="08">Agosto</option>
                    <option value="09">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </select>
            </div>
            <div class="flex items-center gap-2 border-l pl-3">
                <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Año:</span>
                <select wire:model.live="filterYear" class="rounded-md border-gray-200 shadow-sm text-xs py-1">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        @endif

        <div class="flex items-center gap-2 border-l pl-3">
            <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Paciente:</span>
            <select wire:model.live="filterPatientId"
                class="rounded-md border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100 text-xs py-1">
                <option value="">Todos</option>
                @foreach($patients as $patient)
                    <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                @endforeach
            </select>
        </div>

        @if(auth()->user()->role !== 'doctor')
            <div class="flex items-center gap-2 border-l pl-3">
                <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Médico:</span>
                <select wire:model.live="filterDoctorId"
                    class="rounded-md border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100 text-xs py-1">
                    <option value="">Todos</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex items-center gap-2 border-l pl-3">
            <span class="text-xs font-bold text-gray-200 uppercase whitespace-nowrap">Estado:</span>
            <select wire:model.live="filterStatus"
                class="rounded-md border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100 text-xs py-1">
                <option value="">Todos</option>
                <option value="pending">Pendiente</option>
                <option value="confirmed">Confirmada</option>
                <option value="attending">Atendiendo</option>
                <option value="attended">Atendido</option>
                <option value="completed">Completada</option>
                <option value="cancelled">Cancelada</option>
            </select>
        </div>

        <div class="flex gap-2 ml-auto">
            <button
                wire:click="$set('filterDate', '{{ date('Y-m-d') }}'); $set('filterMonth', '{{ date('m') }}'); $set('filterYear', '{{ date('Y') }}'); $set('filterStatus', ''); $set('filterPatientId', ''); $set('filterDoctorId', '')"
                class="px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200 transition text-xs font-bold">
                Limpiar
            </button>
            <button onclick="window.print()"
                class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-xs font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v7" />
                </svg>
                Reporte
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fecha/Hora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médico
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pago
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($appointmentsList as $appointment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-bold">{{ $appointment->date->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $appointment->time }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <a href="{{ route('patients.show', $appointment->patient_id) }}" wire:navigate
                                class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline transition">
                                {{ $appointment->patient->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $appointment->doctor->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $colors = [
                                    'pending'   => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'attending' => 'bg-orange-100 text-orange-800',
                                    'attended'  => 'bg-teal-100 text-teal-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $labels = [
                                    'pending'   => 'Pendiente',
                                    'confirmed' => 'Confirmada',
                                    'attending' => 'Atendiendo',
                                    'attended'  => 'Atendido',
                                    'completed' => 'Completada',
                                    'cancelled' => 'Cancelada',
                                ];
                            @endphp
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $labels[$appointment->status] ?? ucfirst($appointment->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="font-bold text-gray-900">${{ number_format($appointment->price, 2) }}</div>
                            @if($appointment->payment_status === 'paid')
                                <div class="flex flex-col gap-0.5">
                                    <span class="px-2 inline-flex text-[10px] leading-4 font-bold rounded-full bg-green-100 text-green-800 w-max">
                                        Pagado
                                    </span>
                                    @if($appointment->payment_method)
                                        <span class="text-[10px] font-semibold text-gray-500">{{ $appointment->payment_method }}</span>
                                    @endif
                                </div>
                            @else
                                @if(in_array($appointment->status, ['attended', 'completed']))
                                    <button wire:click="openPaymentModal({{ $appointment->id }})"
                                        class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 hover:bg-red-200 transition cursor-pointer border border-red-200 flex items-center gap-1 shadow-xs"
                                        title="Hacer clic para proceder con el cobro de la cita">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Cobrar (Pendiente)
                                    </button>
                                @else
                                    <button disabled
                                        class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed opacity-75"
                                        title="El cobro se habilita únicamente cuando la cita pasa a estado Atendido">
                                        Pendiente
                                    </button>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                @if(in_array(auth()->user()->role, ['doctor', 'admin', 'super_admin']) && in_array($appointment->status, ['pending', 'confirmed', 'attending']))
                                    <button wire:click="attendPatient({{ $appointment->id }})"
                                        class="px-3 py-1 rounded transition text-white flex items-center gap-1 font-semibold text-xs"
                                        style="background-color: {{ $appointment->status === 'attending' ? '#ea580c' : '#16a34a' }};">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                            </path>
                                        </svg>
                                        {{ $appointment->status === 'attending' ? 'Continuar' : 'Atender' }}
                                    </button>
                                @endif
                                @php
                                    $canEditOrDelete = !(auth()->user()->role === 'receptionist' && in_array($appointment->status, ['attending', 'attended']));
                                @endphp
                                @if($canEditOrDelete)
                                    <button wire:click="edit({{ $appointment->id }})" title="Editar Cita"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $appointment->id }})" wire:confirm="¿Estás seguro?" title="Eliminar Cita"
                                        class="text-red-600 hover:text-red-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6">
                                            </path>
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs italic">Bloqueado</span>
                                @endif
                            </div>
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
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ $isEditMode ? 'Editar Cita' : 'Nueva Cita' }}
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Paciente</label>
                                <select wire:model="patient_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Seleccione Paciente</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->dni }})</option>
                                    @endforeach
                                </select>
                                @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Médico</label>
                                <select wire:model.live="doctor_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Seleccione Médico</option>
                                    @foreach($assignableDoctors as $doctor)
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
                                    <label
                                        class="block text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">Estudios
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
                                        <span class="text-xs font-bold text-indigo-800">TOTAL CALCULADO:</span>
                                        <span class="text-sm font-black text-indigo-600">${{ number_format($price, 2) }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                    <input type="date" wire:model="date" min="{{ date('Y-m-d') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Hora</label>
                                    <input type="time" wire:model="time"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select wire:model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="pending">Pendiente</option>
                                    <option value="confirmed">Confirmada</option>
                                    <option value="completed">Completada</option>
                                    <option value="cancelled">Cancelada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notas</label>
                                <textarea wire:model="notes"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Monto de la Cita ($)</label>
                                <input type="number" step="0.01" wire:model="price"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

    <!-- Modal Procesar Cobro -->
    @if($isPaymentModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePaymentModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-indigo-600 px-4 py-4 sm:px-6 flex justify-between items-center text-white">
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Cobro de Cita Médica
                        </h3>
                        <button wire:click="closePaymentModal" class="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
                    </div>

                    <div class="bg-white px-6 pt-5 pb-4 space-y-4">
                        <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 text-sm space-y-1">
                            <p><span class="font-bold text-gray-700">Paciente:</span> <span class="text-indigo-900 font-semibold">{{ $payPatientName }}</span></p>
                            <p><span class="font-bold text-gray-700">Médico:</span> <span class="text-gray-800">{{ $payDoctorName }}</span></p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Monto a Pagar ($)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-bold sm:text-sm">$</span>
                                </div>
                                <input type="number" step="0.01" wire:model="payAmount"
                                    class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-lg font-black text-indigo-700">
                            </div>
                            @error('payAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Pago</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="flex flex-col items-center justify-center p-3 border rounded-lg cursor-pointer transition text-center {{ $payMethod === 'Efectivo' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                    <input type="radio" wire:model.live="payMethod" value="Efectivo" class="sr-only">
                                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="text-xs">Efectivo</span>
                                </label>

                                <label class="flex flex-col items-center justify-center p-3 border rounded-lg cursor-pointer transition text-center {{ $payMethod === 'Pago Móvil' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                    <input type="radio" wire:model.live="payMethod" value="Pago Móvil" class="sr-only">
                                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-xs">Pago Móvil</span>
                                </label>

                                <label class="flex flex-col items-center justify-center p-3 border rounded-lg cursor-pointer transition text-center {{ $payMethod === 'Transferencia' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                    <input type="radio" wire:model.live="payMethod" value="Transferencia" class="sr-only">
                                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                    </svg>
                                    <span class="text-xs">Transferencia</span>
                                </label>
                            </div>
                            @error('payMethod') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if(in_array($payMethod, ['Pago Móvil', 'Transferencia']))
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nº Referencia (Opcional)</label>
                                <input type="text" wire:model="payReference" placeholder="Ej: 123456"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button wire:click="processPayment" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-bold text-white hover:bg-green-700 sm:w-auto sm:text-sm transition">
                            Confirmar Cobro
                        </button>
                        <button wire:click="closePaymentModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>