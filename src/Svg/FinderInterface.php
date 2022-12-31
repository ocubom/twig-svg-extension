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

/**
 * FinderInterface.
 *
 * @author Oscar Cubo Medina <ocubom@gmail.com>
 */
interface FinderInterface
{
    /**
     * Search for the SVG file that match an ident.
     *
     * @param string $ident The SVG identifier such as the relative path or key
     *
     * @return \SplFileInfo Full path to the SVG file
     */
    public function resolve(string $ident): \SplFileInfo;
}
