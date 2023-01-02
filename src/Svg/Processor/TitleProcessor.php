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

use Ocubom\Twig\Extension\Svg\Util\DomHelper;

class TitleProcessor implements ProcessorInterface
{
    public function __invoke(\DOMElement $svg, array $options = []): \DOMElement
    {
        if (empty($options['title'])) {
            return $svg; // Do nothing unless title is set
        }

        /** @var \DOMNode $child */
        foreach ($svg->getElementsByTagName('title') as $child) {
            // Remove title nodes directly under main element
            if ($child->parentNode === $svg) {
                DomHelper::removeNode($child);
            }
        }

        // Create title element
        $title = DomHelper::createElement('title', $options['title'], $svg->firstChild, true);

        // Reference title identifier with aria attribute
        $title->setAttribute('id', $options['aria-labelledby']);
        $svg->setAttribute('aria-labelledby', $options['aria-labelledby']);

        return $svg;
    }
}
