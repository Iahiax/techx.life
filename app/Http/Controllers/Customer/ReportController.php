// app/Http/Controllers/Customer/ReportController.php
public function printContracts()
{
    $customer = auth()->user();
    $contracts = Contract::where('customer_id', $customer->id)->get();
    $pdf = Pdf::loadView('pdf.customer_contracts', compact('contracts'));
    return $pdf->download('تقرير_العقود.pdf');
}
