<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\UserVerification;

class AuthOtpSms extends Controller
{
    public function sendOTP()
    {
        // Your Twilio credentials
        $accountSid = 'AC4dfb4a2135fa658ede11df7ee23e5c2d';
        $authToken = 'b2c2a0719030de2fdee414852e72b3e0';
        $twilioNumber = '+15097613503';

        $client = new Client($accountSid, $authToken);

        try {
            $client->verify->v2->services("VA74bb798a7d106dddaf5352e132d9b4e5")
                ->verifications
                ->create("+85510253374", "sms");
        } catch (\Exception $e) {
            throw new \Exception("Failed to send OTP: " . $e->getMessage());
        }
    }
}
