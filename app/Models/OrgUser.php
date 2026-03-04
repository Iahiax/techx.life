<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * نموذج ربط المستخدمين بالمنشآت (OrgUser)
 * 
 * يمثل جدول وسيط (pivot) يربط المستخدمين (موظفي المنشآت) بالمنشآت (الجهات)
 * مع تحديد دور المستخدم داخل المنشأة (مدير أو مستخدم عادي).
 */
class OrgUser extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'org_users';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'org_id',
        'role',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
     * نطاق للبحث عن المستخدمين الذين هم مدراء (OrgAdmin)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'OrgAdmin');
    }

    /**
     * نطاق للبحث عن المستخدمين العاديين (OrgUser)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('role', 'OrgUser');
    }

    /**
     * دالة helper للتحقق مما إذا كان المستخدم مديراً للمنشأة
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'OrgAdmin';
    }
}
