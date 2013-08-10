<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * UserLogEntryRepository
 *
 */
class UserLogEntryRepository extends EntityRepository
{

  public function findLastSocialActivityForUser($user, $max = 3)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->orderBy('l.created_at', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

  private function getLogsQuery($from, $user, $community = null)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.other_user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC');

    if (!is_null($community)) {
      $query->andWhere('l.community = :community')
            ->setParameter('community', $community);
    }

    return $query;

  }

  public function findLogsForUser($from, $user, $community = null) // Effectively, gets all logs related to new followers of $user
  {

    return $this->getLogsQuery($user, $from, $community)->select('l')
                                                        ->getQuery()
                                                        ->getResult();

  }

  public function countLogsForUser($from, $user, $community = null) // Effectively, conts all logs related to new followers of $user
  {

    return $this->getLogsQuery($user, $from, $community)->select('COUNT(l)')
                                                        ->getQuery()
                                                        ->getSingleScalarResult();

  }

}
