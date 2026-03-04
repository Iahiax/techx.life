{{-- resources/views/organization/dashboard.blade.php --}}
@extends('layouts.organization')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container-fluid">
    <!-- العنوان -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">مرحباً بعودتك، {{ Auth::user()->full_name }}</h1>
        <div>
            <span class="text-muted">آخر تحديث: {{ now()->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    <!-- إحصائيات سريعة (تمرر من الـ Controller) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">إجمالي العقود</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_contracts']) }}</h2>
                        </div>
                        <i class="fas fa-file-contract fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">العقود النشطة</h6>
                            <h2 class="mb-0">{{ number_format($stats['active_contracts']) }}</h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">العقود المعلقة</h6>
                            <h2 class="mb-0">{{ number_format($stats['pending_contracts']) }}</h2>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">إجمالي الاستقطاعات</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_deductions']) }}</h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات مالية -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title text-success">إجمالي المبالغ المحصلة</h6>
                    <h3>{{ number_format($stats['total_collected'], 2) }} ريال</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title text-primary">إجمالي العمولات المدفوعة</h6>
                    <h3>{{ number_format($stats['total_fees'], 2) }} ريال</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning">المبالغ المستحقة (غير المحصلة)</h6>
                    <h3>{{ number_format($stats['outstanding_amount'], 2) }} ريال</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- صف من جدولين: آخر الاستقطاعات وآخر العقود -->
    <div class="row">
        <!-- آخر الاستقطاعات -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">آخر 10 استقطاعات</h5>
                    <a href="{{ route('organization.deductions.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>العميل</th>
                                    <th>رقم العقد</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latest_deductions as $deduction)
                                <tr>
                                    <td>{{ $deduction->customer->full_name ?? 'غير معروف' }}</td>
                                    <td>{{ $deduction->contract->contract_number ?? '--' }}</td>
                                    <td>{{ number_format($deduction->amount, 2) }}</td>
                                    <td>
                                        @if($deduction->status == 'success')
                                            <span class="badge bg-success">ناجح</span>
                                        @elseif($deduction->status == 'failed')
                                            <span class="badge bg-danger">فاشل</span>
                                        @elseif($deduction->status == 'processing')
                                            <span class="badge bg-warning">قيد المعالجة</span>
                                        @else
                                            <span class="badge bg-secondary">معلق</span>
                                        @endif
                                    </td>
                                    <td>{{ $deduction->scheduled_date }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">لا توجد استقطاعات حديثة</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر العقود -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">آخر 10 عقود</h5>
                    <a href="{{ route('organization.contracts.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم العقد</th>
                                    <th>العميل</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإنشاء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latest_contracts as $contract)
                                <tr>
                                    <td>{{ $contract->contract_number }}</td>
                                    <td>{{ $contract->customer->full_name ?? 'غير معروف' }}</td>
                                    <td>{{ number_format($contract->total_amount, 2) }}</td>
                                    <td>
                                        @if($contract->status == 'active')
                                            <span class="badge bg-success">نشط</span>
                                        @elseif($contract->status == 'pending_approval')
                                            <span class="badge bg-warning">معلق</span>
                                        @elseif($contract->status == 'closed')
                                            <span class="badge bg-secondary">منتهي</span>
                                        @elseif($contract->status == 'rejected')
                                            <span class="badge bg-danger">مرفوض</span>
                                        @else
                                            <span class="badge bg-dark">متعثر</span>
                                        @endif
                                    </td>
                                    <td>{{ $contract->created_at->format('Y-m-d') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">لا توجد عقود حديثة</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الإشعارات غير المقروءة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الإشعارات</h5>
                    <a href="{{ route('organization.notifications.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    @if($unread_notifications->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($unread_notifications as $notification)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $notification->title }}</h6>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">{{ $notification->body }}</p>
                                    <small class="text-muted">{{ $notification->type }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i>
                            <p>لا توجد إشعارات جديدة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
