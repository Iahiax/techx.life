<?php

namespace App\Services\OpenBanking;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة التكامل مع Lean Open Banking
 * 
 * توفر هذه الخدمة واجهة للتعامل مع API الخاصة بـ Lean
 * لربط الحسابات المصرفية، الاستعلام عن الأرصدة، وإنشاء التحويلات.
 */
class LeanService
{
    /**
     * عنوان URL الأساسي لـ Lean API
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * معرف التطبيق (App ID)
     *
     * @var string
     */
    protected $appId;

    /**
     * مفتاح التطبيق السري (App Secret)
     *
     * @var string
     */
    protected $appSecret;

    /**
     * مفتاح سر الويب هوك (Webhook Secret)
     *
     * @var string
     */
    protected $webhookSecret;

    /**
     * عنوان URL لإعادة التوجيه بعد ربط الحساب
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * إنشاء مثيل جديد من الخدمة
     */
    public function __construct()
    {
        $this->baseUrl = config('services.lean.base_url', 'https://api.lean.dev');
        $this->appId = config('services.lean.app_id');
        $this->appSecret = config('services.lean.app_secret');
        $this->webhookSecret = config('services.lean.webhook_secret');
        $this->redirectUrl = config('services.lean.redirect_url');
    }

    /**
     * تبادل رمز التفويض (Authorization Code) للحصول على رمز الوصول (Access Token)
     *
     * @param string $code
     * @return array
     * @throws Exception
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('Lean exchangeCode failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل تبادل رمز التفويض مع Lean: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * الحصول على قائمة حسابات المستخدم
     *
     * @param string $accessToken
     * @return array
     * @throws Exception
     */
    public function getAccounts(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("{$this->baseUrl}/api/v1/accounts");

        if ($response->failed()) {
            Log::error('Lean getAccounts failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل جلب الحسابات من Lean: ' . $response->body());
        }

        return $response->json()['accounts'] ?? [];
    }

    /**
     * الحصول على رصيد حساب معين
     *
     * @param string $accessToken
     * @param string $accountId
     * @return float
     * @throws Exception
     */
    public function getAccountBalance(string $accessToken, string $accountId): float
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("{$this->baseUrl}/api/v1/accounts/{$accountId}/balance");

        if ($response->failed()) {
            Log::error('Lean getAccountBalance failed', [
                'account_id' => $accountId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل جلب الرصيد من Lean: ' . $response->body());
        }

        $data = $response->json();
        return (float) ($data['balance'] ?? 0);
    }

    /**
     * إنشاء تحويل (استقطاع) من حساب إلى آخر
     *
     * @param string $accessToken
     * @param string $fromAccountId
     * @param string $toIban
     * @param float $amount
     * @param string $description
     * @return array
     * @throws Exception
     */
    public function createTransfer(
        string $accessToken,
        string $fromAccountId,
        string $toIban,
        float $amount,
        string $description = ''
    ): array {
        $payload = [
            'from_account_id' => $fromAccountId,
            'to_iban' => $toIban,
            'amount' => $amount,
            'currency' => 'SAR',
            'description' => $description,
            'reference' => uniqid('trx_', true), // مرجع فريد للعملية
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post("{$this->baseUrl}/api/v1/transfers", $payload);

        if ($response->failed()) {
            Log::error('Lean createTransfer failed', [
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل إنشاء التحويل في Lean: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * الحصول على تفاصيل تحويل معين
     *
     * @param string $accessToken
     * @param string $transferId
     * @return array
     * @throws Exception
     */
    public function getTransfer(string $accessToken, string $transferId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("{$this->baseUrl}/api/v1/transfers/{$transferId}");

        if ($response->failed()) {
            Log::error('Lean getTransfer failed', [
                'transfer_id' => $transferId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('فشل جلب تفاصيل التحويل من Lean: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * الحصول على رابط ربط حساب (Connect URL) لإعادة توجيه المستخدم
     *
     * @param string $customerId معرف العميل في نظامنا (مثل user id)
     * @param string $nationalId رقم الهوية (اختياري)
     * @return string
     */
    public function getConnectUrl(string $customerId, string $nationalId = ''): string
    {
        $params = [
            'app_id' => $this->appId,
            'redirect_url' => $this->redirectUrl,
            'customer_id' => $customerId,
            'national_id' => $nationalId,
            'response_type' => 'code',
            'scope' => 'accounts,transfers',
        ];

        $query = http_build_query($params);
        return "{$this->baseUrl}/connect?{$query}";
    }

    /**
     * التحقق من توقيع الويب هوك (Webhook Signature)
     *
     * @param string $payload محتوى الطلب (raw body)
     * @param string $signature التوقيع المرسل في الرأس
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($computed, $signature);
    }
}
