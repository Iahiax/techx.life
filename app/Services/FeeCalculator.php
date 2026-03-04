// app/Services/FeeCalculator.php
public function calculate($amount)
{
    $fee = $amount * 0.01;
    if ($amount <= 1000000) {
        $fee = max(1, min(10, $fee));
    }
    return round($fee, 2);
}
