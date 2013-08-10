<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaLogEntryRepository
 *
 */
class IdeaLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($ideas, $from, $exceptedUser, $community)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->from('metaGeneralBundle:Log\IdeaLogEntry', 'l')
            ->where('l.idea IN (:ideas)')
            ->setParameter('ideas', $ideas)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from);

    if ($exceptedUser){
      $query->andWhere('l.user <> :user')
            ->setParameter('user', $exceptedUser);
    }
    
    if (!is_null($community)) {
      $query->andWhere('l.community = :community')
            ->setParameter('community', $community);
    }

    return $query->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForIdeas($ideas, $from, $exceptedUser, $community = null)
  {

    return $this->getLogsQuery($ideas, $from, $exceptedUser, $community)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForIdeas($ideas, $from, $exceptedUser, $community = null)
  {

    return $this->getLogsQuery($ideas, $from, $exceptedUser, $community)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }
}
