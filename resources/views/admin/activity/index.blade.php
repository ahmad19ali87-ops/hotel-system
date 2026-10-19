@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">📜 سجل نشاط وعمليات الموظفين</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">الموظف</th>
                            <th>العملية</th>
                            <th>النوع</th>
                            <th>رقم السجل</th>
                            <th>التفاصيل</th>
                            <th>IP</th>
                            <th class="text-end pe-3">التاريخ والوقت</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $log->user->name ?? 'مستخدم محذوف' }}</td>
                                <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                <td>{{ class_basename($log->model) }}</td>
                                <td>#{{ $log->model_id }}</td>
                                <td>{{ $log->details ?? '—' }}</td>
                                <td><code>{{ $log->ip ?? '—' }}</code></td>
                                <td class="text-end pe-3 text-muted">{{ $log->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    لا يوجد سجل أنشطة مسجل حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
