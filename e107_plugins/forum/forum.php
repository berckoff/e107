<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Forum main page
 *
*/
if(!defined('e107_INIT'))
{
	require_once('../../class2.php');
}
$e107 = e107::getInstance();
$tp = e107::getParser();
$sql = e107::getDb();

if (!$e107->isInstalled('forum'))
{
	// FIXME GLOBAL - get rid of all e_BASE|e_HTTP|Whatever/index.php - just point to SITEURL
	header('Location: '.SITEURL);
	exit;
}
e107::lan('forum', "front", true);
// include_lan(e_PLUGIN.'forum/languages/'.e_LANGUAGE.'/lan_forum.php'); // using English_front.php now

require_once(e_PLUGIN.'forum/forum_class.php');
$forum = new e107forum;

if ($untrackId = varset($_REQUEST['untrack']))
{
	$forum->track('del', USERID, $untrackId);
	header('location:'.$e107->url->create('forum/thread/track', array(), 'full=1&encode=0'));
	exit;
}

if(isset($_GET['f']))
{
	if(isset($_GET['id']))
	{
		$id = (int)$_GET['id'];
	}

	switch($_GET['f'])
	{
		case 'mfar':
			$forum->forumMarkAsRead($id);
			header('location:'.e_SELF);
			exit;
			break;

		case 'rules':
			include_once(HEADERF);

			forum_rules('show');
			include_once(FOOTERF);
			exit;
			break;
	}
}
$fVars = new e_vars;
$gen = new convert;

$fVars->FORUMTITLE = LAN_PLUGIN_FORUM_NAME;
$fVars->THREADTITLE = LAN_FORUM_0002;
$fVars->REPLYTITLE = LAN_FORUM_0003;
$fVars->LASTPOSTITLE = LAN_FORUM_0004;
$fVars->INFOTITLE = LAN_FORUM_0009;
$fVars->LOGO = IMAGE_e;
$fVars->NEWTHREADTITLE = LAN_FORUM_0075;
$fVars->POSTEDTITLE = LAN_FORUM_0074; 
$fVars->NEWIMAGE = IMAGE_new_small;
$fVars->TRACKTITLE = LAN_FORUM_0073;

$rules_text = forum_rules('check');

$fVars->USERINFO = "<a href='".e_BASE."top.php?0.top.forum.10'>".LAN_FORUM_0010."</a> | <a href='".e_BASE."top.php?0.active'>".LAN_FORUM_0011."</a>";
if(USER)
{
	$fVars->USERINFO .= " | <a href='".e_BASE.'userposts.php?0.forums.'.USERID."'>".LAN_FORUM_0012."</a> | <a href='".e_BASE."usersettings.php'>".LAN_FORUM_0013."</a> | <a href='".e_HTTP."user.php ?id.".USERID."'>".LAN_FORUM_0014."</a>";
	if($forum->prefs->get('attach') && (check_class($pref['upload_class']) || getperms('0')))
	{
		$fVars->USERINFO .= " | <a href='".e_PLUGIN."forum/forum_uploads.php'>".LAN_FORUM_0015."</a>";
	}
}
if(!empty($rules_text))
{
	$fVars->USERINFO .= " | <a href='".e107::url('forum','rules')."'>".LAN_FORUM_0016.'</a>';
}


// v2.x --------------------
$uInfo = array();
$uInfo[0] = "<a href='".e107::url('forum','stats')."'>".LAN_FORUM_6013.'</a>';

if(!empty($rules_text))
{
	$uInfo[1] = "<a href='".e107::url('forum','rules')."'>".LAN_FORUM_0016.'</a>';
}

$fVars->USERINFOX = implode(" | ",$uInfo);
// -----------




$total_topics = $sql->count("forum_thread", "(*)");
$total_replies = $sql->count("forum_post", "(*)");
$total_members = $sql->count("user");
$newest_member = $sql->select("user", "*", "user_ban='0' ORDER BY user_join DESC LIMIT 0,1");
list($nuser_id, $nuser_name) = $sql->fetch(); // FIXME $nuser_id & $user_name return empty even though print_a($newest_member); returns proper result.

