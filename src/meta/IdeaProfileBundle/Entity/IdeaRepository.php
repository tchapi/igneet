<?php

namespace meta\IdeaProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaRepository
 *
 */
class IdeaRepository extends EntityRepository
{

  /*
   * Count ideas in the given community, accessible to the user (for the private space)
   */
  public function countIdeasInCommunityForUser($community, $user, $archived = false)
  {

    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('COUNT(i)')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i.deleted_at IS NULL');

    if ($community === null){
      $query->join('i.creators', 'u')
            ->leftJoin('i.participants', 'u2')
            ->andWhere('i.community IS NULL')
            ->andWhere('u.id = :id OR u2.id = :id')
            ->setParameter('id', $user->getId());
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }

    return $query->getQuery()
                 ->getSingleScalarResult();

  }

 /*
  * Fetch ideas in the given communities, accessible to the user (for the private space)
  */
  public function findIdeasInCommunityForUser($community, $user, $page, $maxPerPage, $sort, $archived = false)
  {
    
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i.deleted_at IS NULL');

    if ($community === null){
      $query->join('i.creators', 'u')
            ->leftJoin('i.participants', 'u2')
            ->andWhere('i.community IS NULL')
            ->andWhere('u.id = :id OR u2.id = :id')
            ->setParameter('id', $user->getId());
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }

    switch ($sort) {
      case 'newest':
        $query->orderBy('i.created_at', 'DESC');
        break;
      case 'alpha':
        $query->orderBy('i.name', 'ASC');
        break;
      case 'update':
      default:
        $query->orderBy('i.updated_at', 'DESC');
        break;
    }

    return $query
            ->setFirstResult(($page-1)*$maxPerPage)
            ->setMaxResults($maxPerPage)
            ->getQuery()
            ->getResult();
  }

  /*
   * Find a user by id in a given community
   */
  public function findOneByIdInCommunityForUser($id, $community, $user, $archived = false)
  {

    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('i.deleted_at IS NULL');

    if ($community === null){
      $query->join('i.creators', 'u')
            ->leftJoin('i.participants', 'u2')
            ->andWhere('i.community IS NULL')
            ->andWhere('u.id = :id OR u2.id = :id')
            ->setParameter('id', $user->getId());
    } else {
      $query->andWhere('i.community = :community')
       
    return $query->getQuery()
                 ->getResult();

  }

  /*
   * Find the top N idea for a user in a given community
   */
  public function findTopIdeasInCommunityForUser($community, $userId, $max = 3, $archived = false)
  {
    
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('i, MAX(l.created_at) as last_update')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->join('i.logEntries', 'l')
            ->join('i.creators', 'u')
            ->where('i.archived_at IS ' . $modifier . 'NULL')
            ->andWhere('u.id = :userId')
            ->andWhere('i.deleted_at IS NULL')
            ->setParameter('userId', $userId);

    if ($community === null){
      $query->andWhere('i.community IS NULL');
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }
    
    return $query->groupBy('i.id')
            ->orderBy('last_update', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

  /*
   * Compute log activity for an idea over a week (7 rolling days)
   */
  public function computeWeekActivityForIdeas($ideas, $archived = false)
  {
 
    $modifier = $archived?'NOT ':'';
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l AS log')
            ->addSelect('i.id as id')
            ->addSelect('COUNT(DISTINCT l.id) - COUNT(DISTINCT c.id) AS nb_actions')
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
