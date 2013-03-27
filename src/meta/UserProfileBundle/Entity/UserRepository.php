<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository
{

  /*
   * Count all users in a given community
   */
  public function countUsersInCommunity($community)
  {
    
    if ($community === null){
      return 0;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(u)')
            ->from('metaUserProfileBundle:User', 'u')
            ->leftJoin('u.communities', 'c')
            ->leftJoin('u.restrictedCommunities', 'rc')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community OR rc = :community')
            ->setParameter('community', $community)
            ->getQuery()
            ->getSingleScalarResult();

  }

  /*
   * Fetch all users in a given community
   */
  public function findAllUsersInCommunity($community, $page, $maxPerPage, $sort)
  {
    
    if ($community === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->leftJoin('u.communities', 'c')
            ->leftJoin('u.restrictedCommunities', 'rc')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community OR rc = :community')
            ->setParameter('community', $community);

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

  /*
   * Fetch all users in a given community, except the user $user
   */
  public function findAllUsersInCommunityExceptMe($user, $community)
  {
    
    if ($community === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->leftJoin('u.communities', 'c')
            ->leftJoin('u.restrictedCommunities', 'rc')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community OR rc = :community')
            ->setParameter('community', $community)
            ->andWhere('u <> :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

  }

  /*
   * Find a user by its username in a given community
   */
  public function findOneByUsernameInCommunity($username, $community)
  {

    if ($community === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('u')
            ->from('metaUserProfileBundle:User', 'u')
            ->leftJoin('u.communities', 'c')
            ->leftJoin('u.restrictedCommunities', 'rc')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community OR rc = :community')
            ->setParameter('community', $community)
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery();

    try {
        $result = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $result = null;
    }

    return $result;
  }

}
