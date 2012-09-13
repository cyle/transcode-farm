# Open Transcoding Platform Installation Instructions

The instructions I've written are fairly straightforward, but you need to have a bit of systems/programming experience to be able to tackle this properly. You should be comfortable in a linux command line, be knowledgable of node.js, php, mongodb, etc.

First, you need to set up the MASTER server, which will serve to hold the files, the controlling structure, and maybe even the database. (I'd suggest putting the database elsewhere, but these instructions assume you're not.)

Then you can set up NODE servers to connect to the MASTER server and transcode files.

See files INSTALL_MASTER and INSTALL_NODE, respectively.