<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Native\Laravel\Facades\SecureStorage;
use App\Support\AuthStorage;

new class extends Component {
    public $name;

    public function mount()
    {
        $this->name = AuthStorage::get('name') ?? 'Teacher';
    }

    public function logout()
    {
        try {
            $token = AuthStorage::get('auth_token');

            if (!$token) {
                return redirect()->route('teacher.login');
            }
            sleep(2);
            $response = Http::timeout(10)
                ->withToken($token) // ← Bearer token here
                ->post(config('services.school_api.url') . '/logout');

            // Optional: API may return 204 (no content)
            if ($response->failed() && $response->status() !== 204) {
                $this->addError('email', 'Logout failed. Try again.');
                return;
            }

            // Clear local secure storage
            AuthStorage::forget('auth_token');
            AuthStorage::forget('school_id');
            AuthStorage::forget('name');

            return redirect()->route('teacher.login');
        } catch (\Throwable $e) {
            $this->addError('email', 'Unable to connect to server. Try again.');
        }
    }

    public function goToSelectClass()
    {
        return redirect('/select-class-for-homework');
    }

    public function goToMyClasses()
    {
        return redirect('/timetable');
    }
};
?>

<div class="relative">



    <!-- 📱 Main App -->
    <div class="min-h-screen bg-gray-100 flex flex-col pointer-events-auto">

        <!-- Top Header -->
        @livewire('header', [
            'title' => 'Dashboard',
            'showLogout' => true,
            'name' => $this->name,
            'showBack' => false,
        ])



        <!-- Main Content -->
        <main class="flex-1 p-4 space-y-4 pb-20">

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Classes</p>
                    <p class="text-2xl font-bold text-gray-800">5</p>
                </div>

                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Students</p>
                    <p class="text-2xl font-bold text-gray-800">120</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-6">

                <!-- Actions Section -->
                <div class="bg-white rounded-xl shadow divide-y">

                    <button wire:click="goToSelectClass"
                        class="w-full flex items-center gap-3 px-4 py-4 hover:bg-gray-50">
                        <div
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                            📚
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-800">Add Homework</p>
                            <p class="text-xs text-gray-500">Notify parents instantly</p>
                        </div>
                    </button>

                    <button class="w-full flex items-center gap-3 px-4 py-4 hover:bg-gray-50">
                        <div
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-green-100 text-green-600">
                            📝
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-800">Add Classwork</p>
                            <p class="text-xs text-gray-500">Daily class updates</p>
                        </div>
                    </button>

                    <button class="w-full flex items-center gap-3 px-4 py-4 hover:bg-gray-50">
                        <div
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
                            📢
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-800">General Notice</p>
                            <p class="text-xs text-gray-500">Announcements</p>
                        </div>
                    </button>
                </div>

                <!-- My Classes Section -->
                <div>
                    <div class="flex items-center gap-2 mb-3 px-1">
                        <span class="text-lg">🏫</span>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            My Classes
                        </h3>
                    </div>

                    <div class="bg-white rounded-xl shadow divide-y">
                        <button wire:click="goToMyClasses"
                            class="w-full flex items-center gap-3 px-4 py-4 hover:bg-gray-50 cursor-pointer">
                            <div
                                class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                👩‍🏫
                            </div>

                            <div class="text-left">
                                <p class="font-medium text-gray-800">View Assigned Classes</p>
                                <p class="text-xs text-gray-500">Classes you teach today</p>
                            </div>
                        </button>
                    </div>
                </div>

            </div>


        </main>

        <!-- Bottom Navigation -->
        <nav class="bg-white border-t flex justify-around py-2 fixed bottom-0 inset-x-0 z-40">

            <button class="flex flex-col items-center gap-1 text-indigo-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 9.75L12 4l9 5.75V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.75z" />
                </svg>
                <span class="text-xs">Home</span>
            </button>

            <button class="flex flex-col items-center gap-1 text-gray-500 hover:text-indigo-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v5l3 3M12 3a9 9 0 100 18 9 9 0 000-18z" />
                </svg>
                <span class="text-xs">History </span>
            </button>

            <button class="flex flex-col items-center gap-1 text-gray-500 hover:text-indigo-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5.121 17.804A9 9 0 1118.88 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs">Profile</span>
            </button>

        </nav>

    </div>
</div>
