<?php
/*---------------------------------------------------------------------------+
| Pimped-Fusion Content Management System
| Copyright (C) 2009 - 2010
| http://www.pimped-fusion.net
+----------------------------------------------------------------------------+
| Filename: forum_ranks.php
| Version: Pimped Fusion v0.09.00
+----------------------------------------------------------------------------+
| based on PHP-Fusion CMS v7.01 by Nick Jones
| http://www.php-fusion.co.uk/
+----------------------------------------------------------------------------+
| This program is released as free software under the Affero GPL license.
| You can redistribute it and/or modify it under the terms of this license
| which you can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this copyright header is
| strictly prohibited without written permission from the original author(s).
+---------------------------------------------------------------------------*/
require_once "../maincore.php";
require_once TEMPLATES."admin_header.php";
include LOCALE.LOCALESET."admin/forum_ranks.php";

if (!checkrights("FR") || !defined("iAUTH") || $_GET['aid'] != iAUTH) { redirect("../index.php"); }

if (isset($_GET['status']) && !isset($message)) {
	if ($_GET['status'] == "sn") {
		$message = $locale['410'];
	} elseif ($_GET['status'] == "su") {
		$message = $locale['411'];
	} elseif ($_GET['status'] == "del") {
		$message = $locale['412'];
	} elseif ($_GET['status'] == "se") {
		$message = $locale['413'];
	}
	if (isset($message)) { echo "<div id='close-message'><div class='admin-message'>".$message."</div></div>\n"; }
}

