{{-- resources/views/organization/contracts/show.blade.php --}}
@extends('layouts.organization')

@section('title', 'تفاصيل العقد')

@section('content')
<div class="container-fluid">
    <!-- عنوان الصفحة مع أزرار التنقل -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">تفاصيل العقد #{{ $contract->contract_number }}</h1>
        <div>
            <a href="{{ route('organization.contracts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right ms-2"></i> العودة للقائمة
            </a>
            @if($contract->status == 'pending_approval')
                <a href="{{ route('organization.contracts.edit', $contract->id) }}" class="btn btn-outline-warning">
                    <i class="fas fa-edit ms-2"></i> تعديل
                </a>
            @endif
            @if($contract->status == 'active')
                <a href="{{ route('organization.contracts.print-pdf', $contract->id) }}" class="btn btn-outline-danger" target="_blank">
                    <i class="fas fa-file-pdf ms-2"></i> طباعة PDF
                </a>
            @endif
        </div>
    </div>

    <!-- معلومات العقد والعميل -->
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
                            <th>تاريخ البداية:</th>
                            <td>{{ $contract->start_date->format('Y-m-d') }}</td>
                        </tr>
                        @if($contract->end_date)
                        <tr>
                            <th>تاريخ النهاية:</th>
                            <td>{{ $contract->end_date->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>تاريخ الإنشاء:</th>
                            <td>{{ $contract->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات العميل</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">الاسم:</th>
                            <td>{{ $contract->customer->full_name ?? 'غير معروف' }}</td>
                        </tr>
                        <tr>
                            <th>رقم الهوية:</th>
                            <td>{{ $contract->customer->national_id ?? '--' }}</td>
                        </tr>
                        <tr>
                            <th>الجوال:</th>
                            <td>{{ $contract->customer->phone ?? '--' }}</td>
                        </tr>
                        <tr>
                            <th>البريد الإلكتروني:</th>
                            <td>{{ $contract->customer->email ?? '--' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- المبالغ المالية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">المبالغ المالية</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">أصل المبلغ</h6>
                                <h4>{{ $contract->principal_amount ? number_format($contract->principal_amount, 2) : '--' }} ريال</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">إجمالي المبلغ</h6>
                                <h4>{{ number_format($contract->total_amount, 2) }} ريال</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-success text-white">
                                <h6 class="text-white">المبلغ المدفوع</h6>
                                <h4>{{ number_format($contract->paid_amount, 2) }} ريال</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-danger text-white">
                                <h6 class="text-white">المبلغ المتبقي</h6>
                                <h4>{{ number_format($contract->remaining_amount, 2) }} ريال</h4>
                            </div>
                        </div>
                    </div>
                    @if($contract->monthly_installment)
                    <hr>
                    <div class="row text-center mt-3">
                        <div class="col-md-4">
                            <h6 class="text-muted">القسط الشهري</h6>
                            <p class="h5">{{ number_format($contract->monthly_installment, 2) }} ريال</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">عدد الأقساط الكلي</h6>
                            <p class="h5">{{ $contract->installment_count }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">الأقساط المتبقية</h6>
                            <p class="h5">{{ $contract->remaining_installments }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

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

    <!-- أزرار إضافية (مثل إيقاف الاستقطاع - للمشرفين) -->
    @if($contract->status == 'active' && Auth::user()->can('stop-deductions'))
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <h5 class="text-danger mb-3">إجراءات استثنائية</h5>
                    <p>يمكنك إيقاف جميع الاستقطاعات المستقبلية لهذا العقد في الحالات الاستثنائية.</p>
                    <form action="{{ route('organization.contracts.stop-deductions', $contract->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إيقاف الاستقطاعات لهذا العقد؟');">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-stop-circle ms-2"></i> إيقاف الاستقطاعات
                        </button>
                    </form>
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
