#!/bin/sh
set -e

# Désactiver l'usage de setfacl pour les permissions
export SYMFONY_SKIP_ACL_SET_PERMISSIONS=1

# S'assurer que var/cache et var/log ont les bonnes permissions
mkdir -p var/cache var/log
chmod -R 777 var/cache var/log

# Exécuter la commande originale
exec "$@"
