<?php

namespace App\Support\QrCode;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\CompositeEye;
use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\ModuleInterface;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use SimpleSoftwareIO\QrCode\Generator;

class DesignGenerator extends Generator
{
    public const MODULES = ['square' => 'Square', 'soft' => 'Soft square', 'round' => 'Rounded', 'dot' => 'Dots', 'dots-large' => 'Large dots'];

    public const BORDERS = [
        'square' => 'Square', 'rounded' => 'Rounded', 'circle' => 'Circle', 'leaf' => 'Leaf',
        'leaf-reverse' => 'Leaf reverse', 'square-circle' => 'Square circle',
        'square-rounded' => 'Square rounded', 'rounded-leaf' => 'Rounded leaf',
    ];

    public const CENTERS = [
        'square' => 'Square', 'rounded' => 'Rounded', 'circle' => 'Circle', 'leaf' => 'Leaf',
        'sunburst' => 'Sunburst', 'star' => 'Star', 'diamond' => 'Diamond', 'cross' => 'Cross',
    ];

    private string $designModule = 'square';

    private string $borderStyle = 'square';

    private string $centerStyle = 'square';

    public function design(string $module, string $border, string $center): self
    {
        $this->designModule = array_key_exists($module, self::MODULES) ? $module : 'square';
        $this->borderStyle = array_key_exists($border, self::BORDERS) ? $border : 'square';
        $this->centerStyle = array_key_exists($center, self::CENTERS) ? $center : 'square';

        return $this;
    }

    public function getModule(): ModuleInterface
    {
        $module = match ($this->designModule) {
            'soft' => new RoundnessModule(0.25),
            'round' => new RoundnessModule(0.5),
            'dot' => new DotsModule(0.9),
            'dots-large' => new DotsModule(1),
            default => SquareModule::instance(),
        };

        return $this->designModule === 'square' ? $module : new ScanSafeModule($module);
    }

    public function getEye(): EyeInterface
    {
        return new CompositeEye($this->eyeShape($this->borderStyle), $this->eyeShape($this->centerStyle));
    }

    public static function eyePreview(string $style, bool $border): string
    {
        $eye = (new self)->design('square', $border ? $style : 'square', $border ? 'square' : $style)->getEye();
        $backend = new SvgImageBackEnd;
        $backend->new(64, new Rgb(255, 255, 255));
        $backend->translate(32, 32);
        $backend->scale($border ? 7 : 14);
        $backend->drawPathWithColor($border ? $eye->getExternalPath() : $eye->getInternalPath(), new Rgb(17, 24, 39));

        return $backend->done();
    }

    private function eyeShape(string $style): EyeInterface
    {
        return match ($style) {
            'circle' => new CircleEye,
            'square' => SquareEye::instance(),
            default => new DecorativeEye($style),
        };
    }
}
