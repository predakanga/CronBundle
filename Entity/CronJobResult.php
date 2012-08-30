<?php
namespace ColourStream\Bundle\CronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ColourStream\Bundle\CronBundle\Entity\CronJobResultRepository")
 */
class CronJobResult
{
    const RESULT_MIN = 0;
    const SUCCEEDED = 0;
    const FAILED = 1;
    const SKIPPED = 2;
    const RESULT_MAX = 2;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    protected $id;
    
    /**
     * @ORM\Column(type="datetime")
     * @var DateTime $runAt
     */
    protected $runAt;
    /**
     * @ORM\Column(type="float")
     * @var float $runTime
     */
    protected $runTime;
    
    /**
     * @ORM\Column(type="integer")
     * @var integer $result
     */
    protected $result;
    /**
     * @ORM\Column(type="text")
     * @var string $output
     */
    protected $output;
    
    /**
     * @ORM\ManyToOne(targetEntity="CronJob", inversedBy="results")
     * @var CronJob
     */
    protected $job;

    public function __construct()
    {
        $this->runAt = new DateTime();
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set runAt
     *
     * @param datetime $runAt
     */
    public function setRunAt($runAt)
    {
        $this->runAt = $runAt;
    }

    /**
     * Get runAt
     *
     * @return datetime 
     */
    public function getRunAt()
    {
        return $this->runAt;
    }

    /**
     * Set runTime
     *
     * @param float $runTime
     */
    public function setRunTime($runTime)
    {
        $this->runTime = $runTime;
    }

    /**
     * Get runTime
     *
     * @return float 
     */
    public function getRunTime()
    {
        return $this->runTime;
    }

    /**
     * Set result
     *
     * @param integer $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Get result
     *
     * @return integer 
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set output
     *
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Get output
     *
     * @return string 
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set job
     *
     * @param ColourStream\Bundle\CronBundle\Entity\CronJob $job
     */
    public function setJob(\ColourStream\Bundle\CronBundle\Entity\CronJob $job)
    {
        $this->job = $job;
    }

    /**
     * Get job
     *
     * @return ColourStream\Bundle\CronBundle\Entity\CronJob 
     */
    public function getJob()
    {
        return $this->job;
    }
}