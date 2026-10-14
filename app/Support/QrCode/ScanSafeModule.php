<?php

namespace App\Support\QrCode;

use BaconQrCode\Common\Version;
use BaconQrCode\Encoder\ByteMatrix;
use BaconQrCode\Renderer\Module\ModuleInterface;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\Path\Path;

class ScanSafeModule implements ModuleInterface
{
    public function __construct(private ModuleInterface $decorativeModule) {}

    public function createPath(ByteMatrix $matrix): Path
    {
        // Alignment modules follow the design; timing, format and version bits stay standard.
        $version = Version::getProvisionalVersionForDimension($matrix->getWidth());
        $functionPattern = $version->buildFunctionPattern();
        $alignments = new ByteMatrix($matrix->getWidth(), $matrix->getHeight());
        $centers = $version->getAlignmentPatternCenters();
        $last = count($centers) - 1;
        foreach ($centers as $row => $centerY) {
            foreach ($centers as $column => $centerX) {
                // The three finder corners have no alignment pattern.
                if (($row === 0 && ($column === 0 || $column === $last)) || ($row === $last && $column === 0)) {
                    continue;
                }
                for ($y = $centerY - 2; $y <= $centerY + 2; $y++) {
                    for ($x = $centerX - 2; $x <= $centerX + 2; $x++) {
                        $alignments->set($x, $y, 1);
                    }
                }
            }
        }
        $data = clone $matrix;
        $functions = new ByteMatrix($matrix->getWidth(), $matrix->getHeight());
        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($functionPattern->get($x, $y) && ! $alignments->get($x, $y)) {
                    $functions->set($x, $y, $matrix->get($x, $y));
                    $data->set($x, $y, 0);
                }
            }
        }

        return $this->decorativeModule->createPath($data)->append(SquareModule::instance()->createPath($functions));
    }
}
