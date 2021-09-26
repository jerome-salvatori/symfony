<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    // /**
    //  * @return Article[] Returns an array of Article objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    
    /**
    * @return Article[] Returns an array of Article objects
    */
    public function findLastTen() {
        return $this->createQueryBuilder('a')
            ->orderBy('a.datePublication', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    
    /**
    * @return Article[] Returns an array of Article objects
    */
    public function lastTenNoRepeat($id) {
        $manager = $this->getEntityManager();
        
        $query = $manager->createQuery(
            'SELECT a
            FROM App\Entity\Article a
            WHERE a.id <> :id
            ORDER BY a.datePublication DESC
            '
        )->setMaxResults(10)
        ->setParameter('id', $id);
        
        return $query->getResult();
    }
    
    /**
    * @return Article[] Returns an array of Article objects
    */
    public function fetchPage(int $page) {
        $manager = $this->getEntityManager();
        $first_result = $page * 10 - 10;
        
        $query = $manager->createQuery(
            'SELECT a
            FROM App\Entity\Article a
            '
        )->setMaxResults(10)
        ->setFirstResult($first_result);
            
        return $query->getResult();
    }
    
    public function countArticles() {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM article";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_NUM);
    }
}
