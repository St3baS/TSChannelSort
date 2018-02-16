<?php

/**
 * Created by St3baS
 */
// TS3 Docs
// http://media.teamspeak.com/ts3_literature/TeamSpeak%203%20Server%20Query%20Manual.pdf

require 'configs/devConfig.php';

class TSChannelSort
{
    var $connection;
    var $signalHelper;
    var $defaultChannels;
    var $alphabetChannels;
    var $protectedChannelUIDs;
    var $sortFunction;

    function __construct($config)
    {
        require_once(dirname(__FILE__) . '/ts3phpframework/libraries/TeamSpeak3/TeamSpeak3.php');

        $server_uri = "serverquery://{$config['serveradmin']}:{$config['password']}@{$config['address']}:{$config['queryport']}" .
                      "/?server_port={$config['port']}&nickname={$config['nickname']}&blocking=0";

        $this->server = TeamSpeak3::factory($server_uri);
    }

    private function sortFunction()
    {
        if (is_null($this->sortFunction))
        {
            $this->sortFunction = function (TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host)
            {
                if ($event->getType() == 'channelcreated')
                {
                    $channel = $event->getData();

                    if (in_array($channel["channel_name"], $this->defaultChannels) || //
                        in_array($channel["cid"], $this->alphabetChannels) || //
                        in_array($channel["cid"], $this->protectedChannelUIDs) || //
                        $channel["cpid"] != 0
                    )
                    {
                        return;
                    }
                    $channelName    = preg_replace('/\[PERMANENT\]/', "", $channel['channel_name']);
                    $foundChannel   = FALSE;
                    $channelRenamed = FALSE;
                    foreach ($this->alphabetChannels as $parentChannel => $cid)
                    {
                        $subChannels = $this->server->channelGetById($cid)->subChannelList();
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
                                $this->server->channelDelete($channel["cid"], TRUE);
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
                                        $this->server->channelDelete($channel["cid"]);
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
                                $this->server->channelMove($channel['cid'], $cid, $lastId);
                            }
                            if (preg_match('/[^A-Za-z]/', $parentChannel))
                            {
                                if (! ctype_alpha($channelName[0]))
                                {
                                    $this->server->channelMove($channel['cid'], $cid, $lastId);
                                }
                            }
                        }
                        catch (TeamSpeak3_Exception $e)
                        {
                            try
                            {
                                $this->server->channelDelete($channel["cid"]);
                            }
                            catch (TeamSpeak3_Exception $e)
                            {
                            }
                        }
                    }
                }
            };
        }

        return $this->sortFunction;
    }

    private function setupListeners()
    {
        $this->signalHelper = TeamSpeak3_Helper_Signal::getInstance();

        $this->server->notifyRegister("channel");

        $this->signalHelper->subscribe("notifyEvent", $this->sortFunction());
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
    $tsChannelSort->defaultChannels      = $defaultChannels;
    $tsChannelSort->alphabetChannels     = $alphabetChannels;
    $tsChannelSort->protectedChannelUIDs = $protectedChannelUIDs;

    $tsChannelSort->start();
}
catch (Exception $e)
{
    echo "Error " . $e->getCode() . ": " . $e->getMessage();
}