if ($settings['forum_ranks']) {
	if (isset($_POST['save_rank'])) {
		$rank_title = stripinput($_POST['rank_title']);
		$rank_image = stripinput($_POST['rank_image']);
		$rank_posts = isnum($_POST['rank_posts']) ? $_POST['rank_posts'] : 0;
		$rank_apply = isnum($_POST['rank_apply']) ? $_POST['rank_apply'] : nMEMBER;
		
		$no_groups = array(nMEMBER, nMODERATOR, nADMIN, nSUPERADMIN); // Pimped ->
		if (in_array($rank_apply, $no_groups)) {
		$rank_group = "0";
		} else {
		$rank_group = $rank_apply;
		$rank_apply = nMEMBER;
		} // Pimped <-
		
		if ($rank_title) {
			if (isset($_GET['rank_id']) && isnum($_GET['rank_id'])) {
				$data = dbarray(dbquery("SELECT rank_apply, rank_group FROM ".DB_FORUM_RANKS." WHERE rank_id='".$_GET['rank_id']."'")); // Pimped
				if (($rank_apply > nMEMBER && $rank_apply != $data['rank_apply']) && (dbcount("(rank_id)", DB_FORUM_RANKS, "rank_id!='".$_GET['rank_id']."' AND rank_apply='".$rank_apply."'"))) {
					redirect(FUSION_SELF.$aidlink."&status=se");
				} elseif (($rank_group > 0 && $rank_group != $data['rank_group']) && (dbcount("(rank_id)", DB_FORUM_RANKS, "rank_id!='".$_GET['rank_id']."' AND rank_group='".$rank_group."'"))) { // Pimped
					redirect(FUSION_SELF.$aidlink."&status=se&group"); // Pimped
				} else {
					$result = dbquery("UPDATE ".DB_FORUM_RANKS." SET rank_title='$rank_title', rank_image='$rank_image', rank_posts='$rank_posts', rank_apply='$rank_apply', rank_group='$rank_group' WHERE rank_id='".$_GET['rank_id']."'"); // Pimped
					log_admin_action("admin-2", "admin_forumrank_changed"); // Log Admin's Action
					redirect(FUSION_SELF.$aidlink."&status=su");
				}
			} else {
				if ($rank_apply > nMEMBER && dbcount("(rank_id)", DB_FORUM_RANKS, "rank_apply='".$rank_apply."'")) {
					redirect(FUSION_SELF.$aidlink."&status=se");
				} elseif ($rank_group > 0 && dbcount("(rank_id)", DB_FORUM_RANKS, "rank_group='".$rank_group."'")) { // Pimped
					redirect(FUSION_SELF.$aidlink."&status=se&group"); // Pimped
				} else {
					$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." (rank_title, rank_image, rank_posts, rank_apply, rank_group) VALUES ('$rank_title', '$rank_image', '$rank_posts', '$rank_apply', '$rank_group')"); // Pimped
					log_admin_action("admin-2", "admin_forumrank_added"); // Log Admin's Action
					redirect(FUSION_SELF.$aidlink."&status=sn");
				}
			}
		} else {
			redirect(FUSION_SELF.$aidlink);
		}
	} else if (isset($_GET['delete']) && isnum($_GET['delete'])) {
		$result = dbquery("DELETE FROM ".DB_FORUM_RANKS." WHERE rank_id='".$_GET['delete']."'");
		log_admin_action("admin-2", "admin_forumrank_removed"); // Log Admin's Action
		redirect(FUSION_SELF.$aidlink."&status=del");
	}

	if (isset($_GET['rank_id']) && isnum($_GET['rank_id'])) {
		$result = dbquery("SELECT rank_id, rank_title, rank_image, rank_posts, rank_apply, rank_group FROM ".DB_FORUM_RANKS." WHERE rank_id='".(int)$_GET['rank_id']."'");
		if (dbrows($result)) {
			$data = dbarray($result);
			$rank_title = $data['rank_title'];
			$rank_image = $data['rank_image'];
			$rank_posts = $data['rank_posts'];
			$rank_apply = $data['rank_apply'];
			$rank_group = $data['rank_group']; // Pimped
			$form_action = FUSION_SELF.$aidlink."&amp;rank_id=".$_GET['rank_id'];
			opentable($locale['401']);
		} else {
			redirect(FUSION_SELF.$aidlink);
		}
	} else {
		$rank_title = "";
		$rank_image = "";
		$rank_posts = "0";
		$rank_apply = nMEMBER; // Pimped: 101
		$rank_group = "0"; // Pimped
		$form_action = FUSION_SELF.$aidlink;
		opentable($locale['400']);
	}
	echo "<form name='rank_form' method='post' action='".$form_action."'>\n";
	echo "<table cellpadding='0' cellspacing='0' class='center'>\n<tr>\n";
	echo "<td class='tbl'>".$locale['420']."</td>\n";
	echo "<td class='tbl'><input type='text' name='rank_title' value='".$rank_title."' class='textbox' style='width:150px;' /></td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td class='tbl'>".$locale['421']."</td>\n";
	echo "<td class='tbl'><select name='rank_image' class='textbox' style='width:150px;'>\n";
	$image_files = makefilelist(IMAGES."ranks", ".|..|index.php", true);
	echo makefileopts($image_files, $rank_image)."</select></td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td class='tbl'>".$locale['422']."</td>\n";
	echo "<td class='tbl'><input type='text' name='rank_posts' value='".$rank_posts."' class='textbox' style='width:150px;' /></td>\n";
	echo "</tr>\n<tr>\n";
	echo "<td class='tbl'>".$locale['423']."</td>\n";
	echo "<td class='tbl'><select name='rank_apply' class='textbox' style='width:150px;'>\n"; // Pimped: Special Group Ranks
	##
	
	$opts = ""; $sel = "";
	#$user_groups = getusergroups(0,1,1,1,1,0,1,array('104', $locale['425'])); #what to do with the mods/global mods
	$user_groups = getusergroups(0,1,1,1,1,0,1);
	while(list($key, $user_group) = each($user_groups)){
	
		if($rank_group != "0") {
		$sel = ($rank_group == $user_group['0'] ? " selected='selected'" : "");
		} else {
		$sel = ($rank_apply == $user_group['0'] ? " selected='selected'" : "");
		}
		$opts .= "<option value='".$user_group['0']."'$sel>".$user_group['1']."</option>\n";

	}
	
	echo $opts;
	
	#echo "<option value='101'".($rank_apply == 101 ? " selected='selected'" : "").">".$locale['424']."</option>\n";
	#echo "<option value='104'".($rank_apply == 104 ? " selected='selected'" : "").">".$locale['425']."</option>\n";
	#echo "<option value='102'".($rank_apply == 102 ? " selected='selected'" : "").">".$locale['426']."</option>\n";
	#echo "<option value='103'".($rank_apply == 103 ? " selected='selected'" : "").">".$locale['427']."</option>\n";
	
	##
	echo "</select></td>\n</tr>\n<tr>\n";
	echo "<td align='center' colspan='2' class='tbl'>\n";
	echo "<input class='button' type='submit' name='save_rank' value='".$locale['428']."' /></td>\n";
	echo "</tr>\n</table>\n</form>\n";
	closetable();

	opentable($locale['402']);
	$result = dbquery("SELECT rank_id, rank_title, rank_image, rank_posts, rank_apply, rank_group FROM ".DB_FORUM_RANKS." ORDER BY rank_apply DESC, rank_group DESC, rank_posts"); // Pimped
	if (dbrows($result)) {
		echo "<table cellpadding='0' cellspacing='1' width='500' class='tbl-border center'>\n<tr>\n";
		echo "<td align='center' width='1%' class='tbl2' style='white-space:nowrap'><strong>#</strong></td>\n";
		echo "<td class='tbl2'><strong>".$locale['430']."</strong></td>\n";
		echo "<td width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['431']."</strong></td>\n";
		echo "<td width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['432']."</strong></td>\n";
		echo "<td width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['433']."</strong></td>\n";
		echo "<td align='center' width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['434']."</strong></td>\n";
		echo "</tr>\n";
		$i = 0;
		while ($data = dbarray($result)) {
			$row_color = ($i % 2 == 0 ? "tbl1" : "tbl2");
			echo "<tr>\n";
			echo "<td align='center' width='1%' class='".$row_color."' style='white-space:nowrap'>".($i + 1)."</td>\n";
			echo "<td class='".$row_color."'>".$data['rank_title']."</td>\n";
			echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'>";
			if($data['rank_group'] != "0") { // Pimped
				echo "Group: ".getgroupname($data['rank_group']);
			} elseif ($data['rank_apply'] == nMEMBER) {
				echo $locale['424'];
			} elseif ($data['rank_apply'] == nADMIN) {
				echo $locale['426'];
			} elseif ($data['rank_apply'] == nSUPERADMIN) {
				echo $locale['427'];
			} elseif ($data['rank_apply'] == nMODERATOR) {
				echo $locale['425'];
			}
			echo "</td>\n";
			echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'><img src='".IMAGES."ranks/".$data['rank_image']."' alt='' style='border:0;' /></td>\n";
			echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'>".$data['rank_posts']."</td>\n";
			echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'><a href='".FUSION_SELF.$aidlink."&amp;rank_id=".$data['rank_id']."'>".$locale['435']."</a> ::\n";
			echo "<a href='".FUSION_SELF.$aidlink."&amp;delete=".$data['rank_id']."'>".$locale['436']."</a></td>\n";
			echo "</tr>\n";
			$i++;
		}
		echo "</table>";
	} else {
		echo "<div style='text-align:center'>".$locale['437']."</div>\n";
	}
	closetable();
} else {
	opentable($locale['403']);
	echo "<div style='text-align:center'>\n".sprintf($locale['450'], "<a href='settings_forum.php".$aidlink."'>".$locale['451']."</a>")."</div>\n";
	closetable();
}

require_once TEMPLATES."footer.php";
?>