if(!defined('e_TRACKING_DISABLED'))
{
	$member_users = $sql->select("online", "*", "online_location REGEXP('forum.php') AND online_user_id!='0' ");
	$guest_users = $sql->select("online", "*", "online_location REGEXP('forum.php') AND online_user_id='0' ");
	$users = $member_users+$guest_users;
	$fVars->USERLIST = LAN_FORUM_0036.": ";
	global $listuserson;
	$c = 0;
	if(is_array($listuserson))
	{
	foreach($listuserson as $uinfo => $pinfo)
	{
		list($oid, $oname) = explode(".", $uinfo, 2);
		$c ++;
		$fVars->USERLIST .= "<a href='".e_HTTP."user.php ?id.$oid'>$oname</a>".($c == MEMBERS_ONLINE ? "." :", ");
	}
	}
	$fVars->USERLIST .= "<br /><a rel='external' href='".e_BASE."online.php'>".LAN_FORUM_0037."</a> ".LAN_FORUM_0038;
}
$fVars->STATLINK = "<a href='".e_PLUGIN."forum/forum_stats.php'>".LAN_FORUM_0017."</a>\n";
$fVars->ICONKEY = "
<table class='table table-bordered' style='width:100%'>\n<tr>
<td style='width:2%'>".IMAGE_new_small."</td>
<td style='width:10%'><span class='smallblacktext'>".LAN_FORUM_0039."</span></td>
<td style='width:2%'>".IMAGE_nonew_small."</td>
<td style='width:10%'><span class='smallblacktext'>".LAN_FORUM_0040."</span></td>
<td style='width:2%'>".IMAGE_closed_small."</td>
<td style='width:10%'><span class='smallblacktext'>".LAN_FORUM_0041."</span></td>
</tr>\n</table>\n";

if(!$srchIcon = $tp->toGlyph('fa-search'))
{
	$srchIcon = LAN_SEARCH; 	
}

$fVars->SEARCH = "
<form method='get' class='form-inline input-append' action='".e_BASE."search.php'>
<div class='input-group'>
<input class='tbox form-control' type='text' name='q' size='20' value='' maxlength='50' />
<span class='input-group-btn'>
<button class='btn btn-default button' type='submit' name='s' value='search' />".$srchIcon."</button>
</span>
<input type='hidden' name='r' value='0' />
<input type='hidden' name='ref' value='forum' />
</div>

</form>\n";

$fVars->PERMS = (USER == TRUE || ANON == TRUE ? LAN_FORUM_0043." - ".LAN_FORUM_0045." - ".LAN_FORUM_0047 : LAN_FORUM_0044." - ".LAN_FORUM_0046." - ".LAN_FORUM_0048);

$fVars->INFO = "";
if (USER == TRUE)
{
	$total_new_threads = $sql->count('forum_thread', '(*)', "WHERE thread_datestamp>'".USERLV."' ");
	if (USERVIEWED != "")
	{
		$tmp = explode(".", USERVIEWED); // List of numbers, separated by single period
		$total_read_threads = count($tmp);
	}
	else
	{
		$total_read_threads = 0;
	}

	$fVars->INFO = LAN_FORUM_0018." ".USERNAME."<br />";
	$lastvisit_datestamp = $gen->convert_date(USERLV, 'long');
	$datestamp = $gen->convert_date(time(), "long");
	if (!$total_new_threads)
	{
		$fVars->INFO .= LAN_FORUM_0019." ";
	}
	elseif($total_new_threads == 1)
	{
		$fVars->INFO .= LAN_FORUM_0020;
	}
	else
	{
		$fVars->INFO .= LAN_FORUM_0021." ".$total_new_threads." ".LAN_FORUM_0022." ";
	}
	$fVars->INFO .= LAN_FORUM_0023;
	if ($total_new_threads == $total_read_threads && $total_new_threads != 0 && $total_read_threads >= $total_new_threads)
	{
		$fVars->INFO .= LAN_FORUM_0029;
		$allread = TRUE;
	}
	elseif($total_read_threads != 0)
	{
		$fVars->INFO .= " (".LAN_FORUM_0027." ".$total_read_threads." ".LAN_FORUM_0028.")";
	}

	$fVars->INFO .= "<br />
	".LAN_FORUM_0024." ".$lastvisit_datestamp."<br />
	".LAN_FORUM_0025." ".$datestamp;
}
else
{
	$fVars->INFO .= '';
	if (ANON == TRUE)
	{
		$fVars->INFO .= LAN_FORUM_0049.'<br />'.LAN_FORUM_0050." <a href='".e_SIGNUP."'>".LAN_FORUM_0051."</a> ".LAN_FORUM_0052;
	}
	elseif(USER == FALSE)
	{
		$fVars->INFO .= LAN_FORUM_0049.'<br />'.LAN_FORUM_0053." <a href='".e_SIGNUP."'>".LAN_FORUM_0054."</a> ".LAN_FORUM_0055;
	}
}

