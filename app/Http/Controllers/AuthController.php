<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'password' => 'required|min:8',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = new User();
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role = $request->role;
        $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->save();

        // Отправка SMS кода
        $this->sendSmsViaMTSExolve($user->phone, $user->verification_code);

        return response()->json(['message' => 'Пользователь зарегистрирован. Пожалуйста, подтвердите ваш номер телефона.']);
    }

    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::where('phone', $request->phone)->where('verification_code', $request->code)->first();

        if (!$user) {
            return response()->json(['error' => 'Неверный код подтверждения'], 401);
        }

        $user->is_active = true;
        $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->save();

        return response()->json(['message' => 'Номер телефона успешно подтвержден']);
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

    $user = User::where('username', $request->username)->first();

    // Проверяем существование пользователя
    if (!$user) {
        return response()->json(['error' => 'Пользователь не найден'], 404);
    }

    // Перехешируем пароль, если нужно
    if (Hash::needsRehash($user->password)) {
        $user->password = Hash::make($user->password);
        $user->save();
    }

    // Проверяем пароль
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Неверный пароль'], 401);
    }

    // Проверяем активацию аккаунта
    if (!$user->is_active) {
        return response()->json(['error' => 'Аккаунт не активирован'], 403);
    }

    // Генерируем новый код подтверждения
    $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $user->save();

    // Отправляем SMS
    $this->sendSmsViaMTSExolve($user->phone, $user->verification_code);

    return response()->json([
        'message' => 'Код подтверждения отправлен',
        'userId' => $user->id
    ]);
}
public function verifyLoginCode(Request $request)

