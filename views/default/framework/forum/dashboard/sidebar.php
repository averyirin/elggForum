<?php

$search_title = elgg_echo('hj:forum:filter');
$search_box = elgg_view('framework/forum/filters/forums', $vars);
$page_owner = elgg_get_page_owner_entity();
error_log($page_owner->guid);
// load JS
	elgg_load_js("jquery.tree");
	elgg_load_css("jquery.tree");
	
	elgg_load_js("jquery.hashchange");
?>

<?php
$menu = "<div id='hj-forum-tree'>";
$menu .= elgg_view_menu("forums_sidebar_tree", array(
	"container" => $page_owner,
	"sort_by" => "oe.title"
));
$menu .= "</div>";

echo elgg_view_module("aside", "", $menu, array("id" => "forums_list_tree_container"));
echo elgg_view_module('aside', $search_title, $search_box);