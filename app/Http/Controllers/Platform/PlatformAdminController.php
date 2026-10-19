<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PlatformAdminController extends Controller
{
    public const PERMISSIONS = [
        'hotels_create' => 'إضافة فنادق جديدة للمنصة',
        'hotels_edit' => 'تعديل بيانات الفنادق والغرف والشعارات',
        'hotels_delete' => 'أرشفة وحذف الفنادق',
        'appearance' => 'تعديل مظهر ونصوص الموقع وصورة الغلاف والوجهات',
        'backups' => 'تحميل واستعادة النسخ الاحتياطية للفنادق',
        'admins' => 'إدارة المشرفين والمدراء المساعدين',
    ];

    private function checkPermission(): void
    {
        abort_unless(auth()->user()?->hasPermission('admins'), 403, 'غير مصرح لك بإدارة مدراء المنصة.');
    }

    public function index()
    {
        $this->checkPermission();
        $admins = User::whereIn('role', ['super_admin', 'platform_admin'])
            ->latest('id')
            ->get();

        return view('platform.admins.index', compact('admins'));
    }

    public function create()
    {
        $this->checkPermission();
        $permissions = self::PERMISSIONS;
        return view('platform.admins.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->checkPermission();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(self::PERMISSIONS))],
        ]);

        $permissions = $validated['permissions'] ?? [];

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => 'platform_admin',
            'hotel_id' => null,
            'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
        ]);

        return redirect()->route('platform.admins.index')->with('status', 'تمت إضافة مدير المنصة المساعد وتعيين صلاحياته بنجاح.');
    }

    public function edit(User $admin)
    {
        $this->checkPermission();
        abort_unless(in_array($admin->role, ['super_admin', 'platform_admin'], true), 404);

        $permissions = self::PERMISSIONS;
        $adminPermissions = $admin->permissions_list;

        return view('platform.admins.edit', compact('admin', 'permissions', 'adminPermissions'));
    }

    public function update(Request $request, User $admin)
    {
        $this->checkPermission();
        abort_unless(in_array($admin->role, ['super_admin', 'platform_admin'], true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(self::PERMISSIONS))],
        ]);

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];
        $admin->phone = $validated['phone'] ?? null;

        if ($request->filled('password')) {
            $admin->password = $validated['password'];
        }

        if ($admin->role !== 'super_admin') {
            $admin->permissions = json_encode($validated['permissions'] ?? [], JSON_UNESCAPED_UNICODE);
        }

        $admin->save();

        return redirect()->route('platform.admins.index')->with('status', 'تم تحديث بيانات وصلاحيات المدير بنجاح.');
    }

    public function destroy(User $admin)
    {
        $this->checkPermission();
        if ($admin->id === auth()->id()) {
            return redirect()->route('platform.admins.index')->with('error', 'لا يمكنك حذف حسابك الحالي.');
        }

        if ($admin->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return redirect()->route('platform.admins.index')->with('error', 'لا يمكن حذف المدير العام الرئيسي الوحيد للنظام.');
        }

        $admin->delete();

        return redirect()->route('platform.admins.index')->with('status', 'تم حذف حساب المدير بنجاح.');
    }
}
