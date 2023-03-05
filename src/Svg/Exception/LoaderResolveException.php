<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Exception;

class LoaderResolveException extends RuntimeException
{
    public function __construct(string $ident, string $source = null, int $code = 0, \Throwable $previous = null)
    {
        if ($previous) {
            $message = sprintf(
                '%s loading SVG file for %s (%s).',
                rtrim(" \n\r\t\v\x00.", $previous->getMessage()),
                $ident,
                null === $source
                    ? sprintf('which is loaded in ident "%s"', $ident)
                    : sprintf('which is being imported from "%s"', $source)
            );
        } elseif (null === $source) {
            $message = sprintf('Cannot load SVG file for "%s".', $ident);
        } else {
            $message = sprintf('Cannot load SVG file for "%s" from "%s".', $ident, $source);
        }

        parent::__construct($message, $code, $previous);
    }
}
