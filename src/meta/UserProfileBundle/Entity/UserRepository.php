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
            ->where('u.deleted_at IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

  }

  public function findUsers($page, $maxPerPage, $sort)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->where('u.deleted_at IS NULL');

    switch ($sort) {
      case 'update':
        $query->orderBy('u.updated_at', 'DESC');
        break;
      case 'alpha':
        $query->orderBy('u.first_name', 'ASC');
        break;
      case 'active':
        $query->orderBy('u.last_seen_at', 'DESC');
        break;
      case 'newest':
      default:
        $query->orderBy('u.created_at', 'DESC');
        break;
    }

    return $query
            ->setFirstResult(($page-1)*$maxPerPage)
            ->setMaxResults($maxPerPage)
            ->getQuery()
            ->getResult();

  }

  public function findAllUsersExceptMe($userId)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->where('u.deleted_at IS NULL')
            ->andWhere('u.id <> :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

  }
}
