<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectRepository
 *
 */
class StandardProjectRepository extends EntityRepository
{

  private function getGuestCriteria($community, $user)
  {

    $guestCriteria = '';

    $qb = $this->getEntityManager()->createQueryBuilder();
    $guest = $qb->select('uc')
                    ->from('metaUserBundle:UserCommunity', 'uc')
                    ->where('uc.user = :user')
                    ->setParameter('user', $user)
                    ->andWhere('uc.community = :community')
                    ->setParameter('community', $community)
                    ->getQuery()
                    ->getSingleResult(); // We will always have a single line

    if ($guest->getGuest() === false) {
      $guestCriteria = 'sp.private = 0 OR ';
    }

    return $guestCriteria;

  }

  private function getQuery($community, $user)
  {

    $guestCriteria = $this->getGuestCriteria($community, $user);

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $user);

    if ($community === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $community);
    }

    return $query;

  }

  /* 
   * Count projects in community for user (taking in account guest, privacy and community)
   */
  public function countProjectsInCommunityForUser($community, $user)
  {
    
    $guestCriteria = $this->getGuestCriteria($community, $user);

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('COUNT(DISTINCT sp)')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $user);

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

    $query = $this->getQuery($community, $user);

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

    $query = $this->getQuery($community, $user);

    $query->join('sp.owners', 'u3')
          ->andWhere('u3 = :owner')
          ->setParameter('owner', $owner);

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

    $query = $this->getQuery($community, $user);

    $query->join('sp.participants', 'u3')
          ->andWhere('u3 = :participant')
          ->setParameter('participant', $participant);

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

    $query = $this->getQuery($community, $user);

    $query->join('sp.watchers', 'u3')
          ->andWhere('u3 = :user')
          ->setParameter('user', $user);

    if ($community === null){
      $query->join('sp.owners', 'c') // In the private space, it needs to be the user's own project
            ->andWhere('c = :user')
            ->setParameter('user', $user);;
    }

    return $query
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch top N projects for the user in the given community
   */
  public function findTopProjectsInCommunityForUser($community, $user, $max = 3)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp, MAX(l.created_at) AS last_update')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.logEntries', 'l')
            ->join('sp.owners', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
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
   * Fetch last N projects for the user in the given community
   */
  public function findLastProjectsInCommunityForUser($community, $user, $max = 3)
  {
 
    $query = $this->getQuery($community, $user);

    return $query->groupBy('sp.id')
            ->orderBy('sp.created_at', 'DESC')
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
