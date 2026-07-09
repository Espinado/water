<?php

namespace App\Enums;

enum ServiceCalcType: string
{
    case MeterCold = 'meter_cold';
    case MeterHot = 'meter_hot';
    case MeterHotHeating = 'meter_hot_heating';
    case Area = 'area';
    case Fixed = 'fixed';
    case Correction = 'correction';
}
