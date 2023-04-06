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

use Ocubom\Twig\Extension\Svg\Util\DomUtil;

class Symbol implements SvgInterface
{
    use SvgTrait;

    protected \DOMElement $ref;

    public function __construct(\DOMElement $svg, iterable $options = null)
    {
        $svg = (new Svg($svg, $options))->getElement();

        $this->svg = $this->generateSymbol($svg);
        $this->ref = $this->generateReference($svg);
    }

    public function getId(): string
    {
        return $this->svg->getAttribute('id');
    }

    public function getReference(): \DOMElement
    {
        return $this->ref;
    }

    private function generateSymbol(\DOMElement $svg): \DOMElement
    {
        $node = DomUtil::createElement('symbol');

        /** @var \DOMAttr $attribute */
        foreach ($svg->attributes as $attribute) {
            if (!self::isReferenceAllowedAttribute($attribute)) {
                $node->setAttribute($attribute->name, $attribute->value);
            }
        }

        /** @var \DOMNode $child */
        foreach ($svg->childNodes as $child) {
            if (!self::isReferenceAllowedNode($child)) {
                DomUtil::appendChildNode($child, $node);
            }
        }

        // Add the identifier based on node contents
        $node->setAttribute('id', DomUtil::generateId($node));

        return $node;
    }

    private function generateReference(\DOMElement $svg): \DOMElement
    {
        $node = DomUtil::createElement('svg');

        /** @var \DOMAttr $attribute */
        foreach ($svg->attributes as $attribute) {
            if (self::isReferenceAllowedAttribute($attribute)) {
                $node->setAttribute($attribute->name, $attribute->value);
            }
        }

        /** @var \DOMNode $child */
        foreach ($svg->childNodes as $child) {
            if (self::isReferenceAllowedNode($child)) {
                DomUtil::appendChildNode($child, $node);
            }
        }

        $use = DomUtil::appendChildElement(
            DomUtil::createElement('use'),
            $node
        );
        $use->setAttribute('xlink:href', '#'.$this->getId());

        return $node;
    }

    private static function isReferenceAllowedAttribute(\DOMAttr $value): bool
    {
        // Block list
        return !in_array(mb_strtolower($value->name), [
            'viewbox',
        ]);
    }

    private static function isReferenceAllowedNode(\DOMNode $node): bool
    {
        return $node instanceof \DOMElement
            // Allow list
            && in_array(mb_strtolower($node->tagName), [
                'title',
                'desc',
            ]);
    }
}
