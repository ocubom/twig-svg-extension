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

use Ocubom\Twig\Extension\Svg\Loader\DelegatingLoader;
use Ocubom\Twig\Extension\Svg\Loader\FileLoader;
use Ocubom\Twig\Extension\Svg\Loader\IconifyLoader;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Ocubom\Twig\Extension\Svg\Vendor\FontAwesome\Loader as FaLoader;
use Ocubom\Twig\Extension\Svg\Vendor\FontAwesomeRuntime;
use Ocubom\Twig\Extension\SvgExtension;
use Ocubom\Twig\Extension\SvgRuntime;
use Symfony\Component\Filesystem\Path;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

class SvgExtensionTest extends IntegrationTestCase
{
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

        $loader = new DelegatingLoader([
            new FileLoader($paths),
            new IconifyLoader(
                new PathCollection(
                    \Iconify\IconsJSON\Finder::rootDir()
                ),
                Path::join(sys_get_temp_dir(), 'ocubom-svg-extension-test-'.microtime(true))
            ),
        ]);

        return [
            new FactoryRuntimeLoader([
                SvgRuntime::class => function () use ($loader) {
                    return new SvgRuntime($loader);
                },
                FontAwesomeRuntime::class => function () use ($paths) {
                    return new FontAwesomeRuntime(
                        new FaLoader($paths)
                    );
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
