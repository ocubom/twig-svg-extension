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

use Ocubom\Twig\Extension\Svg\Util\DomHelper;

trait SvgTrait
{
    protected \DOMElement $svg;

    public function getElement(): \DOMElement
    {
        return $this->svg;
    }

    public function __toString(): string
    {
        return DomHelper::toXml($this->svg);
    }
}
