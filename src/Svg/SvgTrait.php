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

    /**
     * @deprecated since ocubom/twig-svg-extension 1.1, use __toString instead
     *
     * @codeCoverageIgnore
     */
    public function render(): string
    {
        trigger_deprecation('ocubom/twig-svg-extension', '1.1', 'Using "%s" is deprecated, use "%s::_toString" instead.', __METHOD__, SvgTrait::class);

        return (string) $this;
    }

    public function __toString(): string
    {
        return DomHelper::toXml($this->svg);
    }
}
