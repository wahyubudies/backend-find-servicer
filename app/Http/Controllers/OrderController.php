<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiResponse;
use App\Helpers\PaginationHelper;

class OrderController extends Controller
{
    public function bookService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required'
        ]);
    
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }
    
        $merchant_id = $request->input('merchant_id');
        $user_id = auth()->user()->id;
    
        $merchant = Merchant::find($merchant_id);
        if (is_null($merchant)) {
            return ApiResponse::error("Merchant data not found", 404);
        }
        
        $order = Order::create([
            'user_id' => $user_id,
            'merchant_id' => $merchant_id,
            'expiration_date' => now()->addMinutes(60),
            'status' => 0
        ]);

        $order['user'] = $order->user;
    
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

    public function cancelOrder($id)
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
        $order->status = 2; // Set status to canceled
        $order->save();

        return ApiResponse::success($order, 'Order successfully canceled', 200);
    }

    public function orderHistories(Request $request)
    {
        // Pagination
        $perPage = $request->input('per_page', 10);
        
        // Filters
        $startDate = $request->input('start_date',null);
        $endDate = $request->input('end_date',null);
        $status = $request->input('status',null);
        $user_id = auth()->user()->id;
        
        $query = Order::query()->where('user_id', $user_id);

        if (!empty($startDate) && !empty($endDate) || !is_null($startDate) && !is_null($endDate)) {
            $query->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate);
        }
    
        if (!empty($status) || !is_null($status)) {
            $query->where('status', $status);
        }
        
        $formattedOrders = $query->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'expiration_date' => $order->expiration_date->format('Y-m-d H:i:s'),
                'status' => $order->status,
                'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                'merchant' => $this->formatMerchant($order->merchant)
            ];
        });

        $paginationOrders = PaginationHelper::paginateCollection($formattedOrders, $perPage);
    
        $result = PaginationHelper::formatPagination($paginationOrders);

        return ApiResponse::success($result, 'Order history retrieved successfully', 200);
    }

    public function formatMerchant ($merchant)
    {
        return [
            'id'=>$merchant->id,
            'service_name' => $merchant->service_name,
            'description' => $merchant->description,
            'price_per_hour' => $merchant->price_per_hour,
            'penalty_count' => $merchant->penalty_count,
            'is_suspended' => $merchant->is_suspended,
            'join_date' => $merchant->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function show($id)
    {
        $order = Order::find($id);
            
        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $formatted = [
            'id' => $order->id,
            'expiration_date' => $order->expiration_date->format('Y-m-d H:i:s'),
            'status' => $order->status,
            'order_date' => $order->created_at->format('Y-m-d H:i:s'),
            'merchant' => $this->formatMerchant($order->merchant)
        ];

        return ApiResponse::success($formatted, 'Order details retrieved successfully', 200);
    }
}
