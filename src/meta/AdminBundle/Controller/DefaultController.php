<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;

use meta\AdminBundle\Stats\Stats;

class DefaultController extends Controller
{
    public function homeAction($start,$end, Request $request)
    {

        // Redirects to the correct url - it's because I don't want the FOS JS Bundle in here to create routing in JS on the fly
        if ( $request->query->get('start') && $request->query->get('end') ){
          return $this->redirect($this->generateUrl('a_home', array('start' => $request->query->get('start'), 'end' => $request->query->get('end'))) );
        }

        if (is_null($start) || is_null($end)){

          $end = date('Y-m-d'); // now
          $start = date('Y-m-d', strtotime("now - 7 days"));

        }

        $stats = $this->get("stats")->getCombinedStats($start,$end);

        return $this->render('metaAdminBundle:Default:home.html.twig', array("stats" => $stats[0], "start_date" => $start, "end_date" => $end));
   
    }

    public function newUsersAction($start, $end, Request $request)
    {

        // Redirects to the correct url - it's because I don't want the FOS JS Bundle in here to create routing in JS on the fly
        if ( $request->query->get('start') && $request->query->get('end') ){
          return $this->redirect($this->generateUrl('a_new_users', array('start' => $request->query->get('start'), 'end' => $request->query->get('end'))) );
        }

        if (is_null($start) || is_null($end)){

          $end = date('Y-m-d'); // now
          $start = date('Y-m-d', strtotime("now - 7 days"));

        }

        $users = $this->get("stats")->getNewUsers($start,$end);

        return $this->render('metaAdminBundle:Default:newUsers.html.twig', array("users" => $users, "start_date" => $start, "end_date" => $end));

    }
}
