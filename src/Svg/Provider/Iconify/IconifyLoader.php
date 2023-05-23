<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\Iconify;

use Iconify\JSONTools\Collection;
use Iconify\JSONTools\SVG as Icon;
use Ocubom\Twig\Extension\Svg\Exception\JsonException;
use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
use Ocubom\Twig\Extension\Svg\Exception\LogicException;
use Ocubom\Twig\Extension\Svg\Loader\LoaderInterface;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Twig\Extension\is_string;

class IconifyLoader implements LoaderInterface
{
    private PathCollection $searchPath;
    private ?string $cacheDir;
    private static Filesystem $fs;
    private const CACHE_BASEDIR = 'iconify';
    private const CACHE_EXTENSION = 'php';

    public function __construct(PathCollection $searchPath, iterable $options = null)
    {
        self::$fs = new Filesystem();

        $this->searchPath = $searchPath;

        // Parse options
        $options = static::configureOptions()
            ->resolve(iterable_to_array($options ?? /* @scrutinizer ignore-type */ []));

        // Check cache directory path
        $this->cacheDir = Path::canonicalize($options['cache_dir'] ?? '');
        if ('' === $this->cacheDir) {
            $this->cacheDir = null; // @codeCoverageIgnore
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
        $count = count($tokens);
        for ($idx = 1; $idx < $count; ++$idx) {
            $icon = $this->loadIcon(
                join('-', array_slice($tokens, 0, $idx)),
                join('-', array_slice($tokens, $idx - $count))
            );

            if ($icon) {
                return new IconifySvg($icon->getSVG(iterable_to_array($options ?? []), true));
            }
        }

        throw new LoaderException($ident, new \ReflectionClass($this));
    }

    private function loadIcon(string $prefix, string $name): ?Icon
    {
        foreach ($this->getCollectionPaths($prefix) as $path) {
            if (!self::$fs->exists($path)) {
                continue;
            }

            $collection = new Collection();
            if (!$collection->loadFromFile($path, null, $this->getCacheFile($prefix))) {
                throw new JsonException(sprintf('Unable to parse "%s" JSON file', $path));
            }

            $data = $collection->getIconData($name);
            if (!$data) {
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
            return null; // @codeCoverageIgnore
        }

        $path = Path::changeExtension(
            Path::join($this->cacheDir, $collection),
            self::CACHE_EXTENSION
        );
        if (!Path::isBasePath($this->cacheDir, $path)) {
            // @codeCoverageIgnoreStart
            throw new LogicException(sprintf(
                'The generated cache path `%s` is outside the base cache directory `%s`.',
                $path,
                $this->cacheDir
            ));
            // @codeCoverageIgnoreEnd
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
            // Try @iconify/json path (full set)
            yield Path::join((string) $basePath, 'json', $name.'.json');

            // Try @iconify-json path (cherry picking)
            yield Path::join((string) $basePath, $name, 'icons.json');
        }
    }

    /** @psalm-suppress MissingClosureParamType */
    protected static function configureOptions(OptionsResolver $resolver = null): OptionsResolver
    {
        $resolver = $resolver ?? new OptionsResolver();

        $resolver->define('cache_dir')
            ->default(null)
            ->allowedTypes('null', 'string', \SplFileInfo::class)
            ->normalize(function (Options $options, $value): string {
                return is_string($value) ? $value : ($value ?? '');
            })
            ->info('Where cache files will be stored');

        return $resolver;
    }
}
