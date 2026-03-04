{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container-fluid">
    <!-- العنوان -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">لوحة التحكم الرئيسية</h1>
        <div>
            <span class="text-muted">آخر تحديث: {{ now()->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    <!-- بطاقات الإحصائيات السريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">إجمالي المستخدمين</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_users']) }}</h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <small class="mt-2 d-block">
                        <span class="badge bg-light text-dark">{{ number_format($stats['total_customers']) }} فرد</span>
                        <span class="badge bg-light text-dark ms-1">{{ number_format($stats['total_org_users']) }} منشأة</span>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">المنشآت المسجلة</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_organizations']) }}</h2>
                        </div>
                        <i class="fas fa-building fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">العقود النشطة</h6>
                            <h2 class="mb-0">{{ number_format($stats['active_contracts']) }}</h2>
                        </div>
                        <i class="fas fa-file-contract fa-3x opacity-50"></i>
                    </div>
                    <small class="mt-2 d-block">
                        <span class="badge bg-dark text-white">معلقة: {{ number_format($stats['pending_contracts']) }}</span>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">الاستقطاعات الناجحة</h6>
                            <h2 class="mb-0">{{ number_format($stats['successful_deductions']) }}</h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                    <small class="mt-2 d-block">
                        <span class="badge bg-light text-dark">فاشلة: {{ number_format($stats['failed_deductions']) }}</span>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات مالية سريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title text-success">إجمالي المبالغ المحصلة</h6>
                    <h3>{{ number_format($stats['total_collected'], 2) }} ريال</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title text-primary">إجمالي العمولات المحصلة</h6>
                    <h3>{{ number_format($stats['total_fees'], 2) }} ريال</h3>
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
                    <a href="{{ route('admin.deductions.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>العميل</th>
                                    <th>الجهة</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latest_deductions as $deduction)
                                <tr>
                                    <td>{{ $deduction->customer->full_name ?? 'غير معروف' }}</td>
                                    <td>{{ $deduction->organization->name ?? 'غير معروف' }}</td>
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
                    <a href="{{ route('admin.contracts.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم العقد</th>
                                    <th>العميل</th>
                                    <th>الجهة</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latest_contracts as $contract)
                                <tr>
                                    <td>{{ $contract->contract_number }}</td>
                                    <td>{{ $contract->customer->full_name ?? 'غير معروف' }}</td>
                                    <td>{{ $contract->organization->name ?? 'غير معروف' }}</td>
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

    <!-- إحصائيات شهرية (اختياري) -->
    @if(count($monthly_stats) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">إحصائيات الاستقطاعات الشهرية (آخر 6 أشهر)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>الشهر</th>
                                    <th>عدد العمليات</th>
                                    <th>ناجحة</th>
                                    <th>فاشلة</th>
                                    <th>إجمالي المبلغ</th>
                                    <th>إجمالي العمولات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthly_stats as $stat)
                                <tr>
                                    <td>{{ \Carbon\Carbon::create($stat->year, $stat->month, 1)->locale('ar')->format('F Y') }}</td>
                                    <td>{{ number_format($stat->total_count) }}</td>
                                    <td>{{ number_format($stat->success_count) }}</td>
                                    <td>{{ number_format($stat->total_count - $stat->success_count) }}</td>
                                    <td>{{ number_format($stat->total_amount, 2) }}</td>
                                    <td>{{ number_format($stat->total_fees, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
