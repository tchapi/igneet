<?php

namespace meta\GeneralBundle\Entity\Comment;

use Doctrine\ORM\EntityRepository;

/**
 * BaseCommentRepository
 *
 */
class BaseCommentRepository extends EntityRepository
{

  public function computeWeekCommentActivityForUser($user)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(c.id) AS nb_comments')
            ->addSelect('SUBSTRING(c.created_at,1,10) AS date')
            ->from('metaGeneralBundle:Comment\BaseComment', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->andWhere("c.deleted_at IS NULL")
            ->andWhere("c.created_at > DATE_SUB(CURRENT_DATE(),7,'DAY')")
            ->groupBy('date')
            ->getQuery()
            ->getResult();

  }

}
