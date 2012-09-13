# Open Transcoding Platform

### made by Cyle Gage for Emerson College (and the world)

The Open Transcoding Platform is a basic farm of nodes that can transcode video to different formats. People can upload video files, which are then transcoded into smaller web-friendly versions for HTML5/Flash Media Server playback.

There is one "master" and at least one "farmer". The master is the web frontend and controller. The farmers simply ask for files to transcode.

This is based entirely on Median's original transcode farm, which I wrote, except this lets you download the end results. This platform is more of an entry point with the difficult video transcoding part done for you, it's up to you to fit this into an existing system which would require it. It is important to note that there is no login system included with this, but there are hooks for it.

Median actually uses this software to transcode different versions of uploaded videos, utilizing 12+ farmers, which range from dedicated IBM Blades to vSphere VMs. I've included a "tier" system in the Farmer schema to differentiate fast encoders and slow encoders if you want to.

Also, just to give credit where it's due, the web interfaces uses the [Foundation framework](http://foundation.zurb.com/) to make it very browser-friendly.

## Technology Required

### For master

- Debian-based distro (Ubuntu 12.04 Server used in production)
- lighttpd 1.4.28+ (1.4.31 used in production)
- PHP 5.3+ (5.4.6 used in production)
- MongoDB 2.0+ (2.2.0 replica set used in production)
- node.js 0.8+ (0.8.8 used in production)
- npm 1.1+ (1.1.59 used in production)
- node module formidable 1.0+ (1.0.11 used in production)
- enough disk space to temporarily store files (250GB+ recommended)

### For farmer node

- Debian-based distro (Ubuntu 11.10 and 12.04 used in production)
- node.js 0.8+ (0.8.8 used in production)
- HandBrakeCLI 0.9+ (0.9.8 used in production)
- enough disk space to temporarily store files (10GB+ recommended)

## Installing

Detailed installation instructions can be found in the /docs/ folder.

## Learning More

Read all the information in the /docs/ folder.

## Features yet to be done

* end-user API (not the Farmer API)
* Help files for the web interface (are they needed?)

## License

I've used the MIT License for this, because why not. I encourage you to let me know if you're using this, and how you're using this, mostly for my own curiousity. My email is cyle_gage@emerson.edu or cylegage@gmail.com.