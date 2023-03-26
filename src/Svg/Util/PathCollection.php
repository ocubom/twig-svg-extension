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

use Symfony\Component\Filesystem\Path;

/**
 * @template-implements \IteratorAggregate<\SplFileInfo>
 */
class PathCollection implements \IteratorAggregate, \Stringable
{
    private array $searchPath = [];

    /**
     * Constructor.
     *
     * @param mixed ...$searchPath The paths where SVG files are located
     */
    public function __construct(...$searchPath)
    {
        foreach ($searchPath as $path) {
            $this->searchPath[] = new \SplFileInfo(Path::canonicalize($path));
        }
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->searchPath as $path) {
            yield $path;
        }
    }

    public function __toString(): string
    {
        return implode(':', $this->searchPath);
    }
}
