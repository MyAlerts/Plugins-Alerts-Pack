<?php
/**
 * Plugins Alerts Pack
 * 
 * Provides integration between the most famous plugins and MyAlerts.
 *
 * @package Plugins Alerts Pack
 * @author  Shade <legend_k@live.it>
 * @license http://opensource.org/licenses/mit-license.php MIT license (same as MyAlerts)
 * @version 1.0
 */


// Misc
$l['pluginspack'] = "Plugins Alerts Pack";

// UCP stuff MyAlerts itself loads
$l['myalerts_setting_mysupport'] = "Receive alert when is changed the status of any of your thread?";
$l['myalerts_setting_myncomments'] = "Receive alert when another user posts on your personal wall (aka Profile Comments)?";
$l['myalerts_setting_subscribedthread'] = "Receive alert when another user posts in a thread you're currently partecipating in?";
$l['myalerts_setting_subscribedforum'] = "Receive alert when another user creates a new thread in a Forum you're currently partecipating in?";
$l['myalerts_setting_announcement_add'] = "Receive alert when a new Announcement is added?";
$l['myalerts_setting_announcement_edit'] = "Receive alert when an Announcement is edited?";

// MySupport
$l['pluginspack_mysupport_notsolved'] = "{1} marked as not solved your thread \"<a href=\"{2}\">{4}</a>\". ({3})";
$l['pluginspack_mysupport_solved'] = "{1} marked as solved your thread \"<a href=\"{2}\">{4}</a>\". ({3})";
$l['pluginspack_mysupport_technical'] = "{1} marked as technical your thread \"<a href=\"{2}\">{4}</a>\". ({3})";
$l['pluginspack_mysupport_solvedandclosed'] = "{1} marked as solved your thread \"<a href=\"{2}\">{4}</a>\" and closed it. ({3})";
$l['pluginspack_mysupport_nottechnical'] = "{1} marked as not technical your thread \"<a href=\"{2}\">{4}</a>\". ({3})";

// MyN Comments
$l['pluginspack_myncomments_newcomment'] = "{1} posted something on \"<a href=\"{2}\">your personal wall</a>\". ({3})";
$l['pluginspack_myncomments_newreply'] = "{1} replied to a comment you've made on \"<a href=\"{2}\">an user wall</a>\". ({3})";

// Subscriptions
$l['pluginspack_subscribedthread_newpost'] = "{1} <a href=\"{2}\">replied</a> to \"{4}\", in which you're partecipating in. ({3})";
$l['pluginspack_subscribedforum_newthread'] = "{1} made <a href=\"{2}\">a new topic</a> in <a href=\"{5}\">{4}</a>, in which you're partecipating in. ({3})";

// Announcements
$l['announcement_add'] = "A new {1}{2}Announcement was added: \"{3}\" ({4});";
$l['announcement_enabled'] = "A {1}{2}Announcement was enabled: \"{3}\" ({4});";
$l['announcement_edit'] = "A {1}{2}Announcement was edited: \"{3}\" ({4});";
$l['announcement_global'] = "global ";
$l['announcement_removeable'] = "removeable ";