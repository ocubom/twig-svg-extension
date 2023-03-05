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

use Iconify\JSONTools\Collection;
use Iconify\JSONTools\SVG as Icon;
use Ocubom\Twig\Extension\Svg\Exception\LoaderResolveException;
use Ocubom\Twig\Extension\Svg\Exception\LogicException;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class IconifyLoader implements LoaderInterface
{
    private PathCollection $searchPath;
    private ?string $cacheDir;
    private LoggerInterface $logger;
    private static Filesystem $fs;
    private const CACHE_BASEDIR = 'iconify';
    private const CACHE_EXTENSION = 'php';

    public function __construct(PathCollection $searchPath, string $cacheDir = null, LoggerInterface $logger = null)
    {
        self::$fs = new Filesystem();

        $this->searchPath = $searchPath;
        $this->cacheDir = $cacheDir ? Path::canonicalize($cacheDir) : '';
        $this->logger = $logger ?? new NullLogger();

        if ('' === $this->cacheDir) {
            $this->cacheDir = null;
        } else {
            // Ensure cache dir is inside prefixed subdir
            if (self::CACHE_BASEDIR !== Path::getFilenameWithoutExtension($this->cacheDir)) {
                $this->cacheDir = Path::join($this->cacheDir, self::CACHE_BASEDIR);
            }
        }
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        // Split ident
        $tokens = preg_split('@[/:\-]+@Uis', $ident);
        for ($idx = 1; $idx < count($tokens); ++$idx) {
            $icon = $this->loadIcon(
                join('-', array_slice($tokens, 0, $idx)),
                join('-', array_slice($tokens, $idx - count($tokens)))
            );

            if ($icon) {
                return new Svg($icon->getSVG());
            }
        }

        throw new LoaderResolveException($ident, __CLASS__);
    }

    private function loadIcon(string $prefix, string $name): ?Icon
    {
        foreach ($this->getCollectionPaths($prefix) as $path) {
            if (!self::$fs->exists($path)) {
                $this->logger->warning('Path {path} of collection {prefix} does not exist.', [
                    'path' => $path,
                    'prefix' => $prefix,
                ]);

                continue;
            }

            $collection = new Collection();
            if (!$collection->loadFromFile($path, null, $this->getCacheFile($prefix))) {
                $this->logger->warning('Unable to load {path} of collection {prefix}.', [
                    'path' => $path,
                    'prefix' => $prefix,
                ]);

                continue;
            }

            $data = $collection->getIconData($name);
            if (!$data) {
                $this->logger->warning('Unable to load {name} icon from {path} of collection {prefix}.', [
                    'path' => $path,
                    'prefix' => $prefix,
                    'name' => $name,
                ]);

                continue;
            }

            return new Icon($data);
        }

        // Unable to find icon
        return null;
    }

    private function getCacheFile(string $collection): ?string
    {
        if (null === $this->cacheDir) {
            return null;
        }

        $path = Path::changeExtension(
            Path::join($this->cacheDir, $collection),
            self::CACHE_EXTENSION
        );
        if (!Path::isBasePath($this->cacheDir, $path)) {
            throw new LogicException(sprintf(
                'The generated cache path `%s` is outside the base cache directory `%s`.',
                $path,
                $this->cacheDir
            ));
        }

        // Ensure cache dir exists
        self::$fs->mkdir($this->cacheDir, 0755);

        return $path;
    }

    /**
     * @psalm-return \Generator<int, string, mixed, void>
     */
    private function getCollectionPaths(string $name): \Generator
    {
        foreach ($this->searchPath as $basePath) {
            // Try iconify/json path
            yield Path::join((string) $basePath, 'json', $name.'.json');

            // Try iconify-json path
            yield Path::join((string) $basePath, $name, 'icons.json');
        }
    }
}
