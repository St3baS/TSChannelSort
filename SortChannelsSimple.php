<?php
/**
 * Created by PhpStorm.
 * User: catilre
 * Date: 2018/02/16
 * Time: 13:58
 */

require 'configs/devConfig.php';
// load framework files
require_once("ts3phpframework/libraries/TeamSpeak3/TeamSpeak3.php");
// connect to local server in non-blocking mode, authenticate and spawn an object for the virtual server on port 9987

$server_uri = "serverquery://{$config['serveradmin']}:{$config['password']}@{$config['address']}:{$config['queryport']}/?server_port={$config['port']}&nickname={$config['nickname']}&blocking=0";

$ts3_VirtualServer = TeamSpeak3::factory($server_uri);
$ts3_SignalHelper  = TeamSpeak3_Helper_Signal::getInstance();

// define a callback function
$onEventCallback = function (TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host) use ($defaultChannels, $alphabetChannels, $protectedChannelUIDs, $ts3_VirtualServer)
{
    if ($event->getType() == 'channelcreated')
    {
        $channel = $event->getData();

        if (in_array($channel["channel_name"], $defaultChannels) || //
            in_array($channel["cid"], $alphabetChannels) || //
            in_array($channel["cid"], $protectedChannelUIDs) || //
            $channel["cpid"] != 0
        )
        {
            return;
        }
        $channelName    = preg_replace('/\[PERMANENT\]/', "", $channel['channel_name']);
        $foundChannel   = FALSE;
        $channelRenamed = FALSE;
        foreach ($alphabetChannels as $parentChannel => $cid)
        {
            $subChannels = $ts3_VirtualServer->channelGetById($cid)->subChannelList();
            if (! empty($subChannels))
            {
                $lastId = end($subChannels)->getId();
            }
            else
            {
                $lastId = NULL;
            }
            if (preg_match('/^\s/', $channelName))
            {
                $channelName = preg_replace('/^\s/', "", $channelName);
            }
            foreach ($subChannels as $subChannel)
            {
                if (strpos($channelName, '[DUPLICATENAME_TO_BE_DELETED]') !== FALSE)
                {
                    $ts3_VirtualServer->channelDelete($channel["cid"], TRUE);
                    continue 2;
                }
                if ($channelName == $subChannel['channel_name'])
                {
                    try
                    {
                        $channel->modify([
                            "channel_name"        => substr($channelName, 0, 11) . '[DUPLICATENAME_TO_BE_DELETED]',
                            "channel_description" => $channelName . ' channel name is already used. Rename this channel as something different',
                        ]);
                    }
                    catch (TeamSpeak3_Exception $e)
                    {
                        try
                        {
                            print_r("DELETE");
                            print_r($e->getMessage());
                            $ts3_VirtualServer->channelDelete($channel["cid"]);
                        }
                        catch (TeamSpeak3_Exception $e)
                        {
                        }
                    }
                }
            }
            try
            {
                if ($channelName[0] === $parentChannel || $channelName[0] === strtolower($parentChannel) || ($parentChannel === "Other" && ! $foundChannel))
                {
                    $foundChannel = TRUE;
                    $ts3_VirtualServer->channelMove($channel['cid'], $cid, $lastId);
                }
                if (preg_match('/[^A-Za-z]/', $parentChannel))
                {
                    if (! ctype_alpha($channelName[0]))
                    {
                        $ts3_VirtualServer->channelMove($channel['cid'], $cid, $lastId);
                    }
                }
            }
            catch (TeamSpeak3_Exception $e)
            {
                try
                {
                    $ts3_VirtualServer->channelDelete($channel["cid"]);
                }
                catch (TeamSpeak3_Exception $e)
                {
                }
            }
        }
    }
};

$ts3_VirtualServer->notifyRegister("channel");
$ts3_SignalHelper->subscribe("notifyEvent", $onEventCallback);

// wait for events
while (1)
{
    $ts3_VirtualServer->getAdapter()->wait();
}

