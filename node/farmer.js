/*


    FARMER NODE

*/

// stuff you might want to configure

var debugmode = false; // use this when not running as daemon to debug this
var master_url = 'farm.emerson.edu'; // the URL to the master server
var scpserver = 'farmer@farm'; // the farmer user and server hostname of the master
var workdir = '/farm/stuff/'; // where to store video files temporarily on this machine, needs "in" and "out" subfolders
var logdir = '/farm/logs/'; // where to store logs on this machine
var heartbeat_interval = 5 * 60 * 1000; // how long between heartbeats (five minutes)
var cycle_interval = 60 * 1000; // how long between asking for a new job (one minute)

// end of stuff you might want to configure

// required stuff
var fs = require('fs');
var spawn = require('child_process').spawn;
var http = require('http');
var util = require('util');
var path = require('path');

function logger(wut, thelog) {
	if (debugmode) console.log(wut);
	thelog.write(wut+"\n");
}

var job_data = { 'jobs': 0 };
var working = false;

var hbstream_pattern = /(hb_stream_open:)(.+)(failed)/mgi;
var zeropercent_pattern = /( 0.00 %)/gmi;
var encoding_pattern = /Encoding: /gi;

var nodelog = fs.createWriteStream(logdir+'farmer.log', { flags: 'a', mode: 0660 });
var logfile;
var rightnow = new Date();
logger('=== farmer starting at '+rightnow.toString(), nodelog);

if (debugmode) console.log('lol, starting');

function heartbeat() {
	var heart_options = {
		host: master_url,
		port: 80,
		path: '/farmer/checkin/'
	};
	http.get(heart_options, function(res) {
		// ok, sent
		if (debugmode) console.log('sent heartbeat!');
	}).on('error', function(e) {
		//console.log("Got error: " + e.message);
		logger('oh god, error trying to send heartbeat to master', nodelog);
	});
}


function cycle() {
	if (working) {
		return;
	}
	var get_options = {
		host: master_url,
		port: 80,
		path: '/farmer/getjob/'
	};
	if (debugmode) console.log('farmer, lookin for a job...');
	http.get(get_options, function(res) {
		//if (debugmode) console.log("Got response: " + res.statusCode);
		if (res.statusCode != 200) {
			//console.log('uh oh!');
		} else {
			var data_result = '';
			res.on('data', function(chunk) {
				data_result += chunk;
			});
			res.on('end', function() {
				if (debugmode) console.log('server returned: '+data_result);
				if (data_result == 'error') {
					logger('oh god, error trying to get job from master', nodelog);
				} else {
					eval('job_data = (' + data_result + ')');
					if (job_data.jobs > 0) {
						console.log('got a job! job id ' + job_data.jid);
						working = true;
						doJob(job_data);
					} else {
						if (debugmode) console.log('no jobs, or not allowed');
					}
				}
			});
		}
	}).on('error', function(e) {
		//console.log("Got error: " + e.message);
		logger('oh god, error trying to get job from master', nodelog);
	});
}


