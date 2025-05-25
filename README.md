# Symfony Docker

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait` to set up and start a fresh Symfony project
4. Open `https://localhost` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Basic commands

### Symfony commands inside Docker

Run Symfony commands inside the PHP container:

```bash
# Basic Symfony CLI commands
docker compose exec php bin/console cache:clear
docker compose exec php bin/console debug:router
docker compose exec php bin/console debug:container

# Database migrations
docker compose exec php bin/console make:migration
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:migrations:status

# Entity management
docker compose exec php bin/console make:entity
docker compose exec php bin/console make:crud

# Creating controllers and forms
docker compose exec php bin/console make:controller
docker compose exec php bin/console make:form

# Database commands
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:schema:update --force
docker compose exec php bin/console doctrine:fixtures:load

# Access database directly
docker compose exec database psql -U app -d app

# Composer commands
docker compose exec php composer require [package-name]
docker compose exec php composer update

#Show tebles
```

You can also use the Symfony console directly by attaching to the PHP container:

```bash
docker compose exec php bash
# Then inside the container:
bin/console [your-command]
```
