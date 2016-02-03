<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;
use CentralDesktop\Stomp\Exception as StompException;
use Smartbox\Integration\FrameworkBundle\Messages\Message;

/**
 * Class QueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueConsumer implements QueueConsumerInterface, UsesMessageHandlerInterface
{
    use UsesMessageHandler;

    /** @var bool */
    protected $stop = false;

    /** @var  QueueDriverInterface */
    protected $queueDriver;

    /** @var int  */
    protected $expirationCount = -1;

    /**
     * @return QueueDriverInterface
     */
    public function getQueueDriver()
    {
        return $this->queueDriver;
    }

    /**
     * @param QueueDriverInterface $queueDriver
     */
    public function setQueueDriver($queueDriver)
    {
        $this->queueDriver = $queueDriver;
    }

    public function stop()
    {
        $this->stop = true;
    }

    /**
     * @param $count
     */
    public function setExpirationCount($count){
        $this->expirationCount = $count;
    }

    /**
     * @return int
     */
    public function getExpirationCount(){
        return $this->expirationCount;
    }

    public function shouldStop(){
        return $this->stop || $this->expirationCount == 0;
    }

    public function consume($queue){
        $this->getQueueDriver()->connect();
        $this->getQueueDriver()->subscribe($queue);

        /**
         * TODO: Add checks for body, header from, etc. and log error events and react accordingly
         */
        while (!$this->shouldStop()) {
            try {
                // Receive
                $queueMessage = $this->getQueueDriver()->receive();

                // Process
                if($queueMessage){
                    $this->expirationCount--;

                    // Determine from
                    $message = $queueMessage->getBody();

                    // Handle
                    $this->getHandler()->handle($message,$queueMessage->getDestinationURI());

                    // Ack
                    $this->getQueueDriver()->ack();
                }

            } catch (StompException $ex) {
                if (!$this->stop){
                    throw $ex;
                }
            }
        }

        $this->getQueueDriver()->unSubscribe();
    }
}
