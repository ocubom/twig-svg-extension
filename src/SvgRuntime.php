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

use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
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

        /** @var \DOMElement[] $elements */
        $elements = [];

        /** @var \DOMElement[] $symbols */
        $symbols = [];

        /** @var \DOMElement $svg */
        foreach ($doc->getElementsByTagName('svg') as $svg) {
            $symbol = new Symbol($svg, [
                'debug' => $twig->isDebug(),
            ]);

            // Replace the SVG with reference
            DomUtil::replaceNode($svg, $symbol->getReference());

            // Index symbol by id
            $symbols[$symbol->getId()] = $symbols[$symbol->getId()] ?? $symbol->getElement();
            $elements[] = $svg;
        }

        // Dump all symbols
        if (count($symbols) > 0) {
            // Create symbols container element before the end of body tag or DOM
            $node = DomUtil::createElement('svg', '', $doc
                ->getElementsByTagName('body')
                ->item(0) ?? $doc
            );
            $node->setAttribute('style', 'display:none');

            // Add format (duplicate previous node space) on debug mode
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

                $this->logger->info('Converted {element_count} SVG elements into {symbol_count} SVG symbols', [
                    'element_count' => count($elements),
                    'element_items' => array_map([DomUtil::class, 'toXml'], $elements),
                    'symbol_count' => count($symbols),
                    'symbol_items' => array_map([DomUtil::class, 'toXml'], $symbols),
                ]);
            }
        } elseif ($twig->isDebug()) {
            $this->logger->debug('No SVG found');
        }

        // Generate normalized HTML
        return Html5Util::toHtml($doc);
    }

    public function embedSvg(Environment $twig, string $ident, array $options = []): string
    {
        try {
            $svg = $this->loader->resolve($ident, $options);

            return $svg->toXml(!$twig->isDebug());
        } catch (LoaderException $err) {
            $this->logger->error($err->getMessage(), ['exception' => $err]);

            if ($twig->isDebug()) {
                return sprintf('<!--{{ svg("%s") }}-->', $ident);
            }
        }

        return '';
    }
}
