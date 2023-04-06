<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\FontAwesome;

use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;
use Ocubom\Twig\Extension\Svg\Util\Html5Util;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class FontAwesomeRuntime implements RuntimeExtensionInterface
{
    private LoaderInterface $loader;

    public function __construct(FontAwesomeLoader $loader)
    {
        $this->loader = $loader;
    }

    public function replaceIcons(Environment $twig, string $html): string
    {
        // Load HTML
        $doc = Html5Util::loadHtml($html);

        $query = implode(' | ', array_map(
            function ($class) {
                return sprintf(
                    'descendant-or-self::*[@class and contains(concat(\' \', normalize-space(@class), \' \'), \' %s \')]',
                    $class
                );
            },
            array_keys(FontAwesome::PREFIXES)
        ));

        /** @var \DOMNode $node */
        foreach (DomUtil::query($query, $doc) as $node) {
            if ($node instanceof \DOMElement) {
                if ($node->hasAttribute('data-fa-transform')) {
                    continue; // Ignore icons with Power Transforms (use svg+js)
                }

                if ($twig->isDebug()) {
                    DomUtil::createComment(DomUtil::toHtml($node), $node, true);
                }

                $icon = $this->loader->resolve(
                    $node->getAttribute('class'),   // Resolve icon with class …
                    DomUtil::getElementAttributes($node)      // … and clone all its attributes as options
                );

                // Replace node
                DomUtil::replaceNode($node, $icon->getElement());
            }
        }

        // Generate normalized HTML
        return Html5Util::toHtml($doc);
    }

    public function renderHtmlTag(string $icon, array $options = []): string
    {
        $icon = $this->loader->resolve($icon, $options);
        assert($icon instanceof FontAwesomeSvg);

        return DomUtil::toHtml($icon->getHtmlTag($options));
    }
}
