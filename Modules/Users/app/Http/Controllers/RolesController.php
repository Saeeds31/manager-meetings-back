<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

class RolesController extends Controller
{
    // لیست نقش‌ها
    public function index()
    {
        $roles = Role::where("is_system", "!=", "1")
            ->get();
        return response()->json(
            [
                'message' => "role list",
                'data' => $roles
            ]
        );
    }
    public function assignRoles(Request $request)
    {
        $data = $request->validate([
            "user_id" => "required|numeric",
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);
        $user = User::findOrFail($data['user_id']);
        // اگر نقش‌ها ارسال نشدن یا خالی بودن
        if (empty($data['roles'])) {
            $customerRoleId = Role::where('name', 'customer')->value('id');
            if (!$customerRoleId) {
                return response()->json([
                    'message' => 'نقش پیشفرض مشتری وجود ندارد لطفا این نقش را در دیتابیس تعریف کنید'
                ], 422);
            }
            $user->roles()->sync([$customerRoleId]);
        } else {
            $user->roles()->sync($data['roles']);
        }

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user->load('roles')
        ]);
    }
    // ایجاد نقش جدید
    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string"
        ]);
        $role = Role::create($data);
        return response()->json([
            'message' => "role ",
            'data' => $role
        ], 201);
    }

    // نمایش یک نقش
    public function show(Role $role)
    {
        return response()->json(
            [
                'message' => "role ",
                'data' => $role
            ]
        );
    }

    // ویرایش نقش
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $data = $request->validate([
            "name" => "required|string"
        ]);
        if ($role->is_system) {
            return response()->json(['error' => 'System roles cannot be updated'], 403);
        }
        $role->update($data);
        return response()->json(
            [
                'message' => "role",
                'data' => $role
            ]
        );
    }

    // حذف نقش
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json(['error' => 'System roles cannot be deleted'], 403);
        }

        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
