<?php

/*
 * This file is part of ocubom/twig-svg-extension
 *
 * © Oscar Cubo Medina <https://ocubom.github.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocubom\Twig\Extension\Svg\Provider\FontAwesome;

use Ocubom\Twig\Extension\Svg\Exception\LoaderException;
use Ocubom\Twig\Extension\Svg\Provider\FileSystem\FileSystemLoader;
use Ocubom\Twig\Extension\Svg\Svg;
use Ocubom\Twig\Extension\Svg\Util\PathCollection;

class FontAwesomeLoader extends FileSystemLoader
{
    public function __construct(PathCollection $searchPath)
    {
        parent::__construct($searchPath);
    }

    public function resolve(string $ident, iterable $options = null): Svg
    {
        $tokens = preg_split('@\s+@', mb_strtolower($ident));
        $tokens[] = FontAwesome::DEFAULT_PREFIX; // Add default prefix as fallback

        $errors = [];

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
                    continue; // @codeCoverageIgnore
                }

                $path = sprintf('%s/%s', $style, $name);
                if (array_key_exists($path, $errors)) {
                    continue;
                }

                try {
                    return new FontAwesomeSvg($this->findPath($path), $options);
                } catch (LoaderException $err) {
                    $errors[sprintf('%s/%s', $style, $name)] = $err;
                }
            }
        }

        // Could not resolve
        throw new LoaderException($ident, new \ReflectionClass(__CLASS__), null, 0, $errors);
    }
}
