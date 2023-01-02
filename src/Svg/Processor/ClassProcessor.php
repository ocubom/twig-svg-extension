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

class ClassProcessor implements ProcessorInterface
{
    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        $generator = function () use ($svg, $options): iterable {
            // Yield default classes (will always be added)
            yield from $options['class_default'] ?? [];

            // Yield options classes
            yield from $options['class'] ?? [];

            // Yield element classes
            yield from preg_split(
                '@\s+@',
                $svg->getAttribute('class')
            );
        };

        $classes = [];
        $blocked = $options['class_block'] ?? [];
        foreach ($generator() as $class) {
            $class = trim($class);
            if (!empty($class) && !in_array(mb_strtolower($class), $blocked)) {
                $classes[$class] = $class;
            }
        }

        if (count($classes) > 0) {
            $svg->setAttribute('class', implode(' ', $classes));
        } else {
            $svg->removeAttribute('class');
        }

        return $svg;
    }
}
