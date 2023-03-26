<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\FileSystem;

use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Symfony\Component\Filesystem\Path;

class FileSystemLoader implements LoaderInterface
{
    private PathCollection $searchPath;

    public function __construct(PathCollection $searchPath)
    {
        $this->searchPath = $searchPath;
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        return new Svg($this->findPath($ident), $options);
    }

    protected function findPath(string $ident): \SplFileInfo
    {
        foreach ($this->searchPath as $basePath) {
            /** @var \SplFileInfo|null $basePath */
            $fullPath = Path::join((string) ($basePath ?? ''), $ident);
            if (!Path::hasExtension($fullPath, 'svg')) {
                $fullPath .= '.svg'; // Add svg extension
            }

            $realPath = realpath($fullPath);
            if ($realPath && is_file($realPath)) {
                return new \SplFileInfo($fullPath);
            }
        }

        throw new LoaderException($ident, new \ReflectionClass(__CLASS__), (string) $this->searchPath);
    }
}
