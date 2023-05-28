<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Util;

use Ocubom\Twig\Extension\Svg\Exception\RuntimeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Math\base_convert;

/** @internal */
final class DomUtil
{
    private const CLEAN_REGEXP = [
        '@\s+@' => ' ',
        '@> <@' => '><',
    ];

    /** @var OptionsResolver[] */
    private static array $resolver = [];

    // @codeCoverageIgnoreStart
    private function __construct()
    {
    }

    // @codeCoverageIgnoreEnd

    public static function generateId(
        \DOMElement $element,
        iterable $options = null
    ): string {
        $resolver = self::$resolver[__METHOD__] = self::$resolver[__METHOD__]
            ?? self::configureIdentOptions();

        // Resolve options
        $options = $resolver->resolve(iterable_to_array($options ?? []));

        // Work on an optimized clone
        $node = DomUtil::cloneElement($element);
        // Remove identifier
        $node->removeAttribute('id');

        // Convert to minified text
        $text = DomUtil::toXml($node, true);

        // Generate a hash
        $hash = hash($options['algo'], $text);

        // Convert to base and pad to maximum length in new base
        $hash = str_pad(
            base_convert($hash, 16, $options['base']),
            (int) ceil(strlen($hash) * log(16, $options['base'])),
            '0',
            \STR_PAD_LEFT
        );

        return sprintf($options['format'], substr($hash, -$options['length']));
    }

    public static function createDocument(iterable $options = null): \DOMDocument
    {
        $resolver = self::$resolver[__METHOD__] = self::$resolver[__METHOD__]
            ?? self::configureDocumentOptions();

        // Resolve options
        $options = $resolver->resolve(iterable_to_array($options ?? []));

        // Create document
        $doc = new \DOMDocument($options['version'], $options['encoding']);

        // Apply options
        foreach ($options as $key => $val) {
            if (in_array($key, ['version', 'encoding', 'standalone'])) {
                continue; // Ignore constructor arguments and deprecated options
            }

            // Apply property
            $doc->{$key} = $val;
        }

        return $doc;
    }

    public static function appendChildElement(
        \DOMElement $element,
        \DOMNode $parent
    ): \DOMElement {
        $child = self::appendChildNode($element, $parent);
        assert($child instanceof \DOMElement);

        return $child;
    }

    public static function appendChildNode(
        \DOMNode $node,
        \DOMNode $parent
    ): \DOMNode {
        assert($parent->ownerDocument instanceof \DOMDocument);

        $child = $parent->appendChild(
            $parent->ownerDocument === $node->ownerDocument
                ? $node->cloneNode(true)
                : $parent->ownerDocument->importNode($node, true)
        );
        assert($child instanceof \DOMNode);

        return $child;
    }

    public static function cloneElement(
        \DOMElement $element,
        bool $deep = true,
        \DOMDocument $document = null
    ): \DOMElement {
        $clone = self::cloneNode($element, $deep, $document);

        assert($clone instanceof \DOMElement);

        return $clone;
    }

    public static function cloneNode(
        \DOMNode $node,
        bool $deep = true,
        \DOMDocument $document = null
    ): \DOMNode {
        $doc = $document ?? self::createDocument();

        $clone = $node->ownerDocument === $doc ? $node->cloneNode($deep) : $doc->importNode($node, $deep);
        assert($clone instanceof \DOMNode);

        return $clone;
    }

    public static function createComment(
        string $data = '',
        \DOMNode $node = null,
        bool $before = false
    ): \DOMComment {
        // Get node document ($node if is a DOMDocument)
        $doc = $node instanceof \DOMNode
            ? ($node->ownerDocument ?? $node)
            : self::createDocument();
        assert($doc instanceof \DOMDocument);

        $new = $doc->createComment($data);
        if (null === $node || $doc === $node) {
            // Append child to the document
            $new = $doc->appendChild($new);
        } elseif ($before) {
            // Insert child before the element
            assert($node->parentNode instanceof \DOMNode);
            $new = $node->parentNode->insertBefore($new, $node);
        } else {
            // Insert child before the end of the element
            $new = $node->insertBefore($new);
        }
        assert($new instanceof \DOMComment);

        return $new;
    }

