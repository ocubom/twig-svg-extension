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
        $blocked = array_map('mb_strtolower', $options['class_block'] ?? []);

        $classes = [];
        foreach ($this->getClasses($svg, $options) as $class) {
            $class = trim(/* @scrutinizer ignore-type */ $class);

            if (!empty($class) && !in_array(mb_strtolower($class), $blocked)) {
                $classes[$class] = $class;
            }
        }

        if (count($classes) > 0) {
            $svg->setAttribute('class', implode(' ', $classes));
        } else {
            $svg->removeAttribute('class');
        }

        // Remove extra options attributes
        $svg->removeAttribute('class_default');
        $svg->removeAttribute('class_block');

        return $svg;
    }

    /**
     * @return iterable<string>
     */
    private function getClasses(\DOMElement $svg, array $options): iterable
    {
        // Yield default classes (will always be added)
        yield from $options['class_default'] ?? [];

        // Yield options classes
        yield from $options['class'] ?? [];

        // Yield element classes
        yield from preg_split(
            '@\s+@',
            $svg->getAttribute('class')
        );
    }
}
