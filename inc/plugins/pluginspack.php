<?php
/**
 * Plugins Alerts Pack
 * 
 * Provides integration between the most famous plugins and MyAlerts.
 *
 * @package Plugins Alerts Pack
 * @author  Shade <legend_k@live.it>
 * @license http://opensource.org/licenses/mit-license.php MIT license (same as MyAlerts)
 * @version β 0.4
 */

$mysupport = 'inc/plugins/mysupport.php';
$mynprofilecomments = 'inc/network/profile/datahandlers/comment.php';

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if (!defined("PLUGINLIBRARY")) {
	define("PLUGINLIBRARY", MYBB_ROOT . "inc/plugins/pluginlibrary.php");
}

function pluginspack_info()
{
	return array(
		'name' => 'Plugins Alerts Pack',
		'description' => 'Provides integration between the most famous plugins and <a href="http://community.mybb.com/thread-127444.html"><b>MyAlerts</b></a>.<br /><span style="color:#f00">MyAlerts is required for Plugins Alerts Pack to work</span>.',
		'website' => 'https://github.com/MyAlerts/Plugins-Alerts-Pack',
		'author' => 'Shade',
		'authorsite' => 'http://www.idevicelab.net/forum',
		'version' => 'β 0.4',
		'compatibility' => '16*',
		'guid' => 'none'
	);
}

function pluginspack_is_installed()
{
	global $cache;
	
	$info = pluginspack_info();
	$installed = $cache->read("shade_plugins");
	if ($installed[$info['name']]) {
		return true;
	}
}

function pluginspack_install()
{
	global $db, $lang, $mybb, $cache, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	// check if myalerts table exist - if false, then MyAlerts is not installed, warn the user and redirect him
	if (!$db->table_exists('alerts')) {
		flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/myalerts\">MyAlerts</a> is not installed. Moderation Alerts Pack requires MyAlerts to be installed in order to properly work.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$PL or require_once PLUGINLIBRARY;
	
	if (file_exists($GLOBALS['mysupport'])) {
		$PL->edit_core('pluginspack', $GLOBALS['mysupport'], array(
			array(
				'search' => '$db->update_query("threads", $status_update, $where_sql);',
				'before' => 'global $plugins;
$args = array("multiple" => &$multiple, "thread_info" => &$thread_info, "status" => &$status);
$plugins->run_hooks("mysupport_myalerts", $args);'
			)
		), true);
	}
	if (file_exists($GLOBALS['mynprofilecomments'])) {
		// disable default alert for MyNetwork Profile Comments
		$PL->edit_core('pluginspack', $GLOBALS['mynprofilecomments'], array(
			array(
				'search' => '$this->comment_alert();',
				'replace' => ''
			)
		), true);
	}
	// core subscription method
	$PL->edit_core('pluginspack', 'inc/datahandlers/post.php', array(
		array(
			'search' => '$emailsubject = $lang->sprintf($emailsubject, $forum[\'name\']);',
			'before' => '$args = array("subscribedmember" => &$subscribedmember, "thread" => &$thread, "forum" => &$forum, "this" => &$this);
$plugins->run_hooks("datahandler_subscribedforum_myalerts", $args);'
		),
		array(
			'search' => '$emailsubject = $lang->sprintf($emailsubject, $subject);',
			'before' => '$args = array("subscribedmember" => &$subscribedmember, "post" => &$post, "subject" => &$subject);
$plugins->run_hooks("datahandler_subscribedthread_myalerts", $args);'
		)
	), true);
	
	$info = pluginspack_info();
	$shadePlugins = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = array(
		'title' => $info['name'],
		'version' => $info['version']
	);
	$cache->update('shade_plugins', $shadePlugins);
	
	if (!$lang->pluginspack) {
		$lang->load('pluginspack');
	}
	
	$query = $db->simple_select("settinggroups", "gid", "name='myalerts'");
	$gid = intval($db->fetch_field($query, "gid"));
	
	$pluginspack_settings_1 = array(
		"name" => "myalerts_alert_mysupport",
		"title" => $lang->setting_pluginspack_alert_mysupport,
		"description" => $lang->setting_pluginspack_alert_mysupport_desc,
		"optionscode" => "yesno",
		"value" => "1",
		"disporder" => "100",
		"gid" => $gid
	);
	$pluginspack_settings_2 = array(
		"name" => "myalerts_alert_myncomments",
		"title" => $lang->setting_pluginspack_alert_myncomments,
		"description" => $lang->setting_pluginspack_alert_myncomments_desc,
		"optionscode" => "yesno",
		"value" => "1",
		"disporder" => "101",
		"gid" => $gid
	);
	$pluginspack_settings_3 = array(
		"name" => "myalerts_alert_subscribedthread",
		"title" => $lang->setting_pluginspack_alert_subscriptions,
		"description" => $lang->setting_pluginspack_alert_subscriptions_desc,
		"optionscode" => "yesno",
		"value" => "1",
		"disporder" => "102",
		"gid" => $gid
	);
	
	$db->insert_query("settings", $pluginspack_settings_1);
	$db->insert_query("settings", $pluginspack_settings_2);
	$db->insert_query("settings", $pluginspack_settings_3);
	
	$insertArray = array(
		0 => array(
			'code' => 'mysupport'
		),
		1 => array(
			'code' => 'myncomments'
		),
		2 => array(
			'code' => 'subscribedthread'
		),
		3 => array(
			'code' => 'subscribedforum'
		)
	);
	
	$db->insert_query_multiple('alert_settings', $insertArray);
	
	$query = $db->simple_select('users', 'uid');
	while ($uids = $db->fetch_array($query)) {
		$users[] = $uids['uid'];
	}
	
	$query = $db->simple_select("alert_settings", "id", "code IN ('mysupport', 'myncomments', 'subscribedthread', 'subscribedforum')");
	while ($setting = $db->fetch_array($query)) {
		$settings[] = $setting['id'];
	}
	
	foreach ($users as $user) {
		foreach ($settings as $setting) {
			$userSettings[] = array(
				'user_id' => (int) $user,
				'setting_id' => (int) $setting,
				'value' => 1
			);
		}
	}
	
	$db->insert_query_multiple('alert_setting_values', $userSettings);
	
	// rebuild settings
	rebuild_settings();
	
}

function pluginspack_uninstall()
{
	global $db, $cache, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$PL or require_once PLUGINLIBRARY;
	
	if (file_exists($GLOBALS['mysupport'])) {
		$PL->edit_core('pluginspack', $GLOBALS['mysupport'], array(), true);
	}
	if (file_exists($GLOBALS['mynprofilecomments'])) {
		$PL->edit_core('pluginspack', $GLOBALS['mynprofilecomments'], array(), true);
	}
	$PL->edit_core('pluginspack', 'inc/datahandlers/post.php', array(), true);
	
	// delete ACP settings
	$db->write_query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN('myalerts_alert_mysupport','myalerts_alert_myncomments','myalerts_alert_subscribedthread','myalerts_alert_subscribedforum')");
	
	// delete existing values
	$query = $db->simple_select("alert_settings", "id", "code IN ('mysupport', 'myncomments', 'subscribedthread', 'subscribedforum')");
	while ($setting = $db->fetch_array($query)) {
		$settings[] = $setting['id'];
	}
	$settings = implode(",", $settings);
	
	// truly delete them
	$db->delete_query("alert_setting_values", "setting_id IN ({$settings})");
	// delete UCP settings
	$db->delete_query("alert_settings", "code IN ('mysupport', 'myncomments', 'subscribedthread', 'subscribedforum')");
	
	$info = pluginspack_info();
	// delete the plugin from cache
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);
	// rebuild settings
	rebuild_settings();
}

