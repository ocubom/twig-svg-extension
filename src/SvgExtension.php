<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SvgExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'svg_symbols',
                [SvgRuntime::class, 'convertToSymbols'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'svg',
                [SvgRuntime::class, 'renderSvg'],
                [
                    'is_safe' => ['html'],
                    // 'needs_environment' => true,
                ]
            ),
        ];
    }
}
