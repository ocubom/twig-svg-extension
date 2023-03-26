<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\FontAwesome;

class FontAwesome
{
    const DEFAULT_PREFIX = 'fas';

    const INLINE_CLASS = 'svg-inline--fa';

    // @see https://fontawesome.com/v6/docs/web/setup/upgrading/whats-changed#full-style-names
    const PREFIXES = [
        // v4- style class names
        'fa' => 'solid',

        // v5 style class names
        'fas' => 'solid',
        'far' => 'regular',
        'fal' => 'light',
        'fat' => 'thin',
        'fad' => 'duotone',
        'fab' => 'brands',
        'fak' => 'kit',

        // v6+ style class names
        'fa-solid' => 'solid',
        'fa-regular' => 'regular',
        'fa-light' => 'light',
        'fa-thin' => 'thin',
        'fa-duotone' => 'duotone',
        'fa-brands' => 'brands',
        'fa-kit' => 'kit',

        // unprefixed class names
        'solid' => 'solid',
        'regular' => 'regular',
        'light' => 'light',
        'thin' => 'thin',
        'duotone' => 'duotone',
        'brands' => 'brands',
        'kit' => 'kit',
    ];

    const HTML_TAG = 'span'; // Usually the <i> tag is used
}