// load our custom lang file into MyAlerts
$plugins->add_hook('myalerts_load_lang', 'pluginspack_load_lang');
function pluginspack_load_lang()
{
	global $lang;
	
	if (!$lang->pluginspack) {
		$lang->load('pluginspack');
	}
}

// generate text and stuff like that - fixes #1
$plugins->add_hook('myalerts_alerts_output_start', 'pluginspack_parseAlerts');
function pluginspack_parseAlerts(&$alert)
{
	global $mybb, $lang;
	
	if (!$lang->pluginspack) {
		$lang->load('pluginspack');
	}
	
	// MySupport
	if ($alert['alert_type'] == 'mysupport' AND $mybb->user['myalerts_settings']['mysupport']) {
		$alert['threadLink'] = get_thread_link($alert['content']['tid']);
		$status = $alert['content']['status'];
		// not solved
		if ($status == 0) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_notsolved, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		}
		// solved
		elseif ($status == 1) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_solved, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		}
		// solved and closed
			elseif ($status == 3) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_solvedandclosed, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		}
		// technical
		if ($status == 2 AND !($mybb->settings['mysupporthidetechnical'] == 1 AND !mysupport_usergroup("canmarktechnical"))) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_technical, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		} elseif ($status == 2 AND $mybb->settings['mysupporthidetechnical'] == 1) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_notsolved, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		}
		// not technical
		if ($status == 4 AND !($mybb->settings['mysupporthidetechnical'] == 1 AND !mysupport_usergroup("canmarktechnical"))) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_nottechnical, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		} elseif ($status == 4 AND $mybb->settings['mysupporthidetechnical'] == 1) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_mysupport_notsolved, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		}
		$alert['rowType'] = 'mysupportAlert';
	}
	// MyNetwork Profile Comments
	elseif ($alert['alert_type'] == 'myncomments' AND $mybb->user['myalerts_settings']['myncomments']) {
		$alert['userLink'] = get_profile_link($mybb->user['uid']) . "?view=comments";
		if ($alert['content']['newconv']) {
			$alert['message'] = $lang->sprintf($lang->pluginspack_myncomments_newcomment, $alert['user'], $alert['userLink'], $alert['dateline']);
		} else {
			$alert['userLink'] = get_profile_link($alert['content']['user']) . "?view=comments";
			$alert['message'] = $lang->sprintf($lang->pluginspack_myncomments_newreply, $alert['user'], $alert['userLink'], $alert['dateline']);
		}
		$alert['rowType'] = 'myncommentsAlert';
	}
	// Subscribed thread
		elseif ($alert['alert_type'] == 'subscribedthread' AND $mybb->user['myalerts_settings']['subscribedthread']) {
		$alert['threadLink'] = $mybb->settings['bburl'] . '/' . get_thread_link($alert['content']['tid']);
		$alert['message'] = $lang->sprintf($lang->pluginspack_subscribedthread_newpost, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['subject']);
		$alert['rowType'] = 'subscribedthreadAlert';
	}
	// Subscribed Forum
		elseif ($alert['alert_type'] == 'subscribedforum' AND $mybb->user['myalerts_settings']['subscribedforum']) {
		$alert['threadLink'] = $mybb->settings['bburl'] . '/' . get_thread_link($alert['content']['tid']);
		$alert['forumLink'] = $mybb->settings['bburl'] . '/' . get_forum_link($alert['content']['fid']);
		$alert['message'] = $lang->sprintf($lang->pluginspack_subscribedforum_newthread, $alert['user'], $alert['threadLink'], $alert['dateline'], $alert['content']['forumname'], $alert['forumLink']);
		$alert['rowType'] = 'subscribedforumAlert';
	}
}

