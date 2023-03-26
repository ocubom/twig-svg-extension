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

class Html5ParseException extends ParseException
{
    /**
     * @param string|\SplFileInfo $source
     */
    public function __construct(string $message, $source = null, int $line = -1, int $col = -1, int $code = 0, \Throwable $previous = null)
    {
        if (preg_match('@Line (\d+), Col (\d+): (.+)$@Uis', $message, $matches)) {
            $message = $matches[3];
            $line = intval($matches[1]);
            $col = intval($matches[2]);
        }

        $message = rtrim($message, " \n\r\t\v\x00.:");
        $snippet = '';

        if ($source instanceof \SplFileInfo) {
            $message .= sprintf(' in %s', (string) $source); // @codeCoverageIgnore
        }

        $sep = 'at';
        if ($line >= 0) {
            $message .= sprintf(' %s line %d', $sep, $line + 1);
            $sep = 'and';

            $snippet = preg_split(
                '@\s*[\n\r]\s*@',
                ($source instanceof \SplFileInfo ? file_get_contents((string) $source) : $source) ?? ''
            );

            $snippet = $snippet[$line] ?? '';
        }

        if ($col >= 0) {
            $message .= sprintf(' %s column %d', $sep, $col + 1);
        }

        if (!empty($snippet)) {
            $message .= sprintf(' (near "%s")', $snippet);
        }

        parent::__construct($message.'.', $code, $previous);
    }
}
