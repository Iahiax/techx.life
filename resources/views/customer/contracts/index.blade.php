{{-- resources/views/customer/contracts/index.blade.php --}}
@extends('layouts.customer')

@section('title', 'العقود')

@section('content')
<div class="container-fluid">
    <!-- عنوان الصفحة مع زر رجوع (اختياري) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">العقود</h1>
        <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right ms-2"></i> العودة للوحة التحكم
        </a>
    </div>

    <!-- نموذج التصفية (اختياري) -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('customer.contracts.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">حالة العقد</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>بانتظار الموافقة</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>منتهي</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                        <option value="defaulted" {{ request('status') == 'defaulted' ? 'selected' : '' }}>متعثر</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="رقم العقد أو اسم الجهة" value="{{ request('search') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter ms-2"></i> تطبيق الفلتر
                    </button>
                    <a href="{{ route('customer.contracts.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-undo ms-2"></i> إعادة ضبط
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- قائمة العقود -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>رقم العقد</th>
                            <th>الجهة</th>
                            <th>نوع العقد</th>
                            <th>المبلغ الإجمالي</th>
                            <th>المبلغ المدفوع</th>
                            <th>المتبقي</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                        <tr>
                            <td>{{ $contract->id }}</td>
                            <td>{{ $contract->contract_number }}</td>
                            <td>{{ $contract->organization->name ?? 'غير معروف' }}</td>
                            <td>{{ $contract->contract_type_name }}</td>
                            <td>{{ number_format($contract->total_amount, 2) }}</td>
                            <td>{{ number_format($contract->paid_amount, 2) }}</td>
                            <td>{{ number_format($contract->remaining_amount, 2) }}</td>
                            <td>
                                @if($contract->status == 'pending_approval')
                                    <span class="badge bg-warning text-dark">بانتظار الموافقة</span>
                                @elseif($contract->status == 'active')
                                    <span class="badge bg-success">نشط</span>
                                @elseif($contract->status == 'closed')
                                    <span class="badge bg-secondary">منتهي</span>
                                @elseif($contract->status == 'rejected')
                                    <span class="badge bg-danger">مرفوض</span>
                                @elseif($contract->status == 'defaulted')
                                    <span class="badge bg-dark">متعثر</span>
                                @endif
                            </td>
                            <td>{{ $contract->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('customer.contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($contract->status == 'pending_approval')
                                    <a href="{{ route('customer.contracts.review', $contract->id) }}" class="btn btn-sm btn-outline-success" title="مراجعة العقد">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                @endif
                                @if($contract->status == 'active' && $contract->deductions()->count() > 0)
                                    <a href="{{ route('customer.contracts.print-pdf', $contract->id) }}" class="btn btn-sm btn-outline-danger" title="طباعة PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-file-contract fa-2x mb-2"></i>
                                <p>لا توجد عقود حالياً</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($contracts, 'links'))
        <div class="card-footer bg-white">
            {{ $contracts->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
