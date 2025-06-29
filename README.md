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

## 📚 Documentation

Pour plus de détails sur l'utilisation de l'API et les commandes avancées, consultez le [README-dev.md](README-dev.md).

## 🎮 Utilisation

L'API propose des endpoints pour :

-   Authentification des utilisateurs
-   Gestion des personnages Attack on Titan
-   Système de quiz quotidien

Voir [README-dev.md](README-dev.md) pour la documentation complète des endpoints.
