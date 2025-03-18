<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $error_log = storage_path('logs/error.log');
        file_put_contents($error_log, "Login attempt: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        file_put_contents($error_log, "Data: " . json_encode($request->all()) . "\n", FILE_APPEND);

        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('auth-token')->plainTextToken;
                return response()->json([
                    'token' => $token,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'message' => 'Login realizado com sucesso'
                ]);
            }
            
            file_put_contents($error_log, "Invalid credentials\n", FILE_APPEND);
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        } catch (\Exception $e) {
            file_put_contents($error_log, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Logout realizado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no logout: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Erro ao realizar logout: ' . $e->getMessage()
            ], 500);
        }
    }
}