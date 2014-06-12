<?php

namespace meta\GeneralBundle\Services;

use Doctrine\ORM\EntityManager;

use meta\UserBundle\Entity\User,
    meta\GeneralBundle\Entity\Log\UserLogEntry,
    meta\GeneralBundle\Entity\Log\IdeaLogEntry,
    meta\GeneralBundle\Entity\Log\StandardProjectLogEntry,
    meta\GeneralBundle\Entity\Log\CommunityLogEntry,
    meta\GeneralBundle\Entity\Comment\BaseComment;

class LogService
{

    private $em;
    private $log_types, $log_social_filters, $log_community_filters, $log_routing, $concurrent_merge_interval;
    private $csrf_provider;
    private $twig, $template_link, $template_link_null, $template_item;

    public function __construct(EntityManager $entity_manager, $log_types, $log_filters, $log_routing, $log_concurrent_merge_interval, $csrf_provider, $security_context, $twig, $translator, $uid)
    {
        $this->em = $entity_manager;
        
        $this->log_types = $log_types;
        $this->log_social_filters = $log_filters['social'];
        $this->log_community_filters = $log_filters['community'];
        $this->log_routing = $log_routing;
        $this->concurrent_merge_interval = $log_concurrent_merge_interval;

        $this->csrf_provider = $csrf_provider;

        $this->security_context = $security_context;

        $this->twig = $twig;
        $this->translator = $translator;

        $this->uid = $uid;

        $this->template_link         = 'metaGeneralBundle:Log:logLink.html.twig';
        $this->template_link_null    = 'metaGeneralBundle:Log:logLink.null.html.twig';
        $this->template_item         = 'metaGeneralBundle:Log:logItem.html.twig';
        $this->template_item_comment = 'metaGeneralBundle:Log:logItemComment.html.twig';
    }

    public function log($user, $logActionName, $subject, array $objects)
    {

        // Find the type of LogEntry
        $type = $this->log_types[$logActionName]['type'];

        switch ($type) {
            case 'idea':
                 $entry = new IdeaLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\IdeaLogEntry';
                 $subjectType = "idea";
                 break;
            case 'project':
                 $entry = new StandardProjectLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\StandardProjectLogEntry';
                 $subjectType = "standardProject";
                 break;
            case 'community':
                 $entry = new CommunityLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\CommunityLogEntry';
                 $subjectType = "community";
                 break;
            case 'other_user':
                 $entry = new UserLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\UserLogEntry';
                 $subjectType = "other_user";
                 break;   
            default:
                 return;
        }
        
        $lastEntry = null;

        if ($this->log_types[$logActionName]['combinable'] === true){
            // Merge concurrent log updates
            // Check if there is a similar log less than X seconds ago
            $repository = $this->em->getRepository('metaGeneralBundle:Log\BaseLogEntry');
            $lastEntries = $repository->findSimilarEntries($repositoryName, $user, $logActionName, $subjectType, $subject, date_create('now -'.$this->concurrent_merge_interval.' seconds'));

            foreach ($lastEntries as $entry) {
                if ( $entry->getObjects() == $objects ) {
                    $lastEntry = $entry;
                    break;
                }
            }
        }

        if ( !is_null($lastEntry) ) {
            // if update < XX secondes , then update the date of the old one with the new date
            $lastEntry->setCreatedAt(new \Datetime('now'));
            $lastEntry->incrementCombinedCount();
        } else {
            // else persists the new log
            // $this->security_context->getToken() is NEVER null here
            $entry->setCommunity($this->security_context->getToken()->getUser()->getCurrentCommunity());
            $entry->setUser($user);
            $entry->setType($logActionName);
            $entry->setSubject($subject);
            $entry->setObjects($objects);

            $this->em->persist($entry);
        }

        // Flush the shit
        $this->em->flush();

    }

    public function getText($logEntryOrComment, $locale = null)
    {

        if ( is_null($logEntryOrComment) ) {
            return $this->twig->render($this->template_link_null);
        }

        if ($logEntryOrComment instanceof BaseComment) {

            return $logEntryOrComment->getText();
            
        } else {

            $parameters = $this->getParameters($logEntryOrComment);

            // We get the text for the log
            if (!is_null($locale)) {
                return $this->translator->trans( "logs." . $logEntryOrComment->getType(), $parameters, 'logs', $locale);
            } else {
                return $this->translator->trans( "logs." . $logEntryOrComment->getType(), $parameters, 'logs' );
            }

        }

    }

