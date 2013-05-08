<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CommonListItemRepository
 *
 */
class CommonListItemRepository extends EntityRepository
{

  public function findOneByIdInProjectAndList($id, $listId, $projectId)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('cli')
            ->from('metaProjectBundle:CommonListItem', 'cli')
            ->join('cli.commonList', 'cl')
            ->join('cl.project', 'sp')
            ->where('sp.id = :pid')
            ->setParameter('pid', $projectId)
            ->andWhere('cl.id = :listId')
            ->setParameter('listId', $listId)
            ->andWhere('cli.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery();

    try {
        $commonListItem = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $commonListItem = null;
    }

    return $commonListItem;

  }
}