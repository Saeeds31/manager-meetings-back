<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Modules\Register\Models\IdentityDocument;
use Modules\Register\Models\ImportantDocument;
use Modules\Register\Models\PhysicalCharacteristics;
use Modules\Register\Models\Register;
use Modules\Users\Models\Otp;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Wallet\Models\Wallet;

class AuthController extends Controller
{
    public function checkMobile(Request $request)
    {
        $request->validate([
            'mobile' => [
                'required',
                'regex:/^09\d{9}$/'
            ],
        ], [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل معتبر نیست. شماره باید با 09 شروع شده و 11 رقم باشد.',
        ]);
        $user = User::where('mobile', $request->mobile)->first();
        $this->sendOtp($request->mobile);
        $otp = Otp::where('mobile', $request->mobile)->first();
        if ($user) {
            return response()->json([
                'status' => 'login',
                "success" => true
            ]);
        } else {
            return response()->json([
                'status' => 'register',
                "success" => true
            ]);
        }
    }
    public function sendOtp($mobile)
    {
        $mobile = trim($mobile);
        $token = rand(100000, 999999);

        Otp::updateOrCreate(
            ['mobile' => $mobile],
            ['token' => $token, 'expires_at' => now()->addMinutes(5)]
        );
        $response = Http::get("https://api.kavenegar.com/v1/523159597A416A4A5A5A4F57564B7662436A6B55454764467672796F574F735648337055374A4F2B4445553D/verify/lookup.json", [
            'receptor' => $mobile,
            'token'    => $token,
            'template' => "verify"
        ]);
        Log::info('Kavenegar response: ' . $response->body());

        return true;
    }

    public  function sendOtpAgain(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:11']);
        $user = User::where("mobile", $request->mobile)->first();
        if ($user) {
            if ($user->status == 'in_progress') {
                $this->sendOtp($request->mobile);
                $otp = Otp::where('mobile', $request->mobile)->first();
                return response()->json([
                    'message' => 'OTP sent',
                    'success' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'اطلاعات از قبل ثبت کامل شده است',
                    'success' => true,
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'اطلاعات کاربر موجود نیست',
                'success' => true,
            ], 404);
        }
    }
    // 4) بررسی OTP
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'mobile' => 'required|digits:11',
            'token'  => 'required|digits:6',
        ]);

        $mobile = trim($data['mobile']);
        $otp = Otp::where('mobile', $mobile)
            ->where('token', $data['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(
                [
                    'message' => 'کد اعتبار خود را از دست داده است مجدد تلاش کنید',
                    'success' => false
                ],
                422
            );
        }
        $user = User::where('mobile', $mobile)->first();
        if ($user) {

            $userRoleId = Role::where('name', 'user')->value('id');
            Wallet::create([
                'user_id' => $user->id,
                'balance' =>  0,
            ]);
            $user->roles()->sync([$userRoleId]);
            $user->update([
                'status' => 'pending'
            ]);
            return response()->json([
                'message' => 'اطلاعات شما با موفقیت ثبت شد به زودی نتیجه آن به اطلاع  شما خواهد رسید',
                "success" => true
            ]);
        } else {
            return response()->json([
                "message" => "شماره تماس کاربر اشتباه است ",
                "success" => false
            ], 404);
        }
    }


    public function adminLogin(Request $request)
    {
        $data = $request->validate([
            'mobile'   => 'required|digits:11',
        ]);
        $user = User::where('mobile', $data['mobile'])->first();
        if ($user) {
            if ($user->roles()->where('name', 'user')->exists()) {
                return response()->json(['message' => 'شما اجازه دسترسی به این بخش را ندارید'], 401);
            }
            $this->sendOtp($request->mobile);
            return response()->json([
                'message' => "کد یکبار مصرف ارسال شد",
                "success" => true
            ]);
        } else {
            return response()->json([
                'message' => "اطلاعات کاربری اشتباه است",
                "success" => false
            ], 404);
        }
    }
    public function adminVerify(Request $request)
    {
        $data = $request->validate([
            'mobile' => 'required|digits:11',
            'token'  => 'required|digits:6',
        ]);

        $mobile = trim($data['mobile']);
        $otp = Otp::where('mobile', $mobile)
            ->where('token', $data['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(
                [
                    'message' => 'کد اعتبار خود را از دست داده است مجدد تلاش کنید',
                    'success' => false
                ],
                422
            );
        }
        $user = User::where('mobile', $mobile)->first();
        if ($user) {
            $token = $user->createToken('auth_token')->plainTextToken;

            $permissions = $user->roles->flatMap(function ($role) {
                return $role->permissions->pluck('name');
            })->unique()->values();

            return response()->json([
                'user' => $user,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $permissions,
                'token' => $token,
            ]);
        } else {
            return response()->json([
                "message" => "شماره تماس کاربر اشتباه است ",
                "success" => false
            ], 404);
        }
    }
    public function adminInfo(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
        ]);
    }
    public function adminPermissions(Request $request)
    {
        $user = $request->user();
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('name');
        })->unique()->values();
        return response()->json([
            'roles' => $user->roles->pluck('name'),
            'permissions' => $permissions,
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
            "success" => true
        ]);
    }
    public function register(Request $request)
    {
        $validated_data = $request->validate([
            'full_name' => 'required|string|min:3|max:100',
            'mobile' => 'required|string|regex:/^09\d{9}$/',
            'national_code' => 'required|string|size:10',
            'birth_date' => 'required|date',
            'birth_certificate_number' => 'required|string|max:20',
            'postal_code' => 'required|string|size:10',
            'marital_status' => 'required|in:1,0',
            'place_of_residence' => 'required|string|max:255',
            'father_name' => 'required|string|max:50',
            'place_birth_certificate' => 'required|string|max:100',
            'job_address' => 'required|string|max:255',
            'phone' => 'nullable|string|regex:/^0\d{10}$/',
            'front_national_cart' => 'required|file|mimes:jpg,jpeg,png|max:1024',
            'back_national_cart' => 'required|file|mimes:jpg,jpeg,png|max:1024',
            'birth_certificate_image' => 'required|file|mimes:jpg,jpeg,png|max:1024',
            'image' => 'required|file|mimes:jpg,jpeg,png|max:1024',
        ]);
        if ($request->hasFile('front_national_cart')) {
            $path = $request->file('front_national_cart')->store('document', 'public');
            $validated_data['front_national_cart'] = $path;
        }

        if ($request->hasFile('back_national_cart')) {
            $path = $request->file('back_national_cart')->store('document', 'public');
            $validated_data['back_national_cart'] = $path;
        }

        if ($request->hasFile('birth_certificate_image')) {
            $path = $request->file('birth_certificate_image')->store('document', 'public');
            $validated_data['birth_certificate_image'] = $path;
        }
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('document', 'public');
            $validated_data['image'] = $path;
        }
        $validated_data["status"] = "in_progress";
        $user = User::where('mobile', $request->mobile)->first();
        if ($user) {
            if ($user->status == 'in_progress') {
                $user->delete();
            } else {
                return response()->json([
                    'message' => 'این شماره تماس از قبل در سیستم ثبت شده است',
                    "success" => false,
                ]);
            }
        }
        User::create($validated_data);
        $this->sendOtp($request->mobile);
        return response()->json([
            'message' => 'برای تایید اطلاعات کد یکبار مصرف را وارد کنید',
            "success" => true,
        ]);
    }
}
