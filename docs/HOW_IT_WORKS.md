# The Open Transcoding Platform - How It Works

It's pretty magical when it's all working. I built this from scratch because I couldn't find anything that could work like this.

First of all, the basic architecture is one MASTER server and many NODES that connect and interact with the MASTER server.

The master server holds all the files, both the intake uploaded files and the outbound downloadable versions. It also holds the web interface for end-users, which includes a basic API for any NODE to interface with.

The connection and reliance between the master server and its nodes is one-way. The master does not check-in on the nodes, the nodes check-in with the master. The nodes download and upload files via scp without having to mount the central filesystem. This way, anything can happen to the master and the nodes will be fine, and anything can happen to the nodes and the master won't care. The nodes check in every five mintues with the master, but that's just informational.

However, of course, in order for the jobs to get done the master needs to rely on the nodes. They're just not locked together. 

## The Overall Experience

1. All web traffic to the master hits HAProxy on port 80. It is configured by default to send traffic to lighttpd running on port 8000.
1. User uploads file(s) to the Farm. This is through the jQuery iframe transport plugin.
1. The file(s) go to /upload.lol, which HAProxy knows should go to node.js running on port 8080.
1. The upload.js file, which is running on the server as a service, takes the incoming file and puts it in the "upload_tmp" folder, which the server cleans once in awhile via a CRON job.
1. Once finished, the upload.js file sends the information about the files to upload_process.php via HTTP.
1. upload_process.php takes the file information, analyzes the video, determines what versions to transcode, enters this into the MongoDB database, and hands the result via JSON back to upload.js
1. upload.js hands the result via JSON back to the end user's jQuery instance, which then puts the results on the upload page.
1. The user is done. They now wait.
1. While all of this is going on, every sixty seconds, nodes are asking (through the farmer.js service running on them) the master server for a job (if they are not already doing one) via the API inside the /farmer web directory.
1. When there is a job available, the job information (where the file is, how to transcode it, etc) is given to the node via JSON.
1. The farmer.js process then downloads the file from the master via scp, opens HandBrakeCLI, transcodes the video as per the instructions, and sends the file back to the master server via scp.
1. If there is an error at any time, it sends via the master farmer API the error and informs the admins of the farm. I've built-in detection for weird HandBrake bugs like staying at 0.00% forever, and some others.
1. Once the job is complete, it sends via the master farmer API that it is done. The master records how long it took.
1. The master server informs the user via email (if an email was given) that a version of their video is done. Users can come to the site and download the versions. They are automatically deleted after 48 hours by default when all versions of a file are complete.
1. This process repeats... FOREVER.

No, but really, that's it.

## Why HAProxy?

Browsers don't like uploading content to different domains or even different ports on the same domain, so it's useful to allow HAProxy to split up where things go based on URL. It has not produced any noticable slowdowns in any of my applications where it's in use.

## Why node.js?

It's awesome, and huge-file uploads are hardly possible with much else, in my experience.

## Why HandBrake?

It's free and super easy and supports a lot of video codecs. However, you could switch it out for whatever you want by editing the farmer.js file.