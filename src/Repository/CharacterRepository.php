<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }
    

    /**
     * Get random characters
     *
     * @param int $limit Number of random characters to return
     * @return Character[] Returns an array of random Character objects
     */
    public function findRandom(int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // PostgreSQL specific random selection
        $sql = 'SELECT c.id FROM character c ORDER BY RANDOM() LIMIT :limit';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        
        $ids = array_column($result->fetchAllAssociative(), 'id');
        
        if (empty($ids)) {
            return [];
        }
        
        return $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find paginated characters with minimal loading
     *
     * @param int $page Page number (starts from 1)
     * @param int $limit Number of items per page
     * @return Character[] Returns an array of Character objects
     */
    public function findPaginated(int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Count total number of characters
     * 
     * @return int Total number of characters
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
