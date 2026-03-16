<?php

declare(strict_types=1);

namespace App\Service;

class ProgressHelper
{
    public function getVisualProgress(int $current, int $total): float
    {
        if ($total === 0) return 0.0;
        $percent = ($current / $total) * 100;
        
        // Effet d'accélération : courbe quadratique pour booster visuellement la fin
        return (float) (($percent ** 1.2) / (100 ** 0.2));
    }
}
