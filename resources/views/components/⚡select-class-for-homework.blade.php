<?php

use Livewire\Component;
use App\Support\AuthStorage;
use Illuminate\Support\Facades\Http;

new class extends Component {
    public $name;
    public $loading = false;
    public $token;
    public $classes = [];

    public function mount()
    {
        $this->loading = true;
        $this->token = AuthStorage::get('auth_token');
        $this->name = AuthStorage::get('name') ?? 'Teacher';
        $this->getClasses();
    }

    public function getClasses()
    {
        try {
            $response = Http::timeout(10)
                ->withToken($this->token)
                ->post(config('services.school_api.url') . '/classes-under-school');

            if ($response->failed()) {
                $this->addError('classes', 'Unable to reach server. Please try again.');
                return;
            }
            $this->classes = $response->json() ?? [];
        } catch (\Throwable $e) {
            report($e);
            $this->addError('classes', 'Something went wrong while loading classes.');
        } finally {
            $this->loading = false;
        }
    }
};
?>

<div>

    <div class="min-h-screen bg-gray-100 flex flex-col pointer-events-auto">

        @livewire('header', [
            'title' => 'Select Class',
            'showLogout' => true,
            'name' => $this->name,
            'showBack' => true,
        ])

        <!-- Loader -->
        @if ($loading)
            <div class="p-6 text-center text-gray-500">
                Loading classes...
            </div>
        @endif

        <!-- Class Cards -->
        @if (!empty($classes))

            <!-- GRID CONTAINER (OUTSIDE LOOP) -->
            <div class="grid grid-cols-1 gap-4 mt-5 mx-5">

                @foreach ($classes as $class)
                    <!-- Class Card -->
                    <button wire:navigate
                        href="{{ route('teacher.student-list-under-class', ['class_id' => $class['id']]) }}"
                        class="bg-white rounded-2xl shadow-md p-4 flex items-center justify-between
                           hover:shadow-lg transition active:scale-[0.98]">
                        <div class="flex items-center gap-4">
                            <!-- Icon -->
                            <div
                                class="h-12 w-12 flex items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                📘
                            </div>

                            <!-- Info -->
                            <div class="text-left">
                                <p class="font-semibold text-gray-800">
                                    {{ $class['name'] ?? 'Class' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $class['students_count'] ?? 0 }} Students
                                </p>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @endforeach
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                No classes found.
            </div>
        @endif

    </div>


</div>
