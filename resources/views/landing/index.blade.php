{{-- resources/views/landing/index.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تكـ — منصة الاستقطاع المالي الذكية</title>
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts (Tajawal) -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            color: #333;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand img {
            height: 40px;
        }
        .hero-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%);
            padding: 80px 0;
            text-align: center;
        }
        .hero-section h1 {
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .hero-section .lead {
            font-size: 1.3rem;
            color: #555;
        }
        .btn-primary-custom {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background-color: #0f2b4f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.3);
        }
        .btn-outline-custom {
            background-color: transparent;
            border: 2px solid #1e3c72;
            color: #1e3c72;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover {
            background-color: #1e3c72;
            color: white;
        }
        .section-title {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 50%;
            transform: translateX(50%);
            width: 80px;
            height: 4px;
            background-color: #1e3c72;
            border-radius: 2px;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            text-align: center;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 3rem;
            color: #1e3c72;
            margin-bottom: 20px;
        }
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            height: 100%;
            transition: 0.3s;
            border: 1px solid #eee;
        }
        .pricing-card.popular {
            border: 2px solid #1e3c72;
            transform: scale(1.05);
        }
        .pricing-card .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e3c72;
        }
        .pricing-card .period {
            color: #777;
        }
        .pricing-card ul {
            list-style: none;
            padding: 0;
            margin: 30px 0;
            text-align: right;
        }
        .pricing-card ul li {
            margin: 15px 0;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .pricing-card ul li i {
            color: #1e3c72;
            margin-left: 10px;
        }
        .steps-section {
            background: #f8fafc;
        }
        .step-item {
            text-align: center;
            padding: 30px;
        }
        .step-number {
            width: 60px;
            height: 60px;
            background: #1e3c72;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        .contact-info {
            background: #1e3c72;
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .contact-info h3 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .contact-info .phone-number {
            font-size: 2.5rem;
            font-weight: 700;
            direction: ltr;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 15px 40px;
            border-radius: 50px;
            margin-top: 20px;
        }
        footer {
            background: #0f2b4f;
            color: white;
            padding: 30px 0;
            text-align: center;
        }
        footer a {
            color: white;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
        .login-buttons .btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    {{-- شريط التنقل --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="https://via.placeholder.com/150x50/1e3c72/ffffff?text=Techxx" alt="Techxx">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#features">المميزات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">الباقات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">آلية العمل</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">اتصل بنا</a></li>
                </ul>
                <div class="login-buttons">
                    <a href="{{ route('nafath.redirect') }}" class="btn btn-outline-custom"><i class="fas fa-fingerprint ms-2"></i>دخول الأفراد (نفاذ)</a>
                    <a href="{{ route('tawtheeq.redirect') }}" class="btn btn-primary-custom"><i class="fas fa-building ms-2"></i>دخول المنشآت (توثيق)</a>
                </div>
            </div>
        </div>
    </nav>

    {{-- القسم الرئيسي (Hero) --}}
    <section class="hero-section">
        <div class="container">
            <h1>منصة الاستقطاع المالي الذكية</h1>
            <p class="lead">اربط حسابك البنكي، وافق على العقود، واستمتع باستقطاعات شهرية آلية مع تقارير شاملة</p>
            <div class="mt-5">
                <a href="#features" class="btn btn-primary-custom btn-lg mx-2">اكتشف المزيد</a>
                <a href="#contact" class="btn btn-outline-custom btn-lg mx-2">تواصل معنا</a>
            </div>
        </div>
    </section>

    {{-- قسم المميزات --}}
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="section-title text-center">لماذا تختار تكـ؟</h2>
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h4>دخول آمن وموثوق</h4>
                        <p>دخول الأفراد عبر النفاذ الوطني ودخول المنشآت عبر توثيق من مركز المعلومات الوطني</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <h4>استقطاع ذكي</h4>
                        <p>تحديد حساب الراتب تلقائياً، وإمكانية الاستقطاع من حسابات متعددة للتجار ورجال الأعمال</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-percent"></i></div>
                        <h4>عمولة منافسة 1%</h4>
                        <p>1% من المبلغ المستقطع (بحد أدنى 1 ريال وأقصى 10 ريالات) باستثناء التمويلات الكبيرة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                        <h4>تقارير PDF شاملة</h4>
                        <p>طباعة كل العمليات للعميل، الجهة، أو الحكومة لمتابعة الحقوق بدقة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                        <h4>جدولة الاستقطاع الشهري</h4>
                        <p>تحديد مواعيد ثابتة للاستقطاع مع إمكانية السداد المبكر وإيقاف الاستقطاع في الحالات الاستثنائية</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-university"></i></div>
                        <h4>دعم جميع الجهات</h4>
                        <p>شركات تمويل، جهات حكومية، فواتير سداد (كهرباء، مياه، اتصالات)، وأفراد</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- قسم باقات الاشتراك --}}
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center">باقات مرنة تناسب جميع الجهات</h2>
            <div class="row g-4 mt-5 align-items-center">
                <div class="col-md-3">
                    <div class="pricing-card">
                        <h4>الصغيرة</h4>
                        <div class="price">299 <span class="period">ريال/شهر</span></div>
                        <ul>
                            <li><i class="fas fa-check"></i> حتى 50 عقد نشط</li>
                            <li><i class="fas fa-check"></i> تقارير أساسية</li>
                            <li><i class="fas fa-check"></i> دعم فني عبر البريد</li>
                            <li><i class="fas fa-times"></i> واجهة برمجية (API)</li>
                        </ul>
                        <a href="#" class="btn btn-outline-custom">اشترك الآن</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="pricing-card">
                        <h4>المتوسطة</h4>
                        <div class="price">599 <span class="period">ريال/شهر</span></div>
                        <ul>
                            <li><i class="fas fa-check"></i> حتى 200 عقد نشط</li>
                            <li><i class="fas fa-check"></i> تقارير متقدمة</li>
                            <li><i class="fas fa-check"></i> دعم فني عبر الهاتف</li>
                            <li><i class="fas fa-check"></i> واجهة برمجية (API)</li>
                        </ul>
                        <a href="#" class="btn btn-outline-custom">اشترك الآن</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="pricing-card popular">
                        <span class="badge bg-primary">الأكثر طلباً</span>
                        <h4>الكبيرة</h4>
                        <div class="price">999 <span class="period">ريال/شهر</span></div>
                        <ul>
                            <li><i class="fas fa-check"></i> عقود غير محدودة</li>
                            <li><i class="fas fa-check"></i> تقارير مخصصة</li>
                            <li><i class="fas fa-check"></i> دعم فني 24/7</li>
                            <li><i class="fas fa-check"></i> واجهة برمجية متكاملة</li>
                        </ul>
                        <a href="#" class="btn btn-primary-custom">اشترك الآن</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="pricing-card">
                        <h4>الحكومية</h4>
                        <div class="price">اتصل بنا</div>
                        <ul>
                            <li><i class="fas fa-check"></i> حلول مخصصة للجهات الحكومية</li>
                            <li><i class="fas fa-check"></i> تكامل مع الأنظمة الحكومية</li>
                            <li><i class="fas fa-check"></i> دعم أولوي</li>
                            <li><i class="fas fa-check"></i> تقارير رقابية</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-custom">تواصل معنا</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- قسم آلية العمل --}}
    <section id="how-it-works" class="py-5 steps-section">
        <div class="container">
            <h2 class="section-title text-center">كيف تعمل المنصة؟</h2>
            <div class="row mt-5">
                <div class="col-md-3">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h5>ربط الحساب البنكي</h5>
                        <p>قم بربط حسابك البنكي عبر Open Banking بكل أمان.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h5>الموافقة على العقود</h5>
                        <p>تستقبل العقود من الجهات وتوافق عليها بنقرة واحدة.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h5>الاستقطاع الشهري</h5>
                        <p>نقوم بخصم الأقساط تلقائياً في الموعد المحدد.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <h5>المتابعة والتقارير</h5>
                        <p>يمكنك متابعة المبالغ المدفوعة والمتبقية وتحميل التقارير.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- قسم التواصل --}}
    <section id="contact" class="contact-info">
        <div class="container">
            <h3>تواصل مع فريقنا</h3>
            <p>للاستفسارات والمساعدة، اتصل بنا على الرقم الموحد</p>
            <div class="phone-number">
                <i class="fas fa-phone-alt ms-3"></i> 0530098089
            </div>
            <p class="mt-4">أو راسلنا على البريد الإلكتروني: <a href="mailto:info@techxx.sa" style="color:white; text-decoration:underline;">info@techxx.sa</a></p>
        </div>
    </section>

    {{-- تذييل الصفحة --}}
    <footer>
        <div class="container">
            <p>جميع الحقوق محفوظة &copy; 2025 تكـ (Techxx) | منصة الاستقطاع المالي الذكية</p>
            <p>
                <a href="#" class="mx-2">الشروط والأحكام</a> |
                <a href="#" class="mx-2">سياسة الخصوصية</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // يمكن إضافة أي كود JavaScript هنا لتحسين التفاعل
        // على سبيل المثال، تمرير سلس عند النقر على الروابط
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if(target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
