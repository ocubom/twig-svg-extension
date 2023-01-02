<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Library\FontAwesome;

use Ocubom\Twig\Extension\Svg\Exception\FileNotFoundException;
use Ocubom\Twig\Extension\Svg\FinderInterface;
use Ocubom\Twig\Extension\Svg\Library\FontAwesome;

class Finder implements FinderInterface
{
    private FinderInterface $finder;

    public function __construct(FinderInterface $finder)
    {
        $this->finder = $finder;
    }

    public function resolve(string $ident): \SplFileInfo
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
                    return $this->finder->resolve(sprintf('%s/%s.svg', $style, $name));
                } catch (FileNotFoundException $err) {
                    // Just ignore
                }
            }
        }

        // Could not resolve
        throw new FileNotFoundException(sprintf(
            'Could not found a FontAwesome icon for "%s" on "%s".',
            $ident,
            (string) $this,
        ));
    }

    public function __toString(): string
    {
        return (string) $this->finder;
    }
}
