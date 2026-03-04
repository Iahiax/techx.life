{{-- resources/views/customer/contracts/show.blade.php --}}
@extends('layouts.customer')

@section('title', 'تفاصيل العقد')

@section('content')
<div class="container-fluid">
    <!-- عنوان الصفحة مع أزرار التنقل -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">تفاصيل العقد #{{ $contract->contract_number }}</h1>
        <div>
            <a href="{{ route('customer.contracts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right ms-2"></i> العودة للقائمة
            </a>
            <a href="{{ route('customer.contracts.print-pdf', $contract->id) }}" class="btn btn-outline-danger" target="_blank">
                <i class="fas fa-file-pdf ms-2"></i> طباعة PDF
            </a>
        </div>
    </div>

    <!-- معلومات العقد -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">معلومات العقد</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">رقم العقد:</th>
                            <td>{{ $contract->contract_number }}</td>
                        </tr>
                        <tr>
                            <th>الجهة:</th>
                            <td>{{ $contract->organization->name ?? 'غير معروف' }}</td>
                        </tr>
                        <tr>
                            <th>نوع العقد:</th>
                            <td>{{ $contract->contract_type_name }}</td>
                        </tr>
                        <tr>
                            <th>الحالة:</th>
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
                        </tr>
                        <tr>
                            <th>تاريخ بداية العقد:</th>
                            <td>{{ $contract->start_date->format('Y-m-d') }}</td>
                        </tr>
                        @if($contract->end_date)
                        <tr>
                            <th>تاريخ نهاية العقد:</th>
                            <td>{{ $contract->end_date->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">المبالغ المالية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        @if($contract->principal_amount)
                        <tr>
                            <th width="40%">أصل المبلغ:</th>
                            <td>{{ number_format($contract->principal_amount, 2) }} ريال</td>
                        </tr>
                        @endif
                        <tr>
                            <th>إجمالي المبلغ:</th>
                            <td>{{ number_format($contract->total_amount, 2) }} ريال</td>
                        </tr>
                        <tr>
                            <th>المبلغ المدفوع:</th>
                            <td class="text-success">{{ number_format($contract->paid_amount, 2) }} ريال</td>
                        </tr>
                        <tr>
                            <th>المبلغ المتبقي:</th>
                            <td class="text-danger">{{ number_format($contract->remaining_amount, 2) }} ريال</td>
                        </tr>
                        @if($contract->monthly_installment)
                        <tr>
                            <th>القسط الشهري:</th>
                            <td>{{ number_format($contract->monthly_installment, 2) }} ريال</td>
                        </tr>
                        @endif
                        @if($contract->installment_count)
                        <tr>
                            <th>عدد الأقساط الكلي:</th>
                            <td>{{ $contract->installment_count }}</td>
                        </tr>
                        <tr>
                            <th>الأقساط المدفوعة:</th>
                            <td>{{ $contract->paid_installments }}</td>
                        </tr>
                        <tr>
                            <th>الأقساط المتبقية:</th>
                            <td>{{ $contract->remaining_installments }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بيانات إضافية (raw contract data) إن وجدت -->
    @if($contract->raw_contract_data)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">تفاصيل إضافية</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="white-space: pre-wrap; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">{{ json_encode($contract->raw_contract_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- سجل الاستقطاعات -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">سجل الاستقطاعات</h5>
                    <span class="badge bg-info">{{ $contract->deductions->count() }} عملية</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ المقرر</th>
                                    <th>تاريخ التنفيذ</th>
                                    <th>المبلغ</th>
                                    <th>عمولة المنصة</th>
                                    <th>الحالة</th>
                                    <th>مرجع العملية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contract->deductions as $deduction)
                                <tr>
                                    <td>{{ $deduction->id }}</td>
                                    <td>{{ $deduction->scheduled_date }}</td>
                                    <td>{{ $deduction->processed_date ?? '--' }}</td>
                                    <td>{{ number_format($deduction->amount, 2) }}</td>
                                    <td>{{ number_format($deduction->platform_fee, 2) }}</td>
                                    <td>
                                        @if($deduction->status == 'success')
                                            <span class="badge bg-success">ناجح</span>
                                        @elseif($deduction->status == 'failed')
                                            <span class="badge bg-danger">فاشل</span>
                                            @if($deduction->failure_reason)
                                                <i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" title="{{ $deduction->failure_reason }}"></i>
                                            @endif
                                        @elseif($deduction->status == 'processing')
                                            <span class="badge bg-warning">قيد المعالجة</span>
                                        @else
                                            <span class="badge bg-secondary">معلق</span>
                                        @endif
                                    </td>
                                    <td>{{ $deduction->transaction_reference ?? '--' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        لا توجد استقطاعات لهذا العقد بعد.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- أزرار الإجراءات بناءً على الحالة -->
    @if($contract->status == 'pending_approval')
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center">
                    <h5 class="text-warning mb-3">هذا العقد بانتظار موافقتك</h5>
                    <p class="mb-4">يرجى مراجعة تفاصيل العقد بعناية قبل الموافقة. بالموافقة، ستسمح للجهة بالبدء في الاستقطاعات الشهرية وفقاً للجدول المتفق عليه.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <form action="{{ route('customer.contracts.approve', $contract->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('هل أنت متأكد من الموافقة على هذا العقد؟')">
                                <i class="fas fa-check-circle ms-2"></i> موافقة
                            </button>
                        </form>
                        <form action="{{ route('customer.contracts.reject', $contract->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('هل أنت متأكد من رفض هذا العقد؟')">
                                <i class="fas fa-times-circle ms-2"></i> رفض
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // تفعيل tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
