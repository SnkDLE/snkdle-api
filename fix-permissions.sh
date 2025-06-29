#!/bin/sh

# Ce script configure les permissions correctement dans Docker
echo "Configuration des permissions pour le dossier var..."
chmod -R 777 var/cache var/log
echo "Permissions configurées avec succès!"
