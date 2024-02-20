<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function bookService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required',
            'user_id' => 'required'
        ]);
    
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }        
    
        $merchant_id = $request->input('merchant_id');
        $user_id = $request->input('user_id');
    
        $merchant = Merchant::find($merchant_id);
        if (is_null($merchant)) {
            return ApiResponse::error("Merchant data not found", 404);
        }
        
        $order = Order::create([
            'user_id' => $user_id,
            'merchant_id' => $merchant_id,
            'order_time' => now(),
            'status' => 0
        ]);
    
        // Set expiration time for the order (5 minutes)
        $expirationTime = Carbon::parse($order->order_time)->addMinutes(5);
        // Schedule a job to automatically cancel the order if not accepted by the merchant
        dispatch(function () use ($order, $merchant) {
            // Sleep for 5 minutes (for testing purposes)
            sleep(300);
    
            // Check if the order is still pending
            $order = Order::find($order->id);
            if ($order && $order->status === 0) {
                // Update order status to canceled
                $order->status = 2; // Set status to canceled
                $order->save();
    
                // Update penalty count for the merchant
                $merchant->increment('penalty_count');
                if ($merchant->penalty_count >= 3) {
                    // Suspend the merchant if penalty count reaches 3
                    $merchant->update(['is_suspended' => 1]);
                }
            }
        })->delay($expirationTime);
    
        return ApiResponse::success($order, 'Order placed successfully', 200);
    }

    public function acceptOrder($id)
    {
        $order = Order::find($id);

        // Check if the order is available
        if (is_null($order)) {
            return ApiResponse::error('Order not found', 404);
        }

        // Check if the order is still pending
        if ($order->status !== 0) {
            return ApiResponse::error('Order has already been processed', 400);
        }

        // Update order status to accepted
        $order->status = 1; // Set status to accepted
        $order->save();

        return ApiResponse::success($order, 'Order successfully accepted', 200);
    }

}
