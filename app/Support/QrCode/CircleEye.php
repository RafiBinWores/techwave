<?php

namespace App\Support\QrCode;

use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Path\Path;

class CircleEye implements EyeInterface
{
    public function getExternalPath(): Path
    {
        return $this->circle(3.5)->append($this->circle(2.5, false));
    }

    public function getInternalPath(): Path
    {
        // A slightly fuller center preserves finder detection after rasterization.
        return $this->circle(1.65);
    }

    private function circle(float $radius, bool $clockwise = true): Path
    {
        $direction = $clockwise ? 1 : -1;

        return (new Path)
            ->move($radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, $radius * $direction)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, -$radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, -$radius * $direction)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, $radius, 0)
            ->close();
    }
}
