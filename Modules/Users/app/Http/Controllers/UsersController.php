<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Http\Requests\UserStoreRequest;
use Modules\Users\Http\Requests\UserUpdateRequest;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Wallet\Models\Wallet;

class UsersController extends Controller
{

    // لیست کاربران

    public function index(Request $request)
    {
        $admin = $request->user(); // ادمینی که درخواست فرستاده

        // نقش‌های ادمین رو به‌صورت آرایه از نام‌ها می‌گیریم
        $adminRoles = $admin->roles->pluck('name')->toArray();

        // فرض می‌کنیم در جدول roles فیلد name داریم (مثلاً "inspector", "admin", ...)
        $query = User::orderBy('id');

        if (in_array('inspector', $adminRoles)) {
            // اگر نقش بازرس دارد → فقط کاربران در حال بررسی
            $query->where('status', 'pending');
        } else {
            // سایر نقش‌ها → فقط کاربران پذیرفته یا رد شده
            $query->whereIn('status', ['accepted', 'rejected']);
        }

        $users = $query->get();

        return response()->json($users);
    }
    public function acceptUser($userId)
    {
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'message' => "کاربر پیدا نشد",
                'success' => false
            ], 404);
        }
        $user->update([
            'status' => 'accepted'
        ]);
        return response()->json([
            'message' => "کاربر تایید شد",
            'success' => true
        ]);
    }
    public function rejectUser($userId)
    {
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'message' => "کاربر پیدا نشد",
                'success' => false
            ], 404);
        }
        $user->update([
            'status' => 'rejected'
        ]);
        return response()->json([
            'message' => "کاربر رد شد",
            'success' => true
        ]);
    }
    public function userProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message' => 'پروفایل کاربر',
            'success' => true,
            'user' => $user->load(['identity_document', 'physical', 'important_document', 'register', 'wallet'])
        ]);
    }
    public function userValidity(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message' => 'وضعیت کاربر',
            'success' => true,
            'validity' => $user->validity
        ]);
    }
    // لیست مدیران
    public function managerIndex()
    {
        $users = User::with(['roles', 'addresses', 'wallet'])
            ->whereHas('roles', function ($query) {
                $query->whereNotIn('name', ['customer', 'super admin']);
            })
            ->get();
        return response()->json($users);
    }
    // ساخت کاربر جدید
    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $customerRoleId = Role::where('name', 'customer')->value('id');
        if (!$customerRoleId) {
            return response()->json([
                'message' => 'نقش پیشفرض مشتری وجود ندارد لطفا این نقش را در دیتابیس تعریف کنید'
            ], 422);
        }
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        Wallet::create([
            'user_id' => $user->id,
            'balance' =>  0,
        ]);
        $user->roles()->sync([$customerRoleId]);
        return response()->json($user->load(['roles', 'addresses', 'wallet']), 201);
    }

    // نمایش یک کاربر
    public function show(User $user)
    {
        return response()->json($user->load(['wallet', 'validity', 'register', 'important_document', 'physical', 'identity_document']));
    }

    // ویرایش کاربر
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();
        if (isset($data['mobile'])) {
            unset($data['mobile']);
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json($user->load(['roles', 'addresses', 'wallet']));
    }

    // حذف کاربر
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