if (USER && vartrue($allread) != TRUE && $total_new_threads && $total_new_threads >= $total_read_threads)
{
	$fVars->INFO .= "<br /><a href='".e_SELF."?mark.all.as.read'>".LAN_FORUM_0057.'</a>'.(e_QUERY != 'new' ? ", <a href='".e_SELF."?new'>".LAN_FORUM_0058."</a>" : '');
}

if (USER && vartrue($forum->prefs->get('track')) && e_QUERY != 'track')
{
	$fVars->INFO .= "<br /><a href='".e_SELF."?track'>".LAN_FORUM_0030.'</a>';
}

$fVars->FORUMINFO = 
str_replace("[x]", ($total_topics+$total_replies), LAN_FORUM_0031)." ($total_topics ".($total_topics == 1 ? LAN_FORUM_0032 : LAN_FORUM_0033).", $total_replies ".($total_replies == 1 ? LAN_FORUM_0034 : LAN_FORUM_0035).")
".(!defined("e_TRACKING_DISABLED") ? "" : "<br />".$users." ".($users == 1 ? LAN_FORUM_0059 : LAN_FORUM_0060)." (".$member_users." ".($member_users == 1 ? LAN_FORUM_0061 : LAN_FORUM_0062).", ".$guest_users." ".($guest_users == 1 ? LAN_FORUM_0063 : LAN_FORUM_0064).")<br />".LAN_FORUM_0066." ".$total_members."<br />".LAN_FORUM_0065." <a href='".e_HTTP."user.php ?id.".$nuser_id."'>".$nuser_name."</a>.\n"); // FIXME cannot find other references to e_TRACKING_DISABLED, use pref?

if (!isset($FORUM_MAIN_START))
{
	if (file_exists(THEME.'forum_template.php'))
	{
		include_once(THEME.'forum_template.php');
	}
}
include(e_PLUGIN.'forum/templates/forum_template.php');


if(is_array($FORUM_TEMPLATE) && deftrue('BOOTSTRAP',false)) // new v2.x format. 
{
		
	$FORUM_MAIN_START		= $FORUM_TEMPLATE['main-start']; 
	$FORUM_MAIN_PARENT 		= $FORUM_TEMPLATE['main-parent'];
	$FORUM_MAIN_FORUM		= $FORUM_TEMPLATE['main-forum'];
	$FORUM_MAIN_END			= $FORUM_TEMPLATE['main-end'];

	$FORUM_NEWPOSTS_START	= $FORUM_TEMPLATE['main-start']; // $FORUM_TEMPLATE['new-start'];
	$FORUM_NEWPOSTS_MAIN 	= $FORUM_TEMPLATE['main-forum']; // $FORUM_TEMPLATE['new-main'];
	$FORUM_NEWPOSTS_END 	= $FORUM_TEMPLATE['main-end']; // $FORUM_TEMPLATE['new-end'];

	$FORUM_TRACK_START		= $FORUM_TEMPLATE['main-start']; // $FORUM_TEMPLATE['track-start'];
	$FORUM_TRACK_MAIN		= $FORUM_TEMPLATE['main-forum']; // $FORUM_TEMPLATE['track-main'];
	$FORUM_TRACK_END		= $FORUM_TEMPLATE['main-end']; // $FORUM_TEMPLATE['track-end'];	
		
}



require_once(HEADERF);

$forumList = $forum->forumGetForumList();
$newflag_list = $forum->forumGetUnreadForums();

if (!$forumList)
{
	$ns->tablerender(LAN_PLUGIN_FORUM_NAME, "<div style='text-align:center'>".LAN_FORUM_0067.'</div>', array('forum', '51'));
	require_once(FOOTERF);
	exit;
}

