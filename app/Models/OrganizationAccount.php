<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * نموذج حسابات المنشآت البنكية (OrganizationAccount)
 * 
 * يمثل حساباً بنكياً تابعاً لمنشأة (جهة) يستقبل الأموال المحولة من العملاء.
 */
class OrganizationAccount extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'organization_accounts';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'org_id',
        'iban',
        'bank_name',
        'account_name',
        'is_default',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * العلاقة مع المنشأة
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * العلاقة مع الاستقطاعات (كحساب هدف)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'target_account_id');
    }

    /**
     * نطاق للبحث عن الحسابات الافتراضية (الرئيسية)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * دالة helper للتحقق مما إذا كان هذا الحساب هو الافتراضي
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->is_default;
    }
}
