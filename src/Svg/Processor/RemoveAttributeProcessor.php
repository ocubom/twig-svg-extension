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

class RemoveAttributeProcessor implements ProcessorInterface
{
    private string $name;

    /**
     * @param string $name Attribute to delete
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        $svg->removeAttribute($this->name);

        return $svg;
    }
}
