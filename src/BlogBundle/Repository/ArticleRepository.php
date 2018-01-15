<?php

namespace BlogBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ArticleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArticleRepository extends EntityRepository
{
    public function getPaginated($pageNr, $limit, $categoryId = null)
    {
        $firstResult = ($pageNr - 1) * $limit;
        $qb = $this->createQueryBuilder('a');
        if ($categoryId) {
            $qb = $qb->where('a.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }
        $qb->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param null $categoryId
     * @return int
     */
    public function countArticles($categoryId = null)
    {
        $qb= $this->createQueryBuilder('a')
            ->select('COUNT(a)');

        if ($categoryId) {
            $qb = $qb->where('a.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