// Generate the actual alerts

// MYSUPPORT
if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_mysupport']) {
	$plugins->add_hook('mysupport_myalerts', 'pluginspack_addAlert_MySupport');
}
function pluginspack_addAlert_MySupport(&$args)
{
	global $mybb, $Alerts;
	
	$thread_info = $args['thread_info'];
	$multiple = $args['multiple'];
	$status = $args['status'];
	
	if ($multiple) {
		foreach ($thread_info as $thread) {
			$thread = get_thread($thread);
			if ($thread['uid'] != $mybb->user['uid']) {
				$Alerts->addAlert((int) $thread['uid'], 'mysupport', (int) $thread['tid'], (int) $mybb->user['uid'], array(
					'status' => $status,
					'tid' => $thread['tid'],
					'subject' => $thread['subject']
				));
			}
		}
	} else {
		if ($thread_info['uid'] != $mybb->user['uid']) {
			$Alerts->addAlert((int) $thread_info['uid'], 'mysupport', (int) $thread_info['tid'], (int) $mybb->user['uid'], array(
				'status' => $status,
				'tid' => $thread_info['tid'],
				'subject' => $thread_info['subject']
			));
		}
	}
}

// MYNETWORK PROFILE COMMENTS
if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_myncomments']) {
	$plugins->add_hook('myn_profile_comments_insert', 'pluginspack_addAlert_MYNComments');
}
function pluginspack_addAlert_MYNComments(&$args)
{
	global $mybb, $Alerts, $db;
	
	$uid = $args[0]->data['uid'];
	$newconv = $args[0]->data['conv'];
	
	if ($newconv == 0) {
		$Alerts->addAlert((int) $uid, 'myncomments', 0, (int) $mybb->user['uid'], array(
			'newconv' => true
		));
	} else {
		$conversation = $db->fetch_array($db->simple_select("myn_comments", "aid", "cid = {$newconv}"));
		if ($mybb->user['uid'] != $conversation['aid']) {
			$Alerts->addAlert((int) $conversation['aid'], 'myncomments', 0, (int) $mybb->user['uid'], array(
				'user' => $uid
			));
		}
		$Alerts->addAlert((int) $uid, 'myncomments', 0, (int) $mybb->user['uid'], array(
			'newconv' => true
		));
	}
}

// THREAD SUBSCRIPTION
if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_subscribedthread']) {
	$plugins->add_hook('datahandler_subscribedforum_myalerts', 'pluginspack_addAlert_subscribedforum');
	$plugins->add_hook('datahandler_subscribedthread_myalerts', 'pluginspack_addAlert_subscribedthread');
}
function pluginspack_addAlert_subscribedthread(&$args)
{
	global $mybb, $Alerts;
	
	$uid = $args['subscribedmember']['uid'];
	$subject = $args['subject'];
	$tid = $args['post']['tid'];
	
	$Alerts->addAlert((int) $uid, 'subscribedthread', (int) $tid, (int) $mybb->user['uid'], array(
		'subject' => $subject,
		'tid' => $tid
	));
}
function pluginspack_addAlert_subscribedforum(&$args)
{
	global $mybb, $Alerts;
	
	$uid = $args['subscribedmember']['uid'];
	$forumname = $args['forum']['name'];
	$fid = $args['forum']['fid'];
	$tid = $args['this']->tid;
	
	$Alerts->addAlert((int) $uid, 'subscribedforum', (int) $tid, (int) $mybb->user['uid'], array(
		'forumname' => $forumname,
		'tid' => $tid,
		'fid' => $fid
	));
}