<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectRepository
 */
class StandardProjectRepository extends EntityRepository
{

  /*
   * Helper function to know if a $user is guest in a $community
    * Options :
    * - 'community'
    * - 'user'
   */
  private function getGuestCriteria($options)
  {

    $guestCriteria = '';

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('uc')
                    ->from('metaUserBundle:UserCommunity', 'uc')
                    ->where('uc.user = :user')
                    ->setParameter('user', $options['user'])
                    ->andWhere('uc.community = :community')
                    ->setParameter('community', $options['community'])
                    ->getQuery(); 

    try {

        $guest = $query->getSingleResult();

        if (!$guest->isGuest()) {
          $guestCriteria = '(sp.private = 0 OR sp.private IS NULL) OR ';
        }

    } catch (\Doctrine\Orm\NoResultException $e) {

        // No community

    }

    return $guestCriteria;

  }

  /*
   * Get the standard query for all functions of the repo
    * Options :
    * - 'community'
    * - 'user'
    * - 'statuses'
   */
  private function getQuery($options)
  {

    $guestCriteria = $this->getGuestCriteria($options);

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $options['user']);

    if ( isset($options['statuses']) && !is_null($options['statuses']) ) { // We have to filter status
      $query->andWhere('sp.status IN (:statuses)')
            ->setParameter('statuses', $options['statuses']);
    }

    if ($options['community'] === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $options['community']);
    }

    return $query;

  }

  /* 
   * Count projects in community for user (taking in account guest, privacy and community)
    * Options :
    * - 'community'
    * - 'user'
    * - 'statuses'
   */
  public function countProjectsInCommunityForUser($options)
  {
    
    $guestCriteria = $this->getGuestCriteria($options);

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('COUNT(DISTINCT sp)')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.owners', 'u')
            ->leftJoin('sp.participants', 'u2')
            ->where('sp.deleted_at IS NULL')
            ->andWhere( $guestCriteria .'u = :user OR u2 = :user')
            ->setParameter('user', $options['user']);

    if ( isset($options['statuses']) && !is_null($options['statuses']) ) { // We have to filter status
      $query->andWhere('sp.status IN (:statuses)')
            ->setParameter('statuses', $options['statuses']);
    }

    if ($options['community'] === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $options['community']);
    }

    return $query->getQuery()
                 ->getSingleScalarResult();

  }

  /* 
   * Fetch projects in community for user (taking in account guest, privacy and community)
    * Options :
    * - 'community'
    * - 'user'
    * - 'statuses'
    * v Pagination
    * - 'page'
    * - 'maxPerPage'
    * - 'sort'
   */
  public function findProjectsInCommunityForUser($options)
  {

    $query = $this->getQuery($options);

    switch ($options['sort']) {
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

    return $query->setFirstResult(($options['page']-1)*$options['maxPerPage'])
            ->setMaxResults($options['maxPerPage'])
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

    $query = $this->getQuery(array('community' => $community, 'user' => $user));

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

    $query = $this->getQuery(array('community' => $community, 'user' =>  $user));

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

    $query = $this->getQuery(array('community' => $community, 'user' =>  $user));

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
  public function findTopProjectsInCommunityForUser($options)
  {
    
    $max = $options['max']?$options['max']:3;

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('sp, MAX(l.created_at) AS last_update')
            ->from('metaProjectBundle:StandardProject', 'sp')
            ->join('sp.logEntries', 'l')
            ->join('sp.owners', 'u')
            ->where('u = :user')
            ->setParameter('user', $options['user'])
            ->andWhere('sp.deleted_at IS NULL');

    if ($options['community'] === null){
      $query->andWhere('sp.community IS NULL');
    } else {
      $query->andWhere('sp.community = :community')
            ->setParameter('community', $options['community']);
    }
    
    return $query->groupBy('sp.id')
            ->orderBy('last_update', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

   /*
   * Fetch last N projects for the user in the given community
   * Options :
   * - 'community'
   * - 'user'
   * - 'max' results
   */
  public function findLastProjectsInCommunityForUser($options)
  {

    $max = $options['max']?$options['max']:3;
 
    $query = $this->getQuery($options);

    return $query->groupBy('sp.id')
            ->orderBy('sp.created_at', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
  }

}
