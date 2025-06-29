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
# commandes basiques symfoni
docker compose exec php bin/console cache:clear
docker compose exec php bin/console debug:router
docker compose exec php bin/console debug:container

# migrations
docker compose exec php bin/console make:migration
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:migrations:status

# Entity manager
docker compose exec php bin/console make:entity
docker compose exec php bin/console make:crud

# controllers
docker compose exec php bin/console make:controller

# Commandes sur la bdd
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:schema:update --force
docker compose exec php bin/console doctrine:fixtures:load


docker compose exec database psql -U app -d app

#voir les table
\dt

#trucate les datas des tables
SELECT 'TRUNCATE TABLE ' || tablename || ' RESTART IDENTITY CASCADE;'
FROM pg_tables
WHERE schemaname = 'public'
\gexecDROP SCHEMA public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO app;
GRANT ALL ON SCHEMA public TO public;

#Delete all tables
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO app;
GRANT ALL ON SCHEMA public TO public;

# Composer commands ajout packages
docker compose exec php composer require [package-name]
docker compose exec php composer update

```

## API Usage

### Authentication Endpoints

L'API utilise un système d'authentification par token. Voici comment utiliser les endpoints d'authentification :

#### 1. Inscription d'un utilisateur

```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Réponse attendue :**

```json
{
    "message": "Utilisateur créé avec succès",
    "user": {
        "id": 1,
        "username": "testuser",
        "email": "test@example.com",
        "createdAt": "2025-06-29 10:30:00"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "apiToken": "1a2b3c4d5e6f7g8h9i0j..."
}
```

#### 2. Connexion d'un utilisateur

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "testuser",
    "password": "password123"
  }'
```

**Note :** Le champ `login` peut être soit le `username` soit l'`email`.

**Réponse attendue :**

```json
{
    "message": "Connexion réussie",
    "user": {
        "id": 1,
        "username": "testuser",
        "email": "test@example.com",
        "dailyScore": 0,
        "lastLogin": "2025-06-29 10:35:00"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "apiToken": "1a2b3c4d5e6f7g8h9i0j..."
}
```

#### 3. Récupérer le profil utilisateur (nécessite authentification)

```bash
curl -X GET http://localhost/api/auth/me \
  -H "Authorization: Bearer VOTRE_API_TOKEN"
```

### Character Endpoints

**⚠️ Important :** Tous les endpoints nécessitent une authentification. Utilisez l'`apiToken` obtenu lors de la connexion.


### Notes importantes

1. **Token à utiliser :** Utilisez l'`apiToken` (pas le `token` JWT) dans le header `Authorization: Bearer`
2. **Endpoints publics :** Seuls `/api/auth/register` et `/api/auth/login` sont accessibles sans authentification
3. **Endpoints protégés :** Tous les autres endpoints nécessitent le header `Authorization`
4. **Cache :** Les données sont mises en cache pour améliorer les performances
5. **API externe :** L'application utilise l'API Attack on Titan pour récupérer les données des personnages