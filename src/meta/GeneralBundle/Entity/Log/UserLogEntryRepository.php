<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * UserLogEntryRepository
 *
 */
class UserLogEntryRepository extends EntityRepository
{

  public function findLogsForUser($user, $from) // Effectively, gets all logs related to new followers of $user
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.other_user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

  public function findSocialLogsForUsers($users, $from)
  {

    // Types of logs we want to see from users we follow :
    $types = array('user_update_profile', 'user_create_project', 'user_create_project_from_idea', 'user_create_idea');

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('l')
            ->from('metaGeneralBundle:Log\BaseLogEntry', 'l')
            ->where('l.user IN (:users)')
            ->setParameter('users', $users)
            ->andWhere('l.type IN (:types)')
            ->setParameter('types', $types)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

}
