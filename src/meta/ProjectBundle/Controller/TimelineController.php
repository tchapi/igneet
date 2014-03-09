<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TimelineController extends BaseController
{

    /*
     * Display the timeline tab of a project
     */
    public function showTimelineAction($uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['timeline']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        return $this->render('metaProjectBundle:Project:showTimeline.html.twig', 
            array('base' => $this->base));
    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output the timeline history
     */
    public function historyAction(Request $request, $uid)
    {

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['timeline']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $format = $this->get('translator')->trans('date.timeline');
        $this->timeframe = array( 'today' => array( 'current' =>  true, 'name' => $this->get('translator')->trans('date.today'), 'data' => array()),
                            'd-1'   => array( 'name' => $this->get('translator')->trans('date.yesterday'), 'data' => array() ),
                            'd-2'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 2)), 'data' => array() ),
                            'd-3'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 3)), 'data' => array() ),
                            'd-4'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 4)), 'data' => array() ),
                            'd-5'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 5)), 'data' => array() ),
                            'd-6'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 6)), 'data' => array() ),
                            'before'=> array( 'name' => $this->get('translator')->trans('date.past.week'), 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
        $entries = $repository->findByStandardProject($this->base['project']);

        $history = array();

        // Logs
        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {
          
          if ($log_types[$entry->getType()]['displayable'] === false ) continue; // We do not display them

          $text = $logService->getHTML($entry);
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt, 'text' => $text);
        
        }

        // Comments
        foreach ($this->base['project']->getComments() as $comment) {

          $text = $logService->getHTML($comment);
          $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt, 'text' => $text);

        }

        // Sort !
        if (!function_exists('meta\ProjectBundle\Controller\build_sorter')) {
          function build_sorter($key) {
              return function ($a, $b) use ($key) {
                  return $a[$key]>$b[$key];
              };
          }
        }
        usort($history, build_sorter('createdAt'));
        
        // Now put the entries in the correct timeframes
        $startOfToday = date_create('midnight');
        $before = date_create('midnight 6 days ago');
        $filter_groups = array();

        foreach ($history as $historyEntry) {
          
          if ( $historyEntry['createdAt'] > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], $historyEntry['text']);

          } else if ( $historyEntry['createdAt'] < $before ) {

            // Before
            array_unshift($this->timeframe['before']['data'], $historyEntry['text']);

          } else {

            // Last seven days, by day
            $days = date_diff($historyEntry['createdAt'], $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], $historyEntry['text']);

          }

        }

        return $this->render('metaGeneralBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
