# The Open Transcode Farm - MongoDB Document Schema

This is how the documents in MongoDB "should" look.

## ENTRY (in db.entries)

	Array(
		
		'e' => true,							// whether it's enabled
		'un' => 'cyle_gage',					// user's account name
		'em' => 'cyle_gage@emerson.edu',		// user's email address
		'fn' => 'whatever.mp4',					// the original filename
		'tsc' => 1231231231, 					// when the entry was created
		'tsu' => 1221231231,					// when the entry was last updated
		'ex' => 1221231231,						// when the entry will expire and be deleted, if ever
		'du' => 120,							// the duration of the video (in seconds)
		'pa' => Array (							// path information for files
				'in' => '/files/in/...'			// the file's upload path
				'c' => Array(					// an array of versions for output
					[0] => Array(
						'p' => '/files/out/...'	// the path for this version
						'e' => true,			// whether it's done or not
						'vb' => 1200,			// video bitrate
						'ab' => 128,			// audio bitrate
						'vh' => 720,			// video height
						'vw' => 1280,			// video width
					)
				)
			)
	
	)

## FARMER (in db.farmers) 

	Array(
		
		'n' => 'Jack',				// friendly display name
		'hn' => 'farm-node-02',		// proper hostname
		'ip' => '199.94.92.91',		// ip address
		'e' => 1,					// enabled or not -- index'd
		'tsc' => 1344350435,		// when created
		'tsh' => 1344351333,		// last heartbeat -- index'd
		't' => 0,					// tier level of hardware, 0 is any input, 1 is low-end only (for VMs, for example)
		
	)

## JOB (in db.jobs) 

	Array(
		
		'eid' => MongoId('awdaw'),		// entry ID -- index'd
		'p' => 1,						// priority, lower is more important -- index'd
		'o' => 1,						// origin, if multiple masters are using this -- index'd
		's' => 1,						// current status code -- index'd
		'fid' => MongoId('901ao21j'),	// farmer mongo ID (if any)
		'in' => '/files/in..',			// file input
		'out' => '/files/out...',		// file output -- index'd
		'vw' => 1280,					// desired video max width
		'vh' => 720,					// desired video max height
		'vb' => 1200,					// desired video bitrate
		'ab' => 128,					// desired audio bitrate
		'tsc' => 1344333443,			// time created
		'tsu' => 1393939222,			// last updated -- index'd
		'm' => 'error info?',			// error message info, if needed
		'hl' => 181,					// how long it took to do (in seconds)
		
	)