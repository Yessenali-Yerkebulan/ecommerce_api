<?php

namespace App\Jobs;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId)
    {
        $this->orderId  = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->orderId);

        // Проверяем, что заказ существует и статус равен 'На оплату'
        if ($order && $order->status == 'На оплату') {
            // Если прошло больше 2 минут с момента создания заказа
            if ($order->created_at->diffInMinutes(Carbon::now()) >= 2) {
                // Обновляем статус на 'Отменен'
                $order->update(['status' => 'Отменен']);
                Log::info('Заказ ' . $order->id . ' обновлен на "Отменен".');
            }
        }
    }
}
