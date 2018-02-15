<?php 

require_once(realpath(dirname(__FILE__) . '/ts3phpframework/libraries/TeamSpeak3/TeamSpeak3.php'));

function connection() {
    
    global $ts3_VirtualServer;
    global $config;
	$ts3_VirtualServer = TeamSpeak3::factory("serverquery://{$config['serveradmin']}:{$config['password']}@{$config['address']}:{$config['queryport']}/?server_port={$config['port']}&nickname={$config['nickname']}");
}

+$config = array(
+    "address" => "", 
+    "queryport" => "", 
+    "serveradmin" => "", 
+    "password" => "", 
+    "port" => "9987", 
+    "nickname" => "ChannelSortBot"
+);

$dont_touch = array(1, 3, 4, 5, 6, 7, 8, 9, 11, 61, 63, 64, 65, 66, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 8);

//AlphabetChannels order
$AlphabetChannels = array(  
    "A" => 242, //CID
    "B" => 243,
	"C" => 244, 
    "D" => 245,
    "E" => 246,
    "F" => 247,
    "G" => 248,
    "H" => 249,
    "I" => 250,
    "J" => 251,
    "K" => 252,
    "L" => 253,
    "M" => 254,
    "N" => 255,
    "O" => 256,
    "P" => 257,
    "Q" => 258,
    "R" => 259,
    "S" => 260,
    "T" => 261,
    "U" => 262,
    "V" => 263,
    "W" => 264,
    "X" => 265,
    "Y" => 266,
    "Z" => 267,
    "Other" => 307,
    
);
try {
    
    while (1) {
        
        connection();
        foreach ($ts3_VirtualServer->channelList() as $AllChannels) 
		{
            if($AllChannels["channel_name"] == "Lobby" || $AllChannels["channel_name"] == "General Gaming Channels"  || $AllChannels["channel_name"] == "Default Channel" || $AllChannels["channel_name"] == "[cspacer1] ----User Created Channels----" || $AllChannels["channel_name"] == "[cspacer0] ---Fixed General Channels---"  )
                continue;
                
            if(in_array($channelList["cid"], $AlphabetChannels))
                continue;

            if($AllChannels["pid"] != 0 )
                continue;

			if(in_array($AllChannels["cid"] , $dont_touch))
				continue;
			
			if(in_array($AllChannels["cid"], $AlphabetChannels))
				continue;
		
			$ChannelName = preg_replace('/\[PERMANENT\]/', "", $AllChannels['channel_name']);
			$foundChannel = false;
			$channelRenamed = false;
			foreach ($AlphabetChannels as $ParentChannel => $cid) {
				$subChannels = $ts3_VirtualServer->channelGetById($cid)->subChannelList();
				if(!empty($subChannels))
					$lastId = end($subChannels)->getId();
				else
					$lastId = null;
				
				if (preg_match('/^\s/', $ChannelName)) 
				   $ChannelName = preg_replace('/^\s/', "", $ChannelName);
			   
			   
				foreach ($subChannels as $subChannel) {
					if(strpos($ChannelName, '[DUPLICATENAME_TO_BE_DELETED]') !== false)
					{
						$ts3_VirtualServer->channelDelete($AllChannels["cid"], true);
						continue 3;
					}
					if ($ChannelName == $subChannel['channel_name']) {
						try
						{
							$AllChannels->modify(array(
								"channel_name" => substr($ChannelName,0,11) . '[DUPLICATENAME_TO_BE_DELETED]',
								"channel_description" =>  $ChannelName . ' channel name is already used. Rename this channel as something different'            
							));
						}
						catch(TeamSpeak3_Exception $e) 
						{
							try
							{
								print_r("DELETE");
								print_r($e->getMessage());
								$ts3_VirtualServer->channelDelete($AllChannels["cid"]);
							}
							catch(TeamSpeak3_Exception $e){}
						}
					}
				}
				
				try
				{
					
					if ($ChannelName[0] === $ParentChannel || $ChannelName[0] === strtolower($ParentChannel) || ($ParentChannel === "Other" && !$foundChannel ) ) {
						$foundChannel = true;

						$ts3_VirtualServer->channelMove($AllChannels['cid'], $cid , $lastId);
					}

					if (preg_match('/[^A-Za-z]/', $ParentChannel)) {
						if (!ctype_alpha($ChannelName[0])) {
							$ts3_VirtualServer->channelMove($AllChannels['cid'], $cid , $lastId);
						}
					}
				}
				catch(TeamSpeak3_Exception $e) {
					try{
						$ts3_VirtualServer->channelDelete($AllChannels["cid"]);
					}
					catch(TeamSpeak3_Exception $e){}
				}			
			} 
        } 
        
        $ts3_VirtualServer->logout();
        sleep(5);
        
    }
} 
catch(TeamSpeak3_Exception $e) {
    echo "Error " . $e->getCode() . ": " . $e->getMessage();
}


?>
