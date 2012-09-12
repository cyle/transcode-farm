# Open Transcoding Platform Installation - Master Instructions

Install Ubuntu 12.04 64bit Server

Once installed, log in via whatever user you created, and set up the root user.

	sudo passwd root

Give it a good password. Or don't. Up to you. 

Now log out of the server and log back in as root. It's easier this way.

Next, just generally update and upgrade the new install.

	apt-get update
	apt-get -y upgrade
	
Next, we'll install some repositories to get the latest copies of MongoDB, lighttpd, and PHP5.

	apt-get install python-software-properties
	add-apt-repository ppa:ondrej/php5
	add-apt-repository ppa:ondrej/common
	add-apt-repository ppa:nathan-renniewaldock/ppa
	
We have to do a little more to add MongoDB repos.

	apt-key adv --keyserver keyserver.ubuntu.com --recv 7F0CEB10
	nano /etc/apt/sources.list.d/10gen.list
	
Inside that file, add this line:

	deb http://downloads-distro.mongodb.org/repo/ubuntu-upstart dist 10gen
	
Ok, now:
	
	apt-get update
	
Cool. Now we can install the main stuff.

	apt-get -y install build-essential libssl-dev curl lighttpd php5-cgi php5-cli mongodb-10gen haproxy ffmpeg

And some PHP5 extensions:

	apt-get -y install php5-dev php5-curl php5-mcrypt php-pear
	pecl install mongo
	cat "extension=mongo.so" > /etc/php5/cgi/conf.d/mongo.ini
	pear install Mail Net_SMTP Auth_SASL

Now we need to tell lighttpd to use PHP5 via FastCGI:

	lighty-enable-mod
	
And type in "fastcgi fastcgi-php" and hit enter.
	
And we'll need to configure lighttpd to allow PHP to use its X-Sendfile functionality:

	nano /etc/lighttpd/conf-enabled/15-fastcgi-php.conf
	
Inside this file, add 
	
	"allow-x-send-file" => "enable",
	
Right below this line:

	"broken-scriptfilename" => "enable",
	
Now restart lighttpd:

	service lighttpd restart
	
Make sure your site is web-accessible and shows the default lighttpd landing page.

Magical. We'll need to actually reconfigure a bit of this later, but for now let's press on with installing prerequisites.

Now to install node.js...

	cd /usr/src
	wget nodejs latest (i.e. wget http://nodejs.org/dist/v0.8.8/node-v0.8.8.tar.gz)
	tar xfz node-???
	cd node-???
	./configure
	make
	make install

You should be able to run "node -v" and get the version number.

Now we can start downloading files
	
	mkdir /farm
	git clone [insert github readonly clone address here] /farm
	
Awesome! Now you should have a /farm directory with a bunch of subfolders. This is where all of the farm stuff lives.

Now we need to configure the server to use all of this... stuff!

Let's start with HAProxy, which manages the connections between lighttpd and node.js where necessary.

	cd /etc/haproxy
	mv haproxy.cfg haproxy.cfg.orig
	ln -s /farm/config/haproxy-farm.conf haproxy.cfg
	nano /etc/default/haproxy
	
In that file, change ENABLED to 1 instead of 0.

	service haproxy restart
	
Bam. Now, to move on to lighttpd.

	cd /etc/lighttpd
	nano lighttpd.conf
	
In this file, first remove the comment # in front of "mod_rewrite" if there is one.
Next, comment-out the "server.document-root" line by putting a # at the beginning of the line.
Next, at the bottom, add this line:

	include "/farm/config/lighty-farm.conf"
	
Okay, now restart lighttpd to take the new config:

	service lighttpd restart
	
Let's set up our MongoDB instance.

	mongo
	
Inside the MongoDB shell, do the following to set up the database, collections, and their indexes:

	use farm
	db.createCollection("entries")
	db.createCollection("jobs")
	db.createCollection("farmers")
	db.entries.ensureIndex({un:1})
	db.entries.ensureIndex({tsc:1})
	db.entries.ensureIndex({ex:1})
	db.jobs.ensureIndex({s:1})
	db.jobs.ensureIndex({out:1})
	db.jobs.ensureIndex({tsu:1})
	db.jobs.ensureIndex({p:1})
	db.jobs.ensureIndex({o:1})
	db.jobs.ensureIndex({hl:1})
	db.jobs.ensureIndex({eid:1})
	db.farmers.ensureIndex({e:1})
	db.farmers.ensureIndex({tsh:1})
	
We're not done yet. Now make sure the upload.js script has the latest version of the "formidable" module:

	cd /farm/node
	npm install formidable
	
Now let's make sure that upload.js script runs all the time.

	nano /etc/init/farm-upload.conf
	
Inside this file, paste this:

	description "farm upload nodejs"
	author      "cyle gage"
	start on started mountall
	stop on shutdown
	
	# Automatically Respawn:
	respawn
	respawn limit 99 5
	
	script
		export HOME="/root"
		exec /usr/local/bin/node /farm/node/upload.js >> /farm/logs/upload.log 2>&1
	end script
    
That's it. You should be able to start the upload.js script by doing this:

	start farm-upload
	
If you want to run the upload script in debug mode with console output, do this instead:

	node /farm/node/upload.js
	
Now let's set up some folder permissions:

	chown -R www-data:www-data /farm/upload_tmp
	chown -R www-data:www-data /farm/files
	chmod 775 www-data:www-data /farm/files
	
Let's add the "farmer" user that farmer nodes will be connecting and downloading/uploading files as:

	useradd -d /home/farmer -m -g www-data -s /bin/bash farmer
	passwd farmer
	
Give him a good password. Make sure you save it somewhere, you'll need it to connect farmer nodes.
	
Add cron jobs by editing crontab:

	crontab -e
	
And then at the bottom of the file, add:

	0 */2 * * * find /farm/upload_tmp/ -type f -mtime +1 -print0 | xargs -0 rm
	* * * * * php /farm/cron/remove_expired.php
	
The first line allows the server to delete old temporary files once every two hours if they are over a day old. A good idea.
The second line runs the remove_expired.php script, which removes expired entries, every minute. Maybe that can be less often, but do what you want.

Wrapping this up, you'll need to do a couple things:

	cd /farm/config
	cp config.example.php config.php
	nano config.php

Inside that file, configure the Farm options, like what URL this lives on, how to send mail, etc.

The last important step is building in some kind of login/accounts system. I cannot include the one I use because... well, it's proprietary to Emerson College. I'd like to make it open source sometime, but at the time of this writing, it's not.

So you'll notice that in most every script there's a require_once() for "login_check.php", which uses the $login_required variable. Before you can use this, you'll need to plug in some kind of login system, even if it's just this:

	<?php
	$current_user['loggedin'] = true;
	?>

Save that as login_check.php and put it in www-includes. That script will just allow anyone to use the system. Plug in your own system if you have one. But bottom line, you have to have something. The user tracking inside this is rudimentary right now, but it's something, at least.

You also should make login.php and logout.php scripts in /farm/www/ for your login system, even if they're just empty files.

That... should... be... it. See INSTALL_NODE to install some nodes and get things working!