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

use Ocubom\Twig\Extension\Svg\Finder;
use Ocubom\Twig\Extension\SvgExtension;
use Ocubom\Twig\Extension\SvgRuntime;
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
        return [
            new FactoryRuntimeLoader([
                SvgRuntime::class => function () {
                    return new SvgRuntime(
                        new Finder('tests/Fixtures/Resources/')
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
