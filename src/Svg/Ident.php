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

use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Math\base_convert;

use Ocubom\Twig\Extension\Svg\Util\DomHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Ident
{
    private static ?OptionsResolver $resolver = null;

    private const REPLACEMENTS = [
        '> <' => '><',
        '" >' => '">',
        '\' >' => '\'>',
    ];

    public static function generate(
        \DOMElement $element,
        iterable $options = null
    ): string {
        // Initialize static variables
        /* @psalm-suppress RedundantPropertyInitializationCheck */
        self::$resolver = self::$resolver ?? self::configureOptions();

        // Resolve options
        $options = self::$resolver->resolve(iterable_to_array($options ?? []));

        // Work on an optimized clone
        $node = DomHelper::cloneElement($element);
        // Remove identifier
        $node->removeAttribute('id');

        // Convert to text
        $text = DomHelper::toXml($node);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = trim($text);
        foreach (self::REPLACEMENTS as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

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

    protected static function configureOptions(OptionsResolver $resolver = null): OptionsResolver
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
