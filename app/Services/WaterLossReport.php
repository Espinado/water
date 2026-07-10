<?php

namespace App\Services;

use App\Models\SupplierInvoice;

class WaterLossReport
{
    public function __construct(
        protected WaterConsumptionAggregator $aggregator,
    ) {}

    /**
     * @return array{
     *     consumption: array<string, int|string>,
     *     invoice: array<string, string>|null,
     *     loss: array<string, string>|null,
     *     has_invoice: bool,
     *     is_complete: bool,
     * }
     */
    public function forPeriod(int $year, int $month, ?SupplierInvoice $invoice = null): array
    {
        $consumption = $this->aggregator->aggregateForPeriod($year, $month);
        $isComplete = $consumption['missing_apartments'] === 0
            && $consumption['incomplete_apartments'] === 0;

        if ($invoice === null) {
            return [
                'consumption' => $consumption,
                'invoice' => null,
                'loss' => null,
                'has_invoice' => false,
                'is_complete' => $isComplete,
            ];
        }

        return [
            'consumption' => $consumption,
            'invoice' => [
                'cold_m3' => (string) $invoice->cold_m3,
                'hot_m3' => (string) $invoice->hot_m3,
                'cold_amount' => (string) $invoice->cold_amount,
                'hot_amount' => (string) $invoice->hot_amount,
            ],
            'loss' => [
                'cold_m3' => $this->subtract((string) $invoice->cold_m3, $consumption['cold_m3'], 3),
                'hot_m3' => $this->subtract((string) $invoice->hot_m3, $consumption['hot_m3'], 3),
                'cold_amount' => $this->subtract((string) $invoice->cold_amount, $consumption['cold_amount'], 2),
                'hot_amount' => $this->subtract((string) $invoice->hot_amount, $consumption['hot_amount'], 2),
            ],
            'has_invoice' => true,
            'is_complete' => $isComplete,
        ];
    }

    protected function subtract(string $invoiceValue, string $consumptionValue, int $scale): string
    {
        $result = (float) $invoiceValue - (float) $consumptionValue;

        return number_format($result, $scale, '.', '');
    }
}
