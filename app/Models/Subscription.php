<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * نموذج اشتراكات المنشآت (Subscription)
 * 
 * يمثل اشتراك منشأة (جهة) في إحدى الباقات (صغيرة، متوسطة، كبيرة، حكومية).
 * يحتوي على تواريخ البدء والانتهاء وحالة الاشتراك.
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'subscriptions';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'org_id',
        'package',
        'price',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
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
     * نطاق للبحث عن الاشتراكات النشطة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * نطاق للبحث عن الاشتراكات المنتهية
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * نطاق للبحث عن الاشتراكات الملغاة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * نطاق للبحث حسب نوع الباقة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $package
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfPackage($query, $package)
    {
        return $query->where('package', $package);
    }

    /**
     * دالة helper للتحقق مما إذا كان الاشتراك ساري المفعول حالياً
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }

    /**
     * دالة helper للحصول على اسم الباقة بالعربية
     *
     * @return string
     */
    public function getPackageNameAttribute()
    {
        $packages = [
            'small' => 'صغيرة',
            'medium' => 'متوسطة',
            'large' => 'كبيرة',
            'government' => 'حكومية',
        ];

        return $packages[$this->package] ?? $this->package;
    }

    /**
     * دالة helper للحصول على حالة الاشتراك بالعربية
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'active' => 'نشط',
            'expired' => 'منتهي',
            'canceled' => 'ملغي',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
