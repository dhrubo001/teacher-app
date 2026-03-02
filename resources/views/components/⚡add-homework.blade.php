<?php

use Livewire\Component;
use App\Support\AuthStorage;

new class extends Component {
    public $period_id;
    public $name;
    public $class_id;

    public $period = [];

    public string $homework = '';
    public string $due_date = '';

    public function mount($periodId)
    {
        $this->period_id = $periodId;
        $this->name = AuthStorage::get('name') ?? 'Teacher';
        $this->getPeriodDetails();
    }

    public function getPeriodDetails()
    {
        try {
            $response = Http::timeout(10)
                ->withToken(AuthStorage::get('auth_token'))
                ->acceptJson()
                ->post(config('services.school_api.url') . '/class-timetable-period-id', [
                    'period_id' => $this->period_id,
                ]);

            if ($response->json('status') === 'false' || $response->failed()) {
                $message = $response->json('message') ?? 'Unable to fetch period details.';

                // 🔥 Dispatch toast
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => $message,
                ]);

                $this->period = [];
                return;
            }
            //dd($response->json());
            $this->period = $response->json('data') ?? [];
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Something went wrong. Please try again.',
            ]);

            $this->period = [];
        } finally {
        }
    }

    public function goBack()
    {
        return redirect()->route('teacher.timetable');
    }

    public function saveHomework()
    {
        try {
            $response = Http::timeout(10)
                ->withToken(AuthStorage::get('auth_token'))
                ->acceptJson()
                ->post(config('services.school_api.url') . '/add-homework', [
                    'class_id' => $this->period['class_id'],
                    'period_id' => $this->period['id'],
                    'teacher_id' => AuthStorage::get('user_id'),
                    'subject_id' => $this->period['subject_id'],
                    'homework' => $this->homework,
                    'due_date' => $this->due_date,
                ]);

            if ($response->failed() || $response->json('status') === false) {
                $errors = $response->json('errors');

                if (is_array($errors)) {
                    $firstFieldErrors = collect($errors)->first();
                    $message = is_array($firstFieldErrors) ? $firstFieldErrors[0] : 'Validation error.';
                } else {
                    $message = $response->json('message') ?? 'Unable to save homework.';
                }

                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => $message,
                ]);

                return;
            }

            // Success toast
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Homework saved and parents notified!',
            ]);

            $this->dispatch('redirect-after-toast', [
                'url' => route('teacher.timetable'),
                'delay' => 2000,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }
};
?>
<div>

    @livewire('header', [
        'title' => 'Add Homework For ' . ($this->period['class']['name'] ?? '') . ' - ' . ($this->period['subject']['subject_name'] ?? ''),
        'showLogout' => true,
        'name' => $this->name,
        'showBack' => true,
    ])

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-8">

        <!-- Page Heading -->
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-900">
                Add Homework
            </h2>
            <p class="text-sm text-gray-500">
                Assign homework and notify parents
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm
                   p-5 sm:p-6 space-y-6">

            <!-- Homework Input -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                    Homework Details
                </label>

                <textarea wire:model.defer="homework" rows="4"
                    placeholder="Example: Complete exercise 5 from the Mathematics textbook"
                    class="w-full rounded-xl border border-gray-300
                           px-4 py-3 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           resize-none"></textarea>

                @error('homework')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Due Date -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                    Due Date
                </label>

                <input type="date" wire:model.defer="due_date" min="{{ now()->toDateString() }}"
                    class="w-full rounded-xl border border-gray-300
                           px-4 py-3 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />

                @error('due_date')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Divider -->
            <div class="pt-5 border-t border-gray-200 flex flex-col sm:flex-row gap-3">

                <!-- Primary -->
                <button wire:click="saveHomework" wire:loading.attr="disabled" wire:target="saveHomework"
                    class="w-full sm:flex-1 inline-flex items-center justify-center gap-2
           bg-indigo-600 text-white py-3 rounded-xl font-medium text-sm
           hover:bg-indigo-700 transition
           disabled:opacity-60">

                    <svg wire:loading.remove wire:target="saveHomework" xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>

                    <span wire:loading.remove wire:target="saveHomework">
                        Save Homework
                    </span>

                    <span wire:loading wire:target="saveHomework">
                        Saving…
                    </span>
                </button>

                <!-- Secondary -->
                <button wire:click="goBack" type="button"
                    class="w-full sm:flex-1 inline-flex items-center justify-center
                           bg-gray-100 text-gray-700 py-3 rounded-xl font-medium text-sm
                           hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </div>

    </div>

</div>
