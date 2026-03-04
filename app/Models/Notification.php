<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * نموذج الإشعارات (Notification)
 * 
 * يمثل إشعاراً يرسل إلى المستخدم (عميل، موظف منشأة، مشرف)
 * لإعلامه بأحداث مهمة مثل وصول عقد جديد، تغيير حالة استقطاع، إلخ.
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'contract_id',
        'is_read',
    ];

    /**
     * تحويل الحقول إلى أنواع معينة
     *
     * @var array
     */
    protected $casts = [
        'is_read' => 'boolean',
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
     * العلاقة مع العقد (اختياري)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * نطاق للبحث عن الإشعارات غير المقروءة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * نطاق للبحث عن الإشعارات المقروءة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * نطاق للبحث حسب نوع الإشعار
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
     * تعليم الإشعار كمقروء
     *
     * @return void
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->saveQuietly();
        }
    }
}