$forum_string = '';
$pVars = new e_vars;
$frm = e107::getForm();
foreach ($forumList['parents'] as $parent)
{
	$status = parse_parent($parent);
	$pVars->PARENTSTATUS = $status;

	$pVars->PARENTNAME = "<a id='".$frm->name2id($parent['forum_name'])."'>".$parent['forum_name']."</a>";
	$forum_string .= $tp->simpleParse($FORUM_MAIN_PARENT, $pVars);
	if (!count($forumList['forums'][$parent['forum_id']]))
	{
		$text .= "<td colspan='5' style='text-align:center' class='forumheader3'>".LAN_FORUM_0068."</td>";
	}
	else
	{
//TODO: Rework the restricted string
		foreach($forumList['forums'][$parent['forum_id']] as $f)
		{
			if ($f['forum_class'] == e_UC_ADMIN && ADMIN)
			{
				$forum_string .= parse_forum($f, LAN_FORUM_0005);
			}
			elseif($f['forum_class'] == e_UC_MEMBER && USER)
			{
				$forum_string .= parse_forum($f, LAN_FORUM_0006);
			}
			elseif($f['forum_class'] == e_UC_READONLY)
			{
				$forum_string .= parse_forum($f, LAN_FORUM_0007);
			}
			elseif($f['forum_class'] && check_class($f['forum_class']))
			{
				$forum_string .= parse_forum($f, LAN_FORUM_0008);
			}
			elseif(!$f['forum_class'])
			{
				$forum_string .= parse_forum($f);
			}
		}
		if (isset($FORUM_MAIN_PARENT_END))
		{
			$forum_string .= $tp->simpleParse($FORUM_MAIN_PARENT_END, $pVars);
		}
	}
}

function parse_parent($parent)
{
	if(!check_class($parent['forum_postclass']))
	{
		$status = '('.LAN_FORUM_0056.')';
	}
	return vartrue($status);
}

function parse_forum($f, $restricted_string = '')
{
	global $FORUM_MAIN_FORUM, $gen, $forum, $newflag_list, $forumList;
	$fVars = new e_vars;
	$e107 = e107::getInstance();
	$tp = e107::getParser();

	if(USER && is_array($newflag_list) && in_array($f['forum_id'], $newflag_list))
	{

		$fVars->NEWFLAG = "<a href='".$e107->url->create('forum/forum/mfar', $f)."'>".IMAGE_new.'</a>';
	}
	else
	{
		$fVars->NEWFLAG = IMAGE_nonew;
	}

	if(substr($f['forum_name'], 0, 1) == '*')
	{
		$f['forum_name'] = substr($f['forum_name'], 1);
	}
	$f['forum_name'] = $tp->toHTML($f['forum_name'], true, 'no_hook');
	$f['forum_description'] = $tp->toHTML($f['forum_description'], true, 'no_hook');


	//$url= $e107->url->create('forum/forum/view', $f);
	$url = e107::url('forum', 'forum', $f);
	$fVars->FORUMNAME = "<a href='".$url."'>{$f['forum_name']}</a>";
	$fVars->FORUMDESCRIPTION = $f['forum_description'].($restricted_string ? "<br /><span class='smalltext'><i>$restricted_string</i></span>" : "");
	$fVars->THREADS = $f['forum_threads'];
	$fVars->REPLIES = $f['forum_replies'];
	$fVars->FORUMSUBFORUMS = '';
	
	
	
	
	
	$badgeReplies = ($f['forum_replies']) ? "badge-info" : "";
	$badgeThreads = ($f['forum_threads']) ? "badge-info" : "";
	
	$fVars->THREADSX = "<span class='badge {$badgeThreads}'>".$f['forum_threads']."</span>";
	$fVars->REPLIESX = "<span class='badge {$badgeReplies}'>".$f['forum_replies']."</span>";



	if(is_array($forumList['subs'][$f['forum_id']]))
	{
		list($lastpost_datestamp, $lastpost_thread) = explode('.', $f['forum_lastpost_info']);
		$ret = parse_subs($forumList, $f['forum_id'], $lastpost_datestamp);
		$fVars->FORUMSUBFORUMS = "<br /><div class='smalltext'>".LAN_FORUM_0069.": {$ret['text']}</div>";
		$fVars->THREADS += $ret['threads'];
		$fVars->REPLIES += $ret['replies'];
		if(isset($ret['lastpost_info']))
		{
			$f['forum_lastpost_info'] = $ret['lastpost_info'];
			$f['forum_lastpost_user'] = $ret['lastpost_user'];
			$f['forum_lastpost_user_anon'] = $ret['lastpost_user_anon'];
			$f['user_name'] = $ret['user_name'];
		}
	}

	if ($f['forum_lastpost_info'])
	{
		list($lastpost_datestamp, $lastpost_thread) = explode('.', $f['forum_lastpost_info']);
		if ($f['user_name'])
		{

			$lastpost_name = "<a href='".$e107->url->create('user/profile/view', array('name' => $f['user_name'], 'id' => $f['forum_lastpost_user']))."'>{$f['user_name']}</a>";
		}
		else
		{
			$lastpost_name = $tp->toHTML($f['forum_lastpost_user_anon']);
		}

		$lastpost = $forum->threadGetLastpost($lastpost_thread); //XXX TODO inefficient to have SQL query here.

		$fVars->LASTPOSTUSER = $lastpost_name;
		// {forum_sef}/{thread_id}-{thread_sef}

		$urlData = array('forum_sef'=>$f['forum_sef'], 'thread_id'=>$lastpost['post_thread'],'thread_sef'=>$lastpost['thread_sef']);
		$url = e107::url('forum', 'topic', $urlData)."?last=1#post-".$lastpost['post_id'];
		$fVars->LASTPOSTDATE .= "<a href='".$url."'>". $gen->computeLapse($lastpost_datestamp, time(), false, false, 'short')."</a>";
		$lastpost_datestamp = $gen->convert_date($lastpost_datestamp, 'forum');
		$fVars->LASTPOST = $lastpost_datestamp.'<br />'.$lastpost_name." <a href='".$e107->url->create('forum/thread/last', array('name' => $lastpost_name, 'id' => $lastpost_thread))."'>".IMAGE_post2.'</a>';
		
	}
	else
	{
		$fVars->LASTPOSTUSER = "";
		$fVars->LASTPOSTDATE = "-";
		$fVars->LASTPOST = '-';
	}
	return $tp->simpleParse($FORUM_MAIN_FORUM, $fVars);
}



