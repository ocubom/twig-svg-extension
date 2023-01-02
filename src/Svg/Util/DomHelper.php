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

use function BenTools\IterableFunctions\iterable_to_array;

use Ocubom\Twig\Extension\Svg\Exception\RuntimeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @internal */
final class DomHelper
{
    /** @var OptionsResolver[] */
    private static array $resolver = [];

    // @codeCoverageIgnoreStart
    private function __construct()
    {
    }

    // @codeCoverageIgnoreEnd

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
        // Get node document ($node if is a DOMdocument)
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
            // Get node document ($node if is a DOMdocument)
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
        \DOMNode $node
    ): string {
        assert($node->ownerDocument instanceof \DOMDocument);

        $output = $node->ownerDocument->saveHTML($node);
        if (false === $output) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'Unable to convert %s to HTML',
                get_class($node)
            ));
            // @codeCoverageIgnoreEnd
        }

        return $output;
    }

    public static function toXml(
        \DOMNode $node,
        int $options = 0
    ): string {
        assert($node->ownerDocument instanceof \DOMDocument);

        $output = $node->ownerDocument->saveXML($node, $options);
        if (false === $output) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'Unable to convert %s to XML',
                get_class($node)
            ));
            // @codeCoverageIgnoreEnd
        }

        return $output;
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
            ->default(false)
            ->allowedTypes('bool')
            ->info('Enables recovery mode');

        // LibXML specific
        $resolver->define('substituteEntities')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Whether or not to substitute entities');

        return $resolver;
    }
}
