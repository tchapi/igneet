<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository
{

  public function findRecentlyCreatedUsers($limit)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->orderBy('u.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

  }

  public function findRecentlyUpdatedUsers($limit)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->orderBy('u.updated_at', 'DESC')
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
