<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectLogEntryRepository
 *
 */
class StandardProjectLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($projects, $from, $exceptedUser, $community)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->from('metaGeneralBundle:Log\StandardProjectLogEntry', 'l')
            ->where('l.standardProject IN (:projects)')
            ->setParameter('projects', $projects);

    if ($from != null){
      $query->andWhere('l.created_at > :from')
            ->setParameter('from', $from);
    }

    if ($exceptedUser){
      $query->andWhere('l.user != :user')
            ->setParameter('user', $exceptedUser);
    }
    
    if (!is_null($community)) {
      $query->andWhere('l.community = :community')
            ->setParameter('community', $community);
    }

    return $query->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForProjects($projects, $from, $exceptedUser, $community = null)
  {

    return $this->getLogsQuery($projects, $from, $exceptedUser, $community)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForProjects($projects, $from, $exceptedUser, $community = null)
  {

    return $this->getLogsQuery($projects, $from, $exceptedUser, $community)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }

}
