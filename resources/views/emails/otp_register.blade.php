<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .otp-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: #333333;
            margin-bottom: 20px;
        }

        p {
            font-size: 18px;
            color: #666666;
            margin-bottom: 20px;
        }

        .otp {
            font-size: 36px;
            color: #007bff;
            margin-bottom: 40px;
        }

        .note {
            font-size: 14px;
            color: #999999;
            margin-bottom: 20px;
        }

    </style>
</head>
<body>
    <div class="otp-container">
        <h1>OTP Verification</h1>
        <p>Please enter the OTP sent to your email address.</p>
        <p class="otp">{{ $otp }}</p>
        {{-- <p class="note">Note: OTP is valid for 5 minutes.</p> --}}
    </div>
</body>
</html>
