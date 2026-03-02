<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use App\Support\AuthStorage;

new class extends Component {
    /* =========================
        STATE
    ========================== */

    public int $class_id;
    public string $class_name = 'Class';
    public string $name = 'Teacher';

    public array $students = [];
    public array $selectedParents = [];

    public bool $showModal = false;
    public bool $notifyAll = false;

    public string $message = '';

    /* =========================
        LIFECYCLE
    ========================== */

    public function mount($class_id)
    {
        $this->class_id = (int) $class_id;
        $this->name = AuthStorage::get('name') ?? 'Teacher';

        $this->loadStudents();
    }

    /* =========================
        DATA FETCH
    ========================== */

    public function loadStudents()
    {
        try {
            $token = AuthStorage::get('auth_token');

            if (!$token) {
                redirect()->route('teacher.login');
                return;
            }

            $response = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->post(config('services.school_api.url') . '/student-list-under-class', ['class_id' => $this->class_id]);

            if ($response->failed()) {
                $this->addError('server', 'Server error. Please try again later.');
                return;
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== true) {
                $this->addError('server', $data['message'] ?? 'Failed to fetch students.');
                return;
            }

            $this->class_name = $data['data']['classroom']['name'] ?? 'Class';
            $this->students = $data['data']['students'] ?? [];
        } catch (\Throwable $e) {
            report($e);
            $this->addError('server', 'Unable to load students.');
        }
    }

    /* =========================
        UI ACTIONS
    ========================== */

    public function openModal(bool $notifyAll)
    {
        if (!$notifyAll && empty($this->selectedParents)) {
            return; // UI already prevents this
        }

        $this->notifyAll = $notifyAll;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'message', 'notifyAll']);
    }

    /* =========================
        SEND NOTIFICATION
    ========================== */

    public function sendNotification()
    {
        if (trim($this->message) === '') {
            $this->addError('message', 'Message is required.');
            return;
        }

        try {
            $token = AuthStorage::get('auth_token');

            if (!$token) {
                redirect()->route('teacher.login');
                return;
            }

            $payload = [
                'type' => $this->notifyAll ? 'whole_class' : 'parents',
                'notification' => $this->message,
                'created_by' => AuthStorage::get('user_id'),
            ];

            if ($this->notifyAll) {
                $payload['class_id'] = $this->class_id;
            } else {
                $payload['parent_ids'] = $this->selectedParents;
            }

            //dd($payload);

            $response = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->post(config('services.school_api.url') . '/create-notification', $payload);

            if ($response->status() === 422) {
                // Validation errors
                $errors = $response->json('errors', []);
                $this->addError('server', 'Validation error');
                $this->resetAll();
                return;
            }

            if ($response->status() === 400) {
                // Business logic error
                $this->addError('server', $response->json('message', 'No valid recipients found.'));
                $this->resetAll();
                return;
            }

            if ($response->failed()) {
                // Any other failure (5xx, timeout, etc.)
                $this->addError('server', 'Failed to send notification. Please try again.');
                $this->resetAll();
                return;
            }

            // ✅ Success
            $data = $response->json();

            $this->resetAll();

            session()->flash('success', "Notification sent successfully to {$data['sent_to']} recipient(s).");
        } catch (\Throwable $e) {
            report($e);
            $this->addError('server', 'Something went wrong while sending notification.');
        }
    }

    public function resetAll()
    {
        $this->reset(['showModal', 'message', 'selectedParents', 'notifyAll']);
    }
};
?>


<div>

    {{-- Header --}}
    @livewire('header', [
        'title' => 'Notify Parents - ' . $this->class_name,
        'showLogout' => true,
        'name' => $this->name,
        'showBack' => true,
    ])

    <div class="px-4 pb-24 space-y-6">


        {{-- Success Message --}}
        @if (session()->has('success'))
            <div class="mt-5 mb-4 rounded-xl bg-green-50 border border-green-200 p-4 text-green-800">
                <p class="text-sm font-medium">
                    {{ session('success') }}
                </p>
            </div>
        @endif

        {{-- Error Message --}}
        @if ($errors->has('server'))
            <div class="mt-5 mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
                <p class="text-sm font-medium">
                    {{ $errors->first('server') }}
                </p>
            </div>
        @endif

        {{-- TOP NOTIFY BUTTON (LIVE SWITCH) --}}
        <div class="mt-5">

            {{-- 🔴 Notify Whole Class --}}
            @if (count($selectedParents) === 0)
                <button wire:click="openModal(true)"
                    class="w-full flex items-center justify-center gap-2
                           bg-gradient-to-r from-red-600 to-red-500
                           text-white py-3.5 rounded-2xl
                           font-semibold shadow-lg
                           active:scale-[0.98] transition">
                    🔔 <span>Notify Whole Class</span>
                </button>
            @endif

            {{-- 🔵 Notify Selected Parents --}}
            @if (count($selectedParents) > 0)
                <button wire:click="openModal(false)"
                    class="w-full flex items-center justify-center gap-2
                           bg-blue-600 hover:bg-blue-700
                           text-white py-3.5 rounded-2xl
                           font-semibold shadow-lg
                           active:scale-[0.98] transition">
                    🔔 <span>Notify Selected Parents ({{ count($selectedParents) }})</span>
                </button>
            @endif

        </div>

        {{-- Students & Parents --}}
        @foreach ($students as $student)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">

                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-2xl">
                    <p class="text-gray-900 font-semibold text-sm">
                        {{ $student['name'] }}
                    </p>
                </div>

                <div class="p-3 space-y-2">
                    @foreach ($student['parents'] as $parent)
                        <label wire:key="parent-{{ $parent['id'] }}"
                            class="flex items-center gap-4
               bg-gray-50 hover:bg-gray-100
               rounded-xl px-3 py-3
               transition cursor-pointer">

                            <input type="checkbox" wire:model.live="selectedParents" value="{{ $parent['id'] }}"
                                class="h-4 w-4 rounded border-gray-300
                      text-blue-600 focus:ring-blue-500">

                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $parent['name'] }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    📞 {{ $parent['phone'] }}
                                </p>
                            </div>
                        </label>
                    @endforeach

                </div>
            </div>
        @endforeach

        {{-- Modal --}}
        @if ($showModal)
            <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
                <div class="absolute inset-0 bg-black/50"></div>

                <div
                    class="relative bg-white w-full sm:max-w-md
                            rounded-t-3xl sm:rounded-3xl p-5">

                    <h2 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ $notifyAll ? 'Notify Whole Class' : 'Notify Parents' }}
                    </h2>

                    <textarea wire:model.defer="message" rows="4" class="w-full border rounded-xl p-3 text-sm"
                        placeholder="Type your message here..."></textarea>

                    @error('message')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-3 mt-5">
                        <button wire:click="closeModal" class="flex-1 bg-gray-100 py-3 rounded-xl">
                            Cancel
                        </button>

                        <button wire:click="sendNotification" class="flex-1 bg-green-600 text-white py-3 rounded-xl">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
