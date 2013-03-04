<?php
/**
 * Plugins Alerts Pack
 * 
 * Provides integration between the most famous plugins and MyAlerts.
 *
 * @package Plugins Alerts Pack
 * @author  Shade <legend_k@live.it>
 * @license http://opensource.org/licenses/mit-license.php MIT license (same as MyAlerts)
 * @version β 0.3
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
		'website' => 'http://www.idevicelab.net/forum',
		'author' => 'Shade',
		'authorsite' => 'http://www.idevicelab.net/forum',
		'version' => 'β 0.3',
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
$args = array("multiple" => &$multiple, "thread_info" => &$thread_info, "status" => &$status, "thread" => &$thread);
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
	
	$db->insert_query("settings", $pluginspack_settings_1);
	$db->insert_query("settings", $pluginspack_settings_2);
	
	// Set our alerts on for all users by default, maintaining existing alerts values
	// Declare a data array containing all our alerts settings we'd like to add. To default them, the array must be associative and keys must be set to "on" (active) or 0 (not active)
	$possible_settings = array(
		'mysupport' => "on",
		'myncomments' => "on"
	);
	
	$query = $db->simple_select('users', 'uid, myalerts_settings', '', array());
	
	while ($settings = $db->fetch_array($query)) {
		// decode existing alerts with corresponding key values. json_decode func returns an associative array by default, we don't need to edit it
		$alert_settings = json_decode($settings['myalerts_settings']);
		
		// merge our settings with existing ones...
		$my_settings = array_merge($possible_settings, (array) $alert_settings);
		
		// and update the table cell, encoding our modified array and paying attention to SQL inj (thanks Nathan!)
		$db->update_query('users', array(
			'myalerts_settings' => $db->escape_string(json_encode($my_settings))
		), 'uid=' . (int) $settings['uid']);
	}
	
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
	
	$db->write_query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN('myalerts_alert_mysupport','myalerts_alert_myncomments')");
	
	$info = pluginspack_info();
	// delete the plugin from cache
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);
	// rebuild settings
	rebuild_settings();
}

// add alerts into UCP
$plugins->add_hook('myalerts_possible_settings', 'pluginspack_possibleSettings');
function pluginspack_possibleSettings(&$possible_settings)
{
	global $lang;
	
	if (!$lang->pluginspack) {
		$lang->load('pluginspack');
	}
	
	$_possible_settings = array(
		'mysupport',
		'myncomments'
	);
	
	$possible_settings = array_merge($possible_settings, $_possible_settings);
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
	$thread = $args['thread'];
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
		if ($thread['uid'] != $mybb->user['uid']) {
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