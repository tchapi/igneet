<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use meta\AdminBundle\Stats\Stats;

class DefaultController extends Controller
{
    public function homeAction($start,$end)
    {

        if (is_null($start) || is_null($end)){

          $end = date('Y-m-d'); // now
          $start = date('Y-m-d', strtotime("now - 7 days"));

        }

        $stats = $this->get("stats")->getCombinedStats($start,$end);

        return $this->render('metaAdminBundle:Default:home.html.twig', array("stats" => $stats[0], "start_date" => $start, "end_date" => $end));
    }
}
