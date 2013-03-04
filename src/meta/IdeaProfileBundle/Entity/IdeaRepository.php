<?php

namespace meta\IdeaProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaRepository
 *
 */
class IdeaRepository extends EntityRepository
{

  public function countIdeas($archived = false)
  {

    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(i)')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i.deleted_at IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

  }

  public function findRecentlyCreatedIdeas($page, $limit, $archived = false)
  {
    
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i.deleted_at IS NULL')
            ->orderBy('i.created_at', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

  }

  public function findRecentlyUpdatedIdeas($page, $limit, $archived = false)
  {
    
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL') 
            ->andWhere('i.deleted_at IS NULL')
            ->orderBy('i.updated_at', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
            
  }

  public function findTopIdeasForUser($userId, $max = 3, $archived = false)
  {
    
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('i, MAX(l.created_at) as last_update')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->join('i.logEntries', 'l')
            ->join('i.creators', 'u')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('u.id = :userId')
            ->andWhere('i.deleted_at IS NULL')
            ->setParameter('userId', $userId)
            ->groupBy('i.id')
            ->orderBy('last_update', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

  public function computeWeekActivityForIdeas($ideas, $archived = false)
  {
 
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l AS log')
            ->addSelect('i.id as id')
            ->addSelect('COUNT(DISTINCT l.id) AS nb_actions')
            ->addSelect('COUNT(DISTINCT c.id) AS nb_comments')
            ->addSelect('SUBSTRING(l.created_at,1,10) AS date')
            ->addSelect('MAX(l.created_at) AS last_activity')

            ->from('metaGeneralBundle:Log\IdeaLogEntry', 'l')
            ->leftJoin('l.idea', 'i')
            
            ->leftJoin('i.logEntries', 'l2', 'WITH', 'SUBSTRING(l2.created_at,1,10) = SUBSTRING(l.created_at,1,10) AND l2.created_at > l.created_at')
            ->leftJoin('i.comments', 'c', 'WITH', 'SUBSTRING(c.created_at,1,10) = SUBSTRING(l.created_at,1,10)')

            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i IN (:iids)')
            ->setParameter('iids', $ideas)
            ->andWhere("l.created_at > DATE_SUB(CURRENT_DATE(),7,'DAY')")
            ->andWhere('i.deleted_at IS NULL')

            ->groupBy('i.id, date')
            ->orderBy('i.updated_at', 'DESC')
            ->addOrderBy('date','DESC')
            ->getQuery()
            ->getResult();

  }
}
