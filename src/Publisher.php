<?php

namespace Bschmitt\Amqp;

/**
 * @author Björn Schmitt <code@bjoern.io>
 */
class Publisher extends Request
{
    /**
    * The result of the call to basic_publish. 
    * Assumed to be successful unless overridden with 
    * a basic.return or basic.nack. Only valid when in 
    * confirm_select mode
    */
    private $publish_result = null;

    /**
     * @param string  $routing
     * @param Message $message
     * @throws Exception\Configuration
     */
    public function publish($routing, $message, $mandatory = false)
    {
        $this->publish_result = true;

        if ($mandatory === true) {
            $this->getChannel()->confirm_select();
            $this->getChannel()->set_nack_handler([$this, 'nack']);
            $this->getChannel()->set_return_listener([$this, 'return']);
        }

        $timeout = $this->getProperty('publish_timeout') > 0
            ? $this->getProperty('publish_timeout')
            : 30;

        $this->getChannel()->basic_publish($message, $this->getProperty('exchange'), $routing, $mandatory);
        $mandatory === true && $this->getChannel()->wait_for_pending_acks_returns((int)$timeout);

        return $this->publish_result;
    }

    /**
    * @param Array array of AMQPMessage objects
    */
    public function nack($msg) 
    {
    	$this->publish_result = false;
    }

    /**
    * @param Array array of AMQPMessage objects
    */
    public function return($msg) 
    {
    	$this->publish_result = false;
    }

    /**
     * Add a message to the batch.
     *
     * @param string $routing
     * @param Message $message
     */
    public function batchBasicPublish($routing, $message)
    {
        $this->getChannel()->batch_basic_publish($message, $this->getProperty('exchange'), $routing);
    }

    /**
     * Publish the batched messages.
     */
    public function batchPublish()
    {
        $this->getChannel()->publish_batch();
    }
}
