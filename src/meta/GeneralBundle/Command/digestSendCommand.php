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

      $output->writeln('Today is : ' . $today.'.');

      if ($sendBiMonthlyEmails){
        $output->writeln(' # This week we are sending bi-monthly emails.');
      }

      if ($sendDefaultDayEmails){
        $output->writeln(" # It's the default day");
      }

      // List all users to whom we need to send a digest today
      $userRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:User');
      $usersToSendDigestsTo = $userRepository->findUsersWhoNeedDigestOnDay($today, $sendBiMonthlyEmails, $sendDefaultDayEmails);

      // Get the communities, and then the emails
      if ($usersToSendDigestsTo){

        $nbUsers = count($usersToSendDigestsTo);

        $output->writeln(' # We have to notify ' . $nbUsers . ' user(s) today');
        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

        if (!$verbose) {
          $progress = $this->getHelperSet()->get('progress');
          $progress->start($output, $nbUsers);
        }

        foreach ($usersToSendDigestsTo as $user) {

          if (!$verbose) {
            $progress->advance();
          } else {
            $output->writeln("   - " . $user->getFullName());
          }

          // Get userCommunity
          $userCommunities = $userCommunityRepository->findByUser($user);

          if (!($user->getEnableSpecificEmails())){
          
            // One mail for all the notifications
            $nbNotifications = $this->getContainer()->get('logService')->countNotifications($user);
            $notifications = $this->getContainer()->get('logService')->getNotifications($user);
            if ($verbose) $output->writeln('     * ' . $nbNotifications . " aggregate notifications to send to " . $user->getEmail());


          } else {
            
            // For each community, get HTML/Text to put in the mail

            foreach ($userCommunities as $userCommunity) {
              
              if ($userCommunity->getGuest()) continue;

              $nbNotifications = $this->getContainer()->get('logService')->countNotifications($user, $userCommunity->getCommunity());
              $notifications = $this->getContainer()->get('logService')->getNotifications($user, null, $userCommunity->getCommunity());
              if ($verbose) $output->writeln('     * ' . $userCommunity->getCommunity()->getName() . " : " . $nbNotifications . " notifications to send to " . $userCommunity->getEmail());

            }

          }

          // We have the notifications, send the mail (or not)
          if ($sendMails && $nbNotifications > 0){

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('user.invitation.mail.subject'))
                ->setFrom($this->container->getParameter('mailer_from'))
                ->setReplyTo($user->getEmail())
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'metaUserBundle:Mail:invite.mail.html.twig',
                        array('user' => $authenticatedUser, 'inviteToken' => $token?$token->getToken():null, 'invitee' => ($user && !$user->isDeleted()), 'community' => $community, 'project' => null )
                    ), 'text/html'
                );
            $this->get('mailer')->send($message);

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
