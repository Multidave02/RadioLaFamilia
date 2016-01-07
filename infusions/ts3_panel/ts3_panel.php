<?php
// -------------------------------------------------------
// PHP-Fusion Content Management System
// Copyright (C) 2002 - 2008 Nick Jones
// http://www.php-fusion.co.uk/
// -------------------------------------------------------
// Author: Markus (HappyF)
// Web: www.xtc-radio.nl
// -------------------------------------------------------
// This program is released as free software under the
// Affero GPL license. You can redistribute it and/or
// modify it under the terms of this license which you
// can read by viewing the included agpl.txt or online
// at www.gnu.org/licenses/agpl.html. Removal of this
// copyright header is strictly prohibited without
// written permission from the original author(s).
// -------------------------------------------------------
if (!defined("IN_FUSION")) { die("Access Denied"); }
include INFUSIONS."ts3_panel/infusion_db.php";

$res_set = dbarray(dbquery("SELECT jquery, refresh FROM ".DB_TS3_CON.""));
	$this_jquery = $res_set['jquery'];
	$this_time = $res_set['refresh'];
	
openside("Teamspeak 3");

if($this_jquery == "1") { echo "<script src='http://code.jquery.com/jquery-latest.js'></script>"; }
echo "<script type='text/javascript'>
	$(document).ready(function() {
	 	$(\"#ts3_view\").load(\"".INFUSIONS."ts3_panel/ts3.info.php\");
	
		setInterval(function() {
    		$(\"#ts3_view\").load(\"".INFUSIONS."ts3_panel/ts3.info.php\");
		}, \"$this_time\");
	   	$.ajaxSetup({ cache: false });
	});
</script>";

echo "<div id='ts3_view' style='width: 161px;'></div>";

closeside();

?>