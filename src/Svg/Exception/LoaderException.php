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

use Ocubom\Twig\Extension\Svg\Util\AggregatedExceptionTrait;

use function BenTools\IterableFunctions\iterable_to_array;
use function Ocubom\Twig\Extension\is_string;

class LoaderException extends RuntimeException
{
    use AggregatedExceptionTrait;

    /**
     * @param \Throwable|iterable<\Throwable> $previous
     */
    public function __construct(string $ident, \ReflectionClass $loader = null, string $source = null, int $code = 0, $previous = null)
    {
        if (is_iterable($previous)) {
            $previous = iterable_to_array(/* @scrutinizer ignore-type */ $previous, false);

            if (1 === count($previous)) {
                $previous = $previous[0];
            }
        }

        if ($previous instanceof \Throwable) {
            $lines = explode("\n", $previous->getMessage());
            $message = sprintf(
                '%s loading SVG for "%s"',
                rtrim($lines[0], " \n\r\t\v\x00.:"),
                $ident
            );

            if (is_string($source)) {
                $message .= sprintf(' on "%s"', $source);
            }

            if ($loader instanceof \ReflectionClass) {
                $message .= sprintf(' by "%s"', $loader->getShortName());
            }

            if (count($lines) > 1) {
                $message .= ':';
                $message .= implode("\n", array_slice($lines, 1));
            } else {
                $message .= '.';
            }
        } else {
            $message = $loader instanceof \ReflectionClass
                ? sprintf('"%s" cannot load SVG for "%s"', $loader->getShortName(), $ident)
                : sprintf('Unable to load SVG for "%s"', $ident);

            if (is_string($source)) {
                $message .= sprintf(' on "%s"', $source);
            }

            $message = $this->formatMessage($message, $previous);
        }

        parent::__construct($message, $code, $previous instanceof \Throwable ? $previous : null);
    }
}
