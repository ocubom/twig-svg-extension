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

use enshrined\svgSanitize\Sanitizer;
use Ocubom\Twig\Extension\Svg\Exception\FileNotFoundException;
use Ocubom\Twig\Extension\Svg\Exception\ParseException;
use Ocubom\Twig\Extension\Svg\Processor\ApplyAttributesProcessor;
use Ocubom\Twig\Extension\Svg\Processor\ClassProcessor;
use Ocubom\Twig\Extension\Svg\Processor\CleanAttributesProcessor;
use Ocubom\Twig\Extension\Svg\Processor\PreserveAspectRatioProcessor;
use Ocubom\Twig\Extension\Svg\Processor\RemoveAttributesProcessor;
use Ocubom\Twig\Extension\Svg\Processor\TitleProcessor;
use Ocubom\Twig\Extension\Svg\Util\DomUtil;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function BenTools\IterableFunctions\iterable_merge;
use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Twig\Extension\is_string;

class Svg implements SvgInterface
{
    use SvgTrait;

    private static array $processors = [];

    /**
     * @param Svg|\DOMNode|\SplFileInfo|\Stringable|string $data The SVG data
     */
    public function __construct($data, iterable $options = null)
    {
        switch (true) {
            case $data instanceof Svg : // "Copy" constructor
                $node = $this->constructFromString(DomUtil::toXml($data->svg)); // @codeCoverageIgnore
                break; // @codeCoverageIgnore

            case $data instanceof \DOMNode :
                $node = $this->constructFromString(DomUtil::toXml($data));
                break;

            case $data instanceof \SplFileInfo :
                $node = $this->constructFromFile($data);
                break;

            case is_string($data) :
                $node = $this->constructFromString($data);
                break;

            default:
                throw new ParseException(sprintf('Unable to create an SVG from "%s"', get_debug_type($data))); // @codeCoverageIgnore
        }

        // Merge options with SVG attributes
        $options = iterable_to_array(iterable_merge(
            DomUtil::getElementAttributes($node),
            $options ?? /* @scrutinizer ignore-type */ []
        ));

        // Define SVG attributes and resolve options + attributes
        $options = static::configureOptions()
            ->setDefined(array_keys($options)) // enables all optional options
            ->resolve($options);

        // Retrieve processors for this class with a naive cache
        $processors = self::$processors[static::class]
            ?? self::$processors[static::class] = call_user_func(function () {
                // Get processors array with default priority
                $processors = array_map(
                    function ($processor): array {
                        return is_callable($processor) ? [$processor, 0] : $processor;
                    },
                    static::getProcessors()
                );

                // Sort by priority
                usort($processors, function ($x, $y) {
                    return $x[1] <=> $y[1];
                });

                return array_column($processors, 0);
            });

        // Apply processors on a flesh clone in new DOM document
        $this->svg = array_reduce(
            $processors,
            function (\DOMElement $svg, callable $processor) use ($options): \DOMElement {
                return $processor($svg, $options);
            },
            DomUtil::cloneElement($node)
        );
    }

    protected function constructFromFile(\SplFileInfo $path): \DOMElement
    {
        $path = (string) $path;

        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist.', $path)); // @codeCoverageIgnore
        }

        if (!is_readable($path)) {
            throw new FileNotFoundException(sprintf('File "%s" cannot be read.', $path)); // @codeCoverageIgnore
        }

