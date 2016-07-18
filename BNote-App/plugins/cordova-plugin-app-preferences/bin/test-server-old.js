var http = require ('http');
var url  = require ('url');
var fs   = require ('fs');
var path = require ('path');

var config = {};
try {config = require ('./test-server.json');} catch (e) {}

var baseDirectory = __dirname;   // or whatever base directory you want

http.createServer (function (request, response) {
	var requestUrl = url.parse (request.url);
	var fsPath = baseDirectory + requestUrl.pathname;

	var mimeTypes = {
		'.html': "text/html",
		'.css':  "text/css",
		'.js':   "text/javascript",
		'.png':  "image/png",
		'.jpg':  "image/jpeg"
	};

	function finish (statusCode, headers) {
		response.writeHead (statusCode, headers);
		response.end;
	}

	fs.stat (fsPath, function (err, stats) {

		var statusCode = 200;
		var headers = {};

		if (err) {
			finish (err.code === 'ENOENT' ? 404 : err.code === 'EPERM' ? 403 : 500);
		} else if (stats.isDirectory()) {
			finish (303, {location: path.join (request.url, 'index.html')});
		} else if (!stats.isFile()) {
			finish (500);
		} else {
			response.writeHead (200, {contentType: mimeTypes [path.extname (fsPath).toLowerCase()]});

			var fsStream = fs.createReadStream (fsPath);
			fsStream.on ('error', function () {response.end()});
			// fsStream.on ('end',   function () {response.end()});
			fsStream.pipe (response);
		}


	})
}).listen(9615);