function parse_subs($forumList, $id ='', $lastpost_datestamp)
{

	$tp = e107::getParser();
	$ret = array();

	$subList = $forumList['subs'][$id];

	$ret['text'] = '';

	foreach($subList as $sub)
	{
		$ret['text'] .= ($ret['text'] ? ', ' : '');

		$urlData                = $sub;
		$urlData['parent_sef']  = $forumList['all'][$sub['forum_sub']]['forum_sef']; //   = array('parent_sef'=>
		$suburl                 = e107::url('forum','forum', $urlData);

		$ret['text']            .= "<a href='{$suburl}'>".$tp->toHTML($sub['forum_name']).'</a>';
		$ret['threads']         += $sub['forum_threads'];
		$ret['replies']         += $sub['forum_replies'];
		$tmp                    = explode('.', $sub['forum_lastpost_info']);

		if($tmp[0] > $lastpost_datestamp)
		{
			$ret['lastpost_info'] = $sub['forum_lastpost_info'];
			$ret['lastpost_user'] = $sub['forum_lastpost_user'];
			$ret['lastpost_user_anon'] = $sub['lastpost_user_anon'];
			$ret['user_name'] = $sub['user_name'];
			$lastpost_datestamp = $tmp[0];
		}
	}


	return $ret;
}



if (e_QUERY == 'track')
{
	if($trackedThreadList = $forum->getTrackedThreadList(USERID, 'list'))
	{
		$trackVars = new e_vars;
		$viewed = $forum->threadGetUserViewed();
		$qry = "
		SELECT t.*, p.* from `#forum_thread` AS t
		LEFT JOIN `#forum_post` AS p ON p.post_thread = t.thread_id AND p.post_datestamp = t.thread_datestamp
		WHERE thread_id IN({$trackedThreadList})
		ORDER BY thread_lastpost DESC
		";
		if($sql->gen($qry))
		{
			while($row = $sql->fetch(MYSQL_ASSOC))
			{
				$trackVars->NEWIMAGE = IMAGE_nonew_small;
				if ($row['thread_datestamp'] > USERLV && !in_array($row['thread_id'], $viewed))
				{
					$trackVars->NEWIMAGE = IMAGE_new_small;
				}

				$url = $e107->url->create('forum/thread/view', $row); // configs will be able to map thread_* vars to the url
				$trackVars->TRACKPOSTNAME = "<a href='{$url}'>".$tp->toHTML($row['thread_name']).'</a>';
				$trackVars->UNTRACK = "<a href='".e_SELF."?untrack.".$row['thread_id']."'>".LAN_FORUM_0070."</a>";
				$forum_trackstring .= $tp->simpleParse($FORUM_TRACK_MAIN, $trackVars);
			}
		}
		$forum_track_start = $tp->simpleParse($FORUM_TRACK_START, $trackVars);
		$forum_track_end = $tp->simpleParse($FORUM_TRACK_END, $trackVars);
		if ($forum->prefs->get('enclose'))
		{
			$ns->tablerender($forum->prefs->get('title'), $forum_track_start.$forum_trackstring.$forum_track_end, array('forum', 'main1'));
		}
		else
		{
			echo $forum_track_start.$forum_trackstring.$forum_track_end;
		}
	}
}