{
    $validator = Validator::make($request->all(), [
        'username' => 'required',
        'code' => 'required',
        
    ]);

    if ($validator->fails()) {
        \Log::error('Ошибка валидации:', $validator->errors()->toArray());
        return response()->json(['error' => $validator->errors()], 400);
    }

    $user = User::where('username', $request->username)
                ->where('verification_code', $request->code)
                ->first();

    if (!$user) {
        \Log::error('Пользователь не найден или код неверен', ['username' => $request->username, 'code' => $request->code]);
        return response()->json(['error' => 'Неверный код подтверждения'], 401);
    }

    $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $user->save();

    // Генерация токена с кастомным полем 'role' в payload
    $token = JWTAuth::fromUser($user, [
        'role' => $user->role // Добавляем роль в полезную нагрузку токена
    ]);

    // Логируем успешный запрос
    \Log::info('Токен сгенерирован успешно:', ['token' => $token]);

    return response()->json(['token' => $token]);
}




    public function startPasswordRecovery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'Номер телефона не найден'], 404);
        }

        $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->save();

        // Отправка SMS кода
        $this->sendSmsViaMTSExolve($user->phone, $user->verification_code);

        return response()->json(['message' => 'Код отправлен на указанный номер телефона']);
    }

    public function verifyRecoveryCode(Request $request)
{
    // Проверка входных данных
    $validator = Validator::make($request->all(), [
        'phone' => 'required|string',
        'code' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Проверка существования пользователя и кода
    $user = User::where('phone', $request->phone)
                ->where('verification_code', $request->code)
                ->first();

    if (!$user) {
        return response()->json(['error' => 'Неверный код подтверждения или номер телефона'], 400);
    }

    // Генерация нового кода
    $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $user->save();

    return response()->json(['message' => 'Код подтвержден']);
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

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'Номер телефона не найден'], 404);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Пароль успешно обновлен']);
    }
    public function resendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required', // или phone, если отправляете код на телефон
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $user = User::where('username', $request->username)->first(); // или по телефону
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        // Генерация нового кода подтверждения
        $user->verification_code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->save();
            // Отправляем SMS
    $this->sendSmsViaMTSExolve($user->phone, $user->verification_code);

    return response()->json([
        'message' => 'Код подтверждения отправлен',
        'userId' => $user->id
    ]);
    }
    private function sendSmsViaMTSExolve($phone, $code)
    {
        $client = new Client();
        $token = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJRV05sMENiTXY1SHZSV29CVUpkWjVNQURXSFVDS0NWODRlNGMzbEQtVHA0In0.eyJleHAiOjIwNDc1MjYwODQsImlhdCI6MTczMjE2NjA4NCwianRpIjoiZDdkYTNhNzktZmIzOS00ZDc5LWJmNTUtYTEzYTcyNWQ2Yzk5IiwiaXNzIjoiaHR0cHM6Ly9zc28uZXhvbHZlLnJ1L3JlYWxtcy9FeG9sdmUiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjAyMjFkZTktMTgxNy00NTFlLWE1ZDEtODdkN2M1ZWZjNmNjIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiNGI2NzFkZmQtNzJhMi00NGFjLTk0MmItNWYwODU2MWI4MzcyIiwic2Vzc2lvbl9zdGF0ZSI6ImIwZTc1ZWM4LTc1MTQtNDc3Ni1hZGFjLTM5YmNlZDc3MzI0ZSIsImFjciI6IjEiLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1leG9sdmUiLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJleG9sdmVfYXBwIHByb2ZpbGUgZW1haWwiLCJzaWQiOiJiMGU3NWVjOC03NTE0LTQ3NzYtYWRhYy0zOWJjZWQ3NzMyNGUiLCJ1c2VyX3V1aWQiOiIyZDdmY2ZlOC01MDczLTRjNWUtYWRhMi05ZGU2YTI3YjU1ZTkiLCJlbWFpbF92ZXJpZmllZCI6ZmFsc2UsImNsaWVudEhvc3QiOiIxNzIuMTYuMTYxLjE5IiwiY2xpZW50SWQiOiI0YjY3MWRmZC03MmEyLTQ0YWMtOTQyYi01ZjA4NTYxYjgzNzIiLCJhcGlfa2V5Ijp0cnVlLCJhcGlmb25pY2Ffc2lkIjoiNGI2NzFkZmQtNzJhMi00NGFjLTk0MmItNWYwODU2MWI4MzcyIiwiYmlsbGluZ19udW1iZXIiOiIxMjUwMDg0IiwiYXBpZm9uaWNhX3Rva2VuIjoiYXV0MDgyYTNmM2EtMGNjNy00YTc2LTk5YWQtMWFlMmQ3MTA0ZTlhIiwicHJlZmVycmVkX3VzZXJuYW1lIjoic2VydmljZS1hY2NvdW50LTRiNjcxZGZkLTcyYTItNDRhYy05NDJiLTVmMDg1NjFiODM3MiIsImN1c3RvbWVyX2lkIjoiNTg4MzAiLCJjbGllbnRBZGRyZXNzIjoiMTcyLjE2LjE2MS4xOSJ9.GuAXwLe5yQuN9RyLBZJriiVXBjMb2WAC4AiZwov9mtk16uewMhmyCXVeg7uT8615t5naJ_2KuGi4mEBTmbuXnSpeULNMROzWZ5vwVEgfPdiziPVkFbpJI5onjDoKmIxe1X0kXUB8nfS8oSp8J2VAuQXk93r6-_kmI3RmWNfpXP30f5Z9jMAPzs-yL_2KXFEvjJ-lCL3p9phyqcYmitzcYO5NsRTAlTvPWlRP7LLRwu7qpHdRCnYa1vUj4TVk_pjsxltSubzeQvNYAYA0N1qF9TTsOqEvOSjM2OKEUU18EGYJ4HQrc2uCgldgsth0zQRWdaVxXxFDm642WY1DO6fk2A';
    
        try {
            $response = $client->post('https://api.exolve.ru/messaging/v1/SendSMS', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => [
                    'number' => '79912008329',
                    'destination' => $phone,
                    'text' => 'Ваш код подтверждения: ' . $code
                ],
                'verify' => false // Отключаем проверку SSL для тестирования
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);
            \Log::info('SMS Response:', $responseData);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('SMS Error Details: ' . $e->getMessage());
            throw new \Exception('SMS sending failed: ' . $e->getMessage());
        }
    }
}
