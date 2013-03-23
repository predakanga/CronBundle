<?php
namespace ColourStream\Bundle\CronBundle\Command;
use Symfony\Component\Console\Output\Output;

/**
 * MemoryWriter implements OutputInterface, writing to an internal buffer
 */
class MemoryWriter extends Output {
    protected $backingStore = "";
    
    public function __construct($verbosity = self::VERBOSITY_NORMAL) {
        parent::__construct($verbosity);
    }

    public function doWrite($message, $newline) {
        $this->backingStore .= $message;
        if($newline) {
            $this->backingStore .= "\n";
        }
    }
    
    public function getOutput() {
        return $this->backingStore;
    }
    
    public function clear() {
        $this->backingStore = "";
    }
}