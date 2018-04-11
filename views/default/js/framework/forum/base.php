<?php if (FALSE) : ?>
	<script type="text/javascript">
<?php endif; ?>

elgg.provide('framework');
elgg.provide('framework.forum');
elgg.provide('framework.forum.tree');

framework.forum.init = function() {

	$(".forum-category-list").sortable({
		items:                'li.elgg-item:has(div.hj-draggable-element)',
		connectWith:          '.forum-category-list',
		handle:               '.hj-draggable-element-handle',
		forcePlaceholderSize: true,
		placeholder:          'hj-draggable-element-placeholder',
		opacity:              0.8,
		revert:               500,
		stop:                 framework.forum.orderCategories
	});

	$('.elgg-button-forum-subscription')
	.live('click', function(e) {
		e.preventDefault();
		$element = $(this);
		elgg.action($(this).attr('href'), {
			success : function(response) {
				if (response.status >= 0) {
					if ($element.text() == elgg.echo('hj:forum:subscription:remove')) {
						$element.text(elgg.echo('hj:forum:subscription:create'));
						$element.removeClass('elgg-state-active');
					} else {
						$element.text(elgg.echo('hj:forum:subscription:remove'));
						$element.addClass('elgg-state-active');
					}
				}
			}
		})
	})

	$('.elgg-button-forum-bookmark')
	.live('click', function(e) {
		e.preventDefault();
		$element = $(this);
		elgg.action($(this).attr('href'), {
			success : function(response) {
				if (response.status >= 0) {
					if ($element.text() == elgg.echo('hj:forum:bookmark:remove')) {
						$element.text(elgg.echo('hj:forum:bookmark:create'));
						$element.removeClass('elgg-state-active');
					} else {
						$element.text(elgg.echo('hj:forum:bookmark:remove'));
						$element.addClass('elgg-state-active');
					}
				}
			}
		})
	})

	framework.forum.tree.init();
		
}

framework.forum.orderCategories = function(event, ui) {

	var data = ui.item
	.closest('.forum-category-list')
	.sortable('serialize');

	elgg.action('action/forum/order/categories?' + data);

	// @hack fixes jquery-ui/opera bug where draggable elements jump
	ui.item.css('top', 0);
	ui.item.css('left', 0);
};

framework.forum.appendNewCategory = function(name, type, params, value) {

	$('.forum-category-list')
	.append($(params.response.output.view));

	window.location.hash = 'elgg-object-' + params.response.output.guid;
	return value;
		
}

framework.forum.replaceCategory = function(name, type, params, value) {

	$('.forum-category-list')
	.find('#elgg-object-' + params.response.output.guid)
	.each(function() {
		$(this).replaceWith($(params.response.output.view));
	})
	window.location.hash = 'elgg-object-' + params.response.output.guid;
	return value;

}

framework.forum.tree.init = function(){
	$tree = $('#hj-forum-tree');
	if($tree.length){
		$tree.tree({
			rules: {
				multiple: false,
				drag_copy: false,
				valid_children : [ "root" ]
			},
			ui: {
				theme_name: "classic"
			},
			callback: {
				onload: function(tree){
					var url = window.location.href;
					if(isNaN(url.split("/").pop())){
						url = url.substring(0, url.lastIndexOf("/"));
					}

					if(url){
						tree.open_branch($tree.find('a[href="#"]'));
						var node = $tree.find('a[href="'+url+'"]');
						var length = node.parent().find('.elgg-child-menu').parent().length;
						length >= 2 ? topLvlMenu = node.parent().find('.elgg-child-menu').parent()[length-2] : topLvlMenu = node.parent().find('.elgg-child-menu').parent()[length-1];
						
						$.each(node.parents('.closed'), function(){
							tree.open_branch($(this));
						});

						//tree.open_branch($(topLvlMenu).find('a:first'));
						node.addClass('clicked');
					}
					$tree.show();
				},
				onselect: function(node, tree){
					var li = $(node);
					var clickedNodes = li.parent().find('.clicked');
					var url = li.find('a:first').attr("href");

					$.each(clickedNodes, function(key, value){
						if(value != url){
							$(this).removeClass('clicked');
						}
					});
					window.location.href = url;
				},
				onmove: function(node, ref_node, type, tree_obj, rb){
					/*var parent_node = tree_obj.parent(node);

					var folder_guid = $(node).find('a:first').attr('href').substr(1);
					var parent_guid = $(parent_node).find('a:first').attr('href').substr(1);
										
					var order = [];
					$(parent_node).find('>ul > li > a').each(function(k, v){
						var guid = $(v).attr('href').substr(1);
						order.push(guid);
					});

					if(parent_guid == window.location.hash.substr(1)){
						$("#file_tools_list_files_container .elgg-ajax-loader").show();
					}
					
					elgg.action("file_tools/folder/reorder", {
						data: {
							folder_guid: folder_guid,
							parent_guid: parent_guid,
							order: order
						},
						success: function(){
							if(parent_guid == window.location.hash.substr(1)){
								elgg.file_tools.load_folder(parent_guid);
							}
						}
					});*/
				}
			}
		});
	}
} 

elgg.register_hook_handler('init', 'system', framework.forum.init);

elgg.register_hook_handler('newcategory', 'framework:forum', framework.forum.appendNewCategory);
elgg.register_hook_handler('editedcategory', 'framework:forum', framework.forum.replaceCategory);

<?php if (FALSE) : ?></script><?php
endif;
?>
