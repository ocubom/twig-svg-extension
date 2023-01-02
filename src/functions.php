<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension;

/**
 * Find whether the type of a variable is string or it can be converted to a string.
 *
 * @see https://php.net/manual/function.is-string.php
 *
 * @param mixed $value the variable being evaluated
 *
 * @return bool true if value is of type string or convertible, false otherwise
 *
 * @psalm-assert-if-true string $value
 */
function is_string($value): bool
{
    return \is_string($value)
        || (is_object($value) && method_exists($value, '__toString'));
}
