<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Kreait\Firebase\Factory;

class OtpAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.otp-login');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'idToken' => 'required'
        ]);

        try {
            $factory = (new Factory)
                ->withServiceAccount(config('firebase.credentials'));

            $auth = $factory->createAuth();

            /**
             * ✅ IMPORTANT FIX
             * Second argument = true
             * This enables strict-but-compatible verification
             */
            $verifiedToken = $auth->verifyIdToken($request->idToken, true);

            $firebaseUid = $verifiedToken->claims()->get('sub');
            $phone       = $verifiedToken->claims()->get('phone_number');

            if (!$phone) {
                throw new \Exception('Phone number missing in token');
            }

            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseUid],
                ['phone' => $phone]
            );

            Auth::login($user);

            return response()->json([
                'success'  => true,
                'redirect' => route('dashboard')
            ]);
        } catch (\Throwable $e) {

            // TEMP: return real error for debugging
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 401);
        }
    }

    public function dashboard()
    {
        return view('auth.dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('otp.login');
    }
}
