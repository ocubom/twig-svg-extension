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

use Ocubom\Twig\Extension\Svg\Exception\RuntimeException;

trait SvgTrait
{
    protected \DOMElement $svg;

    public function getElement(): \DOMElement
    {
        return $this->svg;
    }

    public function render(): string
    {
        assert($this->svg->ownerDocument instanceof \DOMDocument);

        $output = $this->svg->ownerDocument->saveXML($this->svg);
        if (false === $output) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'Unable to render %s to XML',
                get_class($this->svg)
            ));
            // @codeCoverageIgnoreEnd
        }

        return $output;
    }

    public function __toString()
    {
        return $this->render();
    }
}
