<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Processor;

use function Ocubom\Twig\Extension\is_string;

class ApplyAttributesProcessor implements ProcessorInterface
{
    private array $blocked;

    public function __construct(string ...$blocked)
    {
        $this->blocked = $blocked;
    }

    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        foreach ($options as $key => $val) {
            if (in_array($key, $this->blocked)) {
                // Delete option attribute
                $svg->removeAttribute($key);
            } elseif ($val = is_string($val) ? $val : null) {
                // Set option attribute after clean
                $svg->removeAttribute($key);
                $svg->setAttribute($key, $val);
            }
        }

        return $svg;
    }
}
