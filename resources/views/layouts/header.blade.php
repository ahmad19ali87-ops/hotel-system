<nav class="navbar navbar-expand-lg navbar-light bg-primary text-white">
    <div class="container">
        <a class="navbar-brand text-white" href="{{ route('home') }}">🏨 نظام إدارة الفندق</a>

        <ul class="navbar-nav ms-auto">
            @auth
                @if(in_array(Auth::user()->role, ['hotel_admin', 'admin']))
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('rooms.index') }}">الغرف</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('room-types.index') }}">أنواع الغرف</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('maintenance.index') }}">الصيانة</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('cleaning.index') }}">التنظيفات</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('accounts.index') }}">الحسابات</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ route('invoices.index') }}">الفواتير</a></li>
                @endif
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('logout') }}">تسجيل الخروج</a></li>
            @else
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('login') }}">تسجيل الدخول</a></li>
            @endauth
        </ul>
    </div>
</nav>
