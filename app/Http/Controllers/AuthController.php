<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Infobip\Configuration;
use Infobip\ApiException;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Illuminate\Support\Carbon;
use Infobip\Api\SmsApi;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $middleware = ['auth:api' => ['except' => ['login', 'register']]];


    public function showUser()
    {
        $user = User::select('id', 'name', 'email', 'mobile_no', 'role', 'provider_id', 'avatar', 'provider')->get();
        if ($user->isEmpty()) {
            return response()->json([
                'message' => 'There are no users in the list',
                'users' => $user
            ], 204);
        } else {
            return response()->json([
                'Message' => 'List of all users',
                'users' => $user
            ], 200);
        }
    }

    # By default we are using here auth:api middleware

    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'access_token' => $token,
            'user' => $user,
        ]);
    }


    public function register(Request $request)
    {
        // Validate the request data
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'mobile_no' => 'required|string|unique:users,mobile_no',
            'role' => 'nullable|string',
            'provider' => 'nullable|string',
            'provider_id' => 'nullable|string',
            'avatar' => 'nullable|string'
        ]);

        $providerId = Str::random(32);

        // Create a new user
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'mobile_no' => $fields['mobile_no'],
            'role' => 'customer',
            'provider' => $fields['provider'] ?? null,
            'provider_id' => $providerId,
            'avatar' => $fields['avatar'] ?? null,
        ]);

        // Generate JWT token for the user
        $token = JWTAuth::fromUser($user);

        // Prepare the response
        $response = [
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token
        ];

        return response()->json($response, 201);
    }

    public function loginUser(Request $request)
    {
        try {
            // Login with email and password 
            if ($request->has(['email', 'password'])) {
                $credentials = $request->only('email', 'password');

                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Incorrect Credentials',
                    ], 401);
                }
            } elseif ($request->has(['mobile_no', 'password'])) {
                // Login with phone number and password     
                $credentials = $request->only('mobile_no', 'password');
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Incorrect Credentials',
                    ], 401);
                }

                // Generate and send OTP
                $this->generateAndSendOTP($request->mobile_no);
                // If we login with phone number
                $user = JWTAuth::user();
                $user = $user->only('id', 'name', 'email', 'mobile_no', 'role', 'provider_id', 'avatar', 'provider');
                return response()->json([
                    'status' => true,
                    'message' => 'OTP sent to your mobile for verification.',
                    'user' => $user,
                    'access_token' => $token,
                ], 200);
            } else {
                // If we don't provide all of the credentials
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Credentials',
                ], 400);
            }
            $user = JWTAuth::user();
            // IF it correct to all requirement
            $user = $user->only('id', 'name', 'email', 'mobile_no', 'role', 'provider_id', 'avatar', 'provider');
            return $this->respondWithToken($token, $user);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function profile()
    {
        $userData = auth()->user()->only('id', 'name', 'email', 'mobile_no', 'role', 'provider_id', 'avatar', 'provider');

        return response()->json([
            'status' => true,
            'message' => "Successfully Authorized",
            'user' => $userData,
        ]);
    }

    public function refreshToken()
    {
        $newToken = auth()->refresh();
        return response()->json([
            'status' => true,
            'message' => "New token generated",
            'user' => $newToken,
        ]);
    }


    //Generating Random OTP
    private function generateAndSendOTP($mobile_no)
    {
        // Find the user with the provided mobile number
        $user = User::where('mobile_no', $mobile_no)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found with the provided mobile number.'
            ], 404);
        }

        // Check if a verification record already exists for the user
        $userVerification = UserVerification::where('user_id', $user->id)->latest()->first();

        $now = Carbon::now();
        $expireAt = $now->addMinutes(10);

        // If a verification record exists and it's not expired, return it
        if ($userVerification && $now->isBefore($userVerification->expire_at)) {
            return $userVerification;
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Save OTP to the database
        $userVerification = UserVerification::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expire_at' => $expireAt,
        ]);

        // Send OTP via SMS
        $this->sendSMS($mobile_no, $otp);
    }




    public function sendSMS($mobile_no, $otp)
    {
        $configuration = new Configuration(
            host: '0b422e7e3e2b83c7a0247a799bf4acc1-9dc9e955-71df-46e0-8404-0ccc84605384',
            apiKey: 'j3xdgn.api.infobip.com'
        );
        $sendSmsApi = new SmsApi(config: $configuration);

        // Format the mobile number to match Infobip's requirements
        $formatted_mobile_no = '+855' . preg_replace('/\D/', '', $mobile_no);

        // Prepare SMS message
        $message = new SmsTextualMessage(
            destinations: [
                new SmsDestination(to: $formatted_mobile_no)
            ],
            from: 'InfoSMS',
            text: 'Your OTP verification code is: ' . $otp
        );

        // Create SMS request
        $request = new SmsAdvancedTextualRequest(messages: [$message]);

        try {
            // Send SMS message
            $smsResponse = $sendSmsApi->sendSmsMessage($request);
            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully via SMS.'
            ], 200);
        } catch (ApiException $apiException) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP via SMS: ' . $apiException->getMessage()
            ], 500);
        }
    }



    public function redirectToGoogleAuth(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $redirectUrl,
        ]);
    }


    public function handleGoogleAuthCallback(): JsonResponse
    {
        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        // Check if the user already exists in the database by their email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            // If the user exists, log them in
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'access_token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60, // in seconds
                'user' => $user,

            ]);
        } else {
            // Generate a random password
            $password = Str::random(10);

            // Create a new user with the provided information
            $newUser = User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'customer',
                'provider' => 'google',
                'provider_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);

            // Log in the new user
            $token = JWTAuth::fromUser($newUser);


            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'user' => $newUser,
                'access_token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60,

            ]);
        }
    }

    public function redirectToFaceBookAuth(): JsonResponse
    {
        $redirectUrl = Socialite::driver('facebook')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $redirectUrl,
        ]);
    }
    public function handleFacebookCallback(): JsonResponse
    {
        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver('facebook')
                ->stateless()
                ->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        // Check if the user already exists in the database by their email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            // If the user exists, log them in
            Auth::login($user);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'login',
                'user' => $user,
                'access_token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60, // in seconds

            ]);
        } else {
            // Generate a random password
            $password = Str::random(10);

            // Create a new user with the provided information
            $newUser = User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'customer',
                'provider' => 'facebook',
                'provider_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);

            // Log in the new user
            Auth::login($newUser);

            // Generate JWT token
            $token = JWTAuth::fromUser($newUser);

            return response()->json([
                'status' => 'signup',
                'user' => $newUser,
                'access_token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60, // in seconds


            ]);
        }
    }
}
