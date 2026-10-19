<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use App\Models\Setting;
use App\Rules\TurnstileToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $ip = $request->ip();

        // ----------------------------------------------- uma conta por IP
        // Defesa contra criação de contas em massa, que é o ataque número um
        // contra faucet: o bot cria 500 contas e reclama em todas.
        //
        // CUIDADO: no Brasil as operadoras usam CGNAT, então várias pessoas
        // reais aparecem como o mesmo IP. O padrão é 3 contas por IP — suba
        // o valor em Configurações se usuários legítimos forem bloqueados.
        $maxAccountsPerIp = (int) Setting::getValue('max_accounts_per_ip', '3');

        if ($maxAccountsPerIp > 0) {
            $accountsFromThisIp = User::where('registration_ip', $ip)->count();

            if ($accountsFromThisIp >= $maxAccountsPerIp) {
                return response()->json([
                    'message' => 'Já existem muitas contas criadas nesta conexão. Se você divide a internet com outras pessoas, fale com o suporte.',
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:6',
            'referred_by' => 'nullable|string|exists:users,referral_code',
            'cf-turnstile-response' => ['required', new TurnstileToken($ip)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'wallet_address' => $request->wallet_address,
            'email' => $request->email,
            // O model User tem o cast 'password' => 'hashed'. Passamos a senha
            // em texto puro e deixamos o cast fazer o hash — assim eliminamos
            // o risco de hash duplo, que deixaria o login impossível.
            'password' => $request->password,
            'referral_code' => Str::random(10),
            'referred_by' => $request->referred_by,
            'registration_ip' => $ip,
            'last_login_ip' => $ip,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login a user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('wallet_address', $request->wallet_address)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user->update(['last_login_ip' => $request->ip()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Login an admin.
     */
    public function adminLogin(Request $request)
    {
        // A porta do cofre pede captcha desde a primeira tentativa.
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'cf-turnstile-response' => ['required', new TurnstileToken($request->ip())],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // O segundo parâmetro é a habilidade do token. É ela que o middleware
        // EnsureAuthenticatedAsAdmin confere na camada 2. Sem isso, qualquer
        // token de admin cairia no 403.
        $token = $admin->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request)
    {
        // Este endpoint é usado tanto por usuários quanto por administradores
        // (o front-end chama o mesmo caminho nos dois casos). Por isso ele
        // fica fora dos middlewares auth.user e auth.admin.
        //
        // Apagamos somente o token atual, nunca todos os tokens do dono.
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}

