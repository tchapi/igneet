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
            ->andWhere('u = :user OR u2 = :user')
            ->setParameter('user', $user);
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
            ->andWhere('u = :user OR u2 = :user')
            ->setParameter('user', $user);
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
   * Fetch all ideas in a given community, created by $creator
   */
  public function findAllIdeasInCommunityCreatedBy($community, $creator)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->join('i.creators', 'u')
            ->where('i.archived_at IS NULL')
            ->andWhere('i.deleted_at IS NULL')
            ->andWhere('u = :user')
            ->setParameter('user', $creator);

    if ($community === null){
      // We do not have to worry about accessing another user profile
      // when community is null (only our own profile is available in private space)
      $query->andWhere('i.community IS NULL');
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }

    return $query
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch all ideas in a given community, where $participant participates in
   */
  public function findAllIdeasInCommunityParticipatedInBy($community, $participant)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->join('i.participants', 'u')
            ->where('i.archived_at IS NULL')
            ->andWhere('i.deleted_at IS NULL')
            ->andWhere('u = :user')
            ->setParameter('user', $participant);

    if ($community === null){
      // We do not have to worry about accessing another user profile
      // when community is null (only our own profile is available in private space)
      $query->andWhere('i.community IS NULL');
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }

    return $query
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch all ideas watched by the user in the given community
   */
  public function findAllIdeasWatchedInCommunityForUser($community, $user)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('i')
            ->from('metaIdeaProfileBundle:Idea', 'i')
            ->join('i.watchers', 'u')
            ->where('i.archived_at IS NULL')
            ->andWhere('i.deleted_at IS NULL')
            ->andWhere('u = :user')
            ->setParameter('user', $user);

    if ($community === null){
      $query->andWhere('i.community IS NULL')
            ->join('i.creators', 'c') // In the private space, it needs to be the user's own idea
            ->andWhere('c = :user')
            ->setParameter('user', $user);
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }
//var_dump($query->getQuery()->getSql()); die;
    return $query
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
            ->andWhere('u = :user OR u2 = :user')
            ->setParameter('user', $user);
    } else {
      $query->andWhere('i.community = :community')
            ->setParameter('community', $community);
    }
       
    $query = $query->andWhere('i.id = :id')
                   ->setParameter('id', $id)
                   ->getQuery();

    try {
        $result = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $result = null;
    }

    return $result;

  }

  /*
   * Fetch the top N idea for a user in a given community
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
