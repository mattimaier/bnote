<?php

function getFileMimeType($file) {
	if (function_exists("finfo_file")) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
		$mime = finfo_file($finfo, $file);
		finfo_close($finfo);
		return $mime;
	} else if (function_exists("mime_content_type")) {
		return mime_content_type($file);
	} else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
		$file = escapeshellarg($file);
		$mime = shell_exec("file -bi " . $file);
		return $mime;
	} else {
		return false;
	}
}

?>