<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Library;

use function BenTools\IterableFunctions\iterable_to_array;

use Masterminds\HTML5;
use Ocubom\Twig\Extension\Svg\Exception\RuntimeException;
use Ocubom\Twig\Extension\Svg\FinderInterface;
use Ocubom\Twig\Extension\Svg\Library\FontAwesome\Finder;
use Ocubom\Twig\Extension\Svg\Library\FontAwesome\Icon;
use Ocubom\Twig\Extension\Svg\Util\DomHelper;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class FontAwesomeRuntime implements RuntimeExtensionInterface
{
    private Finder $finder;

    public function __construct(FinderInterface $finder)
    {
        $this->finder = $finder instanceof Finder ? $finder : new Finder($finder);
    }

    public function replaceIcons(Environment $twig, string $html): string
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
        foreach (DomHelper::query($query, $doc) as $node) {
            if ($node instanceof \DOMElement) {
                if ($node->hasAttribute('data-fa-transform')) {
                    continue; // Ignore icons with Power Transforms (use svg+js)
                }

                if ($twig->isDebug()) {
                    DomHelper::createComment(DomHelper::toHtml($node), $node, true);
                }

                $icon = new Icon(
                    // Resolve icon with class …
                    $this->finder->resolve($node->getAttribute('class')),
                    // … and clone all its attributes as options
                    iterable_to_array(DomHelper::getElementAttributes($node))
                );

                // Replace node
                DomHelper::replaceNode($node, $icon->getElement());
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

    public function renderHtmlTag(string $icon, array $options = []): string
    {
        $icon = new Icon($this->finder->resolve($icon), $options);

        return DomHelper::toHtml($icon->getHtmlTag($options));
    }
}
