<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * نموذج عمليات الاستقطاع (Deduction)
 * 
 * يمثل عملية استقطاع مالي واحدة من حساب العميل إلى حساب الجهة.
 * يسجل تفاصيل العملية، الحالة، المبالغ، والمراجع البنكية.
 */
class Deduction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'deductions';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'org_id',
        'contract_id',
        'amount',
        'platform_fee',
        'source_account_id',
        'target_account_id',
        'target_type',
        'status',
        'scheduled_date',
        'processed_date',
        'transaction_reference',
        'failure_reason',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'scheduled_date' => 'date',
        'processed_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
     * العلاقة مع المنشأة (الجهة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * العلاقة مع العقد (اختياري)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * العلاقة مع حساب المصدر (العميل)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'source_account_id');
    }

    /**
     * العلاقة مع حساب الهدف (الجهة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function targetAccount()
    {
        return $this->belongsTo(OrganizationAccount::class, 'target_account_id');
    }

    /**
     * نطاق للبحث عن الاستقطاعات الناجحة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * نطاق للبحث عن الاستقطاعات الفاشلة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * نطاق للبحث عن الاستقطاعات المعلقة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * نطاق للبحث عن الاستقطاعات في تاريخ معين
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    /**
     * نطاق للبحث عن الاستقطاعات بين تاريخين
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $from
     * @param string $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('scheduled_date', [$from, $to]);
    }

    /**
     * نطاق للبحث حسب الجهة
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
     * دالة helper للحصول على حالة العملية بالعربية
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'processing' => 'قيد المعالجة',
            'success' => 'ناجحة',
            'failed' => 'فاشلة',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * دالة helper للحصول على نوع الهدف بالعربية
     *
     * @return string
     */
    public function getTargetTypeNameAttribute()
    {
        $types = [
            'bank_account' => 'حساب بنكي',
            'sadad_biller' => 'فاتورة سداد',
            'wallet' => 'محفظة',
            'individual_account' => 'حساب فرد',
        ];

        return $types[$this->target_type] ?? $this->target_type;
    }

    /**
     * تحديث حالة الاستقطاع (مع تسجيل السبب إن وجد)
     *
     * @param string $status
     * @param string|null $reason
     * @return void
     */
    public function updateStatus($status, $reason = null)
    {
        $this->status = $status;
        if ($status === 'success') {
            $this->processed_date = now();
        }
        if ($reason) {
            $this->failure_reason = $reason;
        }
        $this->saveQuietly();
    }
}
