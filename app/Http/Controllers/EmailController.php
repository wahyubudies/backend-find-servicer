<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\OtpRegisterMail;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendOtpRegister()
    {
        $title = 'Otp Register Email Title';
        $body = 'Send test otp register! Body';

        Mail::to('wahyubudies1@gmail.com')->send(new OtpRegisterMail($title, $body));

        return "Email sent successfully!";
    }
}
