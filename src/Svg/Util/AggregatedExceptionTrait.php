<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Util;

use function BenTools\IterableFunctions\iterable_to_array;

trait AggregatedExceptionTrait
{
    /** @var \Throwable[] */
    private array $previous = [];

    /**
     * @param \Throwable|iterable<\Throwable> $previous
     */
    private function formatMessage(string $message = null, $previous = null): string
    {
        $this->previous = $previous instanceof \Throwable
            ? [$previous]
            : iterable_to_array($previous ?? /* @scrutinizer ignore-type */ [], false);
        $count = count($this->previous);

        $message = sprintf(
            rtrim($message ?? 'Generated %d %s', " \n\r\t\v\x00.:"),
            $count,
            1 == $count ? 'exception' : 'exceptions'
        );

        if ($count > 0) {
            $message .= ":\n\n";
            foreach ($this->previous as $idx => $err) {
                $message .= sprintf(
                    "% 3d. [%s] %s.\n",
                    $idx + 1,
                    get_class($err),
                    trim($err->getMessage(), " \n\r\t\v\x00.:")
                );
            }
        } else {
            $message .= '.';
        }

        return $message;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->previous as $exc) {
            yield $exc;
        }
    }

    public function count(): int
    {
        return count($this->previous);
    }
}
