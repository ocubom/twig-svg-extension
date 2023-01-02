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

use function BenTools\IterableFunctions\iterable_merge;
use function BenTools\IterableFunctions\iterable_to_array;

use enshrined\svgSanitize\Sanitizer;

use function Ocubom\Twig\Extension\is_string;

use Ocubom\Twig\Extension\Svg\Exception\FileNotFoundException;
use Ocubom\Twig\Extension\Svg\Exception\ParseException;
use Ocubom\Twig\Extension\Svg\Processor\ClassProcessor;
use Ocubom\Twig\Extension\Svg\Processor\RemoveAttributeProcessor;
use Ocubom\Twig\Extension\Svg\Processor\TitleProcessor;
use Ocubom\Twig\Extension\Svg\Util\DomHelper;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Svg implements SvgInterface
{
    use SvgTrait;

    /** @var array<string, array<string, array<int, callable>|callable>> */
    private static array $processors = [];

    /**
     * @param mixed $data The SVG data
     */
    public function __construct($data, iterable $options = null)
    {
        switch (true) {
            case $data instanceof Svg : // "Copy" constructor
                $node = $this->constructFromString(DomHelper::toXml($data->svg)); // @codeCoverageIgnore
                break; // @codeCoverageIgnore

            case $data instanceof \DOMNode :
                $node = $this->constructFromString(DomHelper::toXml($data));
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
            DomHelper::getElementAttributes($node),
            $options ?? /* @scrutinizer ignore-type */ []
        ));

        // Define SVG attributes and resolve options + attributes
        $options = static::configureOptions()
            ->setDefined(array_keys($options)) // enables all optional options
            ->resolve($options);

        // Retrieve processors for this class with a naive cache
        $processors = self::$processors[static::class]
            ?? self::$processors[static::class] = array_map(
                function ($processors): array {
                    return is_iterable($processors) ? $processors : [$processors];
                },
                static::getProcessors()
            );

        // Apply processors on a flesh clone in new DOM document
        $this->svg = DomHelper::cloneElement($node);
        foreach ($options as $key => $val) {
            if (isset($processors[$key])) {
                // Apply processors
                foreach ($processors[$key] as $processor) {
                    $this->svg = $processor($this->svg, $options);
                }
            } elseif (empty($val)) {
                // Remove empty attributes
                $this->svg->removeAttribute($key);
            } else {
                // Set attribute value
                $this->svg->setAttribute($key, $val);
            }
        }
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
            $contents = (new Sanitizer())->sanitize($contents);
        }

        // Remove all namespaces
        $contents = preg_replace('@xmlns(:.*)?=(\"[^\"]*\"|\'[^\']*\')@Uis', '', $contents);
        if (empty($contents)) {
            throw new ParseException(sprintf('Invalid SVG string "%s".', func_get_arg(0)));
        }

        // Parse contents into DOM
        $doc = DomHelper::createDocument();
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

    /**
     * @param mixed $path The path to the SVG file
     *
     * @return static
     *
     * @psalm-suppress UnsafeInstantiation
     *
     * @deprecated since ocubom/twig-svg-extension 1.1, use Svg constructor with SplInfo argument instead
     *
     * @codeCoverageIgnore
     */
    public static function createFromFile($path, iterable $options = null): self
    {
        trigger_deprecation('ocubom/twig-svg-extension', '1.1', 'Using "%s" is deprecated, use "%s" constructor with SplInfo argument instead.', __METHOD__, Svg::class);

        return new static($path instanceof \SplFileInfo ? $path : new \SplFileInfo((string) $path), $options);
    }

    /**
     * @param mixed $contents The SVG contents as an "stringable"
     *
     * @return static
     *
     * @psalm-suppress UnsafeInstantiation
     *
     * @deprecated since ocubom/twig-svg-extension 1.1, use Svg constructor instead
     *
     * @codeCoverageIgnore
     */
    public static function createFromString($contents, iterable $options = null): self
    {
        trigger_deprecation('ocubom/twig-svg-extension', '1.1', 'Using "%s" is deprecated, use "%s" constructor instead.', __METHOD__, Svg::class);

        return new static((string) $contents, $options);
    }

    /**
     * @return array<string, array<int, callable>|callable>
     */
    protected static function getProcessors(): array
    {
        return [
            // Options will be ignored & removed
            'debug' => new RemoveAttributeProcessor('debug'),
            'minimize' => new RemoveAttributeProcessor('minimize'),
            'class_block' => new RemoveAttributeProcessor('class_block'),

            // Custom process
            'class' => new ClassProcessor(),
            'title' => new TitleProcessor(),

            // Ignore attributes that depends on other options
            'aria-labelledby' => [], // generated on TitleProcessor
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

        /**
         * @param Options              $options
         * @param string|string[]|null $value
         *
         * @return string[]
         */
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
                return $value ?? Ident::generate(DomHelper::createElement('title', $options['title']));
            })
            ->info('Identifies the element that labels this element');

        return $resolver;
    }
}
