<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaLogEntryRepository
 *
 */
class IdeaLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($ideas, $from, $exceptedUser)
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
    
    return $query->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForIdeas($ideas, $from, $exceptedUser = null)
  {

    return $this->getLogsQuery($ideas, $from, $exceptedUser)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForIdeas($ideas, $from, $exceptedUser = null)
  {

    return $this->getLogsQuery($ideas, $from, $exceptedUser)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }
}
