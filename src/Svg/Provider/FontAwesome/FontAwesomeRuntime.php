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

use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;
use Ocubom\Twig\Extension\Svg\Util\Html5Util;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

use function BenTools\IterableFunctions\iterable_to_array;

class FontAwesomeRuntime implements RuntimeExtensionInterface
{
    private LoaderInterface $loader;
    private LoggerInterface $logger;

    public function __construct(LoaderInterface $loader, LoggerInterface $logger = null)
    {
        $this->loader = $loader;
        $this->logger = $logger ?? new NullLogger();
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
                try {
                    if ($node->hasAttribute('data-fa-transform')) {
                        continue; // Ignore icons with Power Transforms (use svg+js)
                    }

                    if ($twig->isDebug()) {
                        DomUtil::createComment(DomUtil::toHtml($node), $node, true);
                    }

                    $icon = $this->loader->resolve(
                        // Resolve icon with class …
                        $node->getAttribute('class'),
                        // … and clone all its attributes as options
                        iterable_to_array(DomUtil::getElementAttributes($node))
                    );

                    // Replace node
                    DomUtil::replaceNode($node, $icon->getElement());
                } catch (LoaderException $err) {
                    $this->logger->notice($err->getMessage(), ['exception' => $err]);

                    DomUtil::removeNode($node);
                }
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
