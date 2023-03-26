<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Loader;

use Ocubom\Twig\Extension\Svg\Svg;

interface LoaderInterface
{
    /**
     * Search for the SVG that match an ident.
     *
     * @param string $ident The SVG identifier such as the relative path or key
     *
     * @return Svg The SVG
     */
    public function resolve(string $ident, iterable $options = null): Svg;
}
