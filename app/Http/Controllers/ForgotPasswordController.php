<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Exception;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Reset link sent to your email.',
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => __($status),
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send reset link.',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token'                 => 'required|string',
                'email'                 => 'required|email',
                'password'              => 'required|string|min:6|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill(['password' => bcrypt($password)])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Password reset successfully. You can now login.',
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => __($status),
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reset password.',
            ], 500);
        }
    }
}
