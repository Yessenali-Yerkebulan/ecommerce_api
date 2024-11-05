<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateOrderStatusJob;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function create(Request $request){
        PaymentMethod::create($request->all());
    }

    public function checkout(Request $request)
    {
        $cart = auth()->user()->cart;

        if (!$cart) {
            return response()->json(['error' => 'Корзина не найдена'], 404);
        }

        if ($cart->products->isEmpty()) {
            return response()->json(['error' => 'Корзина пуста'], 400);
        }

        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        // Создаем заказ
        $order = Order::create([
            'user_id' => auth()->id(),
            'payment_method_id' => $paymentMethod->id,
            'status' => 'На оплату'
        ]);

        UpdateOrderStatusJob::dispatch($order->id)->delay(now()->addMinutes(2));

        // Генерация ссылки на оплату
        $paymentUrl = $paymentMethod->payment_url . '?order_id=' . $order->id;

        // Очистка корзины
        $cart->products()->detach();

        return response()->json(['payment_url' => $paymentUrl, 'order' => $order]);
    }

    public function updateOrderStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => 'Оплачен']);
        return response()->json(['message' => 'Статус заказа обновлен на "Оплачен"']);
    }

    public function index(Request $request)
    {
        $orders = Order::where('user_id', auth()->id());

        // Фильтрация по статусу
        if ($request->has('status')) {
            $orders->where('status', $request->status);
        }

        if($request->has('created_at')) {
            $orders->orderBy('created_at', $request->created_at);

        }

        return response()->json($orders->get());
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        return response()->json($order);
    }
}
