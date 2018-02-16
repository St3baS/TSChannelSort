<?php

/**
 * Created by St3baS
 */
// TS3 Docs
// http://media.teamspeak.com/ts3_literature/TeamSpeak%203%20Server%20Query%20Manual.pdf

require('ts3phpframework/libraries/TeamSpeak3/TeamSpeak3.php');
require('configs/config.php');

class TSChannelSort
{
    var $connection;
    var $signalHelper;
    var $eventFunction;

    function __construct($config)
    {
        $this->server = TeamSpeak3::factory("serverquery://username:password@127.0.0.1:10011/?server_port=9987&blocking=0");
    }

    private function eventFunction()
    {
        if (is_null($this->eventFunction))
        {
            $this->eventFunction = function (TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host)
            {
                echo $event->getType();
            };
        }

        return $this->eventFunction;
    }

    private function setupListeners()
    {
        $this->signalHelper = TeamSpeak3_Helper_Signal::getInstance();

        // server|channel|textserver|textchannel|textprivate
        $this->server->notifyRegister("server");
        $this->server->notifyRegister("channel");
        $this->server->notifyRegister("textserver");
        $this->server->notifyRegister("textchannel");
        $this->server->notifyRegister("textprivate");

        /**
         * Use "notifyEvent" to get notified of all events.
         *
         * For specific notifications, use below to get notified on the event.
         * Then get the event type by using $event->getType.
         * Next change subscribe to "notify{ucfirst($event_type)}"
         */

        $this->signalHelper->subscribe("notifyEvent", $this->eventFunction());
    }

    public function start()
    {
        $this->setupListeners();

        while (1)
        {
            $this->server->getAdapter()->wait();
        }
    }
}

// Get Connection
try
{
    $tsChannelSort                       = new TSChannelSort($config);

    $tsChannelSort->start();
}
catch (TeamSpeak3_Exception $e)
{
    echo "Error " . $e->getCode() . ": " . $e->getMessage();
}
