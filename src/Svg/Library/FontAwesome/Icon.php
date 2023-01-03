<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Library\FontAwesome;

use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Twig\Extension\is_string;

use Ocubom\Twig\Extension\Svg\Exception\ParseException;
use Ocubom\Twig\Extension\Svg\Library\FontAwesome;
use Ocubom\Twig\Extension\Svg\Processor\ClassProcessor;
use Ocubom\Twig\Extension\Svg\Processor\RemoveAttributeProcessor;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\DomHelper;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Icon extends Svg
{
    /**
     * Full path to the icon.
     */
    protected \SplFileInfo $path;

    /**
     * @param mixed $data The FontAwesome Icon data
     */
    public function __construct($data, iterable $options = null)
    {
        try {
            switch (true) {
                case $data instanceof Icon: // "Copy" constructor
                    $this->path = $data->path;
                    break;

                case $data instanceof \SplFileInfo:
                    $this->path = $data;
                    break;

                case is_string($data):
                    $this->path = new \SplFileInfo($data);
                    break;

                default:
                    throw new ParseException(sprintf('Unable to create "%s" from "%s"', __CLASS__, get_debug_type($data)));
            }

            /** @var array $options */
            $options = iterable_to_array($options ?? []);

            // Construct from path
            parent::__construct($this->path, array_merge($options, [
                'class_default' => array_merge($options['class_default'] ?? [], [
                    FontAwesome::INLINE_CLASS, // Add inlined class
                    'fa-'.$this->getName(), // Add icon name class
                ]),
                'class_block' => array_merge($options['class_block'] ?? [], [
                    $this->getStyle(), // Block style
                    $this->getStyleClass(), // Block current classes
                    $this->getStyleClass('5.0'), // Block pre-6.0 classes
                ]),
            ]));

            // Add Font Awesome data-*
            $this->svg->setAttribute('data-prefix', $this->getStyleClass('5.0'));
            $this->svg->setAttribute('data-icon', $this->getName());
        } catch (ParseException $exc) {
            throw new ParseException(sprintf('Unable to create a FontAwesome Icon from "%s"', get_debug_type($data)), 0, $exc);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFaId(): string
    {
        return sprintf(
            'fa-%s-%s',
            $this->getStyle(),
            $this->getName(),
        );
    }

    public function getName(): string
    {
        return $this->path->getBasename('.svg');
    }

    public function getStyle(): string
    {
        $path = $this->path->getPathInfo();
        assert($path instanceof \SplFileInfo);

        return $path->getBasename();
    }

    public function getStyleClass(string $version = '6.0'): string
    {
        return version_compare($version, '6.0', '<')
            ? 'fa'.$this->getStyle()[0]
            : 'fa-'.$this->getStyle();
    }

    public function getHtmlTag(iterable $options = null): \DOMElement
    {
        // Create the HTML Tag node
        $node = DomHelper::createElement(FontAwesome::HTML_TAG);
        // Copy options as attributes
        foreach ($options ?? [] as $key => $val) {
            if (!empty($val)) {
                $val = is_iterable($val) ? implode(' ', iterable_to_array($val)) : (string) $val;

                $node->setAttribute($key, $val);
            }
        }

        // Process classes
        $processor = new ClassProcessor();
        $processor($node, [
            'class' => [
                $this->getStyleClass(),
                'fa-'.$this->getName(),
            ],
            'class_banned' => [
                FontAwesome::INLINE_CLASS,
            ],
        ]);

        return $node;
    }

    /**
     * @return array<string, array<int, callable>|callable>
     *
     * @psalm-suppress InvalidScope
     */
    protected static function getProcessors(): array
    {
        return array_merge(parent::getProcessors(), [
            // Options will be ignored & removed
            'class_default' => new RemoveAttributeProcessor('class_default'),
            'class_block' => new RemoveAttributeProcessor('class_block'),
            'fill' => new RemoveAttributeProcessor('fill'),
            'opacity' => new RemoveAttributeProcessor('opacity'),
            'primary_fill' => new RemoveAttributeProcessor('primary_fill'),
            'primary_opacity' => new RemoveAttributeProcessor('primary_opacity'),
            'secondary_fill' => new RemoveAttributeProcessor('secondary_fill'),
            'secondary_opacity' => new RemoveAttributeProcessor('secondary_opacity'),

            // Remove special attributes
            'data-fa-title-id' => new RemoveAttributeProcessor('data-fa-title-id'),

            '' => function (\DOMElement $svg, array $options = []): \DOMElement {
                // Add FontAwesome fill and opacity values to each path
                /** @var \DOMElement $path */
                foreach ($svg->getElementsByTagName('path') as $path) {
                    $class = array_intersect(
                        ['fa-primary', 'fa-secondary'],
                        preg_split('@\s+@Uis', $path->getAttribute('class'))
                    );
                    $class = count($class) > 0
                        ? substr($class[0], 3)
                        : '';

                    foreach (['fill', 'opacity'] as $name) {
                        $key = $class.'_'.$name;
                        if (!empty($options[$key])) {
                            $path->setAttribute($name, $options[$key]);
                        } elseif (!empty($options[$name])) {
                            $path->setAttribute($name, $options[$name]);
                        }
                    }
                }

                return $svg;
            },
        ]);
    }

    public static function configureOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = parent::configureOptions($resolver);

        /** @psalm-suppress MissingClosureParamType */
        $normalizeFloat = function (Options $options, $value): ?float {
            return is_numeric((string) $value) ? floatval((string) $value) : null;
        };

        $resolver->define('class_default')
            ->default([])
            ->allowedTypes('string[]')
            ->info('Default classes to add unless null');

        $resolver->define('fill')
            ->default('currentColor')
            ->allowedTypes('null', 'string')
            ->info('Default fill color for paths');

        $resolver->define('opacity')
            ->default(null)
            ->allowedTypes('null', 'string', 'float')
            ->normalize($normalizeFloat)
            ->info('Default opacity color for paths');

        $resolver->define('primary_fill')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->info('Default fill color for primary paths (duotone)');

        $resolver->define('primary_opacity')
            ->default(null)
            ->allowedTypes('null', 'string', 'float')
            ->normalize($normalizeFloat)
            ->info('Default opacity color for primary paths (duotone)');

        $resolver->define('secondary_fill')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->info('Default fill color for secondary paths (duotone)');

        $resolver->define('secondary_opacity')
            ->default(null)
            ->allowedTypes('null', 'string', 'float')
            ->normalize($normalizeFloat)
            ->info('Default opacity color for secondary paths (duotone)');

        $resolver->define('data-fa-title-id')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->info('Set the icon title id instead of generate a new one');

        // Uses data-fa-title-id as aria-labelledby if not defined
        $resolver->addNormalizer(
            'aria-labelledby',
            function (Options $options, ?string $value): ?string {
                if (empty($value) && !empty($options['data-fa-title-id'])) {
                    return $options['data-fa-title-id'];
                }

                return $value;
            },
            true
        );

        return $resolver;
    }
}
