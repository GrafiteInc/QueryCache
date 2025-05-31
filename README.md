![Grafite Cache](GrafiteQueryCache-banner.png)

**QueryCache** - A general query result caching system for Eloquent models.

[![Build Status](https://github.com/GrafiteInc/QueryCache/actions/workflows/php-package-tests.yml/badge.svg?branch=main)](https://github.com/GrafiteInc/QueryCache/actions/workflows/php-package-tests.yml)
[![Maintainability](https://qlty.sh/badges/66787aa9-2bf8-4d5a-8fef-f1d23511e787/maintainability.svg)](https://qlty.sh/gh/GrafiteInc/projects/QueryCache)
[![Packagist](https://img.shields.io/packagist/dt/grafite/query-cache.svg)](https://packagist.org/packages/grafite/query-cache)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://packagist.org/packages/grafite/query-cache)

QueryCache is a general query result caching system for Eloquent models. It's optimized to be as automated as possible, and can work with Redis and SQLite as caching.

##### Author(s):
* [Matt Lantz](https://github.com/mlantz) ([@mattylantz](http://twitter.com/mattylantz), mattlantz at gmail dot com)

## Requirements

1. PHP 8.2+

## Compatibility and Support

| Laravel Version | Package Tag | Supported |
|-----------------|-------------|-----------|
| ^11.x - ^12.x | 1.x | yes |

### Installation

Start a new Laravel project:
```php
composer create-project laravel/laravel your-project-name
```

Then run the following to add Support
```php
composer require "grafite/query-cache"
```

## Documentation

[https://docs.grafite.ca/utilities/query-cache](https://docs.grafite.ca/utilities/query-cache)

## License
Support is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
