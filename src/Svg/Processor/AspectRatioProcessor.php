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

class AspectRatioProcessor
{
    const ATTRIBUTE_NAME = 'preserveAspectRatio';
    const DEFAULT_VALUE = ['xMidYMid', 'meet'];

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        $value = preg_split('@\s+@Uis', $svg->getAttribute(self::ATTRIBUTE_NAME));
        $value = array_slice($value, 0, 2);

        foreach (self::DEFAULT_VALUE as $key => $val) {
            $value[$key] = mb_strtolower($value[$key] ?? '') === mb_strtolower(self::DEFAULT_VALUE[$key])
                ? null
                : ($value[$key] ?? null);
        }

        // Restore default align if meet is set
        if (null === $value[0] && null !== $value[1]) {
            $value[0] = self::DEFAULT_VALUE[0];
        }

        // Format new value
        $value = trim(($value[0] ?? '').' '.($value[1] ?? ''));

        // Apply attribute value or remove if empty
        if (empty($value)) {
            // Remove attribute if contains default value
            $svg->removeAttribute(self::ATTRIBUTE_NAME);
        } else {
            // Set the value
            $svg->setAttribute(self::ATTRIBUTE_NAME, $value);
        }

        return $svg;
    }
}
