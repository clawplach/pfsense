<?php
/* $Id$ */
/*
	crash_reporter.php
	part of pfSense
	Copyright (C) 2011 Scott Ullrich
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
/*
	pfSense_MODULE:	header
*/

##|+PRIV
##|*IDENT=page-diagnostics-crash-reporter
##|*NAME=Crash reporter
##|*DESCR=Uploads crash reports to pfSense and or deletes crash reports.
##|*MATCH=crash_reporter.php*
##|-PRIV

require("guiconfig.inc");
require("functions.inc");
require("captiveportal.inc");

function upload_crash_report($files) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_URL, "crashreporter.pfsense.org/submit.php");
    curl_setopt($ch, CURLOPT_POST, true);
    // same as <input type="file" name="file_box">
	$post = array();
	$counter = 0;
	foreach($files as $file) {
		$tmp = array();
		$tmp["file{$counter}"] = "@{$file}";
		$post[] = $tmp;
		$counter++;
	}
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    $response = curl_exec($ch);
	return $response;
}

function output_crash_reporter_html($crash_reports) {
	echo "<strong>" . gettext("Unfortunately we have detected a kernel crash (panic).") . "</strong></p>";
	echo "<strong>" . gettext("Would you like to submit the crash debug logs to the pfSense developers for inspection?") . "</strong></p>";
	echo "<p>Contents of crash reports:<br/>";
	echo "<textarea name='crashreports'>{$crash_reports}</textarea>";
	echo "<p/>";
	echo "<input name=\"Submit\" type=\"submit\" class=\"formbtn\" value=\"" . gettext("Yes") .  ">";
	echo "<input name=\"Submit\" type=\"submit\" class=\"formbtn\" value=\"" . gettext("No") .  ">";
	echo "</p>";
	echo "</form>";
}

$pgtitle = array(gettext("Diagnostics"),gettext("Crash reporter"));
include('head.inc');

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<?php include("fbegin.inc"); ?>

	<form action="crash_reporter.php" method="post">
		<p>

<?php
	if ($_POST['Submit'] == "Yes") {
		echo gettext("Processing...");
		exec("/usr/bin/gzip /var/crash/*");
		$files_to_upload = glob("/var/crash/*");
		echo "<p/>";
		echo gettext("Uploading...");
		echo "<p/>";
		if(is_array($files_to_upload)) {
			upload_crash_report($files_to_upload);
			exec("rm /var/crash/*");
			echo gettext("Crash files have been submitted for inspection.");
			echo "<p/><a href='/'>" . gettext("Continue") . "</a>";
		} else {
			echo "Could not find any crash files.";
		}
	} else if($_POST['Submit'] == "No") {
		exec("rm /var/crash/*");
		Header("Location: /");
		exit;
	} else {
		$crash_files = glob("/var/crash/*");
		if(is_array($crash_files))			
			foreach($crash_files as $cf) 
				$crash_reports .= file_get_contents($cf);
		else 
			echo "Could not locate any crash data.";
		output_crash_reporter_html($crash_reports);
	}
?>

<?php include("fend.inc"); ?>

</body>
</html>