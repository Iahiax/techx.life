{{-- resources/views/pdf/organization/deductions_report.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الاستقطاعات</title>
    <style>
        body {
            font-family: 'Tajawal', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e3c72;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e3c72;
            font-size: 24px;
            margin: 0 0 5px;
        }
        .header p {
            margin: 5px 0;
            color: #555;
        }
        .info-section {
            margin-bottom: 25px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 5px;
        }
        .deductions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .deductions-table th {
            background-color: #1e3c72;
            color: white;
            padding: 10px;
            font-weight: 500;
            text-align: center;
        }
        .deductions-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .deductions-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .summary-box {
            background-color: #e7f1ff;
            border: 1px solid #1e3c72;
            border-radius: 5px;
            padding: 10px 20px;
            margin-top: 20px;
            text-align: left;
        }
        .summary-box p {
            margin: 5px 0;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #333;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الاستقطاعات</h1>
        <p>منصة تكـ للاستقطاع المالي</p>
        <p>تاريخ التقرير: {{ $generated_at }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td><strong>اسم المنشأة:</strong> {{ $organization->name }}</td>
                <td><strong>رقم السجل:</strong> {{ $organization->cr_number }}</td>
            </tr>
            <tr>
                <td><strong>البريد الإلكتروني:</strong> {{ $organization->email ?? '--' }}</td>
                <td><strong>الهاتف:</strong> {{ $organization->phone ?? '--' }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($filters))
    <div style="margin-bottom: 15px; background: #f0f0f0; padding: 10px; border-radius: 5px;">
        <strong>معايير التصفية:</strong>
        @foreach($filters as $key => $value)
            @if(!empty($value))
                <span style="margin-left: 15px;">{{ $key }}: {{ $value }}</span>
            @endif
        @endforeach
    </div>
    @endif

    <table class="deductions-table">
        <thead>
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>رقم العقد</th>
                <th>التاريخ المقرر</th>
                <th>تاريخ التنفيذ</th>
                <th>المبلغ</th>
                <th>عمولة المنصة</th>
                <th>الحالة</th>
                <th>مرجع العملية</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deductions as $deduction)
            <tr>
                <td>{{ $deduction->id }}</td>
                <td>{{ $deduction->customer->full_name ?? 'غير معروف' }}</td>
                <td>{{ $deduction->contract->contract_number ?? '--' }}</td>
                <td>{{ $deduction->scheduled_date }}</td>
                <td>{{ $deduction->processed_date ?? '--' }}</td>
                <td>{{ number_format($deduction->amount, 2) }}</td>
                <td>{{ number_format($deduction->platform_fee, 2) }}</td>
                <td>
                    @if($deduction->status == 'success')
                        <span class="badge-success">ناجح</span>
                    @elseif($deduction->status == 'failed')
                        <span class="badge-danger">فاشل</span>
                    @elseif($deduction->status == 'processing')
                        <span class="badge-warning">قيد المعالجة</span>
                    @else
                        <span class="badge-secondary">معلق</span>
                    @endif
                </td>
                <td>{{ $deduction->transaction_reference ?? '--' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <p><strong>إجمالي عدد الاستقطاعات:</strong> {{ $total_count }}</p>
        <p><strong>الناجحة:</strong> {{ $success_count }}</p>
        <p><strong>الفاشلة:</strong> {{ $failed_count }}</p>
        <p><strong>إجمالي المبالغ المحصلة:</strong> {{ number_format($total_amount, 2) }} ريال</p>
        <p><strong>إجمالي العمولات المدفوعة:</strong> {{ number_format($total_fees, 2) }} ريال</p>
    </div>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام تكـ للاستقطاع المالي © {{ date('Y') }}</p>
        <p>هذا التقرير إلكتروني ولا يحتاج إلى توقيع</p>
    </div>
</body>
</html>
