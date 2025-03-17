<?php

namespace App\Http\Controllers;

use App\Models\TowTruckWorker;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:' . ($request->role == 'client' ? 'clients' : 'tow_truck_workers'),
            'password' => 'required|min:8',
            'email' => 'required|email|unique:' . ($request->role == 'client' ? 'clients' : 'tow_truck_workers'),
            'phone' => 'required|unique:' . ($request->role == 'client' ? 'clients' : 'tow_truck_workers'),
            'role' => 'required|in:client,towtruck',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $verificationCode = rand(1000, 9999);
        $cacheKey = 'register_' . md5($request->phone);
        Cache::put($cacheKey, [
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'verification_code' => $verificationCode
        ], now()->addMinutes(10));
        $this->sendSms($request->phone, "Ваш код подтверждения: $verificationCode");

        return response()->json(['message' => 'Пользователь зарегистрирован. Пожалуйста, подтвердите ваш номер телефона.'], 200);
    }

    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $cacheKey = 'register_' . md5($request->phone);
        $userData = Cache::get($cacheKey);
        if (!$userData) {
            return response()->json(['error' => 'Истекло время ожидания. Попробуйте зарегистрироваться снова.'], 400);
        }

        if ($userData['verification_code'] != $request->code) {
            return response()->json(['error' => 'Неверный код подтверждения'], 400);
        }

        $model = $userData['role'] == 'client' ? Client::class : TowTruckWorker::class;
        $user = $model::create([
            'username' => $userData['username'],
            'password' => $userData['password'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'is_active' => true,
            'role' => $userData['role'],
            'verification_code' => rand(1000, 9999),
        ]);

        Cache::forget($cacheKey);

        return response()->json(['message' => 'Номер телефона успешно подтвержден, учетная запись активирована.'], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $model = \App\Models\Client::where('username', $request->username)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;

        $user = $model::where('username', $request->username)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Неверное имя пользователя или пароль'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Пользователь не активирован'], 401);
        }

        $user->verification_code = rand(1000, 9999);
        $user->save();
        $this->sendSms($user->phone, "Ваш код для входа: " . $user->verification_code);

        return response()->json(['message' => 'Код подтверждения отправлен'], 200);
    }

    public function verifyLoginCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $model = \App\Models\Client::where('username', $request->username)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;

        $user = $model::where('username', $request->username)
                      ->where('verification_code', $request->code)
                      ->first();

        if (!$user) {
            return response()->json(['error' => 'Неверный код подтверждения или имя пользователя'], 401);
        }
        $user->verification_code = rand(1000, 9999);
        $user->save();
        $role = null;
        if (\App\Models\Client::where('id', $user->id)->exists()) {
            $role = 'client';
        } elseif (\App\Models\TowTruckWorker::where('id', $user->id)->exists()) {
            $role = 'towtruck';
        }

        if (!$role) {
            return response()->json(['error' => 'Не удалось определить роль'], 400);
        }

        $token = $user->createToken('auth_token', ['role' => $role])->plainTextToken;

        return response()->json([
            'message' => 'Успешный вход',
            'access_token' => $token,
            'role' => $user->role,
            'token_type' => 'Bearer',
        ]);
    }

    public function startPasswordRecovery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^7\d{10}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Неверный формат номера телефона. Он должен начинаться с 7 и содержать 10 цифр.'], 400);
        }
        $model = \App\Models\Client::where('phone', $request->phone)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;

        $user = $model::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'Такой номер не зарегистрирован.'], 404);
        }
        $user->verification_code = rand(1000, 9999);
        $user->save();
        $this->sendSms($user->phone, "Ваш код для восстановления пароля: " . $user->verification_code);

        return response()->json(['message' => 'Код отправлен на указанный номер телефона'], 200);
    }

    public function verifyRecoveryCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $model = \App\Models\Client::where('phone', $request->phone)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;

        $user = $model::where('phone', $request->phone)
                      ->where('verification_code', $request->code)
                      ->first();

        if (!$user) {
            return response()->json(['error' => 'Неверный код подтверждения или номер телефона'], 401);
        }

        return response()->json(['message' => 'Код подтвержден, вы можете изменить пароль'], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'newPassword' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $model = \App\Models\Client::where('phone', $request->phone)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;
        $user = $model::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        if (Hash::check($request->newPassword, $user->password)) {
            return response()->json(['error' => 'Новый пароль не должен совпадать с текущим'], 400);
        }
        $user->password = Hash::make($request->newPassword);
        $user->verification_code = rand(1000, 9999);
        $user->save();
        return response()->json(['message' => 'Пароль успешно обновлен'], 200);
    }

    public function resendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $model = \App\Models\Client::where('username', $request->username)->exists() ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;
        $user = $model::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $user->verification_code = rand(1000, 9999);
        $user->save();

        $this->sendSms($user->phone, "Ваш новый код подтверждения: " . $user->verification_code);

        return response()->json(['message' => 'Новый код подтверждения отправлен'], 200);
    }

    private function sendSms($phone, $message)
    {
        $isTestMode = true;

        if ($isTestMode) {
            $this->logMessage($message);
            return true;
        } else {
            $url = 'https://stepan.lego03@mail.ru:piq3n42lqqKnmyXT5HXLsv0kPJda@gate.smsaero.ru/v2/sms/send';
            $params = [
                'number' => $phone,
                'text' => $message,
                'sign' => 'SMS Aero',
            ];
            $client = new \GuzzleHttp\Client([
                'verify' => false,
            ]);

            $response = $client->get($url, [
                'query' => $params,
            ]);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            if ($statusCode === 200) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function logMessage($message)
    {
        \Log::info("Текст сообщения: {$message}");
    }

    public function getUser(Request $request)
    {
        if (\App\Models\Client::where('phone', $request->user()->phone)->exists()) {
            $model = \App\Models\Client::class;
            $role = 'client';
        } elseif (\App\Models\TowTruckWorker::where('phone', $request->user()->phone)->exists()) {
            $model = \App\Models\TowTruckWorker::class;
            $role = 'towtruck';
        } else {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $user = $model::where('phone', $request->user()->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $responseData = [
            'username' => $user->username,
            'role' => $role,
        ];
        \Log::info("Response Data: " . json_encode($responseData));

        return response()->json($responseData);
    }

    public function checkAuth(Request $request)
    {
        $user = $request->user();

        $model = $user->role == 'client' ? \App\Models\Client::class : \App\Models\TowTruckWorker::class;

        $user = $model::find($user->id);

        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        return response()->json([
            'message' => 'Пользователь авторизован',
            'user' => $user,
            'role' => $user->role,
        ], 200);
    }
}
