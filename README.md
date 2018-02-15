# TSChannelSort
Small php TeamSpeak script to log into your Teamspeak server using the ts3phpframework.

1) Have php >= v5.5 installed.
2) Place the ts3phpframework folder in the same folder as this script.
3) Edit the config file in the configs folder as needed to allow for the connection to the Teamspeak server.
4) Edit the **$protectedChannelUIDs** array to include all the channels you don't want to be included in, in the sorting mechanism.
4) Edit the **$defaultChannels** array to include default channels like lobby and *spacer* channels that you don't want to be included in, in the sorting mechanism.
5) Edit the **$alphabetChannels** array with the ID's of your Alphabet channels as needed. These need to already exist and marked as permanent. 
6) Run the script and watch as all the channels are sorted alphabetically into the parent channels.

TO-DO:
Sort channels within the parent channel structure.
