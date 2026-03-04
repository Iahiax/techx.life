{{-- resources/views/customer/accounts/index.blade.php --}}
@extends('layouts.customer')

@section('title', 'الحسابات البنكية')

@section('content')
<div class="container-fluid">
    <!-- عنوان الصفحة مع زر إضافة حساب جديد -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">الحسابات البنكية المرتبطة</h1>
        <div>
            <a href="{{ route('customer.accounts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle ms-2"></i> ربط حساب جديد
            </a>
            <a href="{{ route('customer.accounts.connect') }}" class="btn btn-success">
                <i class="fas fa-link ms-2"></i> ربط عبر Lean
            </a>
        </div>
    </div>

    <!-- قائمة الحسابات -->
    @if($accounts->count() > 0)
        <div class="row">
            @foreach($accounts as $account)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">{{ $account->account_name }}</h5>
                                    <p class="text-muted small mb-0">{{ $account->bank_name }}</p>
                                </div>
                                <div>
                                    @if($account->is_primary)
                                        <span class="badge bg-primary">أساسي</span>
                                    @endif
                                    @if($account->is_salary_account)
                                        <span class="badge bg-success">حساب راتب</span>
                                    @endif
                                </div>
                            </div>

                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>IBAN:</th>
                                    <td dir="ltr" class="text-start">{{ $account->iban ?? '--' }}</td>
                                </tr>
                                <tr>
                                    <th>الرصيد:</th>
                                    <td>{{ number_format($account->current_balance, 2) }} {{ $account->currency }}</td>
                                </tr>
                                <tr>
                                    <th>تاريخ الربط:</th>
                                    <td>{{ $account->created_at->format('Y-m-d') }}</td>
                                </tr>
                            </table>

                            <div class="d-flex justify-content-between mt-3">
                                <a href="{{ route('customer.accounts.refresh-balance', $account->id) }}" class="btn btn-sm btn-outline-info" title="تحديث الرصيد">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                                <div class="btn-group" role="group">
                                    @if(!$account->is_primary)
                                        <form action="{{ route('customer.accounts.update', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_primary" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="تعيين كأساسي">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$account->is_salary_account)
                                        <form action="{{ route('customer.accounts.update', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_salary_account" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="تعيين كحساب راتب">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($account->is_primary || $account->is_salary_account)
                                        <form action="{{ route('customer.accounts.update', $account->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_primary" value="0">
                                            <input type="hidden" name="is_salary_account" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="إلغاء التخصيص">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('customer.accounts.destroy', $account->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-university fa-3x mb-3"></i>
            <h5>لا توجد حسابات بنكية مرتبطة</h5>
            <p class="mb-3">قم بربط حسابك البنكي للبدء في استخدام خدمات الاستقطاع</p>
            <a href="{{ route('customer.accounts.connect') }}" class="btn btn-primary">
                <i class="fas fa-link ms-2"></i> ربط حساب جديد عبر Lean
            </a>
        </div>
    @endif
</div>
@endsection
