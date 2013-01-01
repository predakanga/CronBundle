<?php
namespace ColourStream\Bundle\CronBundle\Command;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputInterface;

class CronEnableJobCommand extends CronBaseCommand
{
    protected function configure()
    {
        $this->setName("cron:enable-job")
             ->setDescription("Enables a cron job")
             ->addArgument("job", InputArgument::REQUIRED, "Name of the job to enable");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = $input->getArgument('job');
        $em = $this->getEntityManger();
        $jobRepo = $em->getRepository('ColourStreamCronBundle:CronJob');

        $job = $jobRepo->findOneByCommand($jobName);
        if(!$job)
        {
            $output->writeln("Couldn't find a job by the name of " . $jobName);
            return CronJobResult::FAILED;
        }

        $job->setEnabled(true);
        $em->flush();

        $output->writeln("Enabled cron job by the name of " . $jobName);
    }
}
