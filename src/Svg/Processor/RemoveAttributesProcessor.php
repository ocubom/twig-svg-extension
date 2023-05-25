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

class RemoveAttributesProcessor implements ProcessorInterface
{
    /** @var string[] */
    private array $names;

    public function __construct(string ...$names)
    {
        $this->names = $names;
    }

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        foreach ($this->names as $name) {
            $svg->removeAttribute($name);
        }

        return $svg;
    }
}
