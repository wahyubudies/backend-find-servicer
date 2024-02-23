<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class CheckOrdersExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:orders-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check orders expiration and update status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $expiredOrders = Order::where('status', '=', 0)->get();

        foreach ($expiredOrders as $order) {
            if ($order->expiration_date->isPast()) {
                // Jika expiration_date sudah lewat, ubah status menjadi canceled (2)
                $order->update(['status' => 2]);

                // Update merchant penalty, maksimal sampai 3
                $merchant = $order->merchant;
                $penaltyCount = $merchant->penalty_count;

                // Jika penalty_count belum mencapai 3, tambahkan 1
                if ($penaltyCount < 3) {
                    $penaltyCount += 1;
                    $merchant->penalty_count = $penaltyCount;
                    $merchant->save(); // Simpan perubahan ke database
                }

                // Check if merchant penalty count equals 3
                if ($penaltyCount === 3) {
                    // Suspend the merchant
                    $merchant->update(['is_suspended' => 1]);
                }
            }
        }        
        Log::info('Orders expiration checked successfully.' . now());
    }


}
