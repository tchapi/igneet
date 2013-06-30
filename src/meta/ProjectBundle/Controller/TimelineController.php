<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class TimelineController extends BaseController
{

    /*
     * Display the timeline tab of a project
     */
    public function showTimelineAction($uid, $page)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['timeline']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        return $this->render('metaProjectBundle:Timeline:showTimeline.html.twig', 
            array('base' => $this->base));
    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output the timeline history
     */
    public function historyAction($uid, $page)
    {

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['timeline']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $format = $this->get('translator')->trans('date.timeline');
        $this->timeframe = array( 'today' => array( 'name' => $this->get('translator')->trans('date.today'), 'data' => array()),
                            'd-1'   => array( 'name' => date($format, strtotime("-1 day")), 'data' => array() ),
                            'd-2'   => array( 'name' => date($format, strtotime("-2 day")), 'data' => array() ),
                            'd-3'   => array( 'name' => date($format, strtotime("-3 day")), 'data' => array() ),
                            'd-4'   => array( 'name' => date($format, strtotime("-4 day")), 'data' => array() ),
                            'd-5'   => array( 'name' => date($format, strtotime("-5 day")), 'data' => array() ),
                            'd-6'   => array( 'name' => date($format, strtotime("-6 day")), 'data' => array() ),
                            'before'=> array( 'name' => $this->get('translator')->trans('date.past.week'), 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
        $entries = $repository->findByStandardProject($this->base['standardProject']);

        $history = array();

        // Logs
        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {
          
          if ($log_types[$entry->getType()]['displayable'] === false ) continue; // We do not display them

          $text = $logService->getHTML($entry);
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => $log_types[$entry->getType()]['filter_groups']);
        
        }

        // Comments
        foreach ($this->base['standardProject']->getComments() as $comment) {

          $text = $logService->getHTML($comment);
          $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => array('comments') );

        }

        // Sort !
        function build_sorter($key) {
            return function ($a, $b) use ($key) {
                return $a[$key]>$b[$key];
            };
        }
        usort($history, build_sorter('createdAt'));
        
        // Now put the entries in the correct timeframes
        $startOfToday = date_create('midnight');
        $before = date_create('midnight 6 days ago');
        $filter_groups = array();

        foreach ($history as $historyEntry) {
          
          if ( $historyEntry['createdAt'] > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          } else if ( $historyEntry['createdAt'] < $before ) {

            // Before
            array_unshift($this->timeframe['before']['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          } else {

            // Last seven days, by day
            $days = date_diff($historyEntry['createdAt'], $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          }
          
          $filter_groups = array_merge_recursive($filter_groups,$historyEntry['groups']);

        }

        return $this->render('metaGeneralBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
