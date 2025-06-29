<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de gestion du cache pour l'application.
 */
class CacheManager
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * Constructeur du CacheManager.
     *
     * @param CacheItemPoolInterface $characterCache Cache dédié aux personnages
     */
    public function __construct(CacheItemPoolInterface $characterCache)
    {
        $this->cache = $characterCache;
    }

    /**
     * Récupère un élément du cache ou l'y stocke s'il n'existe pas.
     *
     * @param string   $key      Clé de cache
     * @param callable $callback Fonction à exécuter si la valeur n'est pas en cache
     * @param int|null $ttl      Durée de vie en secondes (null = valeur par défaut)
     * 
     * @return mixed La valeur récupérée du cache ou générée par le callback
     */
    public function get(string $key, callable $callback, ?int $ttl = null)
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            if ($ttl !== null) {
                $item->expiresAfter($ttl);
            }

            return $callback();
        });
    }

    /**
     * Supprime un élément du cache.
     *
     * @param string $key Clé de cache à supprimer
     * 
     * @return bool True si l'élément a été supprimé, false sinon
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Vide tout le cache.
     *
     * @return bool True si le cache a été vidé, false sinon
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Génère une clé de cache pour un personnage.
     *
     * @param string $action Action (search, daily, etc.)
     * @param mixed  $params Paramètres supplémentaires
     * 
     * @return string La clé de cache
     */
    public function generateCharacterCacheKey(string $action, $params = null): string
    {
        $key = 'character_' . $action;
        
        if ($params !== null) {
            if (is_string($params) || is_numeric($params)) {
                $key .= '_' . $params;
            } elseif (is_array($params)) {
                $key .= '_' . md5(json_encode($params));
            }
        }
        
        return $key;
    }

    /**
     * Invalide tous les caches liés aux personnages.
     * Utile quand un nouveau personnage est ajouté ou modifié.
     *
     * @return bool True si l'invalidation a réussi, false sinon
     */
    public function invalidateCharacterCaches(): bool
    {
        // Pour invalider sélectivement, on peut soit :
        // 1. Vider tout le cache (solution simple mais brutale)
        // 2. Supprimer des clés spécifiques si on les connaît
        
        // Solution 1 : Vider tout le cache des personnages
        return $this->clear();
        
        // Solution 2 (alternative plus fine) : 
        // Supprimer des clés spécifiques si nécessaire
        // $keysToDelete = [
        //     $this->generateCharacterCacheKey('daily', date('Y-m-d')),
        //     $this->generateCharacterCacheKey('random', date('Y-m-d-H')),
        //     // Ajouter d'autres clés si nécessaire
        // ];
        // 
        // $success = true;
        // foreach ($keysToDelete as $key) {
        //     $success &= $this->delete($key);
        // }
        // return $success;
    }
}