if (e_QUERY == 'new')
{
	$nVars = new e_vars;
	$newThreadList = $forum->threadGetNew(10);
	foreach($newThreadList as $thread)
	{
		$author_name = ($thread['user_name'] ? $thread['user_name'] : $thread['lastuser_anon']);

		$datestamp = $gen->convert_date($thread['thread_lastpost'], 'forum');
		if(!$thread['user_name'])
		{
			$nVars->STARTERTITLE = $author_name.'<br />'.$datestamp;
		}
		else
		{
			$nVars->STARTERTITLE = "<a href='".$e107->url->create('user/profile/view', array('id' => $thread['thread_lastuser'], 'name' => $author_name))."'>{$author_name}</a><br />".$datestamp;
		}
		$nVars->NEWSPOSTNAME = "<a href='".$e107->url->create('forum/thread/last', $thread)."'>".$tp->toHTML($thread['thread_name'], TRUE, 'no_make_clickable, no_hook').'</a>';

		$forum_newstring .= $tp->simpleParse($FORUM_NEWPOSTS_MAIN, $nVars);
	}

	if (!$newThreadList)
	{
		$nVars->NEWSPOSTNAME = LAN_FORUM_0029;
		$forum_newstring = $tp->simpleParse($FORUM_NEWPOSTS_MAIN, $nVars);

	}
	$forum_new_start = $tp->simpleParse($FORUM_NEWPOSTS_START, $nVars);
	$forum_new_end = $tp->simpleParse($FORUM_NEWPOSTS_END, $nVars);

	if ($forum->prefs->get('enclose'))
	{
		$ns->tablerender($forum->prefs->get('title'), $forum_new_start.$forum_newstring.$forum_new_end, array('forum', 'main2'));
	}
	else
	{
		echo $forum_new_start.$forum_newstring.$forum_new_end;
	}
}

$frm = e107::getForm();

$breadarray = array(
					array('text'=> $forum->prefs->get('title'), 'url' => e107::url('forum','index') )
);

$fVars->FORUM_BREADCRUMB = $frm->breadcrumb($breadarray);

$forum_main_start = $tp->simpleParse($FORUM_MAIN_START, $fVars);
$forum_main_end = $tp->simpleParse($FORUM_MAIN_END, $fVars);

if ($forum->prefs->get('enclose'))
{
	$ns->tablerender($forum->prefs->get('title'), $forum_main_start.$forum_string.$forum_main_end, array('forum', 'main3'));
}
else
{
	echo $forum_main_start.$forum_string.$forum_main_end;
}








require_once(FOOTERF);

function forum_rules($action = 'check')
{
	if (ADMIN == true)
	{
		$type = 'forum_rules_admin';
	}
	elseif(USER == true)
	{
		$type = 'forum_rules_member';
	}
	else
	{
		$type = 'forum_rules_guest';
	}
	$result = e107::getDb()->select('generic', 'gen_chardata', "gen_type = '$type' AND gen_intdata = 1");
	if ($action == 'check') { return $result; }

	if ($result)
	{
		$row = e107::getDb()->fetch();
		$rules_text = e107::getParser()->toHTML($row['gen_chardata'], true);
	}
	else
	{
		$rules_text = LAN_FORUM_0072;
	}

	$text = '';

	if(deftrue('BOOTSTRAP'))
	{
		$breadarray = array(
			array('text'=> e107::pref('forum','title', LAN_PLUGIN_FORUM_NAME), 'url' => e107::url('forum','index') ),
			array('text'=>LAN_FORUM_0016, 'url'=>null)
		);

		$text = e107::getForm()->breadcrumb($breadarray);
	}

	$text .= "<div id='forum-rules'>".$rules_text."</div>";
	$text .=  "<div class='center'>".e107::getForm()->pagination(e107::url('forum','index'), LAN_BACK)."</div>";

	e107::getRender()->tablerender(LAN_FORUM_0016, $text, array('forum', 'forum_rules'));
}

?>