        try {
            return $this->constructFromString(file_get_contents($path));
        } catch (ParseException $exc) {
            throw new ParseException(sprintf('File "%s" does not contain a valid SVG.', $path), 0, $exc);
        }
    }

    protected function constructFromString(string $contents): \DOMElement
    {
        // Sanitize contents (if enshrined\svgSanitize is installed)
        if (class_exists(Sanitizer::class)) {
            $contents = (new Sanitizer())->sanitize($contents) ?: $contents;
        }

        // Remove all namespaces
        $contents = preg_replace('@xmlns(:.*)?=(\"[^\"]*\"|\'[^\']*\')@Uis', '', $contents);
        if (empty($contents)) {
            throw new ParseException(sprintf('Invalid SVG string "%s".', func_get_arg(0)));
        }

        // Parse contents into DOM
        $doc = DomUtil::createDocument();
        if (false === $doc->loadXML($contents)) {
            throw new ParseException(sprintf('Unable to load SVG string "%s".', func_get_arg(0))); // @codeCoverageIgnore
        }

        // Get first svg item
        $node = $doc->getElementsByTagName('svg')->item(0);
        if ($node instanceof \DOMElement) {
            return $node; // Return first SVG element
        }

        throw new ParseException(sprintf('String "%s" does not contain any SVG.', func_get_arg(0))); // @codeCoverageIgnore
    }

    protected static function getProcessors(): array
    {
        $options = [
            'debug',
            'minimize',
            'class_block',
        ];

        return [
            // Apply options
            [new ApplyAttributesProcessor(...$options), -1000],

            // Options will be ignored & removed
            [new RemoveAttributesProcessor(...$options), 1000],

            // Custom process
            new ClassProcessor(),
            new TitleProcessor(),

            // Remove default values
            new PreserveAspectRatioProcessor(),

            // Final clean
            [new CleanAttributesProcessor(), 10000],
        ];
    }

    /** @psalm-suppress MissingClosureParamType */
    protected static function configureOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = $resolver ?? new OptionsResolver();

        // Options

        $resolver->define('debug')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Enable debug output');

        $resolver->define('minimize')
            ->default(true)
            ->allowedTypes('bool')
            ->info('Minimize output');

        // Attributes

        /** @psalm-suppress MissingClosureParamType */
        $normalizeClass = function (Options $options, $value): array {
            return array_filter(
                is_string($value) ? preg_split('@\s+@Uis', $value) : ($value ?? []),
                function (string $item): bool {
                    return !empty($item);
                }
            );
        };

        $resolver->define('class')
            ->default('')
            ->allowedTypes('null', 'string', 'string[]')
            ->normalize($normalizeClass)
            ->info('Classes to apply');

        $resolver->define('class_block')
            ->default('')
            ->allowedTypes('null', 'string', 'string[]')
            ->normalize($normalizeClass)
            ->info('Classes to block');

        $resolver->define('width')
            ->default('1em')
            ->allowedTypes('string')
            ->info('Width of the element');

        $resolver->define('height')
            ->default('1em')
            ->allowedTypes('string')
            ->info('Height of the element');

        $resolver->define('title')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(function (Options $options, ?string $value): ?string {
                return empty($value ?? '') ? null : $value;
            })
            ->info('Title of the icon. Used for semantic icons.');

        $resolver->define('focusable')
            ->default('false')
            ->allowedTypes('null', 'bool', 'string')
            ->normalize(function (Options $options, $value): ?string {
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return empty($value) ? null : $value;
            })
            ->info('Indicates if the element can take the focus');

        $resolver->define('role')
            ->default('img')
            ->allowedTypes('null', 'string')
            ->info('Indicates the semantic meaning of the content');

        $resolver->define('aria-hidden')
            ->default(null)
            ->allowedTypes('null', 'bool', 'string')
            ->allowedValues(null, true, 'true', false, 'false')
            ->normalize(function (Options $options, $value): ?string {
                // Decorative icon: no title and not previously defined aria-hidden value
                if (
                    null === $value
                    && empty($options['aria-labelledby'])
                    && empty($options['title'])
                ) {
                    return 'true';
                }

                // Convert bool value into text
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return empty($value)
                    ? null
                    : ('true' === $value ? 'true' : 'false');
            })
            ->info('Indicates whether the element is exposed to an accessibility API');

        $resolver->define('aria-labelledby')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(function (Options $options, $value): ?string {
                // Decorative icon: no title and not previously defined aria-hidden value
                if (empty($options['title'])) {
                    return null;
                }

                // Generate an identifier based on title contents
                return $value ?? DomUtil::generateId(DomUtil::createElement('title', $options['title']));
            })
            ->info('Identifies the element that labels this element');

        return $resolver;
    }
}
