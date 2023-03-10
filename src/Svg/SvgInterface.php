<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg;

interface SvgInterface extends \Stringable
{
    /**
     * Generate a DOM element node for the SVG.
     */
    public function getElement(): \DOMElement;
}
