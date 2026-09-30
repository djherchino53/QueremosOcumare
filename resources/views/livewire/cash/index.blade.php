<?php
use Livewire\Volt\Component;
use App\Models\CashMovement;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $amount, $type = 'in', $category = 'appointment', $description, $appointment_id, $doctor_id, $date;
    public $payment_method = 'Efectivo';
    public $payReference = '';
    public $isModalOpen = false;
    public $filterType = '';
    public $filterCategory = '';
    public $filterDate = '';
    public $filterMonth = '';
    public $filterYear = '';

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->filterDate = date('Y-m-d');
        $this->filterYear = date('Y');
        $this->filterMonth = date('m');
    }

    public function with()
    {
        $query = CashMovement::with(['appointment.patient', 'doctor', 'user'])
            ->where(function ($q) {
                $q->whereNull('appointment_id')
                  ->orWhereHas('appointment', function ($appQ) {
                      $appQ->where('payment_status', 'paid');
                  });
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }
        if ($this->filterDate) {
            $query->whereDate('date', $this->filterDate);
        }
        if ($this->filterMonth && !$this->filterDate) {
            $query->whereMonth('date', $this->filterMonth);
        }
        if ($this->filterYear && !$this->filterDate) {
            $query->whereYear('date', $this->filterYear);
        }

        $movements = $query->get();

        return [
            'movements' => $movements,
            'totalIn' => $movements->where('type', 'in')->sum('amount'),
            'totalOut' => $movements->where('type', 'out')->sum('amount'),
            'unpaidAppointments' => Appointment::where('payment_status', 'unpaid')->with('patient')->get(),
            'doctors' => User::where('role', 'doctor')->get(),
        ];
    }

    public function openModal($type = 'in')
    {
        $this->reset(['amount', 'description', 'appointment_id', 'doctor_id', 'payReference']);
        $this->type = $type;
        $this->payment_method = 'Efectivo';
        $this->date = date('Y-m-d');
        $this->category = ($type === 'in') ? 'appointment' : 'doctor_payment';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:in,out',
            'category' => 'required',
            'date' => 'required|date',
        ];

        if ($this->type === 'in') {
            $rules['payment_method'] = 'required|in:Efectivo,Pago Móvil,Transferencia';
        }

        $this->validate($rules);

        $methodLabel = ($this->type === 'in')
            ? ($this->payment_method . ($this->payReference ? " (Ref: {$this->payReference})" : ""))
            : null;

        $movement = CashMovement::create([
            'amount'         => $this->amount,
            'type'           => $this->type,
            'category'       => $this->category,
            'payment_method' => $methodLabel,
            'description'    => $this->description,
            'appointment_id' => $this->appointment_id ?: null,
            'doctor_id'      => $this->doctor_id ?: null,
            'user_id'        => Auth::id(),
            'date'           => $this->date,
        ]);

        if ($this->appointment_id && $this->type === 'in') {
            $appointment = Appointment::find($this->appointment_id);
            if ($appointment) {
                $appointment->update([
                    'payment_status' => 'paid',
                    'payment_method' => $methodLabel,
                ]);
            }
        }

        $this->closeModal();
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6 print:hidden">
        <h1 class="text-3xl font-bold text-gray-800">Caja y Finanzas</h1>
        <div class="flex gap-2">
            <button wire:click="openModal('in')"
                class="bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 transition text-white hover:text-white flex items-center gap-1"
                style="background-color: #16a34a;">
                Registrar Ingreso
            </button>
            <button wire:click="openModal('out')"
                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition font-bold shadow-sm">
                Registrar Egreso
            </button>
        </div>
    </div>

    <!-- Print Header (only visible when printing) -->
    <div class="hidden print:block mb-6 border-b pb-4">
        <h1 class="text-3xl font-bold text-gray-800">Reporte de Caja - Queremos Ocumare</h1>
        <p class="text-gray-600">
            @if($filterDate)
                Fecha: {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }}
            @elseif($filterMonth)
                Mes: {{ $filterMonth }}/{{ $filterYear }}
            @else
                Año: {{ $filterYear }}
            @endif
        </p>
    </div>

    <!-- Summary Bar -->
    <div class="flex items-center gap-8 mb-8 bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <div class="flex flex-col">
            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Ingresos</span>
            <span class="text-2xl font-black text-green-600">${{ number_format($totalIn, 2) }}</span>
        </div>
        <div class="h-10 w-px bg-gray-100"></div>
        <div class="flex flex-col">
            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Egresos</span>
            <span class="text-2xl font-black text-red-600">${{ number_format($totalOut, 2) }}</span>
        </div>
        <div class="h-10 w-px bg-gray-100"></div>
        <div class="flex flex-col">
            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Balance</span>
            <span class="text-2xl font-black text-indigo-600">${{ number_format($totalIn - $totalOut, 2) }}</span>
        </div>
    </div>

    <!-- Filters -->
    <div
        class="mb-6 flex flex-wrap gap-4 items-end bg-white p-4 rounded-lg shadow-sm border border-gray-100 print:hidden">
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Día Específico</label>
            <input type="date" wire:model.live="filterDate" class="rounded-md border-gray-200 text-sm">
        </div>

        @if(!$filterDate)
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Mes</label>
                <select wire:model.live="filterMonth" class="rounded-md border-gray-200 text-sm">
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
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Año</label>
                <select wire:model.live="filterYear" class="rounded-md border-gray-200 text-sm">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        @endif

        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Tipo</label>
            <select wire:model.live="filterType" class="rounded-md border-gray-200 text-sm">
                <option value="">Todos</option>
                <option value="in">Ingresos</option>
                <option value="out">Egresos</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Categoría</label>
            <select wire:model.live="filterCategory" class="rounded-md border-gray-200 text-sm">
                <option value="">Todas</option>
                <option value="appointment">Consulta/Cita</option>
                <option value="doctor_payment">Pago a Médico</option>
                <option value="other">Otro</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button wire:click="$set('filterDate', ''); $set('filterMonth', ''); $set('filterYear', '{{ date('Y') }}')"
                class="px-3 py-2 bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200 transition text-sm font-bold">
                Limpiar
            </button>
            <button onclick="window.print()"
                class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition text-sm font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v7" />
                </svg>
                Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Responsable</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($movements as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $m->date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-bold flex items-center gap-2">
                                        {{ $m->category === 'appointment' ? 'Consulta: ' . ($m->appointment->patient->name ?? 'N/A') :
                    ($m->category === 'doctor_payment' ? 'Pago Médico: ' . ($m->doctor->name ?? 'N/A') : $m->description) }}
                                        @if($m->payment_method)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                {{ $m->payment_method }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($m->description && $m->category !== 'other')
                                        <div class="text-xs text-gray-500">{{ $m->description }}</div>
                                    @endif
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ $m->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $m->type === 'in' ? '+' : '-' }} ${{ number_format($m->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $m->user->name }}</td>
                            </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                        <h3
                            class="text-xl font-bold text-gray-900 {{ $type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $type === 'in' ? 'Nuevo Ingreso' : 'Nuevo Egreso' }}
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Monto ($)</label>
                                <input type="number" step="0.01" wire:model="amount"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 border-gray-300">
                                @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
                                <input type="date" wire:model="date" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                            <select wire:model.live="category" class="w-full rounded-md border-gray-300 shadow-sm">
                                @if($type === 'in')
                                    <option value="appointment">Cobro de Consulta</option>
                                    <option value="other">Otro Ingreso</option>
                                @else
                                    <option value="doctor_payment">Pago a Médico</option>
                                    <option value="other">Otro Egreso</option>
                                @endif
                            </select>
                        </div>

                        @if($category === 'appointment')
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Cita Pendiente</label>
                                <select wire:model="appointment_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Seleccione Cita</option>
                                    @foreach($unpaidAppointments as $app)
                                        <option value="{{ $app->id }}">{{ $app->date->format('d/m') }} - {{ $app->patient->name }}
                                            (${{ $app->price }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($category === 'doctor_payment')
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Médico</label>
                                <select wire:model="doctor_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Seleccione Médico</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($type === 'in')
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Pago</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label class="flex flex-col items-center justify-center p-2.5 border rounded-lg cursor-pointer transition text-center {{ $payment_method === 'Efectivo' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                        <input type="radio" wire:model.live="payment_method" value="Efectivo" class="sr-only">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span class="text-xs">Efectivo</span>
                                    </label>

                                    <label class="flex flex-col items-center justify-center p-2.5 border rounded-lg cursor-pointer transition text-center {{ $payment_method === 'Pago Móvil' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                        <input type="radio" wire:model.live="payment_method" value="Pago Móvil" class="sr-only">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-xs">Pago Móvil</span>
                                    </label>

                                    <label class="flex flex-col items-center justify-center p-2.5 border rounded-lg cursor-pointer transition text-center {{ $payment_method === 'Transferencia' ? 'bg-indigo-50 border-indigo-600 text-indigo-700 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                                        <input type="radio" wire:model.live="payment_method" value="Transferencia" class="sr-only">
                                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                        </svg>
                                        <span class="text-xs">Transferencia</span>
                                    </label>
                                </div>
                                @error('payment_method') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            @if(in_array($payment_method, ['Pago Móvil', 'Transferencia']))
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nº Referencia (Opcional)</label>
                                    <input type="text" wire:model="payReference" placeholder="Ej: 123456"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                </div>
                            @endif
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción / Observación</label>
                            <textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Opcional..."></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="save"
                            class="w-full inline-flex justify-center items-center gap-1 rounded-md px-3 py-1 transition text-white font-semibold
                                                                {{ $type === 'in' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                            style="{{ $type === 'in' ? 'background-color: #16a34a;' : 'background-color: #dc2626;' }}">
                            Guardar Registro
                        </button>
                        <button wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>