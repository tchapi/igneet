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

  public function computeWeekActivityForUser($user)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(l.id) AS nb_actions')
            ->addSelect('SUBSTRING(l.created_at,1,10) AS date')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->andWhere("l.created_at > DATE_SUB(CURRENT_DATE(),7,'DAY')")
            ->groupBy('date')
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

  public function findSocialLogsForUsersInCommunities($users, $allowedCommunities, $from)
  {

    // He has no community apart from private space => no social logs anyway
    if (count($allowedCommunities) === 0) {
      return null;
    }

    // Types of logs we want to see from users we follow :
    $types = array('user_update_profile', 'user_create_project', 'user_create_project_from_idea', 'user_create_idea');

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user IN (:users)')
            ->setParameter('users', $users)
            ->andWhere('l.type IN (:types)')
            ->setParameter('types', $types)
            ->andWhere('l.community IN (:allowedCommunities)')
            ->setParameter('allowedCommunities', $allowedCommunities)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

}
