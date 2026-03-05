<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - الصفحة غير موجودة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .error-container {
            max-width: 600px;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 6rem;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 0;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .btn-primary-custom {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background-color: #0f2b4f;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>عذراً، الصفحة التي تبحث عنها غير موجودة</h2>
        <p>قد تكون الصفحة قد تم نقلها أو حذفها أو أن الرابط الذي أدخلته غير صحيح.</p>
        <a href="{{ url('/') }}" class="btn-primary-custom">العودة إلى الصفحة الرئيسية</a>
    </div>
</body>
</html>
