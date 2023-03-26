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

use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
use Ocubom\Twig\Extension\Svg\Svg;

class ChainLoader implements LoaderInterface
{
    private iterable $loaders;

    /**
     * @param iterable<LoaderInterface> $loaders
     */
    public function __construct(iterable $loaders = null)
    {
        $this->loaders = $loaders ?? /* @scrutinizer ignore-type */ [];
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        $errors = [];
        foreach ($this->loaders as $loader) {
            /* @var LoaderInterface $loader */
            try {
                return $loader->resolve($ident, $options);
            } catch (LoaderException $err) {
                $errors[] = $err;
            }
        }

        throw new LoaderException($ident, new \ReflectionClass(__CLASS__), null, 0, $errors);
    }
}
