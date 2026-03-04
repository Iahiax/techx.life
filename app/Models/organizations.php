<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * نموذج المنشآت (Organization)
 * 
 * يمثل منشأة (جهة) مسجلة في النظام، مثل شركات التمويل، الجهات الحكومية، شركات الخدمات، الأفراد المستفيدين.
 */
class Organization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'organizations';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'cr_number',
        'type',
        'is_trusted',
        'subscription_package',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'is_trusted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدمين (موظفي المنشأة) عبر جدول org_users
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'org_id');
    }

    /**
     * العلاقة مع المستخدمين (موظفي المنشأة) بطريقة مباشرة
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'org_users', 'org_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * العلاقة مع الحسابات البنكية للمنشأة
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(OrganizationAccount::class, 'org_id');
    }

    /**
     * العلاقة مع العقود (كجهة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'org_id');
    }

    /**
     * العلاقة مع الاستقطاعات (كجهة مستفيدة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'org_id');
    }

    /**
     * العلاقة مع الاشتراكات
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'org_id');
    }

    /**
     * العلاقة مع الموافقات (كجهة)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function consents()
    {
        return $this->hasMany(Consent::class, 'org_id');
    }

    /**
     * نطاق للبحث عن المنشآت الموثوقة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * نطاق للبحث حسب نوع المنشأة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * نطاق للبحث حسب باقة الاشتراك
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $package
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPackage($query, $package)
    {
        return $query->where('subscription_package', $package);
    }

    /**
     * الحصول على الاشتراك النشط الحالي
     *
     * @return \App\Models\Subscription|null
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();
    }

    /**
     * التحقق مما إذا كانت المنشأة لديها اشتراك نشط
     *
     * @return bool
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * دالة helper للحصول على اسم نوع المنشأة بالعربية
     *
     * @return string
     */
    public function getTypeNameAttribute()
    {
        $types = [
            'funding' => 'تمويل',
            'leasing' => 'تأجير',
            'government' => 'حكومية',
            'billing' => 'فوترة',
            'telecom' => 'اتصالات',
            'utility' => 'خدمات',
            'individual_beneficiary' => 'فرد مستفيد',
            'other' => 'أخرى',
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * دالة helper للحصول على اسم باقة الاشتراك بالعربية
     *
     * @return string|null
     */
    public function getPackageNameAttribute()
    {
        $packages = [
            'small' => 'صغيرة',
            'medium' => 'متوسطة',
            'large' => 'كبيرة',
            'government' => 'حكومية',
        ];

        return $packages[$this->subscription_package] ?? $this->subscription_package;
    }
}
