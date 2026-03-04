<?php

namespace App\Services;

use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Support\Facades\View;

/**
 * خدمة إنشاء ملفات PDF
 * 
 * تقوم هذه الخدمة بتوحيد عملية إنشاء ملفات PDF
 * باستخدام مكتبة Dompdf، مع توفير دوال مساعدة لتحميل القوالب
 * وتعيين خيارات الطباعة.
 */
class PdfService
{
    /**
     * إنشاء ملف PDF من قالب Blade
     *
     * @param string $view اسم القالب (مثل 'pdf.customer.contract')
     * @param array $data البيانات التي تمرر إلى القالب
     * @param array $options خيارات إضافية لـ Dompdf (اختياري)
     * @return \Barryvdh\Dompdf\PDF
     */
    public function generateFromView(string $view, array $data = [], array $options = [])
    {
        // إعدادات افتراضية
        $defaultOptions = [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'options' => [
                'defaultFont' => 'Tajawal', // دعم اللغة العربية
                'isRemoteEnabled' => true,   // لتحميل الصور من URLs
                'isHtml5ParserEnabled' => true,
            ]
        ];

        // دمج الخيارات
        $config = array_merge($defaultOptions, $options);

        // إنشاء PDF
        $pdf = Pdf::loadView($view, $data);

        // تطبيق الخيارات
        $pdf->setPaper($config['paper'], $config['orientation']);

        return $pdf;
    }

    /**
     * تحميل ملف PDF مباشرة (force download)
     *
     * @param string $view
     * @param array $data
     * @param string $filename اسم الملف
     * @param array $options
     * @return \Illuminate\Http\Response
     */
    public function download(string $view, array $data, string $filename, array $options = [])
    {
        $pdf = $this->generateFromView($view, $data, $options);
        return $pdf->download($filename);
    }

    /**
     * عرض ملف PDF في المتصفح
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return \Illuminate\Http\Response
     */
    public function stream(string $view, array $data, string $filename, array $options = [])
    {
        $pdf = $this->generateFromView($view, $data, $options);
        return $pdf->stream($filename);
    }

    /**
     * حفظ ملف PDF على الخادم
     *
     * @param string $view
     * @param array $data
     * @param string $path مسار الحفظ الكامل (مثل storage_path('app/public/reports/file.pdf'))
     * @param array $options
     * @return bool
     */
    public function save(string $view, array $data, string $path, array $options = [])
    {
        $pdf = $this->generateFromView($view, $data, $options);
        return file_put_contents($path, $pdf->output()) !== false;
    }

    /**
     * الحصول على محتوى الـ PDF كـ string
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return string
     */
    public function output(string $view, array $data, array $options = []): string
    {
        $pdf = $this->generateFromView($view, $data, $options);
        return $pdf->output();
    }
}
