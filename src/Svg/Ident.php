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

use Ocubom\Twig\Extension\Svg\Util\DomIdent;

/**
 * @deprecated since ocubom/twig-svg-extension 1.1.2, use Ocubom\Twig\Extension\Svg\Util\DomIdent instead
 *
 * @codeCoverageIgnore
 */
class Ident
{
    public static function generate(
        \DOMElement $element,
        iterable $options = null
    ): string {
        trigger_deprecation('ocubom/twig-svg-extension', '1.1.2', 'Using "%s" is deprecated, use "%s" instead.', static::class, DomIdent::class);

        return DomIdent::generate($element, $options);
    }
}
