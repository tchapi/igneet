<?php

namespace meta\AdminBundle\Stats;

class Stats
{

  private $em;

  public function __construct($entityManager)
  {
      $this->em = $entityManager;
  }

  private function run($sql)
  {

    $db = $this->em->getConnection(); // In a Controller
    
    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();  
  
  }

  public function getCombinedStats($start,$end)
  {

    $sql = "SELECT 
              SUM(IF(u.created_at > '$start' AND u.created_at <= '$end' ,1,0)) AS nb_created,
              SUM(IF(u.deleted_at > '$start'  AND u.deleted_at <= '$end' ,1,0)) AS nb_deleted,
              SUM(IF(u.created_at < '$end' AND u.deleted_at IS NULL,1,0)) AS total_users,
              SUM(IF(u.created_at < '$end' AND u.deleted_at IS NULL AND u.last_seen_at >= '$start' ,1,0)) AS active_users,
              SUM(IF(u.created_at < '$end' AND u.deleted_at IS NULL AND u.last_notified_at >= '$start' ,1,0)) AS notified_users
            FROM User u";

    return $this->run($sql);

  }

  public function getNewUsers($start, $end)
  {

    $qb = $this->em->createQueryBuilder();
    $query = $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->where('u.created_at >= :start')
            ->setParameter('start', $start)
            ->andWhere('u.created_at <= :end')
            ->setParameter('end', $end)
            ->andWhere('u.deleted_at IS NULL')
            ->getQuery();

    return $query->getResult();
  }

}