<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$PL or require_once PLUGINLIBRARY;

$page->add_breadcrumb_item($lang->pluginspack, "index.php?module=config-pluginspack");

if ($mybb->input['action'] == "modify") {
	//We have to modify a file
	if (!isset($mybb->input['plugin']) || !isset($supported_plugins[$mybb->input['plugin']])) {
		//Invalid Plugin
		flash_message($lang->pluginspack_invalid_plugin, 'error');
		admin_redirect("index.php?module=config-pluginspack");
	}

	$fix_func = "fix_".$mybb->input['plugin'];
	if (!function_exists($fix_func)) {
		//Don't need to be modified
		flash_message($lang->pluginspack_no_modify, 'error');
		admin_redirect("index.php?module=config-pluginspack");
	}

	//Let's do it
	$result = $fix_func();
	
	if ($result === false) {
		flash_message($lang->pluginspack_chmod, 'error');
		admin_redirect("index.php?module=config-pluginspack");
	}
	
	flash_message($lang->pluginspack_modified, 'success');
	admin_redirect("index.php?module=config-pluginspack");
} elseif ($mybb->input['action'] == "unmodify") {
	//We have to unmodify a file
	if (!isset($mybb->input['plugin']) || !isset($supported_plugins[$mybb->input['plugin']])) {
		//Invalid Plugin
		flash_message($lang->pluginspack_invalid_plugin, 'error');
		admin_redirect("index.php?module=config-pluginspack");
	}

	$result = $PL->edit_core('pluginspack', $supported_plugins[$mybb->input['plugin']]['file'], array(), true);

	if ($result === false) {
		flash_message($lang->pluginspack_chmod, 'error');
		admin_redirect("index.php?module=config-pluginspack");
	}

	flash_message($lang->pluginspack_unmodified, 'success');
	admin_redirect("index.php?module=config-pluginspack");
} else {
	$page->output_header($lang->pluginspack);

	$table = new Table;
	$table->construct_header($lang->plugin);
	$table->construct_header($lang->pluginspack_modify, array("style" => "text-align: center;"));

	foreach ($supported_plugins as $plugin) {
		if ($plugin['exists']) {
			$info_func = "{$plugin['name']}_info";
			$info = $info_func();

			//Do we have the name in our language package?
			if (isset($lang->$plugin['name']))
			    $name = $lang->$plugin['name'];
			else
				$name = $info['name'];

			//Let's get the status of our Plugin
			if ($plugin['needs_install'] && !$plugin['installed']) {
				$status = $lang->uninstalled;
			} else {
				if($plugin['activated'])
				    $status = $lang->activated;
				else
					$status = $lang->deactivated;
			}

			$table->construct_cell($name." (<i>{$status}</i>)");

			$content = file_get_contents(MYBB_ROOT . $plugin['file']);
			//Is it already modified?
			if (strpos($content, "/* + PL:pluginspack + */"))
				$table->construct_cell("<a href=\"index.php?module=config-pluginspack&action=unmodify&plugin={$plugin['name']}\">".$lang->pluginspack_unmodify."</a>", array("style" => "text-align: center;"));
			else
				$table->construct_cell("<a href=\"index.php?module=config-pluginspack&action=modify&plugin={$plugin['name']}\">".$lang->pluginspack_modify."</a>", array("style" => "text-align: center;"));

			$table->construct_row();
		} else {
			//Do we have the name in our language package?
			if (isset($lang->$plugin['name']))
			    $name = $lang->$plugin['name'];
			else
				$name = $plugin['name'];

			$table->construct_cell($name);
			$table->construct_cell($lang->pluginspack_not_existant, array("style" => "text-align: center;"));
			$table->construct_row();
		}
	}
	$table->output($lang->pluginspack_supported);
}

$page->output_footer();
?>