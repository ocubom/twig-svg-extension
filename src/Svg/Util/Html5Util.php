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

use Masterminds\HTML5;
use Ocubom\Twig\Extension\Svg\Exception\Html5Exception;
use Ocubom\Twig\Extension\Svg\Exception\Html5ParseException;

/** @internal */
final class Html5Util
{
    // @codeCoverageIgnoreStart
    private function __construct()
    {
    }

    // @codeCoverageIgnoreEnd

    public static function loadHtml(string $html, array $options = []): \DOMDocument
    {
        $parser = new HTML5();

        // Load Document
        $doc = $parser->loadHTML($html, $options);
        if ($parser->hasErrors()) {
            throw new Html5Exception(null, 0, array_map(
                function ($message) use ($html) {
                    return new Html5ParseException($message, $html);
                },

                $parser->getErrors(),
            ));
        }

        return $doc;
    }

    public static function toHtml(\DOMDocument $doc, array $options = []): string
    {
        // Normalize final doc
        if ($options['normalize_document'] ?? true) {
            $doc->normalize();
        }

        // Generate output
        $html = (new HTML5())->saveHTML($doc, $options);

        // Fix EOL lines
        if (($options['normalize_eol'] ?? "\n") !== \PHP_EOL) {
            $html = str_replace(\PHP_EOL, $options['normalize_eol'] ?? "\n", $html); // @codeCoverageIgnore
        }

        return $html;
    }
}
