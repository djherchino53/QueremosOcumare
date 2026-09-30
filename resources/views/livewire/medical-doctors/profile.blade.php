<?php
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\DoctorProfile;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?User $user = null;
    public $bio, $professional_id, $working_days = [], $working_hours = ['start' => '08:00', 'end' => '16:00'];
    public $name, $email, $phone, $dni;

    public function mount(User $user = null)
    {
        if ($user && $user->id) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin'])) {
                abort(403);
            }
            $this->user = $user;
        } else {
            $this->user = Auth::user();
        }

        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->dni = $this->user->dni;

        $profile = DoctorProfile::firstOrCreate(['user_id' => $this->user->id]);
        $this->bio = $profile->bio;
        $this->professional_id = $profile->professional_id;
        $this->working_days = $profile->working_days ?? [];
        $this->working_hours = $profile->working_hours ?? ['start' => '08:00', 'end' => '16:00'];
    }

    public function save()
    {
        $this->user->update([
            'phone' => $this->phone,
            'dni' => $this->dni,
        ]);

        $profile = DoctorProfile::firstOrCreate(['user_id' => $this->user->id]);
        $profile->update([
            'bio' => $this->bio,
            'professional_id' => $this->professional_id,
            'working_days' => $this->working_days,
            'working_hours' => $this->working_hours,
        ]);

        session()->flash('message', 'Perfil actualizado correctamente.');
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8 border-l-8 border-indigo-600 pl-4">Perfil Médico: {{ $name }}
        </h1>

        @if (session()->has('message'))
            <div
                class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 flex items-center gap-3 shadow-sm rounded-r-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">{{ session('message') }}</span>
            </div>
        @endif

        <form wire:submit="save" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Columna 1: Info Básica y Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center">
                        <div
                            class="h-24 w-24 rounded-full bg-indigo-600 flex items-center justify-center text-white text-4xl font-black uppercase mx-auto mb-4 shadow-lg ring-4 ring-indigo-50">
                            {{ substr($name, 0, 1) }}
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-gray-900">{{ $name }}</h2>
                        <p class="text-indigo-600 font-semibold text-sm mb-4 uppercase tracking-wider">Médico
                            Especialista
                        </p>

                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach($user->specialties as $s)
                                <span
                                    class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-indigo-100 italic">
                                    {{ $s->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-indigo-900 p-6 rounded-2xl shadow-xl">
                        <h3 class="font-bold text-indigo-200 uppercase text-xs tracking-widest mb-4">Información de
                            Contacto
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-1"><svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg></div>
                                <div>
                                    <p class="text-[10px] text-indigo-400 uppercase font-bold">Correo Electrónico</p>
                                    <p class="text-sm font-medium">{{ $email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: Información Profesional -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Información Profesional
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Reg.
                                Profesional</label>
                            <input type="text" wire:model="professional_id"
                                class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm"
                                placeholder="Ej: MPPS 123456">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">
                                Cédula</label>
                            <input type="text" wire:model="dni"
                                class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Teléfono</label>
                            <input type="text" wire:model="phone"
                                class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Resumen Profesional</label>
                            <textarea wire:model="bio" rows="6"
                                class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm"
                                placeholder="Experiencia profesional..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: Horario de Atención -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Horario de Atención
                    </h3>

                    <div class="space-y-4">
                        <span class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Días de
                            Atención</span>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach([
                                'monday' => 'Lunes', 
                                'tuesday' => 'Martes', 
                                'wednesday' => 'Miércoles', 
                                'thursday' => 'Jueves', 
                                'friday' => 'Viernes', 
                                'saturday' => 'Sábado'
                                //, 'sunday' => 'Domingo'
                                ] as $key => $label)
                                <label
                                    class="flex items-center gap-2 p-2 rounded-xl border border-gray-100 hover:bg-indigo-50 transition cursor-pointer group {{ in_array($key, $working_days) ? 'bg-indigo-50 border-indigo-200' : '' }}">
                                    <input type="checkbox" wire:model="working_days" value="{{ $key }}"
                                        class="rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3">
                                    <span
                                        class="text-[10px] font-semibold text-gray-600 group-hover:text-indigo-900 transition">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 gap-4 pt-4">
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Hora
                                    Inicio</label>
                                <input type="time" wire:model="working_hours.start"
                                    class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm uppercase text-[10px]">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-700 mb-1 tracking-tight uppercase">Hora
                                    Cierre</label>
                                <input type="time" wire:model="working_hours.end"
                                    class="w-full rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm uppercase text-[10px]">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de Guardado -->
                <div class="lg:col-span-3 flex justify-end pt-4">
                    <button type="submit"
                        class="px-8 py-3 rounded-xl transition text-white font-bold shadow-lg hover:shadow-indigo-200"
                        style="background-color: #4f46e5;">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>