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
use App\Mail\OtpRegisterMail;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class MerchantController extends Controller
{
    public function orders(Request $request)
    {
        // Pagination
        $perPage = $request->input('per_page', 10);

        // Filters
        $startDate = $request->input('start_date', null);
        $endDate = $request->input('end_date', null);
        $status = $request->input('status', null);
        $merchant_id = auth()->user()->id;
        dd($merchant_id);
        $query = Order::query()->where('merchant_id', $merchant_id);
        if (!empty($startDate) && !empty($endDate) || !is_null($startDate) && !is_null($endDate)) {
            $query->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        }
        
        if (!empty($status) || !is_null($status)) {
            $query->where('status', $status);
        }
        
        $query->orderBy('created_at', 'desc');
        dd($query->toSql());
        
        $formattedOrders = $query->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'expiration_date' => $order->expiration_date->format('Y-m-d H:i:s'),
                'status' => $order->status,
                'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                'user' => $this->formarUser($order->user)
            ];
        });

        $paginationOrders = PaginationHelper::paginateCollection($formattedOrders, $perPage);

        $result = PaginationHelper::formatPagination($paginationOrders);

        return ApiResponse::success($result, 'Order history retrieved successfully', 200);
    }

    public function list(Request $request)
    {
        $query = User::with('merchant')->where('role', 3);
        $isSuspended = $this->formatIsSuspend($request->input('is_suspended'));
        $searchValue = $this->formatSearchValue($request->input('search'));

        if (!is_null($isSuspended)) {
            $query->whereHas('merchant', function ($query) use ($isSuspended) {
                $query->where('is_suspended', $isSuspended);
            });
        }

        if (!is_null($searchValue)) {
            $query->where('name', 'like', $searchValue)
                ->orWhereHas('merchant', function ($query) use ($searchValue) {
                    $query->where('service_name', 'like', $searchValue);
                });
        }

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
        if (!isset($request)) {
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
        $validator = Validator::make($request->all(), [
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

    public function formarUser($user)
    {
        return [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "gender" => $user->gender,
            "phone_number" => $user->phone_number,
            "address" => $user->address,
            "photo" => $user->photo,
            "role" => $user->role,
            "join_date" => $user->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function register(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
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
        $data = $request->all();
        $data['photo'] = $this->getPhoto($photoPath);

        // Generate OTP
        $otp = $this->generateNumericString(6);

        // Send OTP via email
        $this->sendOtpEmail($request->email, $otp);

        $result = [
            'registered' => $data,
            'otpEmail' => $otp
        ];

        return ApiResponse::success($result, 'Merchant registered successfully', 201);
    }

    public function verifyRegister(Request $request)
    {
        $otpInput = $request->otpInput;
        $otpEmail = $request->otpEmail;

        if ($otpInput != $otpEmail) {
            // OTP is incorrect
            return ApiResponse::error('Invalid OTP', 400);
        }

        // Store user data in database
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'photo' => $request->photo,
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

        return ApiResponse::success($result, 'User registered successfully', 201);
    }

    // Function to send OTP via email
    public function sendOtpEmail($email, $otp)
    {
        Mail::to($email)->send(new OtpRegisterMail($otp));
    }

    public function getPhoto($photoPath)
    {
        if ($photoPath == "" || !isset($photoPath)) {
            return "";
        }
        return "/storage/" . $photoPath;
    }

    public static function generateNumericString($length)
    {
        $otp = '';

        for ($i = 0; $i < $length; $i++) {
            $otp .= mt_rand(0, 9); // Generate a random number between 0 and 9
        }

        return $otp;
    }
}
