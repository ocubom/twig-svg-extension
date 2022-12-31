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

interface ProcessorInterface
{
    /**
     * Apply process.
     *
     * @param \DOMElement $svg     The SVG element to process
     * @param array       $options Options
     *
     * @return \DOMElement The resulting SVG element
     */
    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement;
}
