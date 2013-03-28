<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectRepository
 *
 */
class StandardProjectRepository extends EntityRepository
{

  /* 
   * Count projects in community for user (taking in account guest, privacy and community)
   */
  public function countProjectsInCommunityForUser($community, $user)
  {
    
    $guestCriteria = $user->isGuestInCurrentCommunity()?'':'sp.private = 0 OR ';

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('COUNT(DISTINCT sp)')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u.id = :id OR u2.id = :id')
            ->setParameter('id', $user->getId());

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    return $query->getQuery()
                 ->getSingleScalarResult();

  }

  /* 
   * Fetch projects in community for user (taking in account guest, privacy and community)
   */
  public function findProjectsInCommunityForUser($community, $user, $page, $maxPerPage, $sort)
  {

    $guestCriteria = $user->isGuestInCurrentCommunity()?'':'sp.private = 0 OR ';

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u.id = :id OR u2.id = :id')
            ->setParameter('id', $user->getId());

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    switch ($sort) {
      case 'newest':
        $query->orderBy('sp.created_at', 'DESC');
        break;
      case 'alpha':
        $query->orderBy('sp.name', 'ASC');
        break;
      case 'update':
        $query->orderBy('sp.updated_at', 'DESC');
      default:
        break;
    }

    return $query->setFirstResult(($page-1)*$maxPerPage)
            ->setMaxResults($maxPerPage)
            ->groupBy('sp.id')
            ->getQuery()
            ->getResult();
  }

  /* 
   * Fetch projects in community for user (taking in account guest, privacy and community)
   * where owner is an owner of the projects
   */
  public function findAllProjectsInCommunityForUserOwnedBy($community, $user, $owner)
  {

    $guestCriteria = $user->isGuestInCurrentCommunity()?'':'sp.private = 0 OR ';

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->join('sp.owners', 'u3')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $user)
            ->andWhere('u3 = :owner')
            ->setParameter('owner', $owner);

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    return $query
            ->groupBy('sp.id')
            ->getQuery()
            ->getResult();
  }

  /* 
   * Fetch projects in community for user (taking in account guest, privacy and community)
   * where participant is a participant of the projects
   */
  public function findAllProjectsInCommunityForUserParticipatedInBy($community, $user, $participant)
  {

    $guestCriteria = $user->isGuestInCurrentCommunity()?'':'sp.private = 0 OR ';

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->join('sp.participants', 'u3')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $user)
            ->andWhere('u3 = :participant')
            ->setParameter('participant', $participant);

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    return $query
            ->groupBy('sp.id')
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch all projects watched by the user in the given community
   */
  public function findAllProjectsWatchedInCommunityForUser($community, $user)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.watchers', 'u')
            ->andWhere('sp.deleted_at IS NULL')
            ->andWhere('u = :user')
            ->setParameter('user', $user);

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    return $query
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch top N projects for the user in the given community
   */
  public function findTopProjectsInCommunityForUser($community, $userId, $max = 3)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('sp, MAX(l.created_at) AS last_update')
            ->from('metaStandardProjectProfileBundle:StandardProject', 'sp')
            ->join('sp.logEntries', 'l')
            ->join('sp.owners', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('sp.deleted_at IS NULL');

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }
    
    return $query->groupBy('sp.id')
            ->orderBy('last_update', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

  /*
   * Compute log activity for a project over a week (7 rolling days)
   */
  public function computeWeekActivityForProjects($projects)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l AS log')
            ->addSelect('sp.id as id')
            ->addSelect('COUNT(DISTINCT l.id) - COUNT(DISTINCT c.id) AS nb_actions')
            ->addSelect('COUNT(DISTINCT c.id) AS nb_comments')
            ->addSelect('SUBSTRING(l.created_at,1,10) AS date')
            ->addSelect('MAX(l.created_at) AS last_activity')

            ->from('metaGeneralBundle:Log\StandardProjectLogEntry', 'l')
            ->leftJoin('l.standardProject', 'sp')
            
            ->leftJoin('sp.logEntries', 'l2', 'WITH', 'SUBSTRING(l2.created_at,1,10) = SUBSTRING(l.created_at,1,10) AND l2.created_at > l.created_at')
            ->leftJoin('sp.comments', 'c', 'WITH', 'SUBSTRING(c.created_at,1,10) = SUBSTRING(l.created_at,1,10)')

            ->andWhere('sp IN (:pids)')
            ->setParameter('pids', $projects)
            ->andWhere("l.created_at > DATE_SUB(CURRENT_DATE(),7,'DAY')")
            
            ->andWhere('sp.deleted_at IS NULL')
            
            ->groupBy('sp.id, date')
            ->orderBy('sp.updated_at', 'DESC')
            ->addOrderBy('date','DESC')
            ->getQuery()
            ->getResult();

  }
}
