<?php

namespace Nicro\Nui\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use InvalidArgumentException;

class QrCode
{
    public static function svg(string $value, int $size = 192, string $level = 'M'): string
    {
        if ($value === '' || strlen($value) > 2953) {
            throw new InvalidArgumentException('O conteúdo deve ter entre 1 e 2953 bytes.');
        }

        $correction = match (strtoupper($level)) {
            'L' => ErrorCorrectionLevel::L(),
            'Q' => ErrorCorrectionLevel::Q(),
            'H' => ErrorCorrectionLevel::H(),
            default => ErrorCorrectionLevel::M(),
        };
        $matrix = Encoder::encode($value, $correction, 'UTF-8')->getMatrix();
        $width = $matrix->getWidth();
        $viewBox = $width + 8;
        $size = max(64, min(1024, $size));
        $path = '';

        // Four modules of quiet zone remain inside the exported image at every size.
        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $start = $x;
                    while ($x + 1 < $width && $matrix->get($x + 1, $y) === 1) {
                        $x++;
                    }
                    $run = $x - $start + 1;
                    $path .= 'M'.($start + 4).' '.($y + 4).'h'.$run.'v1h-'.$run.'z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$viewBox.' '.$viewBox.'" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path fill="#000" d="'.$path.'"/></svg>';
    }
}
