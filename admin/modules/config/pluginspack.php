<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$page->add_breadcrumb_item($lang->pluginspack, "index.php?module=config-pluginspack");

if ($mybb->input['action'] == "modify") {
	//We have to modify a file
} else {
	$page->output_header($lang->pluginspack);

	$table = new Table;
	$table->construct_header($lang->plugin);
	$table->construct_header($lang->pluginspack_modify);

	foreach($supported_plugins as $plugin) {
		$table->construct_cell($plugin['name']);
		$table->construct_cell($lang->pluginspack_modify);
		$table->construct_row();
	}
	
	$table->output($lang->pluginspack_supported);
}

$page->output_footer();
?>