<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة التكامل مع منصة سداد (Sadad)
 * 
 * توفر هذه الخدمة واجهة للتعامل مع API الخاصة بمنصة سداد
 * للاستعلام عن الفواتير وإنشاء طلبات الدفع والتحقق من حالة الفواتير.
 */
class SadadService
{
    /**
     * عنوان URL الأساسي لـ API سداد
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * معرف التاجر (Merchant ID)
     *
     * @var string
     */
    protected $merchantId;

    /**
     * المفتاح السري للتاجر (Merchant Secret)
     *
     * @var string
     */
    protected $merchantSecret;

    /**
     * رمز المصادقة (Access Token)
     *
     * @var string|null
     */
    protected $accessToken;

    /**
     * إنشاء مثيل جديد من الخدمة
     */
    public function __construct()
    {
        $this->baseUrl = config('services.sadad.base_url', 'https://api.sadad.sa');
        $this->merchantId = config('services.sadad.merchant_id');
        $this->merchantSecret = config('services.sadad.merchant_secret');
        $this->accessToken = config('services.sadad.access_token'); // قد يكون مخزناً مسبقاً
    }

    /**
     * الحصول على رمز وصول جديد (Access Token) من سداد
     *
     * @return string
     * @throws Exception
     */
    public function authenticate(): string
    {
        $response = Http::post("{$this->baseUrl}/v1/auth/token", [
            'merchant_id' => $this->merchantId,
            'merchant_secret' => $this->merchantSecret,
        ]);

        if ($response->failed()) {
            Log::error('Sadad authentication failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل المصادقة مع سداد: ' . $response->body());
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'] ?? null;

        if (!$this->accessToken) {
            throw new Exception('لم يتم استلام رمز وصول من سداد');
        }

        return $this->accessToken;
    }

    /**
     * إعداد الرؤوس (Headers) للطلبات
     *
     * @return array
     * @throws Exception
     */
    protected function getHeaders(): array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * الاستعلام عن الفواتير الخاصة بعميل (برقم الهوية)
     *
     * @param string $nationalId رقم الهوية الوطنية للعميل
     * @param string|null $billerCode رمز المفوتر (اختياري)
     * @return array قائمة الفواتير
     * @throws Exception
     */
    public function getBills(string $nationalId, ?string $billerCode = null): array
    {
        $headers = $this->getHeaders();

        $payload = [
            'national_id' => $nationalId,
        ];

        if ($billerCode) {
            $payload['biller_code'] = $billerCode;
        }

        $response = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/v1/bills/inquiry", $payload);

        if ($response->failed()) {
            Log::error('Sadad getBills failed', [
                'national_id' => $nationalId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل الاستعلام عن الفواتير من سداد: ' . $response->body());
        }

        return $response->json()['bills'] ?? [];
    }

    /**
     * دفع فاتورة معينة
     *
     * @param string $billNumber رقم الفاتورة
     * @param string $nationalId رقم الهوية الوطنية للعميل
     * @param float $amount المبلغ المطلوب دفعه
     * @param string $customerId معرف العميل في نظامنا (اختياري)
     * @return array بيانات عملية الدفع
     * @throws Exception
     */
    public function payBill(string $billNumber, string $nationalId, float $amount, string $customerId = ''): array
    {
        $headers = $this->getHeaders();

        $payload = [
            'bill_number' => $billNumber,
            'national_id' => $nationalId,
            'amount' => $amount,
            'customer_reference' => $customerId,
            'merchant_id' => $this->merchantId,
        ];

        $response = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/v1/bills/pay", $payload);

        if ($response->failed()) {
            Log::error('Sadad payBill failed', [
                'bill_number' => $billNumber,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل دفع الفاتورة عبر سداد: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * الاستعلام عن حالة فاتورة
     *
     * @param string $billNumber رقم الفاتورة
     * @return array حالة الفاتورة
     * @throws Exception
     */
    public function getBillStatus(string $billNumber): array
    {
        $headers = $this->getHeaders();

        $payload = [
            'bill_number' => $billNumber,
        ];

        $response = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/v1/bills/status", $payload);

        if ($response->failed()) {
            Log::error('Sadad getBillStatus failed', [
                'bill_number' => $billNumber,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل الاستعلام عن حالة الفاتورة من سداد: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * إلغاء عملية دفع (إذا كان مسموحاً)
     *
     * @param string $paymentReference مرجع الدفع
     * @return array نتيجة الإلغاء
     * @throws Exception
     */
    public function cancelPayment(string $paymentReference): array
    {
        $headers = $this->getHeaders();

        $payload = [
            'payment_reference' => $paymentReference,
        ];

        $response = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/v1/payments/cancel", $payload);

        if ($response->failed()) {
            Log::error('Sadad cancelPayment failed', [
                'payment_reference' => $paymentReference,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل إلغاء الدفع عبر سداد: ' . $response->body());
        }

        return $response->json();
    }
}
