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

use Ocubom\Twig\Extension\Svg\Util\DomUtil;

use function BenTools\IterableFunctions\iterable_to_array;

class CleanAttributesProcessor implements ProcessorInterface
{
    const ATTRIBUTES_SETS = [
        // size attributes
        'viewBox' => '@size0',
        'height' => '@size1',
        'width' => ' @size1',

        // aria-* attributes followed with related attributes
        'aria-' => 'aria',
        'focusable' => 'aria@1',
        'role' => 'aria@1',

        // data-* attributes
        'data-' => 'data',
    ];

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        $attributes = iterable_to_array(DomUtil::getElementAttributes($svg));
        uksort($attributes, function ($x, $y) {
            $values = [
                'x' => [$x, '', ''],
                'y' => [$y, '', ''],
            ];
            foreach (self::ATTRIBUTES_SETS as $key => $val) {
                foreach (['x', 'y'] as $var) {
                    if (str_starts_with($values[$var][0], $key)) {
                        $values[$var][1] = $val;
                        $values[$var][2] = substr($values[$var][0], strlen($key));
                    }
                }
            }

            return strnatcasecmp($values['x'][1], $values['y'][1])
                ?: strnatcasecmp($values['x'][2], $values['y'][2])
                ?: strnatcasecmp($x, $y);
        });

        foreach ($attributes as $key => $val) {
            $svg->removeAttribute($key);

            $val = trim($val);
            if (!empty($val)) {
                // Recreate attribute in correct order
                $svg->setAttribute($key, $val);
            }
        }

        return $svg;
    }
}