function doJob(job) {
	if (debugmode) console.log('job data: '+job);
	logfile = fs.createWriteStream(logdir+'job_'+job.jid+'.log', { flags: 'a', mode: 0660 });
	var rightnow = new Date();
	var zeropercent_count = 0;
	var error = false;
	var error_msg = '';
	logger('new job at ' + rightnow.toString(), logfile);
	logger('job data: ' + util.inspect(job), logfile);
	var scp_inbound = scpserver+':'+job['in'];
	if (debugmode) console.log('inbound scp: '+scp_inbound);
	var scp_in = spawn('scp', [ scp_inbound, workdir+'in']);
	scp_in.stderr.on('data', function (data) {
		logger('scp_in stderr: ' + data, logfile);
	});
	scp_in.on('exit', function (code) {
		logger('scp_in exited with code: ' + code, logfile);
		if (code == 0) {
			var inpath = workdir+'in/' + path.basename(job['in']);
			var outpath = workdir+'out/' + job.mid + '.mp4';
			//console.log('file should be at '+localpath);
			// got the file...... start handbrake....
			var hb = spawn('HandBrakeCLI', ['-i', inpath, '-o', outpath, '-e', 'x264', '-E', 'faac', '-b', job.vb, '-B', job.ab, '-2', '-T', '-6', 'stereo', '-R', '44.1', '-X', job.vw, '-Y', job.vh]);
			hb.stdout.on('data', function (data) {
				logger('hb stdout: ' + data, logfile);
				var currentline = '' + data + '';
				// this is where i'd need to catch the "0.00 %" bug -- allow maybe 25 of them before aborting
				// 0.00 % is due to handbrake not knowing what the fuck the file is
				if (zeropercent_count == 75) {
					logger('too many zero percents!', logfile);
					error = true;
					error_msg = 'There was an error transcoding: too many 0.00%.';
					hb.kill();
				} else if (zeropercent_pattern.test(currentline)) {
					zeropercent_count++;
				}
				// stdout looks like this: Encoding: task 2 of 2, 97.28 % (11.86 fps, avg 5.93 fps, ETA 00h00m11s)
				if (encoding_pattern.test(currentline)) {
					job_data.hbinfo = currentline;
				}
			});
			hb.stderr.on('data', function (data) {
				logger('hb stderr: ' + data, logfile);
				var currentline = '' + data + '';
				// this is where i'd need to catch the "hb_stream_open: open ...(path)... failed" bug
				// dunno why this bug happens...?
				if (hbstream_pattern.test(currentline)) {
					// abort...
					logger('hb_stream_open failure!!!!', logfile);
					error = true;
					error_msg = 'There was an error transcoding: hb_stream_open failure.';
					hb.kill();
				}
			});
			hb.on('exit', function (code) {
				logger('hb exited with code: ' + code, logfile);
				if (code == 0) {
					if (error) {
						// there was an error...
						logger('there was an error with encoding!', logfile);
						if (error_msg == '') {
							error_msg = 'There was an error transcoding the file.';
						}
						sendStatus(job.jid, 3, error_msg);
						fs.unlink(inpath);
						fs.unlink(outpath);
					} else {
						// no error; send file back to master.......
						logger('sending it back to master!', logfile);
						var scp_outbound = scpserver+':'+job['out'];
						if (debugmode) console.log('outbound scp: '+scp_outbound);
						var scp_out = spawn('scp', [outpath, scp_outbound]);
						scp_out.stdout.on('data', function (data) {
							logger('scp_out stdout: ' + data, logfile);
						});
						scp_out.stderr.on('data', function (data) {
							logger('scp_out stderr: ' + data, logfile);
						});
						scp_out.on('exit', function (code) {
							logger('scp_out exited with code: ' + code, logfile);
							if (code == 0) {
								sendStatus(job.jid, 2);
								fs.unlink(inpath);
								fs.unlink(outpath);
							} else {
								// error with scp_out exit
								logger('there was an error with copying the file out!', logfile);
								sendStatus(job.jid, 3, 'There was an error copying the file out to master.');
								fs.unlink(inpath);
							} // end of scp_out exit code if
						}); // end scp_out process on exit
					} // end if error
				} else {
					// error with handbrake exit
					logger('there was an error with handbrake!', logfile);
					sendStatus(job.jid, 3, 'There was an error transcoding the file.');
					// remove local copies of media
					fs.unlink(inpath);
				} // end of handbrake exit code if
			}); // end handbrake process on exit
		} else {
			// error with scp_in exit
			logger('there was an error with copying the file in!', logfile);
			sendStatus(job.jid, 3, 'There was an error copying the file to the farmer.');
		} // end of scp_in exit code if
	}); // end of scp_in process on exit
} // end doJob()

function sendStatus(jid, status, message) {
	// send job status to master
	// with error message if need be
	
	var result = { };
	result.jid = jid;
	result.s = status * 1;
	if (message != undefined && message != '') {
		result.m = message;
	}
	var postdata = 'j='+JSON.stringify(result);
	if (debugmode) console.log('sending POST: '+postdata);
	var postlength = postdata.length;
	var sendinfo_opts = { method: 'POST', path: '/farmer/setjob/', host: master_url, port: 80, headers: { 'Content-Length': postlength, 'Content-Type': 'application/x-www-form-urlencoded' } };
	var sendinfo = http.request(sendinfo_opts, function(newres) {
		//console.log('sent to batch temp!');
		var data_result = '';
		newres.on('data', function(chunk) {
			//console.log('from batch temp: '+chunk+'');
			data_result += chunk;
		});
		newres.on('end', function() {
			if (data_result != 'ok') {
				logger("Got error back from updating status: " + data_result, logfile);
			} else {
				logger('OH MY GOD DONE WITH THAT JOB', logfile);
			}
			logfile.end();
			logfile.destroySoon();
			working = false;
			job_data = { 'jobs': 0 };
		});
	});
	sendinfo.write(postdata); 
	sendinfo.end();
}


http.createServer(function (req, res) {
	res.writeHead(200, {'Content-Type': 'text/plain'});
	res.end(JSON.stringify(job_data));
}).listen(80);
logger('http server started...', nodelog);

// every 60 seconds, if it's not already working on something, check if there's something new to work on
setInterval(cycle, cycle_interval);
//cycle();

// every five minutes, send a heartbeat to the server....
setInterval(heartbeat, heartbeat_interval);
heartbeat();

//console.log('farming cycle started...');
logger('farming cycle started...', nodelog);
