{{-- resources/views/pdf/customer_contracts.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير العقود</title>
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
        .contracts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .contracts-table th {
            background-color: #1e3c72;
            color: white;
            padding: 10px;
            font-weight: 500;
            text-align: center;
        }
        .contracts-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .contracts-table tr:nth-child(even) {
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
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-active { background-color: #28a745; color: white; }
        .badge-pending { background-color: #ffc107; color: #333; }
        .badge-closed { background-color: #6c757d; color: white; }
        .badge-rejected { background-color: #dc3545; color: white; }
        .badge-defaulted { background-color: #343a40; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير العقود</h1>
        <p>منصة تكـ للاستقطاع المالي</p>
        <p>تاريخ التقرير: {{ $generated_at }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td><strong>اسم العميل:</strong> {{ $user->full_name }}</td>
                <td><strong>رقم الهوية:</strong> {{ $user->national_id }}</td>
            </tr>
            <tr>
                <td><strong>البريد الإلكتروني:</strong> {{ $user->email ?? '--' }}</td>
                <td><strong>الجوال:</strong> {{ $user->phone ?? '--' }}</td>
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

    <table class="contracts-table">
        <thead>
            <tr>
                <th>رقم العقد</th>
                <th>الجهة</th>
                <th>نوع العقد</th>
                <th>المبلغ الإجمالي</th>
                <th>المدفوع</th>
                <th>المتبقي</th>
                <th>الحالة</th>
                <th>تاريخ الإنشاء</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $contract)
            <tr>
                <td>{{ $contract->contract_number }}</td>
                <td>{{ $contract->organization->name ?? 'غير محدد' }}</td>
                <td>{{ $contract->contract_type_name }}</td>
                <td>{{ number_format($contract->total_amount, 2) }}</td>
                <td>{{ number_format($contract->paid_amount, 2) }}</td>
                <td>{{ number_format($contract->remaining_amount, 2) }}</td>
                <td>
                    @if($contract->status == 'active')
                        <span class="badge badge-active">نشط</span>
                    @elseif($contract->status == 'pending_approval')
                        <span class="badge badge-pending">معلق</span>
                    @elseif($contract->status == 'closed')
                        <span class="badge badge-closed">منتهي</span>
                    @elseif($contract->status == 'rejected')
                        <span class="badge badge-rejected">مرفوض</span>
                    @elseif($contract->status == 'defaulted')
                        <span class="badge badge-defaulted">متعثر</span>
                    @else
                        {{ $contract->status }}
                    @endif
                </td>
                <td>{{ $contract->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <p><strong>إجمالي عدد العقود:</strong> {{ $total_count }}</p>
        <p><strong>إجمالي المبالغ:</strong> {{ number_format($total_amount, 2) }} ريال</p>
        <p><strong>إجمالي المدفوع:</strong> {{ number_format($paid_amount, 2) }} ريال</p>
        <p><strong>إجمالي المتبقي:</strong> {{ number_format($remaining_amount, 2) }} ريال</p>
    </div>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام تكـ للاستقطاع المالي © {{ date('Y') }}</p>
        <p>هذا التقرير إلكتروني ولا يحتاج إلى توقيع</p>
    </div>
</body>
</html>
