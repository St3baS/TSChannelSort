<?php

/**
 * Created by St3baS
 */

require_once(realpath(dirname(__FILE__) . '/ts3phpframework/libraries/TeamSpeak3/TeamSpeak3.php'));

require 'configs/config.php';

try
{
    // Create connection to TS3 server
    $server_uri        = "serverquery://{$config['serveradmin']}:{$config['password']}@{$config['address']}:{$config['queryport']}" .
                         "/?server_port={$config['port']}&nickname={$config['nickname']}&blocking=0";
    $ts3_VirtualServer = TeamSpeak3::factory($server_uri);

    while (1)
    {
        foreach ($ts3_VirtualServer->channelList() as $channel)
        {
            if (in_array($channel["channel_name"], $defaultChannels) || //
                in_array($channel["cid"], $alphabetChannels) || //
                in_array($channel["cid"], $protectedChannelUIDs) || //
                $channel["pid"] != 0
            )
            {
                continue;
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
                        continue 3;
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

        $ts3_VirtualServer->channelListReset();
        sleep(1);
    }
}
catch (TeamSpeak3_Exception $e)
{
    echo "Error " . $e->getCode() . ": " . $e->getMessage();
}

