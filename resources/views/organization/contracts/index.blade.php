{{-- resources/views/organization/contracts/index.blade.php --}}
@extends('layouts.organization')

@section('title', 'العقود')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">العقود</h1>
        <a href="{{ route('organization.contracts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle ms-2"></i> إنشاء عقد جديد
        </a>
    </div>

    <!-- نموذج التصفية -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('organization.contracts.index') }}" class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label for="contract_type" class="form-label">نوع العقد</label>
                    <select name="contract_type" id="contract_type" class="form-select">
                        <option value="">الكل</option>
                        <option value="funding" {{ request('contract_type') == 'funding' ? 'selected' : '' }}>تمويل</option>
                        <option value="leasing" {{ request('contract_type') == 'leasing' ? 'selected' : '' }}>تأجير</option>
                        <option value="government_fee" {{ request('contract_type') == 'government_fee' ? 'selected' : '' }}>رسوم حكومية</option>
                        <option value="utility_bill" {{ request('contract_type') == 'utility_bill' ? 'selected' : '' }}>فاتورة خدمات</option>
                        <option value="subscription" {{ request('contract_type') == 'subscription' ? 'selected' : '' }}>اشتراك</option>
                        <option value="personal_loan" {{ request('contract_type') == 'personal_loan' ? 'selected' : '' }}>قرض شخصي</option>
                        <option value="other" {{ request('contract_type') == 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="رقم العقد أو اسم العميل أو رقم الهوية" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search ms-2"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول العقود -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>رقم العقد</th>
                            <th>العميل</th>
                            <th>رقم الهوية</th>
                            <th>نوع العقد</th>
                            <th>المبلغ الإجمالي</th>
                            <th>المدفوع</th>
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
                            <td>{{ $contract->customer->full_name ?? 'غير معروف' }}</td>
                            <td>{{ $contract->customer->national_id ?? '--' }}</td>
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
                                <a href="{{ route('organization.contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($contract->status == 'pending_approval')
                                    <a href="{{ route('organization.contracts.edit', $contract->id) }}" class="btn btn-sm btn-outline-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('organization.contracts.destroy', $contract->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($contract->status == 'active')
                                    <a href="{{ route('organization.contracts.deductions', $contract->id) }}" class="btn btn-sm btn-outline-info" title="سجل الاستقطاعات">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <a href="{{ route('organization.contracts.print-pdf', $contract->id) }}" class="btn btn-sm btn-outline-danger" title="طباعة PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
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
