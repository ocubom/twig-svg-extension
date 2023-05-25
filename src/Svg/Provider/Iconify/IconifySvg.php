<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\Iconify;

use Ocubom\Twig\Extension\Svg\Processor\RemoveAttributesProcessor;
use Ocubom\Twig\Extension\Svg\Svg;

class IconifySvg extends Svg
{
    protected static function getProcessors(): array
    {
        return array_merge(parent::getProcessors(), [
            // Options will be ignored & removed
            new RemoveAttributesProcessor(
                'data-icon',
                'icon',
            ),
        ]);
    }
}
