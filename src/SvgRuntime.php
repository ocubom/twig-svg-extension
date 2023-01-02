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

use Masterminds\HTML5;
use Ocubom\Twig\Extension\Svg\Exception\RuntimeException;
use Ocubom\Twig\Extension\Svg\FinderInterface;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Symbol;
use Ocubom\Twig\Extension\Svg\Util\DomHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class SvgRuntime implements RuntimeExtensionInterface
{
    private FinderInterface $finder;
    private LoggerInterface $logger;

    public function __construct(FinderInterface $finder, LoggerInterface $logger = null)
    {
        $this->finder = $finder;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Convert all SVG into Symbol references and inline symbols.
     */
    public function convertToSymbols(Environment $twig, string $html): string
    {
        $parser = new HTML5();

        // Load Document
        $doc = $parser->loadHTML($html);
        if ($parser->hasErrors()) {
            throw new RuntimeException(sprintf(
                'Unable to parse HTML: %s',
                implode("\n", $parser->getErrors())
            ));
        }

        /** @var \DOMElement[] $symbols */
        $symbols = [];

        /** @var \DOMElement $svg */
        foreach ($doc->getElementsByTagName('svg') as $svg) {
            $symbol = new Symbol($svg, [
                'debug' => $twig->isDebug(),
            ]);

            // Replace all SVG with use
            DomHelper::replaceNode($svg, $symbol->getReference());

            // Index symbol by id
            $symbols[$symbol->getId()] = $symbol->getElement();
        }

        // Dump all symbols
        if (count($symbols) > 0) {
            // Create symbols container element before the end of body tag or DOM
            $node = DomHelper::createElement('svg', '', $doc
                ->getElementsByTagName('body')
                ->item(0) ?? $doc
            );
            $node->setAttribute('style', 'display:none');

            // Add format on debug mode
            if ($twig->isDebug()) {
                assert($node->previousSibling instanceof \DOMNode);
                DomHelper::appendChildNode($node->previousSibling, $node);
            }

            uksort($symbols, 'strnatcasecmp');
            foreach ($symbols as $symbol) {
                DomHelper::appendChildNode($symbol, $node);

                if ($twig->isDebug()) {
                    assert($node->previousSibling instanceof \DOMNode);
                    DomHelper::appendChildNode($node->previousSibling, $node);
                }
            }

            if ($twig->isDebug()) {
                assert($node->parentNode instanceof \DOMNode);

                DomHelper::appendChildNode(
                    $doc->createTextNode("\n"),
                    $node->parentNode
                );
            }
        }

        // Normalize final doc
        $doc->normalize();

        // Fix EOL lines problem on Windows
        if ('Windows' === \PHP_OS_FAMILY) {
            return str_replace(\PHP_EOL, "\n", $parser->saveHTML($doc)); // @codeCoverageIgnore
        }

        // Generate output
        return $parser->saveHTML($doc);
    }

    /**
     * Render an inlined SVG image.
     */
    public function renderSvg(string $ident, array $options = []): string
    {
        $this->logger->debug('Resolving "{ident}"', [
            'ident' => $ident,
            'options' => $options,
        ]);

        $svg = new Svg($this->finder->resolve($ident), $options);

        $this->logger->debug('Render "{ident}" as inlined SVG', [
            'ident' => $ident,
        ]);

        return (string) $svg;
    }
}
