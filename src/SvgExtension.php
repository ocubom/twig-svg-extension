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

use Ocubom\Twig\Extension\Svg\Provider\FontAwesome\FontAwesomeRuntime;
use Ocubom\Twig\Extension\Svg\Provider\Iconify\IconifyRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SvgExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // SVG core
            new TwigFilter(
                'svg',
                [SvgRuntime::class, 'convertToSymbols'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFilter(
                'svg_symbols',
                [SvgRuntime::class, 'convertToSymbols'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),

            // Providers
            new TwigFilter(
                'fontawesome',
                [FontAwesomeRuntime::class, 'replaceIcons'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFilter(
                'iconify',
                [IconifyRuntime::class, 'replaceIcons'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                    'is_variadic' => true,
                ]
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            // SVG Core
            new TwigFunction(
                'svg',
                [SvgRuntime::class, 'embedSvg'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),

            // Providers
            new TwigFunction(
                'fa',
                [FontAwesomeRuntime::class, 'renderHtmlTag'],
                [
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }
}
