<?php

namespace Nicro\Nui\Support;

/**
 * Geometry and number formatting shared by the chart cards (area, combo, bar list, activity rings).
 * Everything is computed on the server so the cards render complete SVG without a chart library.
 */
class Chart
{
    /**
     * Round an axis maximum up to a "nice" value and return evenly spaced ticks from zero.
     *
     * @return array{max: float, ticks: list<float>}
     */
    public static function scale(float $maximum, int $steps = 3): array
    {
        if ($maximum <= 0) {
            return ['max' => 1.0, 'ticks' => range(0, 1, 1 / $steps)];
        }

        $rough = $maximum / $steps;
        $magnitude = 10 ** floor(log10($rough));
        $step = collect([1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 7.5, 10])
            ->map(fn (float $factor): float => $factor * $magnitude)
            ->first(fn (float $candidate): bool => $candidate * $steps >= $maximum) ?? 10 * $magnitude;

        return ['max' => $step * $steps, 'ticks' => array_map(fn (int $index): float => $index * $step, range(0, $steps))];
    }

    /**
     * Monotone cubic path through the points (Fritsch-Carlson), so a smoothed line never overshoots the data.
     *
     * @param  list<array{0: float, 1: float}>  $points
     */
    public static function smoothPath(array $points): string
    {
        $count = count($points);

        if ($count === 0) {
            return '';
        }

        if ($count < 3) {
            return 'M'.implode(' L', array_map(fn (array $point): string => self::pair($point), $points));
        }

        $slopes = [];
        for ($index = 0; $index < $count - 1; $index++) {
            $step = $points[$index + 1][0] - $points[$index][0];
            $slopes[] = ($points[$index + 1][1] - $points[$index][1]) / (abs($step) < 1e-9 ? 1e-9 : $step);
        }

        $tangents = [$slopes[0]];
        for ($index = 1; $index < $count - 1; $index++) {
            $tangents[] = $slopes[$index - 1] * $slopes[$index] <= 0 ? 0.0 : ($slopes[$index - 1] + $slopes[$index]) / 2;
        }
        $tangents[] = $slopes[$count - 2];

        for ($index = 0; $index < $count - 1; $index++) {
            if ($slopes[$index] == 0) {
                $tangents[$index] = $tangents[$index + 1] = 0.0;

                continue;
            }

            $alpha = $tangents[$index] / $slopes[$index];
            $beta = $tangents[$index + 1] / $slopes[$index];
            $length = $alpha ** 2 + $beta ** 2;

            if ($length > 9) {
                $factor = 3 / sqrt($length);
                $tangents[$index] = $factor * $alpha * $slopes[$index];
                $tangents[$index + 1] = $factor * $beta * $slopes[$index];
            }
        }

        $path = 'M'.self::pair($points[0]);
        for ($index = 0; $index < $count - 1; $index++) {
            $third = ($points[$index + 1][0] - $points[$index][0]) / 3;
            $path .= ' C'.self::pair([$points[$index][0] + $third, $points[$index][1] + $tangents[$index] * $third])
                .' '.self::pair([$points[$index + 1][0] - $third, $points[$index + 1][1] - $tangents[$index + 1] * $third])
                .' '.self::pair($points[$index + 1]);
        }

        return $path;
    }

    /**
     * Full number in pt-BR: 94700 → "94.700", 3.7 → "3,7".
     */
    public static function number(float|int $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /**
     * Short axis label in pt-BR: 13500 → "13,5 mil", 2000000 → "2 mi".
     */
    public static function compact(float|int $value): string
    {
        foreach ([1_000_000 => ' mi', 1_000 => ' mil'] as $unit => $suffix) {
            if (abs($value) >= $unit) {
                $short = $value / $unit;

                return self::number($short, fmod($short, 1) == 0.0 ? 0 : 1).$suffix;
            }
        }

        return self::number($value, fmod((float) $value, 1) == 0.0 ? 0 : 1);
    }

    /**
     * @param  array{0: float, 1: float}  $point
     */
    private static function pair(array $point): string
    {
        return round($point[0], 2).','.round($point[1], 2);
    }
}
