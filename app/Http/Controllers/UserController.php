<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Helpers\ApiResponse;

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

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'photo' => $this->getPhoto($photoPath),
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
