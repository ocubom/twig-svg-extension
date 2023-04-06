<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\Iconify;

use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;
use Ocubom\Twig\Extension\Svg\Util\Html5Util;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

use function BenTools\IterableFunctions\iterable_merge;
use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Twig\Extension\is_string;

class IconifyRuntime implements RuntimeExtensionInterface
{
    private LoaderInterface $loader;

    private array $options;

    public function __construct(IconifyLoader $loader, iterable $options = null)
    {
        $this->loader = $loader;
        $this->options = static::configureOptions()
            ->resolve(iterable_to_array($options ?? /* @scrutinizer ignore-type */ []));
    }

    public function replaceIcons(
        Environment $twig,
        string $html,
        array $options = []
    ): string {
        // Load HTML
        $doc = Html5Util::loadHtml($html);

        /** @var \DOMNode $node */
        foreach (iterable_to_array($this->queryIconify($doc, $options)) as $node) {
            if ($node instanceof \DOMElement) {
                if ($twig->isDebug()) {
                    DomUtil::createComment(DomUtil::toHtml($node), $node, true);
                }

                $ident = $node->hasAttribute('data-icon')
                    ? $node->getAttribute('data-icon')
                    : $node->getAttribute('icon');

                $icon = $this->loader->resolve(
                    $ident, // Resolve icon
                    iterable_to_array(iterable_merge(
                        DomUtil::getElementAttributes($node), // … and clone all its attributes as options
                        $options
                    ))
                );

                // Replace node
                DomUtil::replaceNode($node, $icon->getElement());
            }
        }

        // Generate normalized HTML
        return Html5Util::toHtml($doc);
    }

    /**
     * @return iterable<\DOMElement>
     */
    private function queryIconify(\DOMDocument $doc, iterable $options = null)
    {
        $options = static::configureOptions()->resolve(iterable_to_array(iterable_merge(
            $this->options,
            $options ?? /* @scrutinizer ignore-type */ []
        )));

        // SVG Framework
        // <span class="iconify" data-icon="mdi:home"></span>
        // <span class="iconify-inline" data-icon="mdi:home"></span>
        if ($options['svg_framework']) {
            $query = implode(' | ', array_map(
                function (string $class): string {
                    return sprintf(
                        'descendant-or-self::*[@class and contains(concat(\' \', normalize-space(@class), \' \'), \' %s \')]',
                        $class
                    );
                },
                $options['svg_framework']
            ));

            foreach (DomUtil::query($query, $doc) as $node) {
                if ($node instanceof \DOMElement && $node->hasAttribute('data-icon')) {
                    yield $node;
                }
            }
        }

        // Web Component
        // <icon icon="mdi:home" />
        // <iconify-icon icon="mdi:home"></iconify-icon>
        if ($options['web_component']) {
            foreach ($options['web_component'] as $tag) {
                foreach ($doc->getElementsByTagName($tag) as $node) {
                    if ($node instanceof \DOMElement && $node->hasAttribute('icon')) {
                        yield $node;
                    }
                }
            }
        }
    }

    /** @psalm-suppress MissingClosureParamType */
    protected static function configureOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = $resolver ?? new OptionsResolver();

        $normalizeStringArray = function (Options $options, $value): array {
            return array_filter(
                is_string($value) ? preg_split('@\s+@Uis', $value) : ($value ?? []),
                function (string $item): bool {
                    return !empty($item);
                }
            );
        };

        $resolver->define('svg_framework')
            ->default(['iconify', 'iconify-inline'])
            ->allowedTypes('null', 'string', 'string[]')
            ->normalize($normalizeStringArray)
            ->info('SVG Framework classes');

        $resolver->define('web_component')
            ->default(['icon', 'iconify-icon'])
            ->allowedTypes('null', 'string', 'string[]')
            ->normalize($normalizeStringArray)
            ->info('Web Component tags');

        return $resolver;
    }
}