    public function getHTML($logEntryOrComment, $lastNotified, $locale = null)
    {

        if ( is_null($logEntryOrComment) ) {
            return $this->twig->render($this->template_link_null);
        }

        if ($logEntryOrComment instanceof BaseComment) {

            return $this->twig->render($this->template_item_comment, array('comment' => $logEntryOrComment, 'locale' => $locale, 'lastNotified' => $lastNotified));

        } else {

            $parameters = $this->getParameters($logEntryOrComment);

            // We get the formatted text for the log
            $text = $this->translator->trans( "logs." . $logEntryOrComment->getType(), $parameters, 'logs', $locale );

            $date = $logEntryOrComment->getCreatedAt();
            $user = $logEntryOrComment->getUser();
            $combinedCount = $logEntryOrComment->getCombinedCount();
            $icon = $this->log_types[$logEntryOrComment->getType()]['icon'];
            $groups = $this->log_types[$logEntryOrComment->getType()]['filter_groups'];

            return $this->twig->render($this->template_item, array( 'icon' => $icon, 'user' => $user, 'text' => $text, 'date' => $date, 'combinedCount' => $combinedCount, 'groups' => $groups, 'locale' => $locale, 'lastNotified' => $lastNotified));

        }

    }

    private function getParameters($logEntry)
    {

        $type = $this->log_types[$logEntry->getType()]['type'];
        $parameters = array();

        // Fetches parameters to display the link to the user that created the log
        $user_logName = $logEntry->getUser()->getLogName();

        // Constructs the uid from the id (in this case, we don't need to obfuscate)
        $user_uid = $logEntry->getUser()->getUsername();

        // Deleted users should not be linked to
        if ($logEntry->getUser()->isDeleted()) {
            $user_routing = null;
        } else {
            $user_routing = array( 'path' => $this->log_routing['user']['path'], 'args' => array( $this->log_routing['user']['key'] => $user_uid ) );
        } 
       
        $parameters["%user%"] = $this->twig->render($this->template_link, array( 'logName' => $user_logName, 'routing' => $user_routing ) );


        // Fetches parameters to display the subject link
        $subject_logName = $logEntry->getSubject()->getLogName();

        // Constructs the uid from the id
        $subject_uid = $logEntry->getSubject()->getId();
        if ($this->log_routing["$type"]['is_uid']){
            $subject_uid = $this->uid->toUId($subject_uid);
        }

        // Do we need a token ?
        $token = null;
        if (isset($this->log_routing["$type"]['token'])){
            $token = $this->csrf_provider->generateCsrfToken($this->log_routing["$type"]['token']);
        }

        // Creates the routing
        $subject_routing = array( 'path' => $this->log_routing["$type"]['path'], 'args' => array( $this->log_routing["$type"]['key'] => $subject_uid, 'token' => $token) );

        $parameters["%$type%"] = $this->twig->render($this->template_link, array( 'logName' => $subject_logName, 'routing' => $subject_routing ) );


        // Now fetches objects
        foreach ($logEntry->getObjects() as $key => $object) {

            // Can we link to the object ? (backward compatibility is ensured here)
            if ( !isset($object['identifier']) || is_null($object['identifier']) ) {
                $routing = null;
            } else {

                // Constructs the uid from the id
                $object_uid = $object['identifier'];
                if ($this->log_routing["$key"]['is_uid']){
                    $object_uid = $this->uid->toUId($object_uid);
                }

                // Do we need a token ?
                $token = null;
                if (isset($this->log_routing["$key"]['token'])){
                    $token = $this->csrf_provider->generateCsrfToken($this->log_routing["$key"]['token']);
                }

                // we need to merge with the subject for the routing (/project/{uid}/list/{list_uid} for example)
                $routing = array( 'path' => $this->log_routing["$key"]['path'], 'args' => array_merge($subject_routing['args'], array( $this->log_routing["$key"]['key'] => $object_uid, 'token' => $token )) ); 
            
            }

            $logName = strip_tags($object['logName']);

            $parameters["%$key%"] = $this->twig->render($this->template_link, array( 'logName' => $logName, 'routing' => $routing ) );
            
        }

        return $parameters;

    }

