<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension;

use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Symbol;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;
use Ocubom\Twig\Extension\Svg\Util\Html5Util;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class SvgRuntime implements RuntimeExtensionInterface
{
    private LoaderInterface $loader;

    private LoggerInterface $logger;

    public function __construct(LoaderInterface $loader, LoggerInterface $logger = null)
    {
        $this->loader = $loader;
        $this->logger = $logger ?? new NullLogger();
    }

    public function convertToSymbols(Environment $twig, string $html): string
    {
        // Load HTML
        $doc = Html5Util::loadHtml($html);

        /** @var \DOMElement[] $symbols */
        $symbols = [];

        /** @var \DOMElement $svg */
        foreach ($doc->getElementsByTagName('svg') as $svg) {
            $symbol = new Symbol($svg, [
                'debug' => $twig->isDebug(),
            ]);

            // Replace all SVG with use
            DomUtil::replaceNode($svg, $symbol->getReference());

            // Index symbol by id
            $symbols[$symbol->getId()] = $symbol->getElement();
        }

        // Dump all symbols
        if (count($symbols) > 0) {
            // Create symbols container element before the end of body tag or DOM
            $node = DomUtil::createElement('svg', '', $doc
                ->getElementsByTagName('body')
                ->item(0) ?? $doc
            );
            $node->setAttribute('style', 'display:none');

            // Add format on debug mode
            if ($twig->isDebug()) {
                assert($node->previousSibling instanceof \DOMNode);
                DomUtil::appendChildNode($node->previousSibling, $node);
            }

            uksort($symbols, 'strnatcasecmp');
            foreach ($symbols as $symbol) {
                DomUtil::appendChildNode($symbol, $node);

                if ($twig->isDebug()) {
                    assert($node->previousSibling instanceof \DOMNode);
                    DomUtil::appendChildNode($node->previousSibling, $node);
                }
            }

            if ($twig->isDebug()) {
                assert($node->parentNode instanceof \DOMNode);

                DomUtil::appendChildNode(
                    $doc->createTextNode("\n"),
                    $node->parentNode
                );
            }

            $this->logger->info('Converted {count} SVG to symbols', [
                'count' => count($symbols),
            ]);
        } elseif ($twig->isDebug()) {
            $this->logger->debug('No SVG to convert');
        }

        // Generate normalized HTML
        return Html5Util::toHtml($doc);
    }

    public function embedSvg(string $ident, array $options = []): string
    {
        $this->logger->debug('Resolving "{ident}"', [
            'ident' => $ident,
            'options' => $options,
        ]);

        $svg = $this->loader->resolve($ident, $options);

        $this->logger->debug('Render "{ident}" as inlined SVG', [
            'ident' => $ident,
        ]);

        return (string) $svg;
    }
}
