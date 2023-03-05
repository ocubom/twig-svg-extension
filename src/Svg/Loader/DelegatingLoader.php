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

use Ocubom\Twig\Extension\Svg\Exception\LoaderResolveException;
use Ocubom\Twig\Extension\Svg\Svg;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DelegatingLoader implements LoaderInterface
{
    /** @var LoaderInterface[] */
    private array $loaders;

    private LoggerInterface $logger;

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(iterable $loaders = null, LoggerInterface $logger = null)
    {
        $this->loaders = $loaders ?? [];
        $this->logger = $logger ?? new NullLogger();
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->resolve($ident, $options);
            } catch (LoaderResolveException $err) {
                // Just log error to enable debug
                $this->logger->warning($err->getMessage());
            }
        }

        throw new LoaderResolveException($ident);
    }
}
