
# Chebur Daemon Command

[![PHP requirements](https://img.shields.io/packagist/php-v/chebur/daemon-command.svg)](https://packagist.org/packages/chebur/daemon-command "PHP requirements")
[![Latest version](https://img.shields.io/packagist/v/chebur/daemon-command.svg)](https://packagist.org/packages/chebur/daemon-command "Last version")
[![Total downloads](https://img.shields.io/packagist/dt/chebur/daemon-command.svg)](https://packagist.org/packages/chebur/daemon-command "Total downloads")
[![License](https://img.shields.io/packagist/l/chebur/daemon-command.svg)](https://packagist.org/packages/chebur/daemon-command "License")

## Installation
Require the bundle and its dependencies with composer:
```bash
composer require chebur/daemon-command
```
Create your daemon commands extending AbstractDaemonCommand
```php

```

## Tests
Tests run in Docker:
```bash
docker compose build php
docker compose run --rm php composer install
docker compose run --rm php composer test
```

## License
See [LICENSE](LICENSE).
