<?php
namespace meta\GeneralBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
  
class digestSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('digest:send')->setDescription('Sends the automatic mail digests (only if --force is passed)');

        // By default, no mails are sent, the output is just printed out (number of mails sent)
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Sends the mail FOR REAL');
    }
  
    protected function execute(InputInterface $input, OutputInterface $output)
    {

      // Should we really send mails
      $sendMails = $input->getOption('force');
      $verbose = (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity());
 
      $today = $this->getActualDay();
      $sendBiMonthlyEmails = $this->isEvenWeek();
      $sendDefaultDayEmails = $this->isDefaultDay();

      $output->writeln('Today is : <info>' . $today.'.</info>');

      if ($sendBiMonthlyEmails){
        $output->writeln(' # This week we are sending <info>bi-monthly emails</info>.');
      }

      if ($sendDefaultDayEmails){
        $output->writeln(" # It's the <info>default</info> day");
      }

      // List all users to whom we need to send a digest today
      $userRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:User');
      $usersToSendDigestsTo = $userRepository->findUsersWhoNeedDigestOnDay($today, $sendBiMonthlyEmails, $sendDefaultDayEmails);

      // Do we have users to notify ?
      if ($usersToSendDigestsTo){

        $nbUsers = count($usersToSendDigestsTo);

        $output->writeln(' # We have to notify <comment>' . $nbUsers . ' user(s)</comment> today');
        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

        if (!$verbose) {
          $progress = $this->getHelperSet()->get('progress');
          $progress->start($output, $nbUsers);
        }

        // Initializes an array of messages
        $messages = array();

        foreach ($usersToSendDigestsTo as $user) {

          if (!$verbose) {
            $progress->advance();
          } else {
            $output->writeln("   - <question>" . $user->getFullName() . "</question>");
          }

          // Get userCommunity
          $userCommunities = $userCommunityRepository->findByUser($user);

          if (!($user->getEnableSpecificEmails())){
          
            /*
             * One mail for all the notifications
             */

            // -- DEBUG One mail for all the notifications
            $nbNotifications = $this->getContainer()->get('logService')->countNotifications($user);
            if ($verbose) $output->writeln('     * ' . $nbNotifications . " aggregate notification(s) to send to " . $user->getEmail());
            // -- END DEBUG

            $notificationsArray = $this->getContainer()->get('logService')->getNotifications($user);

            $messages[] = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->get('translator')->trans('user.digest.mail.subject'))
                ->setFrom($this->getContainer()->getParameter('mailer_from'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'metaGeneralBundle:Digest:digest.mail.html.twig',
                        array('notifications' => $notificationsArray['notifications'], 'lastNotified' => $notificationsArray['lastNotified'], 'from' => $notificationsArray['from'], 'community' => null)
                    ), 'text/html'
                );

          } else {
            
            /*
             * For each community, a separate mail
             */

            foreach ($userCommunities as $userCommunity) {
              
              if ($userCommunity->getGuest()) continue;

              // -- DEBUG One mail for all the notifications
              $nbNotifications = $this->getContainer()->get('logService')->countNotifications($user, $userCommunity->getCommunity());
              if ($verbose) $output->writeln('     * ' . $userCommunity->getCommunity()->getName() . " : " . $nbNotifications . " notification(s) to send to " . $userCommunity->getEmail());
              // -- END DEBUG

              $notificationsArray = $this->getContainer()->get('logService')->getNotifications($user, null, $userCommunity->getCommunity());
              
              $messages[] = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->get('translator')->trans('user.digest.mail.subject'))
                ->setFrom($this->getContainer()->getParameter('mailer_from'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'metaGeneralBundle:Digest:digest.mail.html.twig',
                        array('notifications' => $notificationsArray['notifications'], 'lastNotified' => $notificationsArray['lastNotified'], 'from' => $notificationsArray['from'], 'community' => $userCommunity->getCommunity())
                    ), 'text/html'
                );

            }

          }

          // We have the notifications, send the mail (or not)
          if ($sendMails && $nbNotifications > 0){

            $mailer = $container = $this->getContainer()->get('mailer');
            $spool = $mailer->getTransport()->getSpool();
            $transport = $this->getContainer()->get('swiftmailer.transport.real');

            foreach ($messages as $message) {
              $this->getContainer()->get('mailer')->send($message);
            }

            // Sends for real... maybe put that at the very end of the script ?
            $spool->flushQueue($transport);

            if ($verbose) $output->writeln('     --> Mail sent');

          } else {
            
            if ($verbose) $output->writeln('     --> Mail NOT sent');

          }

        }

        if (!$verbose) $progress->finish();

      } else {

        $output->writeln(' # No users to notify today.');

      }


    }


    private function getActualDay(){

      return strtolower(date('l'));

    }

    private function isEvenWeek(){

      return (intval(date('W')) % 2 == 0);

    }

    private function isDefaultDay(){

      $defaultDay = $this->getContainer()->getParameter('digest.day');
      return ($this->getActualDay() == $defaultDay);

    }
}
