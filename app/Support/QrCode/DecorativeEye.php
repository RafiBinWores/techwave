<?php

namespace App\Support\QrCode;

use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Path\Path;

class DecorativeEye implements EyeInterface
{
    public function __construct(private string $style) {}

    public function getExternalPath(): Path
    {
        [$outer, $inner] = match ($this->style) {
            'rounded' => [[1.5, 1.5, 1.5, 1.5], [0.7, 0.7, 0.7, 0.7]],
            'leaf' => [[0, 2, 2, 2], [0, 1.3, 1.3, 1.3]],
            'leaf-reverse' => [[2, 2, 0, 2], [1.3, 1.3, 0, 1.3]],
            'rounded-leaf' => [[0, 2, 0, 2], [0, 1.3, 0, 1.3]],
            'square-rounded' => [[0, 0, 0, 0], [1.4, 1.4, 1.4, 1.4]],
            default => [[0, 0, 0, 0], [0, 0, 0, 0]],
        };
        $hole = $this->style === 'square-circle' ? $this->circle(2.5, false) : $this->box(2.5, $inner, false);

        return $this->box(3.5, $outer)->append($hole);
    }

    public function getInternalPath(): Path
    {
        return match ($this->style) {
            'rounded' => $this->box(1.5, [0.6, 0.6, 0.6, 0.6]),
            'leaf' => $this->box(1.6, [0, 1.1, 1.1, 1.1]),
            'sunburst' => $this->radial(16, 1.8, 1.5),
            'star' => $this->radial(5, 2, 1.2),
            'diamond' => $this->polygon([[0, -1.75], [1.75, 0], [0, 1.75], [-1.75, 0]]),
            'cross' => $this->polygon([[-0.65, -1.7], [0.65, -1.7], [0.65, -0.65], [1.7, -0.65], [1.7, 0.65], [0.65, 0.65], [0.65, 1.7], [-0.65, 1.7], [-0.65, 0.65], [-1.7, 0.65], [-1.7, -0.65], [-0.65, -0.65]]),
            default => $this->box(1.5, [0, 0, 0, 0]),
        };
    }

    private function box(float $half, array $corners, bool $clockwise = true): Path
    {
        $direction = $clockwise ? 1 : -1;
        if (! $clockwise) {
            $corners = [$corners[1], $corners[0], $corners[3], $corners[2]];
        }
        [$tl, $tr, $br, $bl] = $corners;
        $path = (new Path)->move((-$half + $tl) * $direction, -$half);
        $segments = [
            [$half - $tr, -$half, $tr, $half, -$half + $tr],
            [$half, $half - $br, $br, $half - $br, $half],
            [-$half + $bl, $half, $bl, -$half, $half - $bl],
            [-$half, -$half + $tl, $tl, -$half + $tl, -$half],
        ];
        foreach ($segments as [$x, $y, $radius, $endX, $endY]) {
            $path = $path->line($x * $direction, $y);
            if ($radius > 0) {
                $path = $path->ellipticArc($radius, $radius, 0, false, $clockwise, $endX * $direction, $endY);
            }
        }

        return $path->close();
    }

    private function circle(float $radius, bool $clockwise = true): Path
    {
        $direction = $clockwise ? 1 : -1;

        return (new Path)->move($radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, $radius * $direction)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, -$radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, -$radius * $direction)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, $radius, 0)->close();
    }

    private function radial(int $points, float $outer, float $inner): Path
    {
        $vertices = [];
        for ($i = 0; $i < $points * 2; $i++) {
            $angle = -M_PI / 2 + $i * M_PI / $points;
            $radius = $i % 2 === 0 ? $outer : $inner;
            $vertices[] = [cos($angle) * $radius, sin($angle) * $radius];
        }

        return $this->polygon($vertices);
    }

    private function polygon(array $vertices): Path
    {
        [$x, $y] = array_shift($vertices);
        $path = (new Path)->move($x, $y);
        foreach ($vertices as [$x, $y]) {
            $path = $path->line($x, $y);
        }

        return $path->close();
    }
}
