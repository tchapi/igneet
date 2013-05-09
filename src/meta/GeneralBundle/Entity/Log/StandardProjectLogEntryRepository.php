<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectLogEntryRepository
 *
 */
class StandardProjectLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($projects, $from, $exceptedUser)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->from('metaGeneralBundle:Log\StandardProjectLogEntry', 'l')
            ->where('l.standardProject IN (:projects)')
            ->setParameter('projects', $projects)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from);

    if ($exceptedUser){
      $query->andWhere('l.user != :user')
            ->setParameter('user', $exceptedUser);
    }
    
    return $query->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForProjects($projects, $from, $exceptedUser = null)
  {

    return $this->getLogsQuery($projects, $from, $exceptedUser)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForProjects($projects, $from, $exceptedUser = null)
  {

    return $this->getLogsQuery($projects, $from, $exceptedUser)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }

}
