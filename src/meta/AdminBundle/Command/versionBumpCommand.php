<?php
namespace meta\AdminBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class versionBumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('version:bump')->setDescription('Bumps the actual git version and commit number of the application');
        
        // Config placeholder
        $this->pattern = '/version\:\ \'([^\"]*)\'/';

        // New style for <important>
        $this->importantStyle = new OutputFormatterStyle('red', null, array('bold'));

        // By default, no mails are sent, the output is just printed out (number of mails sent)
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Writes the new version to the config.yml file');
        
    }
  
    protected function execute(InputInterface $input, OutputInterface $output)
    {

      // Should we really write the config
      $writeConfig = $input->getOption('force');

      // Additional styling
      $output->getFormatter()->setStyle('important', $this->importantStyle);

      // Get tag " v1.0 "
      exec('git describe --abbrev=0 --tags', $cli);
      $tag = $cli[0];

      // Tag commit
      exec('git rev-parse --short "'. $tag . '"', $cli2);
      $tag_sha = $cli2[0];

      // Last commit
      exec('git rev-parse --short HEAD', $cli3);
      $head_sha = $cli3[0];

      // Date
      exec('git show -s --format="%ci" "' . $tag . '"', $cli4);
      $date = $cli4[0];

      if ($head_sha === $tag_sha) {
        // We're on a tag
        $version_full = $tag . "-" . $tag_sha . "/RELEASE";
      } else {
        // We have commited stuff but not released <- not very good
        $version_full = $tag . "-" . $tag_sha . "/" . $head_sha . "(HEAD)";
      }

      $version_full .= " [" . $date . "]";

      $output->writeln('');
      $output->writeln('Actual repository version is <comment>' . $version_full .'.</comment>');

      $output->writeln('<comment>Reading parameters.yml</comment>');

      // Get parameters file      
      $configDirectories = array($this->getContainer()->getParameter('kernel.root_dir') .'/config');
      $locator = new FileLocator($configDirectories);
      $config_file = $locator->locate('parameters.yml', null, true); // returns the first file

      // read the entire string
      $config = file_get_contents($config_file);

      // replace something in the file string
      $config_new = preg_replace($this->pattern, "version: '$version_full'", $config);
      
      // config_new should be different and writeConfig enabled
      if ($writeConfig && ($config_new !== $config) ) {
          file_put_contents($config_file, $config_new);
          $output->writeln('<info>Parameters.yml written.</info>');
      } else if (!$writeConfig && ($config_new !== $config) ) {
          $output->writeln('<important>Parameters.yml not written.</important> Use --force to write the changes');
      } else if ($config_new === $config ) {
          $output->writeln('<important>Nothing to replace — version has not changed.</important>');
      } else {
          $output->writeln('<important>Parameters.yml untouched.</important>');
      }

      $output->writeln('<info>Done.</info>');
      $output->writeln('');

    }


}
