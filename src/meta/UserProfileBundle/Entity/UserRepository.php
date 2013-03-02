<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository
{

  public function countUsers()
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(u)')
            ->from('metaUserProfileBundle:User', 'u')
            ->getQuery()
            ->getSingleScalarResult();

  }

  public function findRecentlyCreatedUsers($page, $limit)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->orderBy('u.created_at', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

  }

  public function findRecentlyUpdatedUsers($page, $limit)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->orderBy('u.updated_at', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
            
  }

  public function findAllUsersExceptMe($userId)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->where('u.id <> :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

  }
}
