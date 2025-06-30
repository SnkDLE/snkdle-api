# Snkdle API

Une API Symfony pour un jeu de quiz sur les personnages d'Attack on Titan.

## 🚀 Démarrage rapide

### Prérequis

-   [Docker](https://docs.docker.com/get-docker/)
-   [Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)

### Installation et lancement

1. **Cloner le projet**

    ```bash
    git clone <url-du-repo>
    cd snkdle-api
    ```

2. **Construire les images Docker**

    ```bash
    docker compose build --no-cache
    ```

3. **Lancer l'application**

    ```bash
    docker compose up --pull always -d --wait
    ```

4. **Accéder à l'API**
    - Ouvrir [https://localhost](https://localhost) dans votre navigateur
    - Accepter le certificat TLS auto-généré

## 🛠️ Commandes utiles

### Arrêter l'application

```bash
docker compose down --remove-orphans
```

### Voir les logs

```bash
docker compose logs -f
```

### Accéder au conteneur PHP

```bash
docker compose exec php bash

```

### Générer une clé JWT

```bash
# Supprimer les anciennes clés
rm -f config/jwt/private.pem config/jwt/public.pem

# Créer le dossier s'il n'existe pas
mkdir -p config/jwt

# Générer la clé privée AVEC votre passphrase
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:b286dc36f881383de0cc2e5b19bec891ef6c25be370537fe6474685203952659

# Générer la clé publique
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:b286dc36f881383de0cc2e5b19bec891ef6c25be370537fe6474685203952659

# Permissions
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem
```

## 📚 Documentation

Pour plus de détails sur l'utilisation de l'API et les commandes avancées, consultez le [README-dev.md](README-dev.md).

## 🎮 Utilisation

L'API propose des endpoints pour :

-   Authentification des utilisateurs
-   Gestion des personnages Attack on Titan
-   Système de quiz quotidien

Voir [README-dev.md](README-dev.md) pour la documentation complète des endpoints.
