<?php

namespace meta\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SkillRepository
 *
 */
class SkillRepository extends EntityRepository
{

  /*
   * Fetch all skills which slugs are in the given array
   */
  public function findSkillsByArrayOfSlugs($arrayOfSlugs)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('s')
            ->from('metaUserBundle:Skill', 's')
            ->andWhere('s.slug IN (:slugs)')
            ->setParameter('slugs', $arrayOfSlugs)
            ->getQuery()
            ->getResult();

  }

}
