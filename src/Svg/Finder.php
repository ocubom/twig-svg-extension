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

use Ocubom\Twig\Extension\Svg\Exception\FileNotFoundException;

/**
 * Finder.
 *
 * @author Oscar Cubo Medina <ocubom@gmail.com>
 */
class Finder implements FinderInterface
{
    private array $searchPath;

    /**
     * Constructor.
     *
     * @param mixed ...$searchPath The paths where SVG files are located
     */
    public function __construct(...$searchPath)
    {
        $this->searchPath = $searchPath;
    }

    public function resolve(string $ident): \SplFileInfo
    {
        foreach ($this->searchPath as $basePath) {
            $fullPath = rtrim($basePath, '/\\').'/'.$ident;
            if ('.svg' !== substr($fullPath, -4)) {
                $fullPath .= '.svg'; // Add svg extension
            }

            if (is_file($fullPath)) {
                return new \SplFileInfo($fullPath);
            }
        }

        throw new FileNotFoundException(sprintf(
            'SVG file for "%s" could not be found on "%s".',
            $ident,
            (string) $this,
        ));
    }

    public function __toString(): string
    {
        return implode(':', $this->searchPath);
    }
}
