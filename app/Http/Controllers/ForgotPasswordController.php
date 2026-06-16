<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Exception;

class ForgotPasswordController extends Controller
{
    private function cacheKey(string $email): string
    {
        return 'password_reset_otp_' . md5(strtolower($email));
    }

    public function sendOtp(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email tidak terdaftar.',
                ], 422);
            }

            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            Cache::put($this->cacheKey($request->email), Hash::make($otp), now()->addMinutes(10));

            Mail::to($user->email)->send(new OtpMail($user->name, $otp));

            return response()->json([
                'status'  => 'success',
                'message' => 'Kode OTP telah dikirim ke email kamu.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengirim OTP.',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email'                 => 'required|email',
                'otp'                   => 'required|string|size:4',
                'password'              => 'required|string|min:6|confirmed',
            ]);

            $cached = Cache::get($this->cacheKey($request->email));

            if (!$cached || !Hash::check($request->otp, $cached)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.',
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email tidak ditemukan.',
                ], 422);
            }

            $user->update(['password' => Hash::make($request->password)]);

            Cache::forget($this->cacheKey($request->email));

            return response()->json([
                'status'  => 'success',
                'message' => 'Password berhasil direset. Silakan login.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mereset password.',
            ], 500);
        }
    }
}
