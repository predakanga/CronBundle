<?php
namespace ColourStream\Bundle\CronBundle\Entity;
use Doctrine\ORM\EntityRepository;

class CronJobRepository extends EntityRepository
{
    public function getKnownJobs()
    {
        $data = $this->getEntityManager()
                     ->createQuery("SELECT job.command FROM ColourStreamCronBundle:CronJob job")
                     ->getScalarResult();
        $toRet = array();
        foreach($data as $datum)
        {
            $toRet[] = $datum['command'];
        }
        return $toRet;
    }
    
    public function findDueTasks()
    {
        return $this->getEntityManager()
                    ->createQuery("SELECT job FROM ColourStreamCronBundle:CronJob job
                                              WHERE job.nextRun <= :curTime
                                              AND job.enabled = 1")
                    ->setParameter('curTime', new \DateTime())
                    ->getResult();
    }
}