    public static function createElement(
        string $name,
        string $value = '',
        \DOMNode $node = null,
        bool $before = false
    ): \DOMElement {
        try {
            // Get node document ($node if is a DOMDocument)
            $doc = $node instanceof \DOMNode
                ? ($node->ownerDocument ?? $node)
                : self::createDocument();
            assert($doc instanceof \DOMDocument);

            $new = $doc->createElement($name, $value);
            if (null === $node || $doc === $node) {
                // Append child to the document
                $new = $doc->appendChild($new);
            } elseif ($before) {
                // Insert child before the element
                assert($node->parentNode instanceof \DOMNode);
                $new = $node->parentNode->insertBefore($new, $node);
            } else {
                // Insert child before the end of the element
                $new = $node->insertBefore($new);
            }
            assert($new instanceof \DOMElement);

            return $new;
        } catch (\DOMException $exc) { // @codeCoverageIgnore
            throw new RuntimeException($exc->getMessage(), $exc->getCode(), $exc); // @codeCoverageIgnore
        }
    }

    public static function query(
        string $expression,
        \DOMNode $node,
        bool $registerNodeNS = true
    ): \DOMNodeList {
        $doc = $node instanceof \DOMDocument ? $node : $node->ownerDocument;
        assert($doc instanceof \DOMDocument);

        $nodes = (new \DOMXPath($doc))->query(
            $expression,
            $node instanceof \DOMDocument ? null : $node,
            $registerNodeNS
        );

        assert($nodes instanceof \DOMNodeList);

        return $nodes;
    }

    public static function removeNode(
        \DOMNode $node
    ): void {
        assert($node->parentNode instanceof \DOMNode);

        $node->parentNode->removeChild($node);
    }

    public static function replaceNode(
        \DOMNode $old,
        \DOMNode $new
    ): \DOMNode {
        $doc = $old->ownerDocument;
        assert($doc instanceof \DOMDocument);

        // Clone new node in the document
        $new = $doc->importNode($new, true);
        assert($new instanceof \DOMNode);

        // Obtain parent
        $parent = $old->parentNode;
        assert($parent instanceof \DOMNode);

        $parent->replaceChild($new, $old);

        return $new;
    }

    public static function getElementAttributes(
        \DOMElement $node
    ): iterable {
        /** @var \DOMAttr $attribute */
        foreach ($node->attributes as $attribute) {
            yield $attribute->name => $attribute->value;
        }
    }

    public static function toHtml(
        \DOMNode $node,
        bool $minimize = false
    ): string {
        assert($node->ownerDocument instanceof \DOMDocument);

        $formatOutput = $node->ownerDocument->formatOutput;
        try {
            $node->ownerDocument->formatOutput = !$minimize;

            $text = $node->ownerDocument->saveHTML($node);
            if (false === $text) {
                throw new RuntimeException(sprintf('Unable to convert %s to HTML', get_class($node))); // @codeCoverageIgnore
            }

            if ($minimize) {
                $text = self::cleanTextOutput($text);
            }

            return $text;
        } finally {
            $node->ownerDocument->formatOutput = $formatOutput;
        }
    }

    public static function toXml(
        \DOMNode $node,
        bool $minimize = false
    ): string {
        assert($node->ownerDocument instanceof \DOMDocument);

        $formatOutput = $node->ownerDocument->formatOutput;
        try {
            $node->ownerDocument->formatOutput = !$minimize;

            $text = $node->ownerDocument->saveXML($node);
            if (false === $text) {
                throw new RuntimeException(sprintf('Unable to convert %s to XML', get_class($node))); // @codeCoverageIgnore
            }

            if ($minimize) {
                $text = self::cleanTextOutput($text);
            }

            return $text;
        } finally {
            $node->ownerDocument->formatOutput = $formatOutput;
        }
    }

