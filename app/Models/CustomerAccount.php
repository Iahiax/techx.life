<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * نموذج حسابات العملاء البنكية (CustomerAccount)
 * 
 * يمثل حساباً بنكياً لعميل (فرد) تم ربطه عبر Open Banking (Lean).
 * يحتوي على معلومات الحساب مثل IBAN، اسم البنك، الرصيد، وتحديد إذا كان حساب راتب أو أساسي.
 */
class CustomerAccount extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'customer_accounts';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'provider_account_id',
        'iban',
        'bank_name',
        'account_name',
        'is_salary_account',
        'is_primary',
        'current_balance',
        'currency',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'is_salary_account' => 'boolean',
        'is_primary' => 'boolean',
        'current_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * العلاقة مع الاستقطاعات (كمصدر للتحويل)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'source_account_id');
    }

    /**
     * نطاق للبحث عن حسابات الراتب
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSalaryAccounts($query)
    {
        return $query->where('is_salary_account', true);
    }

    /**
     * نطاق للبحث عن الحسابات الأساسية
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * نطاق للبحث حسب العملة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * تحديث الرصيد الحالي (يمكن استدعاؤها بعد جلب الرصيد من Lean)
     *
     * @param float $newBalance
     * @return void
     */
    public function updateBalance($newBalance)
    {
        $this->current_balance = $newBalance;
        $this->saveQuietly();
    }

    /**
     * دالة helper لمعرفة ما إذا كان هذا الحساب يمكن استخدامه للاستقطاع
     *
     * @return bool
     */
    public function isAvailableForDeduction()
    {
        // يمكن إضافة شروط إضافية مثل الرصيد الكافي، الحساب نشط، إلخ.
        return true;
    }
}
