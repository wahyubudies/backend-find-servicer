<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Merchant;
use App\Helpers\ApiResponse;
use App\Helpers\PaginationHelper;

class MerchantController extends Controller
{
    public function list(Request $request)
    {
        $query = User::with('merchant')->where('role', 3);
        $isSuspended = $this->formatIsSuspend($request->input('is_suspended'));
        $searchValue = $this->formatSearchValue($request->input('search'));
        
        if(!is_null($isSuspended)){
            $query->whereHas('merchant', function ($query) use ($isSuspended) {
                $query->where('is_suspended', $isSuspended);
            });
        }
        
        if(!is_null($searchValue)){
            $query->where('name', 'like', $searchValue)
                ->orWhereHas('merchant', function ($query) use ($searchValue) {
                    $query->where('service_name', 'like', $searchValue);
                });
        }

        // dd($query->toSql(), $query->getBindings());

        $perPage = $request->input('per_page', 10);
        $merchants = $query->paginate($perPage);
        $formattedPagination = PaginationHelper::formatPagination($merchants);

        return ApiResponse::success($formattedPagination, 'Data Retrieved Successfully', 200);
    }

    public function formatSearchValue($request) 
    {
        if (!isset($request) || $request == "") {
            return null;
        }

        return '%' . $request . '%';
    }

    public function formatIsSuspend($request)
    {
        if(!isset($request)){
            return null;
        }

        if (!is_numeric($request) || !in_array($request, [0, 1])) {
            return null;
        }

        return $request;
    }

    public function login(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            if ($user->role != 3) {
                return ApiResponse::error('Unauthorized', 401);
            }
            $token = $user->createToken('authToken')->plainTextToken;
            $result = ['user' => $user, 'token' => $token];
            return ApiResponse::success($result, 'Login successful', 200);
        } else {
            return ApiResponse::error('Unauthorized', 401);
        }
    }

    public function register(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(),[
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required',
            'gender' => 'required|in:L,P',
            'phone_number' => 'required',
            'address' => 'required',
            'description' => 'required|max:500',
            'service_name' => 'required',
            'price_per_hour' => 'required|numeric|min:0',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional photo field
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoPath = $photo->store('profile', 'public');
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'photo' => $this->getPhoto($photoPath),
            'role' => 3
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'description' => $request->description,
            'service_name' => $request->service_name,
            'price_per_hour' => $request->price_per_hour
        ]);
        
        $result = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'gender' => $user->gender,
            'phone_number' => $user->phone_number,
            'address' => $user->address,
            'photo' => $user->photo,
            'role' => $user->role,
            'description' => $merchant->description,
            'service_name' => $merchant->service_name,
            'price_per_hour' => $merchant->price_per_hour
        ];
        return ApiResponse::success($result, 'Merchant registered successfully', 201);
    }

    public function getPhoto( $photoPath)
    {
        if($photoPath == "" || !isset($photoPath)){
            return "";
        }
        return "/storage/" . $photoPath;
    }

    public function acceptOrder(Request $request, $id)
    {
        $order = Order::find($id);

        // Check if the order is available
        if(is_null($order)) {
            return ApiResponse::error('Order not found', 404);
        }

        // Check if the order is still pending
        if ($order->status !== 0) {
            return ApiResponse::error('Order has already been processed', 400);
        }

        // Check if the order has exceeded the time limit
        $currentTime = now();
        $expirationTime = Carbon::parse($order->order_time)->addMinutes(5);
        if ($currentTime > $expirationTime) {
            return ApiResponse::error('Order has expired', 400);
        }

        // Update order status to accepted
        $order->status = 1; // Set status to accepted
        $order->save();

        return ApiResponse::success($order, 'Order successfully accepted', 200);
    }
}
