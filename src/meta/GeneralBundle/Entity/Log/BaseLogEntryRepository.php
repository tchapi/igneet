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

  public function computeWeekActivityForUser($userId)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(l.id) AS nb_actions')
            ->addSelect('SUBSTRING(l.created_at,1,10) AS date')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user = :uid')
            ->setParameter('uid', $userId)
            ->andWhere("l.created_at > DATE_SUB(CURRENT_DATE(),7,'DAY')")
            ->groupBy('date')
            ->getQuery()
            ->getResult();

  }

  public function findLastActivityDateForUser($userId)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->select('MAX(l.created_at) AS date')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user = :uid')
            ->setParameter('uid', $userId)
            ->getQuery();

    try {
        $result = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $result = null;
    }

    return $result;
  }

  public function findLastSocialActivityForUser($userId, $max = 3)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.user = :uid')
            ->setParameter('uid', $userId)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->orderBy('l.created_at', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

}
