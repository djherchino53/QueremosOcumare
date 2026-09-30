<?php
use Livewire\Volt\Component;
use App\Models\CashMovement;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = date('Y-m-01'); // First day of current month
        $this->endDate = date('Y-m-d');
    }

    public function with()
    {
        $start = $this->startDate;
        $end = $this->endDate;

        $month = fn (string $column) => DB::getDriverName() === 'pgsql'
            ? "to_char($column, 'YYYY-MM')"
            : "DATE_FORMAT($column, '%Y-%m')";

        // Financial Data
        $finances = CashMovement::select(
            DB::raw($month('date') . ' as month'),
            DB::raw("SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END) as income"),
            DB::raw("SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END) as expense")
        )
            ->whereBetween('date', [$start, $end])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Doctor Performance
        $doctorStats = Appointment::select('users.name as doctor_name', DB::raw('count(*) as total'))
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->whereBetween('date', [$start, $end])
            ->where('status', 'completed')
            ->groupBy('users.name')
            ->orderBy('total', 'desc')
            ->get();

        // Patient Growth/Mobility
        $patientStats = Patient::select(DB::raw($month('created_at') . ' as month'),DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'financeData' => [
                'labels' => $finances->pluck('month'),
                'income' => $finances->pluck('income'),
                'expense' => $finances->pluck('expense'),
            ],
            'doctorData' => [
                'labels' => $doctorStats->pluck('doctor_name'),
                'totals' => $doctorStats->pluck('total'),
            ],
            'patientData' => [
                'labels' => $patientStats->pluck('month'),
                'totals' => $patientStats->pluck('total'),
            ],
            'summary' => [
                'totalIncome' => CashMovement::where('type', 'in')->whereBetween('date', [$start, $end])->sum('amount'),
                'totalExpense' => CashMovement::where('type', 'out')->whereBetween('date', [$start, $end])->sum('amount'),
                'totalAppointments' => Appointment::whereBetween('date', [$start, $end])->count(),
                'newPatients' => Patient::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->count(),
            ]
        ];
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reportes y Estadísticas</h1>
        <div class="flex gap-4 bg-white p-2 rounded-lg shadow-sm border border-gray-100">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-400 uppercase">Desde:</label>
                <input type="date" wire:model.live="startDate" class="text-sm border-gray-200 rounded-md py-1">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-400 uppercase">Hasta:</label>
                <input type="date" wire:model.live="endDate" class="text-sm border-gray-200 rounded-md py-1">
            </div>
            <button onclick="window.print()"
                class="px-3 bg-gray-100 hover:bg-gray-200 rounded-md transition text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Ingresos Totales</p>
            <p class="text-2xl font-black text-green-600">${{ number_format($summary['totalIncome'], 2) }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Egresos Totales</p>
            <p class="text-2xl font-black text-red-600">${{ number_format($summary['totalExpense'], 2) }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Citas en Periodo</p>
            <p class="text-2xl font-black text-indigo-600">{{ $summary['totalAppointments'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Nuevos Pacientes</p>
            <p class="text-2xl font-black text-amber-600">{{ $summary['newPatients'] }}</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Finance Chart -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Finanzas Mensuales (Ingresos vs Egresos)</h3>
            <div style="height: 300px;">
                <canvas id="financeChart"></canvas>
            </div>
        </div>

        <!-- Doctor Chart -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Atenciones por Médico (Citas Completadas)</h3>
            <div style="height: 300px;">
                <canvas id="doctorChart"></canvas>
            </div>
        </div>

        <!-- Patient Growth Chart -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Crecimiento de Pacientes (Nuevos Registros)</h3>
            <div style="height: 300px;">
                <canvas id="patientChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let financeChart, doctorChart, patientChart;

            function initCharts() {
                const financeCtx = document.getElementById('financeChart').getContext('2d');
                const doctorCtx = document.getElementById('doctorChart').getContext('2d');
                const patientCtx = document.getElementById('patientChart').getContext('2d');

                financeChart = new Chart(financeCtx, {
                    type: 'bar',
                    data: {
                        labels: @js($financeData['labels']),
                        datasets: [
                            {
                                label: 'Ingresos',
                                data: @js($financeData['income']),
                                backgroundColor: '#16a34a',
                                borderRadius: 5,
                            },
                            {
                                label: 'Egresos',
                                data: @js($financeData['expense']),
                                backgroundColor: '#dc2626',
                                borderRadius: 5,
                            }
                        ]
                    },
                    options: { maintainAspectRatio: false }
                });

                doctorChart = new Chart(doctorCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @js($doctorData['labels']),
                        datasets: [{
                            data: @js($doctorData['totals']),
                            backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                        }]
                    },
                    options: { maintainAspectRatio: false }
                });

                patientChart = new Chart(patientCtx, {
                    type: 'line',
                    data: {
                        labels: @js($patientData['labels']),
                        datasets: [{
                            label: 'Nuevos Pacientes',
                            data: @js($patientData['totals']),
                            borderColor: '#f59e0b',
                            backgroundColor: '#fef3c7',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { maintainAspectRatio: false }
                });
            }

            initCharts();

            Livewire.on('updated', () => {
                financeChart.destroy();
                doctorChart.destroy();
                patientChart.destroy();
                initCharts();
            });
        });
    </script>
</div>