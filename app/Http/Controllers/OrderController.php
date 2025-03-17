<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\Car;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_location' => 'required',
            'issue_type' => 'required',
            'comments' => 'nullable',
            'car_model' => 'required|string',
            'car_year' => 'required|digits:4',
            'car_weight' => 'required|numeric',
            'car_reg_number' => 'required|string',
            'accident' => 'boolean',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = $request->user();

        \Log::info("Authenticated user data: ", ['id' => $user->id, 'role' => $user->role]);

        if (!$user || !$user->role) {
            \Log::error("Role is missing for user with ID: " . $user->id);
            return response()->json(['error' => 'Role is missing for user'], 400);
        }

        $car = Car::create([
            'model' => $request->car_model,
            'year' => $request->car_year,
            'weight' => $request->car_weight,
            'reg_number' => $request->car_reg_number,
        ]);

        $clientId = null;
        $towTruckWorkerId = null;

        if ($user->role == 'client') {
            $clientId = $user->id;
        } elseif ($user->role == 'towtruck') {
            $towTruckWorkerId = $user->id;
        } else {
            \Log::error("Invalid user role: " . $user->role . " for user with ID: " . $user->id);
            return response()->json(['error' => 'Invalid user role'], 400);
        }

        $order = Order::create([
            'user_location' => $request->user_location,
            'issue_type' => $request->issue_type,
            'comments' => $request->comments,
            'client_id' => $clientId,
            'tow_truck_worker_id' => $towTruckWorkerId,
            'status' => 'новый',
            'car_id' => $car->id,
            'accident' => $request->accident,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if ($photo->isValid()) {
                    $path = $photo->store('uploads', 'public');
                    OrderPhoto::create([
                        'order_id' => $order->id,
                        'photo_url' => $path,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Order created successfully', 'order_id' => $order->id], 200);
    }

    public function cancelOrder(Request $request, $orderId)
    {
        $user = $request->user();

        if ($user->role != 'client') {
            return response()->json(['message' => 'Only clients can cancel orders'], 403);
        }

        $order = Order::where('id', $orderId)
                      ->where('client_id', $user->id)
                      ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found or does not belong to you'], 404);
        }

        if (in_array($order->status, ['выполнен', 'отменен'])) {
            return response()->json(['message' => 'Order cannot be canceled as it is completed or canceled already'], 400);
        }

        $order->status = 'отменен';
        $order->save();

        return response()->json(['message' => 'Order was successfully canceled'], 200);
    }

    public function getCompletedOrders(Request $request)
    {
        $user = $request->user();

        if ($user->role != 'client') {
            return response()->json(['message' => 'Only clients can view completed orders'], 403);
        }

        $orders = Order::with('photos', 'car')
                       ->where('client_id', $user->id)
                       ->whereIn('status', ['выполнен', 'отменен'])
                       ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No completed or canceled orders found'], 200);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function getUserOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $role = $user->role ?? null;

        if (!$role) {
            return response()->json(['error' => 'Role not found for user'], 400);
        }

        \Log::info("Authenticated user role: " . $role);

        if ($role == 'client') {
            \Log::info("Fetching orders for client");

            $orders = Order::with('photos', 'car')
                           ->where('client_id', $user->id)
                           ->whereNotIn('status', ['отменен', 'выполнен'])
                           ->orderBy('created_at', 'desc')
                           ->get();
        } else {
            return response()->json(['message' => 'Only clients can view their orders'], 403);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function getUserOrderStatuses(Request $request)
    {
        $user = $request->user();

        $orders = Order::where($user->role == 'client' ? 'client_id' : 'tow_truck_worker_id', $user->id)
                       ->orderBy('created_at', 'desc')
                       ->get(['id', 'status']);

        return response()->json(['statuses' => $orders], 200);
    }

    public function getOrders(Request $request)
    {
        $orders = Order::with('photos', 'car', 'client')
                       ->whereIn('status', ['Новый'])
                       ->orderBy('created_at', 'desc')
                       ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No new orders found'], 200);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function acceptOrder($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (auth()->user()->role !== 'towtruck') {
            return response()->json(['message' => 'Only towtruck users can accept orders'], 403);
        }

        $order->status = 'Принят исполнителем';
        $order->tow_truck_worker_id = auth()->user()->id;
        $order->updated_at = now();
        $order->save();

        return response()->json(['message' => 'Order accepted successfully', 'order' => $order], 200);
    }

    public function getAcceptedOrders(Request $request)
    {
        $orders = Order::with('photos', 'car', 'client')
                       ->where('tow_truck_worker_id', $request->user()->id)
                       ->whereIn('status', ['Принят исполнителем'])
                       ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No accepted orders found'], 200);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function completeOrder($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->tow_truck_worker_id !== auth()->user()->id) {
            return response()->json(['message' => 'This order is not assigned to you'], 403);
        }

        $order->status = 'Выполнен';
        $order->updated_at = now();
        $order->save();

        return response()->json(['message' => 'Order completed successfully', 'order' => $order], 200);
    }

    public function getTowTruckCompletedOrders(Request $request)
    {
        $orders = Order::with('photos', 'car', 'client')
                       ->where('tow_truck_worker_id', $request->user()->id)
                       ->whereIn('status', ['Выполнен'])
                       ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No completed orders found'], 200);
        }

        return response()->json(['orders' => $orders], 200);
    }
}
