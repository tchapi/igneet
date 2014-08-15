<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * CommunityLogEntryRepository
 *
 */
class CommunityLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($communities, $from, $exceptedUser)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->from('metaGeneralBundle:Log\CommunityLogEntry', 'l')
            ->where('l.community IN (:communities)')
            ->setParameter('communities', $communities);

    if ($from != null) {
      $query->andWhere('l.created_at > :from')
            ->setParameter('from', $from);
    }

    if ($exceptedUser){
      $query->andWhere('l.user <> :user')
            ->setParameter('user', $exceptedUser);
    }
    return $query->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForCommunities($communities, $from, $exceptedUser)
  {

    return $this->getLogsQuery($communities, $from, $exceptedUser)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForCommunities($communities, $from, $exceptedUser)
  {

    return $this->getLogsQuery($communities, $from, $exceptedUser)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }
}
