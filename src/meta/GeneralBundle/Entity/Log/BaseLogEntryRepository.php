<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * BaseLogEntryRepository
 *
 */
class BaseLogEntryRepository extends EntityRepository
{

  public function findSimilarEntries($model, $user, $logActionName, $subjectType, $subject, $date)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l')
            ->from($model, 'l')
            ->where('l.user = :uid')
            ->setParameter('uid', $user)
            ->andWhere("l.created_at > :date")
            ->setParameter('date', $date)
            ->andWhere('l.type = :type')
            ->setParameter('type', $logActionName)
            ->andWhere('l.'.$subjectType.' = :subject')
            ->setParameter('subject', $subject)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();
  
  }

  public function findLastActivityDateForUser($user)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->select('MAX(l.created_at) AS date')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery();

    try {
        $result = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $result = null;
    }

    return $result;
  }

  private function getSocialLogsQuery($logTypes, $users, $from, $user, $community)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->leftJoin('l.community', 'c')
            ->leftJoin('c.userCommunities', 'uc')
            ->where('l.user IN (:users)')
            ->setParameter('users', $users)
            ->andWhere('l.type IN (:types)')
            ->setParameter('types', $logTypes)
            ->andWhere('uc.user = :user')
            ->setParameter('user', $user)
            ->andWhere('uc.guest = :guest')
            ->setParameter('guest', false);

    if ($from != null) {
      $query->andWhere('l.created_at > :from')
            ->setParameter('from', $from);
    }

    if (!is_null($community)) {
      $query->andWhere('l.community = :community')
            ->setParameter('community', $community);
    }

    $query->orderBy('l.created_at', 'DESC');

    return $query;

  }


  public function findSocialLogsForUsersInCommunitiesOfUser($logTypes, $users, $from, $user, $community = null)
  {

    $query = $this->getSocialLogsQuery($logTypes, $users, $from, $user, $community);

    if ($query === null) {
      return null;
    } else {
      return $query->select('l')
                   ->getQuery()
                   ->getResult();
    }

  }

  public function countSocialLogsForUsersInCommunitiesOfUser($logTypes, $users, $from, $user, $community = null)
  {

    $query = $this->getSocialLogsQuery($logTypes, $users, $from, $user, $community);

    if ($query === null) {
      return 0;
    } else {
      return $query->select('COUNT(l)')
                   ->getQuery()
                   ->getSingleScalarResult();
    }

  }

  public function findByLogTypes($logTypes, $options)
  {

      if (is_null($logTypes) || count($logTypes) === 0){
        return $this->findByCommunity($options['community']);
      }

      $qb = $this->getEntityManager()->createQueryBuilder();

      $query = $qb->select('l')
                  ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
                  ->where('l.community = :community')
                  ->setParameter('community', $options['community'])
                  ->andWhere('l.type IN (:types)')
                  ->setParameter('types', $logTypes)
                  ->orderBy('l.created_at', 'DESC');
                  
      return $query->getQuery()
                   ->getResult();
  }

}
