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
        $errors = [];

        foreach (self::parseIdent(preg_split('@\s+@', mb_strtolower($ident))) as $path) {
            if (array_key_exists($path, $errors)) {
                continue;
            }

            try {
                return new FontAwesomeSvg($this->findPath($path), $options);
            } catch (LoaderException $err) {
                $errors[$path] = $err;
            }
        }

        // Could not resolve
        throw new LoaderException($ident, new \ReflectionClass(__CLASS__), null, 0, $errors);
    }

    private static function parseIdent(array $ident): iterable
    {
        $ident[] = FontAwesome::DEFAULT_PREFIX; // Add default prefix as fallback

        foreach ($ident as $prefix) {
            $style = FontAwesome::PREFIXES[$prefix] ?? null;
            if (null === $style) {
                continue; // Ignore tokens that are *not* a known prefix
            }

            foreach ($ident as $name) {
                if (isset(FontAwesome::PREFIXES[$name])) {
                    continue; // Ignore tokens that are a known prefix
                }

                $name = (0 === mb_strpos($name, 'fa-')) ? mb_substr($name, 3) : $name;
                if (empty($name)) {
                    continue; // @codeCoverageIgnore
                }

                yield sprintf('%s/%s', $style, $name);
            }
        }
    }
}