    private static function cleanTextOutput(string $text): string
    {
        return preg_replace(
            array_keys(self::CLEAN_REGEXP),
            array_values(self::CLEAN_REGEXP),
            $text
        );
    }

    private static function configureDocumentOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = $resolver ?? new OptionsResolver();

        $resolver->define('encoding')
            ->default('')
            ->allowedTypes('string')
            ->info('Encoding of the document, as specified by the XML declaration');

        $resolver->define('standalone')
            ->default(true)
            ->allowedTypes('bool')
            ->deprecated(
                'ocubom/twig-svg-extension',
                '1.0',
                'The option "standalone" is deprecated, use "xmlStandalone" instead.'
            )
            ->info('Whether or not the document is standalone, as specified by the XML declaration');

        $resolver->define('xmlStandalone')
            ->default(true)
            ->allowedTypes('null', 'bool')
            ->normalize(function (Options $options, ?bool $value): bool {
                return $value ?? (bool) $options
                    ->/* @scrutinizer ignore-call */ offsetGet('standalone', false);
            })
            ->info('Whether or not the document is standalone, as specified by the XML declaration');

        $resolver->define('version')
            ->default('1.0')
            ->allowedTypes('string')
            ->deprecated(
                'ocubom/twig-svg-extension',
                '1.0',
                'The option "version" is deprecated, use "xmlVersion" instead.'
            )
            ->info('The version number of this document, as specified by the XML declaration');

        $resolver->define('xmlVersion')
            ->default('1.0')
            ->allowedTypes('null', 'string')
            ->normalize(function (Options $options, ?string $value): string {
                return $value ?? (string) $options
                    ->/* @scrutinizer ignore-call */ offsetGet('version', false);
            })
            ->info('The version number of this document, as specified by the XML declaration');

        $resolver->define('strictErrorChecking')
            ->default(true)
            ->allowedTypes('bool')
            ->info('Throws \DOMException on errors');

        $resolver->define('documentURI')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->info('The location of the document');

        $resolver->define('formatOutput')
            ->default(true) // LibXML default is false
            ->allowedTypes('bool')
            ->info('Nicely formats output with indentation and extra space');

        $resolver->define('validateOnParse')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Loads and validates against the DTD');

        $resolver->define('resolveExternals')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Load external entities from a doctype declaration');

        $resolver->define('preserveWhiteSpace')
            ->default(true)
            ->allowedTypes('bool')
            ->normalize(function (Options $options, bool $value): bool {
                // Must be false to enable formatOutput
                return $options['formatOutput'] ? false : $value;
            })
            ->info('Do not remove redundant white space');

        // LibXML specific
        $resolver->define('recover')
            ->default(true) // Not default
            ->allowedTypes('bool')
            ->info('Enables recovery mode');

        // LibXML specific
        $resolver->define('substituteEntities')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Whether or not to substitute entities');

        return $resolver;
    }

    private static function configureIdentOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = $resolver ?? new OptionsResolver();

        $resolver->define('algo')
            ->default('sha512')
            ->allowedTypes('string')
            ->allowedValues(...hash_algos())
            ->info('Hash algorithm used to hash values');

        $resolver->define('format')
            ->default('%s')
            ->allowedTypes('string')
            ->allowedValues(function (string $value) {
                return str_contains($value, '%');
            })
            ->info('Native printf format string to generate final output. Must include %s');

        $resolver->define('base')
            ->default(62)
            ->allowedTypes('int')
            ->allowedValues(function (int $value) {
                return $value >= 2 && $value <= 62;
            })
            ->info('The base used to encode value to reduce its length or increase entropy');

        $resolver->define('length')
            ->default(7)
            ->allowedTypes('int')
            ->info('Length of the generated identifier (after base conversion)');

        return $resolver;
    }
}