    /*
     * Helpers for count and get Notifications (DRY-style)
     */
    private function getAllObjects($user)
    {

        // Projects
        $allProjects = array();
        foreach ($user->getProjectsWatched() as $project) { $allProjects[] = $project; }
        foreach ($user->getProjectsOwned() as $project) { $allProjects[] = $project; }
        foreach ($user->getProjectsParticipatedIn() as $project) { $allProjects[] = $project; }

        // Ideas
        $allIdeas = array();
        foreach ($user->getIdeasWatched() as $idea){ $allIdeas[] = $idea; }
        foreach ($user->getIdeasCreated() as $idea){ $allIdeas[] = $idea; }
        foreach ($user->getIdeasParticipatedIn() as $idea){ $allIdeas[] = $idea; }
            
        // Users
        $usersFollowed = $user->getFollowing()->toArray();

        // Communities
        $communities = array();
        foreach ($user->getUserCommunities() as $userCommunity){ $communities[] = $userCommunity->getCommunity(); }

        return array( 'projects' => $allProjects,
                      'ideas'   => $allIdeas,
                      'users'   => $usersFollowed,
                      'communities' => $communities);
    }

    /*
     * Count the new notifications for a user
     * No 'backward' style as per the showNotificationsAction (it's just the count of the new stuff)
     */
    public function countNotifications($user, $community = null)
    {

        $objects = $this->getAllObjects($user);
        $from = $user->getLastNotifiedAt();

        // Around myself
        $userLogRepository = $this->em->getRepository('metaGeneralBundle:Log\UserLogEntry');
        $selfLogs = $userLogRepository->countLogsForUser($from, $user, $community); // New followers of user

        // Fetch logs related to the projects
        if (count($objects['projects']) > 0){
            $projectLogRepository = $this->em->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
            $projectLogs = $projectLogRepository->countLogsForProjects($objects['projects'], $from, $user, $community);
        } else {
            $projectLogs = 0;
        }
        
        // Fetch all logs related to the ideas
        if (count($objects['ideas']) > 0){
            $ideaLogRepository = $this->em->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
            $ideaLogs = $ideaLogRepository->countLogsForIdeas($objects['ideas'], $from, $user, $community);
        } else {
            $ideaLogs = 0;
        }

        // Fetch all logs related to the users followed (their updates, or if they have created new projects or been added into one)
        // In the repository, we make sure we only get logs for the communities the current user can see
        if (count($objects['users']) > 0){
            $baseLogRepository = $this->em->getRepository('metaGeneralBundle:Log\BaseLogEntry');
            $userLogs = $baseLogRepository->countSocialLogsForUsersInCommunitiesOfUser($this->log_social_filters, $objects['users'], $from, $user, $community);
        } else {
            $userLogs = 0;
        }

        $total = $selfLogs + $projectLogs + $ideaLogs + $userLogs;

        return $total;
    }

