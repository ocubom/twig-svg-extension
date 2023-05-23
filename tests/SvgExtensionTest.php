<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Tests;

use Iconify\IconsJSON\Finder as IconifyJsonFinder;
use Ocubom\Twig\Extension\Svg\Loader\ChainLoader;
use Ocubom\Twig\Extension\Svg\Provider\FileSystem\FileSystemLoader;
use Ocubom\Twig\Extension\Svg\Provider\FontAwesome\FontAwesomeLoader;
use Ocubom\Twig\Extension\Svg\Provider\FontAwesome\FontAwesomeRuntime;
use Ocubom\Twig\Extension\Svg\Provider\Iconify\IconifyLoader;
use Ocubom\Twig\Extension\Svg\Provider\Iconify\IconifyRuntime;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Ocubom\Twig\Extension\SvgExtension;
use Ocubom\Twig\Extension\SvgRuntime;
use Symfony\Component\Filesystem\Path;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

class SvgExtensionTest extends IntegrationTestCase
{
    public function testSvgSerialization()
    {
        $svg = new Svg(new \SplFileInfo($this->getFixturesDir().'Resources/test.svg'));

        $this->assertEquals((string) $svg, (string) unserialize(serialize($svg)));
    }

    public function getFixturesDir(): string
    {
        return __DIR__.'/Fixtures/';
    }

    public function getExtensions(): array
    {
        return [
            new SvgExtension(),
        ];
    }

    public function getRuntimeLoaders(): array
    {
        $paths = new PathCollection(
            'tests/Fixtures/Resources/'
        );

        $loader = new ChainLoader([
            new FileSystemLoader($paths),
            $faLoader = new FontAwesomeLoader($paths),
            $iconifyLoader = new IconifyLoader(
                new PathCollection(
                    IconifyJsonFinder::rootDir(),
                    ...$paths,
                ),
                [
                    'cache_dir' => Path::join(
                        sys_get_temp_dir(),
                        'ocubom-svg-extension-test-'.microtime(true)
                    ),
                ]
            ),
        ]);

        return [
            new FactoryRuntimeLoader([
                // SVG Core
                SvgRuntime::class => function () use ($loader) {
                    return new SvgRuntime($loader);
                },

                // SVG Providers
                FontAwesomeRuntime::class => function () use ($faLoader) {
                    return new FontAwesomeRuntime($faLoader);
                },
                IconifyRuntime::class => function () use ($iconifyLoader) {
                    return new IconifyRuntime($iconifyLoader);
                },
            ]),
        ];
    }

    public function getTests($name, $legacyTests = false)
    {
        return array_reduce(
            parent::getTests($name, $legacyTests),
            function ($tests, $test) {
                // Change key to be more descriptive
                $tests[sprintf('[% 3d] %s', count($tests), $test[1])] = $test;

                return $tests;
            },
            []
        );
    }
}
