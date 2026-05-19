<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * POST /api/forgot-password
     * Kirim OTP ke email
     */
    public function sendResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Hapus OTP lama milik email ini
        \DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->delete();

        // Buat OTP 6 digit
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \DB::table('password_reset_otps')->insert([
            'email'      => $request->email,
            'otp'        => $otp,
            'is_used'    => false,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kirim email OTP
        Mail::send([], [], function ($message) use ($request, $otp) {
            $message->to($request->email)
                ->subject('Kode OTP Reset Password GudangSafe')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                        <h2 style='color: #1B5E20;'>🌿 GudangSafe</h2>
                        <p>Halo,</p>
                        <p>Kami menerima permintaan reset password untuk akun GudangSafe Anda.</p>
                        <div style='background: #f1f8e9; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 14px; color: #555;'>Kode OTP Anda:</p>
                            <h1 style='letter-spacing: 8px; color: #1B5E20; margin: 10px 0;'>{$otp}</h1>
                            <p style='margin: 0; font-size: 12px; color: #999;'>Berlaku selama <strong>10 menit</strong></p>
                        </div>
                        <p style='font-size: 13px; color: #777;'>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                        <p style='font-size: 13px; color: #777;'>— Tim GudangSafe, Toko Pertanian Bumi Jaya Jember</p>
                    </div>
                ");
        });

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ]);
    }

    /**
     * POST /api/reset-password
     * Verifikasi OTP + reset password sekaligus
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $record = \DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid.',
            ], 422);
        }

        if (now()->isAfter($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kadaluarsa. Minta kode baru.',
            ], 422);
        }

        // Update password user
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        // Hapus record OTP
        \DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login kembali.',
        ]);
    }
}