    public function getLastNotificationDate($user, $community = null)
    {

        $objects = $this->getAllObjects($user);

        // Around myself
        $userLogRepository = $this->em->getRepository('metaGeneralBundle:Log\UserLogEntry');
        // Last self log
        $selfLogs = $userLogRepository->findLogsForUser(null, $user, $community);
        if (count($selfLogs) > 0) {
            $lastDate = $selfLogs[0]->getCreatedAt();
        } else {
            $lastDate = new \DateTime('now - 1 year');
        }

        // Last projet log
        if (count($objects['projects']) > 0){
            $projectLogRepository = $this->em->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
            $projectLogs = $projectLogRepository->findLogsForProjects($objects['projects'], null, $user, $community);
            if (count($projectLogs) > 0) {
                $lastDate = max($lastDate, $projectLogs[0]->getCreatedAt());
            }
        }

        // Last idea log
        if (count($objects['ideas']) > 0){
            $ideaLogRepository = $this->em->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
            $ideaLogs = $ideaLogRepository->findLogsForIdeas($objects['ideas'], null, $user, $community);
            if (count($ideaLogs) > 0) {
                $lastDate = max($lastDate, $ideaLogs[0]->getCreatedAt());
            }
        }

        // Last user log
        // In the repository, we make sure we only get logs for the communities the current user can see
        $baseLogRepository = $this->em->getRepository('metaGeneralBundle:Log\BaseLogEntry');
        if (count($objects['users']) > 0){
            $userLogs = $baseLogRepository->findSocialLogsForUsersInCommunitiesOfUser($this->log_social_filters, $objects['users'], null, $user, $community);
            if (count($userLogs) > 0) {
                $lastDate = max($lastDate, $userLogs[0]->getCreatedAt());
            }
        }

        // Last community log
        if (count($objects['communities']) > 0){
            foreach ($objects['communities'] as $community) {
                $entries = $baseLogRepository->findByLogTypes(null/*$this->log_community_filters*/, array('community' => $community));
                if (count($entries) > 0) {
                    $lastDate = max($lastDate, $entries[0]->getCreatedAt());
                }
            }
        }

        return $lastDate;

    }

    public function getNotifications($user, $date = null, $community = null, $locale = null)
    {

        $objects = $this->getAllObjects($user);

        // So let's get the stuff
        $lastNotified = $user->getLastNotifiedAt();
        $from = is_null($date)?$lastNotified:date_create($date);
        
        // Now get the logs
        $notifications = array();

        // Around myself
        $userLogRepository = $this->em->getRepository('metaGeneralBundle:Log\UserLogEntry');
        $selfLogs = $userLogRepository->findLogsForUser($from, $user, $community); // New followers of user
        foreach ($selfLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $this->getHTML($notification, $lastNotified, $locale) ); }

        // Fetch logs related to the projects
        if (count($objects['projects']) > 0){
            $projectLogRepository = $this->em->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
            $projectLogs = $projectLogRepository->findLogsForProjects($objects['projects'], $from, $user, $community);
            foreach ($projectLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $this->getHTML($notification, $lastNotified, $locale) ); }
        }
        
        // Fetch all logs related to the ideas
        if (count($objects['ideas']) > 0){
            $ideaLogRepository = $this->em->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
            $ideaLogs = $ideaLogRepository->findLogsForIdeas($objects['ideas'], $from, $user, $community);
            foreach ($ideaLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $this->getHTML($notification, $lastNotified, $locale) ); }
        }

        // Fetch all logs related to the users followed (their updates, or if they have created new projects or been added into one)
        // In the repository, we make sure we only get logs for the communities the current user can see
        $baseLogRepository = $this->em->getRepository('metaGeneralBundle:Log\BaseLogEntry'); // used for the two next loops
        if (count($objects['users']) > 0){
            $userLogs = $baseLogRepository->findSocialLogsForUsersInCommunitiesOfUser($this->log_social_filters, $objects['users'], $from, $user, $community);
            foreach ($userLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $this->getHTML($notification, $lastNotified, $locale) ); }
        }

        // Fetch all logs related to the communities
        if (count($objects['communities']) > 0){
            foreach ($objects['communities'] as $community) {
                $entries = $baseLogRepository->findByLogTypes(null/*$this->log_community_filters*/, array('community' => $community));
                foreach ($entries as $notification) { 
                    // Strips private projects logs
                    if ($this->log_types[$notification->getType()]['type'] === "project" && $notification->getSubject()->isPrivate()) {
                        continue;
                    }
                    // If it's a log from the user, no need to display !
                    if ($notification->getUser() !== $user) {
                        $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $this->getHTML($notification, $lastNotified, $locale) ); 
                    }
                }
            }
        }

        // Sort !
        if (!function_exists('meta\GeneralBundle\Services\build_sorter')){
            function build_sorter($key) {
                return function ($a, $b) use ($key) {
                    return $a[$key]<$b[$key];
                };
            }
        }
        
        $notifications = array_unique($notifications, SORT_REGULAR);
        usort($notifications, build_sorter('createdAt'));

        return array('notifications' => $notifications,
                    'lastNotified' => $lastNotified,
                    'from' => $from
                );
    }
}