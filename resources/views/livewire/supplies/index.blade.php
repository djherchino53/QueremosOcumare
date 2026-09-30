<?php
use Livewire\Volt\Component;
use App\Models\Supply;
use App\Models\Patient;
use App\Models\SupplyMovement;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $name, $description, $quantity, $expiration_date;
    public $supplyId;
    public $isModalOpen = false;
    public $isEditMode = false;
    public $search = '';

    // Propiedades para Despacho a Paciente
    public $isDispatchModalOpen = false;
    public $dispatch_patient_id;
    public $dispatch_quantity;
    public $dispatch_description;
    public $selectedSupply;

    public function with()
    {
        return [
            'suppliesList' => Supply::whereLike('name', '%' . $this->search . '%')
            ->orWhereLike('description', '%' . $this->search . '%')
            ->get(),
            'recentMovements' => SupplyMovement::with(['supply', 'patient', 'user'])->orderBy('date', 'desc')->latest()->take(10)->get(),
            'patients' => Patient::orderBy('name')->get(),
        ];
    }

    public function rules()
    {
        if ($this->isDispatchModalOpen) {
            return [
                'dispatch_patient_id' => 'required|exists:patients,id',
                'dispatch_quantity' => 'required|integer|min:1|max:' . ($this->selectedSupply->quantity ?? 0),
            ];
        }
        return [
            'name' => 'required|min:3',
            'quantity' => 'required|integer|min:0',
            'expiration_date' => 'required|date',
        ];
    }

    // Ingresar insumos: admins y farmacia. Editar y eliminar: solo admins.
    private function authorizeRoles(array $roles): void
    {
        abort_unless(in_array(Auth::user()->role, $roles), 403);
    }

    public function openModal()
    {
        $this->authorizeRoles(['super_admin', 'admin', 'pharmacist']);
        $this->reset(['name', 'description', 'quantity', 'expiration_date', 'supplyId', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->isDispatchModalOpen = false;
    }

    public function edit($id)
    {
        $this->authorizeRoles(['super_admin', 'admin']);
        $supply = Supply::find($id);
        $this->supplyId = $supply->id;
        $this->name = $supply->name;
        $this->description = $supply->description;
        $this->quantity = $supply->quantity;
        $this->expiration_date = $supply->expiration_date ? $supply->expiration_date->format('Y-m-d') : null;
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function openDispatch($id)
    {
        $this->selectedSupply = Supply::find($id);
        $this->supplyId = $id;
        $this->reset(['dispatch_patient_id', 'dispatch_quantity', 'dispatch_description']);
        $this->isDispatchModalOpen = true;
    }

    public function save()
    {
        $this->authorizeRoles($this->isEditMode ? ['super_admin', 'admin'] : ['super_admin', 'admin', 'pharmacist']);
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'expiration_date' => $this->expiration_date,
        ];

        if ($this->isEditMode) {
            $supply = Supply::find($this->supplyId);
            $diff = $this->quantity - $supply->quantity;
            
            $supply->update($data);

            // Registrar movimiento de ajuste si cambió la cantidad
            if ($diff != 0) {
                SupplyMovement::create([
                    'supply_id' => $supply->id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'quantity' => abs($diff),
                    'reason' => 'Ajuste manual por edición',
                    'user_id' => Auth::id(),
                    'date' => now(),
                ]);
            }
        } else {
            $supply = Supply::create($data);
            // Registrar entrada inicial
            SupplyMovement::create([
                'supply_id' => $supply->id,
                'type' => 'in',
                'quantity' => $this->quantity,
                'reason' => 'Entrada inicial',
                'user_id' => Auth::id(),
                'date' => now(),
            ]);
        }

        $this->closeModal();
    }

    public function saveDispatch()
    {
        $this->validate();

        $supply = Supply::find($this->supplyId);
        
        // Descontar stock
        $supply->decrement('quantity', $this->dispatch_quantity);

        // Registrar entrega
        SupplyMovement::create([
            'supply_id' => $supply->id,
            'patient_id' => $this->dispatch_patient_id,
            'type' => 'out',
            'quantity' => $this->dispatch_quantity,
            'reason' => 'Entrega a paciente',
            'description' => $this->dispatch_description,
            'user_id' => Auth::id(),
            'date' => now(),
        ]);

        $this->closeModal();
        session()->flash('message', 'Entrega registrada exitosamente.');
    }

    // Propiedades para ver detalles de movimiento
    public $isViewMovementModalOpen = false;
    public $selectedMovement;

    public function viewMovement($id)
    {
        $this->selectedMovement = SupplyMovement::with(['supply', 'patient', 'user'])->find($id);
        $this->isViewMovementModalOpen = true;
    }

    public function delete($id)
    {
        $this->authorizeRoles(['super_admin', 'admin']);
        Supply::find($id)->delete();
    }
};
?>

<div>
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-xl md:text-3xl font-bold text-gray-800">Control de Insumos - Farmacia</h1>
        <div class="flex gap-2">
            <input wire:model.live="search" type="text" placeholder="Buscar insumo"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'pharmacist']))
                <button wire:click="openModal"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                    Ingresar Insumo
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto border border-gray-200">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripcion del Insumo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Vencimiento</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($suppliesList as $supply)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-bold">{{ $supply->name }}</div>
                            <div class="text-xs text-gray-500">{{ Str::limit($supply->description, 30) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $supply->quantity < 10 ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                            @if($supply->quantity <> 0) 
                                {{ $supply->quantity }}
                            @endif
                            
                            {{-- cantidad baja --}}
                            
                            @if($supply->quantity == 0) 
                                <span class="bg-red-100 text-red-800 text-xs px-2 rounded-full absolute ml-2">Sin Existencia</span> 
                            @elseif($supply->quantity < 10) 
                                <span class="bg-red-100 text-red-800 text-xs px-2 rounded-full absolute ml-2">Bajo</span> 
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($supply->expiration_date)
                                <div class="{{ $supply->expiration_date->isPast() ? 'text-red-600 font-bold' : ($supply->expiration_date->diffInDays(now()) < 30 ? 'text-orange-600' : 'text-green-600') }}">
                                    {{ $supply->expiration_date->format('d/m/Y') }}
                                </div>

                                @if($supply->expiration_date->isPast()) 
                                    <span class="bg-red-100 text-red-800 text-xs px-2 rounded-full absolute ml-2">Vencido</span> 
                                @endif

                            @else 
                            - 
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button wire:click="openDispatch({{ $supply->id }})" 
                                class="bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 transition text-white hover:text-white" 
                                style="background-color: #16a34a;">
                                Entregar a Paciente
                            </button>
                            @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                                <button wire:click="edit({{ $supply->id }})" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $supply->id }})" 
                                        wire:confirm="¿Estás seguro?" 
                                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-900 p-2 -m-2 rounded-lg hover:bg-red-50"
                                        title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"></path>
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Historial de Movimientos -->
    <div class="mt-12 bg-white rounded-lg shadow overflow-x-auto border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-base md:text-xl font-bold text-gray-800">Recientes Movimientos de Farmacia</h2>
            <span class="text-xs text-gray-500 uppercase tracking-widest font-bold">Registro de Controles</span>
        </div>
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripcion del Insumo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente/Motivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                @foreach($recentMovements as $movement)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $movement->date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $movement->supply->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $movement->type == 'in' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $movement->type == 'in' ? 'ENTRADA' : 'SALIDA' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-bold">{{ $movement->quantity }}</td>
                        <td class="px-6 py-4 max-w-xs truncate">
                            @if($movement->patient)
                                <div class="text-indigo-600 font-bold">Paciente: {{ $movement->patient->name }}</div>
                                <div class="text-xs text-gray-500">{{ $movement->description }}</div>
                            @else
                                <div class="text-gray-600 font-medium">{{ $movement->reason }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $movement->user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <button wire:click="viewMovement({{ $movement->id }})" class="p-1 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal Detalle de Movimiento / Constancia -->
    @if($isViewMovementModalOpen && $selectedMovement)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('isViewMovementModalOpen', false)"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="p-8" id="printable-constancia">
                    <div class="text-center mb-8">
                        <h2 class="text-lg md:text-2xl font-bold text-gray-900">CONSTANCIA DE MOVIMIENTO</h2>
                        <p class="text-gray-500">Queremos Ocumare - Gestión Médica</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8 text-sm">
                        <div>
                            <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Fecha</p>
                            <p class="font-bold text-gray-800">{{ $selectedMovement->date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Tipo de Movimiento</p>
                            <p class="font-bold text-{{ $selectedMovement->type == 'in' ? 'blue' : 'orange' }}-600 uppercase">
                                {{ $selectedMovement->type == 'in' ? 'Entrada / Adquisición' : 'Salida / Entrega' }}
                            </p>
                        </div>
                        <div class="col-span-2 border-t pt-4">
                            <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Insumo</p>
                            <p class="font-bold text-gray-800 text-lg">{{ $selectedMovement->supply->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Cantidad</p>
                            <p class="font-bold text-gray-800 text-lg">{{ $selectedMovement->quantity }} unidades</p>
                        </div>
                        <div>
                            <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Registrado por</p>
                            <p class="font-bold text-gray-800">{{ $selectedMovement->user->name }}</p>
                        </div>
                    </div>

                    @if($selectedMovement->patient)
                    <div class="bg-indigo-50 p-4 rounded-lg mb-8">
                        <p class="text-indigo-400 uppercase tracking-widest font-bold text-xs mb-2">Información del Paciente</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Nombre Completo</p>
                                <p class="font-bold text-gray-800">{{ $selectedMovement->patient->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Cédula</p>
                                <p class="font-bold text-gray-800">{{ $selectedMovement->patient->dni }}</p>
                            </div>
                        </div>
                        @if($selectedMovement->description)
                        <div class="mt-4">
                            <p class="text-xs text-gray-500">Observaciones/Descripción</p>
                            <p class="italic text-gray-700">"{{ $selectedMovement->description }}"</p>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="bg-gray-50 p-4 rounded-lg mb-8">
                        <p class="text-gray-400 uppercase tracking-widest font-bold text-xs">Motivo / Razón</p>
                        <p class="text-gray-800">{{ $selectedMovement->reason }}</p>
                    </div>
                    @endif

                    <div class="mt-12 flex justify-between gap-4 print:hidden">
                        <button onclick="window.print()" class="flex-1 bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h8z"></path></svg>
                            Imprimir Constancia
                        </button>
                        <button wire:click="$set('isViewMovementModalOpen', false)" class="px-6 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        @media print {
            body * { visibility: hidden; }
            #printable-constancia, #printable-constancia * { visibility: visible; }
            #printable-constancia { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
            .print\:hidden { display: none !important; }
        }
    </style>
    @endif


    <!-- Modal Entrada/Edición -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ $isEditMode ? 'Ajustar Insumo' : 'Entrada de Insumo' }}
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                                <textarea wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" wire:model="quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fecha Vencimiento</label>
                                    <input type="date" wire:model="expiration_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('expiration_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="save" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Guardar</button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Despacho a Paciente -->
    @if($isDispatchModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 font-bold text-green-700">
                            Constancia de Entrega a Paciente
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">Insumo: <span class="font-bold text-gray-800">{{ $selectedSupply->name }}</span> (Stock disponible: {{ $selectedSupply->quantity }})</p>
                        
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Paciente</label>
                                <select wire:model="dispatch_patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Seleccione un paciente</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->name }} - {{ $patient->dni }}</option>
                                    @endforeach
                                </select>
                                @error('dispatch_patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cantidad a Entregar</label>
                                <input type="number" wire:model="dispatch_quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('dispatch_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descripción de la Entrega (Observaciones)</label>
                                <textarea wire:model="dispatch_description" rows="3" placeholder="Ej: Entrega mensual de medicamentos..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                        <button wire:click="saveDispatch" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm transition animate-pulse" style="background-color: #16a34a;">
                            Registrar Entrega
                        </button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>