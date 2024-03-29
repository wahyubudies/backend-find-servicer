<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Support\Str;
use App\Mail\OtpRegisterMail;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            if ($user->role != 2 && $user->role != 1) {
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
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required',
            'gender' => 'required|in:L,P',
            'phone_number' => 'required|min:11',
            'address' => 'required',
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

        return ApiResponse::success($result , 'OTP sent to email for verification', 200);
    }

    public static function generateNumericString($length)
    {
        $otp = '';

        for ($i = 0; $i < $length; $i++) {
            $otp .= mt_rand(0, 9); // Generate a random number between 0 and 9
        }

        return $otp;
    }

    // Function to send OTP via email
    public function sendOtpEmail($email, $otp)
    {
        Mail::to($email)->send(new OtpRegisterMail($otp));
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
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'name' => $request['name'],
            'gender' => $request['gender'],
            'phone_number' => $request['phone_number'],
            'address' => $request['address'],
            'photo' => $request['photo'],
            'role' => 2
        ]);

        return ApiResponse::success($user, 'User registered successfully', 201);
    }

    public function getPhoto( $photoPath)
    {
        if($photoPath == "" || !isset($photoPath)){
            return "";
        }
        return "/storage/" . $photoPath;
    }
}
