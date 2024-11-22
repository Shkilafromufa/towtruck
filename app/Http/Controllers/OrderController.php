<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderPhoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        Log::info('Запрос дошел до контроллера:', $request->all());
        // Проверка авторизации
        if (!Auth::check()) {
            return response()->json(['error' => 'Вы должны быть авторизованы для создания заказа.'], 401);
        }

        // Валидация входящих данных
        $validator = Validator::make($request->all(), [
            'user_location' => 'required|string',
            'issue_type' => 'required|string|max:255',
            'vehicle_type' => 'required|string|max:255',
            'comments' => 'nullable|string|max:500',
            'photos' => 'nullable|array',
            'photos.*' => 'file|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Возвращаем ошибки валидации
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Создание нового заказа
            $order = new Order();
            $order->user_id = Auth::id();
            $order->user_location = $request->input('user_location');
            $order->issue_type = $request->input('issue_type');
            $order->vehicle_type = $request->input('vehicle_type');
            $order->comments = $request->input('comments', '');
            $order->save();

            // Обработка фотографий, если они есть
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('uploads/orders', 'public');
                    $orderPhoto = new OrderPhoto();
                    $orderPhoto->order_id = $order->id;
                    $orderPhoto->photo_url = $path;
                    $orderPhoto->save();
                }
            }

            // Возвращаем успешный ответ
            return response()->json(['message' => 'Заказ успешно создан', 'order_id' => $order->id], 201);
        } catch (\Exception $e) {
            // Логируем ошибку
            \Log::error('Ошибка при создании заказа: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка сервера, попробуйте позже.'], 500);
        }
    }
public function getUserOrders()
{
    \Log::info('Метод getUserOrders вызван для пользователя', ['user_id' => Auth::id()]);

    try {
        $orders = Order::with('photos')->where('user_id', Auth::id())->get();

        if ($orders->isEmpty()) {
            \Log::info('Заказы не найдены для пользователя', ['user_id' => Auth::id()]);
            return response()->json(['orders' => []]);
        }

        \Log::info('Заказы получены успешно', ['orders' => $orders->toArray()]);
        return response()->json(['orders' => $orders]);
    } catch (\Exception $e) {
        \Log::error('Ошибка при получении заказов', [
            'user_id' => Auth::id(),
            'exception' => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Ошибка сервера'], 500);
    }
}


    public function getUserOrderStatuses()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Пользователь не авторизован'], 401);
        }
    
        try {
            $statuses = Order::where('user_id', Auth::user()->id)->select('id', 'status')->get();
            if ($statuses->isEmpty()) {
                return response()->json(['message' => 'Заказы не найдены'], 404);
            }
            return response()->json(['statuses' => $statuses]);
        } catch (\Exception $e) {
            \Log::error('Ошибка получения статусов заказов: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка сервера'], 500);
        }
    }
    

    public function getOrders()
    {
        $orders = Order::all();
        return response()->json(['orders' => $orders]);
    }
}
