// app/Http/Controllers/Auth/NafathController.php
public function redirect()
{
    $query = http_build_query([
        'client_id' => config('services.nafath.client_id'),
        'redirect_uri' => config('services.nafath.redirect'),
        'response_type' => 'code',
        'scope' => 'identity',
    ]);
    return redirect('https://nafath.sa/auth?' . $query);
}

public function callback(Request $request)
{
    $code = $request->get('code');
    // تبادل الكود مع token
    $response = Http::post('https://nafath.sa/token', [
        'grant_type' => 'authorization_code',
        'client_id' => config('services.nafath.client_id'),
        'client_secret' => config('services.nafath.client_secret'),
        'code' => $code,
        'redirect_uri' => config('services.nafath.redirect'),
    ]);
    $tokenData = $response->json();
    // استعلام عن بيانات المستخدم
    $userInfo = Http::withHeaders([
        'Authorization' => 'Bearer ' . $tokenData['access_token']
    ])->get('https://nafath.sa/userinfo')->json();

    // البحث عن المستخدم أو إنشاؤه
    $user = User::updateOrCreate(
        ['national_id' => $userInfo['nationalId']],
        [
            'full_name' => $userInfo['fullName'],
            'phone' => $userInfo['mobile'],
            'auth_provider' => 'nafath',
            'provider_id' => $userInfo['sub'],
            'type' => 'customer',
        ]
    );

    Auth::login($user);
    return redirect()->intended('/customer/dashboard');
}
