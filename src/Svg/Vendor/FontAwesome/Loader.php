<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Vendor\FontAwesome;

use Ocubom\Twig\Extension\Svg\Exception\LoaderResolveException;
use Ocubom\Twig\Extension\Svg\Loader\FileLoader;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;
use Ocubom\Twig\Extension\Svg\Vendor\FontAwesome;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Loader extends FileLoader
{
    private PathCollection $searchPath;
    private LoggerInterface $logger;

    public function __construct(PathCollection $searchPath, LoggerInterface $logger = null)
    {
        parent::__construct($searchPath);
        $this->logger = $logger ?? new NullLogger();
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        $tokens = preg_split('@\s+@', mb_strtolower($ident));
        $tokens[] = FontAwesome::DEFAULT_PREFIX; // Add default prefix as fallback

        foreach ($tokens as $prefix) {
            $style = FontAwesome::PREFIXES[$prefix] ?? null;
            if (null === $style) {
                continue; // Ignore tokens that are *not* a known prefix
            }

            foreach ($tokens as $name) {
                if (isset(FontAwesome::PREFIXES[$name])) {
                    continue; // Ignore tokens that are a known prefix
                }

                $name = (0 === mb_strpos($name, 'fa-')) ? mb_substr($name, 3) : $name;
                if (empty($name)) {
                    continue; // Ignore empty names
                }

                try {
                    return new Icon($this->findPath(sprintf('%s/%s', $style, $name)), $options);
                } catch (LoaderResolveException $err) {
                    // Just log error to enable debug
                    $this->logger->warning($err->getMessage());
                }
            }
        }

        // Could not resolve
        throw new LoaderResolveException($ident, __CLASS__);
    }
}
