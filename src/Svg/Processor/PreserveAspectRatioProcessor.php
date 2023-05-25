<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Processor;

class PreserveAspectRatioProcessor
{
    const ATTRIBUTE_NAME = 'preserveAspectRatio';
    const VALUES = [
        [
            'name' => 'align',
            'default' => 'xMidYMid',
            'values' => [
                'none' => 'none',
                'xminymin' => 'xMinYMin',
                'xmidymin' => 'xMidYMin',
                'xmaxymin' => 'xMaxYMin',
                'xminymid' => 'xMinYMid',
                'xmidymid' => 'xMidYMid', // Default
                'xmaxymid' => 'xMaxYMid',
                'xminymax' => 'xMinYMax',
                'xmidymax' => 'xMidYMax',
                'xmaxymax' => 'xMaxYMax',
            ],
        ], [
            'name' => 'meet or slice',
            'default' => 'meet',
            'values' => [
                'meet' => 'meet', // Default
                'slice' => 'slice',
            ],
        ],
    ];

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        $value = preg_split('@\s+@Uis', $svg->getAttribute(self::ATTRIBUTE_NAME));
        $value = array_slice($value, 0, count(self::VALUES));

        $count = count(self::VALUES);
        foreach (array_reverse(self::VALUES, true) as $pos => $data) {
            // Use default value if not set
            $value[$pos] = $value[$pos] ?? $data['default'];

            // Normalize value
            $value[$pos] = $data['values'][mb_strtolower($value[$pos])] ?? $value[$pos];

            // Update default value count
            if ($count === $pos + 1 && $value[$pos] === $data['default']) {
                $count = $pos;
            }
        }

        // Discard defaults at end
        $value = array_slice($value, 0, $count);

        // Set the normalized value or remove attribute if is empty or the defaults
        if (count($value) > 0) {
            $svg->setAttribute(self::ATTRIBUTE_NAME, implode(' ', $value));
        } else {
            $svg->removeAttribute(self::ATTRIBUTE_NAME);
        }

        return $svg;
    }
}
