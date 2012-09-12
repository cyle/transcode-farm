/*

	FILE UPLOADER

*/

// stuff you might want to configure

var tmp_path = '/farm/upload_tmp/'; // where files are uploaded, but not where they end up
var this_host = 'farm.emerson.edu'; // the master host URL
var garbage_collection_interval = 120 * 60 * 1000; // when to run garbage collection

// end of stuff you might want to configure

var formidable = require('formidable'),
    http = require('http'),
    util = require('util'),
    fs = require('fs'),
    path = require('path'),
    url = require('url'),
    os = require('os');

var uploads = [];

var server = http.createServer(function(req, res) {
	//console.log('new request!');
	//console.log(util.inspect(req, false, null));
	var theurl = url.parse(req.url, true);
	//console.log(util.inspect(theurl, false, null));
	var theid = 0;
	var username = '';
	var email = '';
	if (theurl.query.id != undefined) {
		theid = theurl.query.id * 1;
	}
	if (theurl.query.un != undefined) {
		username = theurl.query.un;
	}
	if (theurl.query.em != undefined) {
		email = theurl.query.em;
	}
	if (theurl.pathname == '/upload.lol' && req.method.toLowerCase() == 'post') {
		if (theid <= 0) {
			res.writeHead(404, {'content-type': 'application/json'});
			res.end(JSON.stringify({ 'error': 'No ID given to upload.' }));
			return;
		}
		var d = new Date();
		console.log('new upload with temp id '+theid);
		console.log(d.toLocaleString());
		uploads[theid] = { 'brec': 0, 'btotal': 0, 'last': new Date(), 'uploaded': false };
		var form = new formidable.IncomingForm();
		form.keepExtensions = true;
		form.uploadDir = tmp_path;
		var files = [];
		var fields = [];
		form.on('progress', function(brec, btotal) {
			//console.log(brec + ' of ' + btotal + ' uploaded...');
			uploads[theid]['brec'] = brec;
			uploads[theid]['btotal'] = btotal;
			uploads[theid]['last'] = new Date();
		});
		form.on('file', function(name, file) {
			if (file.size > 0) {
				//console.log('new file detected');
				//console.log(util.inspect(file, false, null));
				files.push(file);
			}
		});
		form.on('field', function(name, value) {
			if (name != 'X-Requested-With' && value != '') {
				fields.push(value);
				//console.log('new field detected: '+name+': '+value);
			}
		});
		form.on('aborted', function() {
			console.log('oops, upload aborted by user!');
			res.writeHead(404, {'content-type': 'application/json'});
			res.end(JSON.stringify({ 'error': 'User aborted the upload!' }));
		});
		form.on('error', function(err) {
			console.log(err);
			res.writeHead(404, {'content-type': 'application/json'});
			res.end(JSON.stringify({ 'error': err }));
		});
		form.on('end', function() {
			//console.log(util.inspect({'fields': fields, 'files': files}));
			uploads[theid]['uploaded'] = true;			
			// ok so now parse through the files and put them in their own object/array
			var final_results = [];
			for (var i = 0; i < files.length; i++) {
				var temp_result = { 'name': '', 'path': '', 'username': username, 'email': email, 'presets': fields[0] };
				temp_result.name = files[i].name;
				temp_result.path = files[i].path;
				fs.chownSync(files[i].path, 33, 33);
				final_results.push(temp_result);
			}
			console.log(util.inspect(final_results, false, null));
			console.log('sending data to upload processing script...');
			var postdata = 'j='+JSON.stringify(final_results);
			var postlength = postdata.length;
			thepath = '/upload_process.php';
			var sendinfo_opts = { method: 'POST', path: thepath, host: this_host, port: 80, headers: { 'Content-Length': postlength, 'Content-Type': 'application/x-www-form-urlencoded' } };
			var sendinfo = http.request(sendinfo_opts, function(newres) {
				var data_result = '';
				newres.on('data', function(chunk) {
					data_result += chunk;
				});
				newres.on('end', function() {
					console.log('processing script returned: '+data_result);
					var returned_json = JSON.parse(data_result);
					console.log('returned info: ' + util.inspect(returned_json, false, null));
					res.writeHead(200, {'Content-Type': 'application/json'});
					res.end(JSON.stringify({'info': returned_json}));
					var goodbye = uploads.splice(theid,1);
					console.log('end of '+theid);
				});
			});
			sendinfo.write(postdata); 
			sendinfo.end();
		});
		form.parse(req);
	}  else if (theurl.pathname == '/status.lol') {
		// ok get status of the upload
		if (theid > 0) {
			// ok get status of that id
			if (uploads[theid] == undefined) {
				res.writeHead(404, {'content-type': 'application/json'});
				res.end(JSON.stringify({ 'error': 'The provided ID does not exist.' }));
			} else {
				res.writeHead(200, {'content-type': 'text/plain'});
				res.end(JSON.stringify({ 'bytesReceived': uploads[theid]['brec'], 'bytesTotal': uploads[theid]['btotal'], 'uploaded': uploads[theid]['uploaded'] }));
			}
		} else {
			res.writeHead(404, {'content-type': 'application/json'});
			res.end(JSON.stringify({ 'error': 'No ID given to check.' }));
		}
	} else {
		// uhh what do you want
		res.writeHead(404, {'content-type': 'application/json'});
		res.end(JSON.stringify({ 'error': 'No path or invalid path given.' }));
	}
});


// do some garbage collection if necessary
// removing old uploads
setInterval(function() { 
	var rightnow = new Date();
	for (var index in uploads) {
		if (uploads[index]['last'].getTime() < (rightnow.getTime() - garbage_collection_interval)) {
			//console.log('old id, '+index+', removing');
			var goodbye = uploads.splice(index,1);
		}
	}
}, 2000);


// ok ok ok
server.listen(8080);
console.log('Batch upload server running on :8080...');

