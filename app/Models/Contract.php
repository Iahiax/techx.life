<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * نموذج العقود (Contract)
 * 
 * يمثل عقداً بين عميل (فرد) ومنشأة (جهة) لاستقطاع مالي شهري أو لمرة واحدة.
 * يدعم الحذف الناعم (SoftDeletes).
 */
class Contract extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'contracts';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'org_id',
        'customer_id',
        'contract_number',
        'contract_type',
        'principal_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'monthly_installment',
        'installment_count',
        'paid_installments',
        'remaining_installments',
        'start_date',
        'end_date',
        'status',
        'raw_contract_data',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'principal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'installment_count' => 'integer',
        'paid_installments' => 'integer',
        'remaining_installments' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'raw_contract_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * العلاقة مع المنشأة (الجهة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * العلاقة مع العميل (المستخدم)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * العلاقة مع الاستقطاعات
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'contract_id');
    }

    /**
     * العلاقة مع الموافقات
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function consents()
    {
        return $this->hasMany(Consent::class, 'contract_id');
    }

    /**
     * العلاقة مع الإشعارات
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'contract_id');
    }

    /**
     * نطاق للبحث عن العقود النشطة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * نطاق للبحث عن العقود المعلقة (بانتظار موافقة العميل)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * نطاق للبحث عن العقود المغلقة (منتهية)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * نطاق للبحث عن العقود المتعثرة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    /**
     * نطاق للبحث حسب نوع العقد
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('contract_type', $type);
    }

    /**
     * نطاق للبحث حسب الجهة (المنشأة)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $orgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * نطاق للبحث حسب العميل
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * تحديث المبالغ المدفوعة والمتبقية بناءً على آخر استقطاع
     * يمكن استدعاؤها بعد إضافة استقطاع جديد
     *
     * @return void
     */
    public function refreshPaidAmount()
    {
        $this->paid_amount = $this->deductions()->where('status', 'success')->sum('amount');
        $this->remaining_amount = $this->total_amount - $this->paid_amount;
        
        if ($this->monthly_installment > 0) {
            $this->paid_installments = $this->deductions()
                ->where('status', 'success')
                ->where('amount', '>=', $this->monthly_installment)
                ->count();
            $this->remaining_installments = $this->installment_count - $this->paid_installments;
        }

        if ($this->remaining_amount <= 0) {
            $this->status = 'closed';
            $this->remaining_amount = 0;
        }

        $this->saveQuietly(); // حفظ بدون تشغيل الأحداث
    }

    /**
     * دالة helper للحصول على اسم نوع العقد بالعربية
     *
     * @return string
     */
    public function getContractTypeNameAttribute()
    {
        $types = [
            'funding' => 'تمويل',
            'leasing' => 'تأجير',
            'government_fee' => 'رسوم حكومية',
            'utility_bill' => 'فاتورة خدمات',
            'subscription' => 'اشتراك',
            'personal_loan' => 'قرض شخصي',
            'other' => 'أخرى',
        ];

        return $types[$this->contract_type] ?? $this->contract_type;
    }

    /**
     * دالة helper للحصول على حالة العقد بالعربية
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending_approval' => 'بانتظار الموافقة',
            'active' => 'نشط',
            'rejected' => 'مرفوض',
            'closed' => 'منتهي',
            'defaulted' => 'متعثر',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
