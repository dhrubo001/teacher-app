<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use App\Support\AuthStorage;
use Carbon\Carbon;

new class extends Component {
    public string $day;
    public string $date;

    public array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    public array $weekDates = []; // day => date

    public array $timetable = [];
    public bool $loading = false;
    public string $name = 'Teacher';

    protected function isTodaySelected(): bool
    {
        return $this->date === now()->toDateString();
    }

    public function mount()
    {
        $this->name = AuthStorage::get('name') ?? 'Teacher';

        // Build current week dates (Mon–Sat)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        foreach ($this->days as $index => $day) {
            $this->weekDates[$day] = $startOfWeek->copy()->addDays($index)->toDateString();
        }

        // Default = today (fallback to monday)
        $today = strtolower(now()->format('l'));
        $this->day = in_array($today, $this->days) ? $today : 'monday';
        $this->date = $this->weekDates[$this->day];

        $this->fetchTimetable();
    }

    public function selectDay(string $day)
    {
        $this->day = $day;
        $this->date = $this->weekDates[$day];

        $this->fetchTimetable();
    }

    public function fetchTimetable()
    {
        $this->loading = true;

        try {
            $response = Http::withToken(AuthStorage::get('auth_token'))->post(config('services.school_api.url') . '/timetable', [
                'teacher_id' => AuthStorage::get('user_id'),
                'day' => $this->day,
                'date' => $this->date,
            ]);
            //dd($response->json());
            $this->timetable = $response->successful() ? $response->json() ?? [] : [];
        } catch (\Throwable $e) {
            report($e);
            $this->timetable = [];
        } finally {
            $this->loading = false;
        }
    }

    public function addHomework($periodId)
    {
        if (!$this->isTodaySelected()) {
            $this->toast('You can only add homework for today.', 'error');
            return;
        }

        return redirect()->route('teacher.add-homework', [
            'period_id' => $periodId,
            'date' => $this->date,
        ]);
    }

    protected function toast(string $message, string $type = 'error')
    {
        $this->dispatch('toast', compact('type', 'message'));
    }
};

?>

<div>

    <!-- Full Page Loader -->
    <div wire:teleport="body">
        <div wire:loading wire:target="selectDay"
            class="fixed inset-0 z-[9999] bg-white/80 backdrop-blur-sm pointer-events-auto">

            <!-- TRUE CENTER (safe-area proof) -->
            <div
                class="absolute top-1/2 left-1/2
                   -translate-x-1/2 -translate-y-1/2
                   flex flex-col items-center space-y-4">

                <div class="h-12 w-12 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>

                <p class="text-sm font-medium text-gray-700">
                    Loading timetable
                </p>
            </div>

        </div>
    </div>


    @livewire('header', [
        'title' => 'My Timetable',
        'showLogout' => true,
        'name' => $this->name,
        'showBack' => true,
    ])

    <div class="p-6">

        <h2 class="text-2xl font-bold mb-4">My Timetable</h2>

        @php
            $isTodaySelected = $this->day === strtolower(\Carbon\Carbon::now()->format('l'));
        @endphp

        <!-- Day Selector -->
        <div class="flex gap-2 mb-6 flex-wrap">
            @foreach ($days as $d)
                <button wire:click="selectDay('{{ $d }}')"
                    class="px-4 py-2 rounded
            {{ $day === $d ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">
                    {{ ucfirst($d) }}
                </button>
            @endforeach
        </div>

        <!-- Timetable -->
        @if (empty($timetable))
            <p class="text-gray-500">No classes scheduled.</p>
        @else
            <div class="space-y-4">
                @foreach ($timetable as $period)
                    <button wire:key="period-{{ $period['id'] }}"
                        @if ($this->isTodaySelected()) wire:click="addHomework({{ $period['id'] }})"
                    @else
                        disabled @endif
                        class="w-full text-left p-4 border rounded-lg bg-white shadow-sm
                    {{ $this->isTodaySelected()
                        ? 'hover:bg-indigo-50 hover:border-indigo-300 cursor-pointer'
                        : 'opacity-60 cursor-not-allowed' }}
                    transition flex justify-between items-center">

                        <div>
                            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                                Period {{ $period['period_number'] }} —
                                {{ $period['subject']['subject_name'] }}

                                {{-- Homework Tag --}}
                                @if ($period['homework_added'])
                                    <span
                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full
                                           text-xs font-semibold bg-green-100 text-green-700">
                                        Homework Added
                                    </span>
                                @endif
                            </h3>

                            <p class="text-sm text-gray-600">
                                Class: {{ $period['class']['name'] }}
                            </p>
                        </div>

                        <div class="text-sm text-gray-700 whitespace-nowrap text-right">
                            {{ $period['start_time'] }} – {{ $period['end_time'] }}
                        </div>
                    </button>
                @endforeach
            </div>
        @endif

    </div>
</div>
