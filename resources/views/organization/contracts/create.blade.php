{{-- resources/views/organization/contracts/create.blade.php --}}
@extends('layouts.organization')

@section('title', 'إنشاء عقد جديد')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">إنشاء عقد جديد</h1>
        <a href="{{ route('organization.contracts.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right ms-2"></i> العودة للقائمة
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">بيانات العقد</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('organization.contracts.store') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- العميل (رقم الهوية) -->
                    <div class="col-md-6 mb-3">
                        <label for="customer_national_id" class="form-label">رقم هوية العميل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('customer_national_id') is-invalid @enderror" id="customer_national_id" name="customer_national_id" value="{{ old('customer_national_id') }}" required placeholder="أدخل رقم الهوية">
                        @error('customer_national_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">يجب أن يكون العميل مسجلاً في المنصة من قبل.</small>
                    </div>

                    <!-- رقم العقد -->
                    <div class="col-md-6 mb-3">
                        <label for="contract_number" class="form-label">رقم العقد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('contract_number') is-invalid @enderror" id="contract_number" name="contract_number" value="{{ old('contract_number') }}" required placeholder="رقم العقد حسب نظامكم">
                        @error('contract_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- نوع العقد -->
                    <div class="col-md-6 mb-3">
                        <label for="contract_type" class="form-label">نوع العقد <span class="text-danger">*</span></label>
                        <select class="form-select @error('contract_type') is-invalid @enderror" id="contract_type" name="contract_type" required>
                            <option value="">اختر النوع</option>
                            <option value="funding" {{ old('contract_type') == 'funding' ? 'selected' : '' }}>تمويل</option>
                            <option value="leasing" {{ old('contract_type') == 'leasing' ? 'selected' : '' }}>تأجير</option>
                            <option value="government_fee" {{ old('contract_type') == 'government_fee' ? 'selected' : '' }}>رسوم حكومية</option>
                            <option value="utility_bill" {{ old('contract_type') == 'utility_bill' ? 'selected' : '' }}>فاتورة خدمات</option>
                            <option value="subscription" {{ old('contract_type') == 'subscription' ? 'selected' : '' }}>اشتراك</option>
                            <option value="personal_loan" {{ old('contract_type') == 'personal_loan' ? 'selected' : '' }}>قرض شخصي</option>
                            <option value="other" {{ old('contract_type') == 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                        @error('contract_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- المبالغ -->
                    <div class="col-md-6 mb-3">
                        <label for="principal_amount" class="form-label">أصل المبلغ (اختياري)</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('principal_amount') is-invalid @enderror" id="principal_amount" name="principal_amount" value="{{ old('principal_amount') }}" placeholder="مبلغ التمويل الأصلي">
                        @error('principal_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="total_amount" class="form-label">إجمالي المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control @error('total_amount') is-invalid @enderror" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" required placeholder="المبلغ الإجمالي المطلوب">
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- الأقساط الشهرية -->
                    <div class="col-md-4 mb-3">
                        <label for="monthly_installment" class="form-label">القسط الشهري (اختياري)</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('monthly_installment') is-invalid @enderror" id="monthly_installment" name="monthly_installment" value="{{ old('monthly_installment') }}" placeholder="مبلغ القسط الشهري">
                        @error('monthly_installment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="installment_count" class="form-label">عدد الأقساط (اختياري)</label>
                        <input type="number" min="1" class="form-control @error('installment_count') is-invalid @enderror" id="installment_count" name="installment_count" value="{{ old('installment_count') }}" placeholder="عدد الأشهر">
                        @error('installment_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- التواريخ -->
                    <div class="col-md-4 mb-3">
                        <label for="start_date" class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="end_date" class="form-label">تاريخ النهاية (اختياري)</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- بيانات إضافية بصيغة JSON -->
                    <div class="col-12 mb-3">
                        <label for="raw_contract_data" class="form-label">بيانات إضافية (JSON - اختياري)</label>
                        <textarea class="form-control @error('raw_contract_data') is-invalid @enderror" id="raw_contract_data" name="raw_contract_data" rows="4" placeholder='{"product": "تمويل شخصي", "rate": "3.5%", "fees": 500}'>{{ old('raw_contract_data') }}</textarea>
                        @error('raw_contract_data')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">يمكنك إضافة بيانات إضافية بصيغة JSON (اختياري).</small>
                    </div>
                </div>

                <hr class="my-4">

                <div class="alert alert-info">
                    <i class="fas fa-info-circle ms-2"></i>
                    <strong>ملاحظة:</strong> بعد إنشاء العقد، سيرسل للعميل للموافقة عليه. لن يتم بدء الاستقطاعات إلا بعد موافقة العميل.
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save ms-2"></i> حفظ العقد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // يمكن إضافة أي سكربتات مساعدة هنا (مثل تحويل الأرقام، التحقق المتقدم)
</script>
@endpush
