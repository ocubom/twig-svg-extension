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

use Ocubom\Twig\Extension\Svg\Exception\ParseException;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;

trait SvgTrait
{
    protected \DOMElement $svg;

    public function getElement(): \DOMElement
    {
        return $this->svg;
    }

    public function __toString(): string
    {
        return DomUtil::toXml($this->svg);
    }

    public function __serialize(): array
    {
        return ['svg' => (string) $this];
    }

    public function __unserialize(array $data): void
    {
        $doc = DomUtil::createDocument();
        if (false === $doc->loadXML($data['svg'])) {
            throw new ParseException(sprintf('Unable to load SVG string "%s".', func_get_arg(0))); // @codeCoverageIgnore
        }

        // Get first svg item
        $node = $doc->getElementsByTagName('svg')->item(0);
        assert($node instanceof \DOMElement);

        $this->svg = $node;
    }
}
