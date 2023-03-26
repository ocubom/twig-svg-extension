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

class Html5Exception extends RuntimeException
{
    use AggregatedExceptionTrait;

    /**
     * @param \Throwable|iterable<\Throwable> $previous
     */
    public function __construct(string $message = null, int $code = 0, $previous = null)
    {
        parent::__construct(
            $this->formatMessage($message ?? 'Generated %d %s parsing HTML5', $previous),
            $code
        );
    }
}
