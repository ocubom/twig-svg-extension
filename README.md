<div align="center">

Ocubom Twig SVG Extension
=========================

A custom suite of Twig filters for SVG manipulation

[![Contributors][contributors-img]][contributors-url]
[![Forks][forks-img]][forks-url]
[![Stargazers][stars-img]][stars-url]
[![Issues][issues-img]][issues-url]
[![License][license-img]][license-url]

[![Version][packagist-img]][packagist-url]
[![CI][workflow-ci-img]][workflow-ci-url]
[![Code Quality][quality-img]][quality-url]
[![Coverage][coverage-img]][coverage-url]

[**Explore the docs »**][Documentation]

[Report Bug](https://github.com/ocubom/twig-svg-extension/issues)
·
[Request Feature](https://github.com/ocubom/twig-svg-extension/issues)

</div>

<details>
<summary>Contents</summary>

* [About TwigSvgExtension](#about-twigsvgextension)
* [Getting Started](#getting-started)
    * [Installation](#installation)
    * [Usage](#usage)
* [Roadmap](#roadmap)
* [Contributing](#contributing)
* [Authorship](#authorship)
* [License](#license)

</details>

## About TwigSvgExtension

[TwigSvgExtension](https://github.com/ocubom/twig-svg-extension) is a custom suite of **[Twig filters]** for SVG manipulation.

This suite started as an internal class to inline SVG files into HTML documents.
This class used to be embedded into several projects.
Over time, each project adapted its version slightly, leading to fragmented development and difficult maintenance.
Therefore, the development is unified in this extension which is made public in case it is useful for other projects.

## Getting Started

### Installation

Just use [composer][] to add the dependency:

```console
composer require ocubom/twig-svg-extension
```

Or add the dependency manually:

1.  Update ``composer.json`` file with the lines:

    ```json
    {
        "require": {
            "ocubom/twig-svg-extension": "^1.0.0"
        }
    }
    ```

2.  And update the dependencies:

    ```console
    composer update "ocubom/twig-svg-extension"
    ```

### Usage

Just register the Twig extension:

```php
$twig = new \Twig\Environment();
$twig->addExtension(new \Ocubom\Twig\Extension\SvgExtension());
```

_For more examples, please refer to the [Documentation][]._

## Roadmap

See the [open issues](https://github.com/ocubom/twig-svg-extension/issues) for a full list of proposed features (and known issues).

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create.
Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request.
You can also simply open an issue with the tag "enhancement".

1. Fork the Project.
2. Create your Feature Branch (`git checkout -b feature/your-feature`).
3. Commit your Changes (`git commit -m 'Add your-feature'`).
4. Push to the Branch (`git push origin feature/your-feature`).
5. Open a Pull Request.

## Authorship

* Oscar Cubo Medina — [@ocubom](https://twitter.com/ocubom) — https://ocubom.github.io

See also the list of [contributors][contributors-url] who participated in this project.

## License

Distributed under the MIT License.
See [LICENSE][] for more information.


[Documentation]: https://github.com/ocubom/twig-svg-extension
[LICENSE]: https://github.com/ocubom/twig-svg-extension/blob/master/LICENSE

<!-- Links -->
[composer]: https://getcomposer.org/
[Symfony]: https://symfony.com/
[Twig filters]: https://twig.symfony.com/doc/3.x/advanced.html#filters

<!-- Project Badges -->
[contributors-img]: https://img.shields.io/github/contributors/ocubom/twig-svg-extension.svg?style=for-the-badge
[contributors-url]: https://github.com/ocubom/twig-svg-extension/graphs/contributors
[forks-img]:        https://img.shields.io/github/forks/ocubom/twig-svg-extension.svg?style=for-the-badge
[forks-url]:        https://github.com/ocubom/twig-svg-extension/network/members
[stars-img]:        https://img.shields.io/github/stars/ocubom/twig-svg-extension.svg?style=for-the-badge
[stars-url]:        https://github.com/ocubom/twig-svg-extension/stargazers
[issues-img]:       https://img.shields.io/github/issues/ocubom/twig-svg-extension.svg?style=for-the-badge
[issues-url]:       https://github.com/ocubom/twig-svg-extension/issues
[license-img]:      https://img.shields.io/github/license/ocubom/twig-svg-extension.svg?style=for-the-badge
[license-url]:      https://github.com/ocubom/twig-svg-extension/blob/master/LICENSE
[workflow-ci-img]:  https://img.shields.io/github/actions/workflow/status/ocubom/twig-svg-extension/test.yml?branch=main&label=CI&logo=github&style=for-the-badge
[workflow-ci-url]:  https://github.com/ocubom/twig-svg-extension/actions/
[packagist-img]:    https://img.shields.io/packagist/v/ocubom/twig-svg-extension.svg?logo=packagist&logoColor=%23fefefe&style=for-the-badge
[packagist-url]:    https://packagist.org/packages/ocubom/twig-svg-extension
[coverage-img]:     https://img.shields.io/scrutinizer/coverage/g/ocubom/twig-svg-extension.svg?logo=scrutinizer&logoColor=fff&style=for-the-badge
[coverage-url]:     https://scrutinizer-ci.com/g/ocubom/twig-svg-extension/code-structure/main/code-coverage
[quality-img]:      https://img.shields.io/scrutinizer/quality/g/ocubom/twig-svg-extension.svg?logo=scrutinizer&logoColor=fff&style=for-the-badge
[quality-url]:      https://scrutinizer-ci.com/g/ocubom/twig-svg-extension/
