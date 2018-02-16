<?php
/**
 * Created by PhpStorm.
 * User: catilre
 * Date: 2018/02/16
 * Time: 13:58
 */
// load framework files
require_once("libraries/TeamSpeak3/TeamSpeak3.php");
// connect to local server in non-blocking mode, authenticate and spawn an object for the virtual server on port 9987

$ts3_VirtualServer = TeamSpeak3::factory("serverquery://username:password@127.0.0.1:10011/?server_port=9987&blocking=0");
$ts3_SignalHelper  = TeamSpeak3_Helper_Signal::getInstance();

// define a callback function
$onTextMessage = function (TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host)
{
    echo "Client " . $event["invokername"] . " sent textmessage: " . $event["msg"];
};


// server|channel|textserver|textchannel|textprivate
$ts3_VirtualServer->notifyRegister("server");
$ts3_VirtualServer->notifyRegister("channel");
$ts3_VirtualServer->notifyRegister("textserver");
$ts3_VirtualServer->notifyRegister("textchannel");
$ts3_VirtualServer->notifyRegister("textprivate");

/**
 * Use "notifyEvent" to get notified of all events.
 *
 * For specific notifications, use below to get notified on the event.
 * Then get the event type by using $event->getType.
 * Next change subscribe to "notify{ucfirst($event_type)}"
 */
$ts3_SignalHelper->subscribe("notifyEvent", $this->eventFunction());

// wait for events
while(1) $ts3_VirtualServer->getAdapter()->wait();

