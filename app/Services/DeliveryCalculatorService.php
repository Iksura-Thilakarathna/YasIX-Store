<?php

namespace App\Services;

use App\Repositories\DeliveryRuleRepository;

class DeliveryCalculatorService extends BaseService
{
    private DeliveryRuleRepository $deliveryRuleRepository;

    public function __construct(?DeliveryRuleRepository $deliveryRuleRepository = null)
    {
        $this->deliveryRuleRepository = $deliveryRuleRepository ?? new DeliveryRuleRepository();
    }

    public function getActiveRules(): array
    {
        return $this->deliveryRuleRepository->getAllActive();
    }

    public function calculateItemWeight(
        float $actualWeightKg,
        float $lengthCm,
        float $widthCm,
        float $heightCm,
        int $volumetricDivisor = 5000
    ): array {
        $actualWeight = max(0.00, $actualWeightKg);
        $divisor = ($volumetricDivisor > 0) ? $volumetricDivisor : 5000;

        $length = max(0.00, $lengthCm);
        $width = max(0.00, $widthCm);
        $height = max(0.00, $heightCm);

        $volumetricWeight = ($length * $width * $height) / $divisor;
        $chargeableWeight = max($actualWeight, $volumetricWeight);

        return [
            'actual_weight_kg' => round($actualWeight, 2),
            'volumetric_weight_kg' => round($volumetricWeight, 2),
            'chargeable_weight_kg' => round($chargeableWeight, 2)
        ];
    }

    public function calculateDelivery(array $cartItems, int|string $ruleIdentifier): array
    {
        $rule = null;
        if (is_numeric($ruleIdentifier)) {
            $rule = $this->deliveryRuleRepository->findById((int)$ruleIdentifier);
        } else {
            $rule = $this->deliveryRuleRepository->findByRegionName((string)$ruleIdentifier);
        }

        if (!$rule) {
            return [
                'success' => false,
                'message' => 'Selected delivery region is invalid or currently unsupported for delivery.'
            ];
        }

        $divisor = !empty($rule['volumetric_divisor']) ? (int)$rule['volumetric_divisor'] : 5000;
        $baseFee = (float)$rule['base_fee'];
        $includedWeight = (float)($rule['included_weight_kg'] ?? 1.00);
        $additionalKgFee = (float)$rule['additional_kg_fee'];
        $handlingFee = (float)($rule['handling_fee'] ?? 0.00);

        $totalActualWeight = 0.00;
        $totalVolumetricWeight = 0.00;
        $totalRawChargeable = 0.00;

        foreach ($cartItems as $item) {
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $weight = max(0.00, (float)($item['weight'] ?? 0.00));
            $len = max(0.00, (float)($item['package_length'] ?? 0.00));
            $w = max(0.00, (float)($item['package_width'] ?? 0.00));
            $h = max(0.00, (float)($item['package_height'] ?? 0.00));

            $itemWeightData = $this->calculateItemWeight($weight, $len, $w, $h, $divisor);

            $totalActualWeight += ($itemWeightData['actual_weight_kg'] * $qty);
            $totalVolumetricWeight += ($itemWeightData['volumetric_weight_kg'] * $qty);
            $totalRawChargeable += ($itemWeightData['chargeable_weight_kg'] * $qty);
        }

        // Standard local logistics rule: Round chargeable weight up to the next 0.5 kg
        $chargeableWeight = ceil($totalRawChargeable * 2) / 2;
        if ($chargeableWeight <= 0) {
            $chargeableWeight = 0.5; // Minimum billing increment
        }

        // Check if weight exceeds configured max limit
        $maxWeight = (float)$rule['max_weight_kg'];
        if ($maxWeight > 0 && $chargeableWeight > $maxWeight) {
            return [
                'success' => false,
                'message' => "Order weight ({$chargeableWeight} kg) exceeds maximum courier threshold ({$maxWeight} kg) for {$rule['region_name']}."
            ];
        }

        $extraWeight = max(0.00, $chargeableWeight - $includedWeight);
        // Billing extra weight: rounded up to whole kg increments above allowance
        $billableExtraKg = ceil($extraWeight);
        $extraWeightFee = $billableExtraKg * $additionalKgFee;

        $totalDeliveryFee = round($baseFee + $extraWeightFee + $handlingFee, 2);

        return [
            'success' => true,
            'rule_id' => (int)$rule['id'],
            'region_name' => $rule['region_name'],
            'actual_weight_kg' => round($totalActualWeight, 2),
            'volumetric_weight_kg' => round($totalVolumetricWeight, 2),
            'chargeable_weight_kg' => round($chargeableWeight, 2),
            'included_weight_kg' => round($includedWeight, 2),
            'base_fee' => $baseFee,
            'extra_weight_fee' => $extraWeightFee,
            'handling_fee' => $handlingFee,
            'total_delivery_fee' => $totalDeliveryFee,
            'formatted_delivery_fee' => formatCurrency($totalDeliveryFee),
            'estimated_days' => '5–7 working days'
        ];
    }
}
