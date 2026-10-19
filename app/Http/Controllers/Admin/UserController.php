<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public const EMPLOYEE_ROLES = ['hotel_owner', 'reception', 'accountant', 'maintenance', 'cleaner'];

    public const AVAILABLE_PERMISSIONS = [
        'accountant' => '💼 إدارة المحاسبة والمالية (القيود وسندات الصرف والقبض)',
        'invoices' => '🧾 إدارة الفواتير وإجراء السداد',
        'reception' => '🛎️ إدارة الحجوزات وتسجيل الوصول والمغادرة',
        'guests' => '👤 إدارة وتوثيق النزلاء والوثائق الرسمية',
        'rooms' => '🚪 إدارة الغرف وأنواع الغرف والأسعار',
        'maintenance' => '🔧 إدارة ومتابعة أوامر الصيانة والأعطال',
        'cleaner' => '🧹 إدارة ومتابعة جدول تنظيف الغرف',
        'reports' => '📊 استعراض التقارير المالية والإحصائيات والستيتمنت',
        'complaints' => '📢 استلام ومتابعة شكاوى النزلاء والتقييمات',
    ];

    public function index()
    {
        $users = User::where('hotel_id', auth()->user()->hotel_id)
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للاطلاع والمعاينة فقط.');

        return view('admin.users.create', [
            'roles' => self::EMPLOYEE_ROLES,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للاطلاع والمعاينة فقط.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:' . implode(',', self::EMPLOYEE_ROLES)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(self::AVAILABLE_PERMISSIONS))],
        ]);

        $permissions = $request->input('permissions', []);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'permissions' => json_encode($permissions),
            'hotel_id' => auth()->user()->hotel_id,
        ]);

        return redirect()->route('employees.index')->with('status', 'تم إنشاء حساب الموظف وتحديد صلاحياته بنجاح.');
    }

    public function edit(User $user)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للاطلاع والمعاينة فقط.');
        abort_unless((int) $user->hotel_id === (int) auth()->user()->hotel_id && $user->id !== auth()->id(), 404);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => self::EMPLOYEE_ROLES,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للاطلاع والمعاينة فقط.');
        abort_unless((int) $user->hotel_id === (int) auth()->user()->hotel_id && $user->id !== auth()->id(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:' . implode(',', self::EMPLOYEE_ROLES)],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(self::AVAILABLE_PERMISSIONS))],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->permissions = json_encode($request->input('permissions', []));
        if ($request->filled('password')) {
            $user->password = $validated['password'];
        }
        $user->save();

        return redirect()->route('employees.index')->with('status', 'تم تحديث بيانات وصلاحيات الموظف بنجاح.');
    }

    public function destroy(User $user)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للاطلاع والمعاينة فقط.');
        abort_unless((int) $user->hotel_id === (int) auth()->user()->hotel_id && $user->id !== auth()->id(), 404);

        $user->delete();

        return redirect()->route('employees.index')->with('status', 'تم حذف حساب الموظف بنجاح.');
    }
}
