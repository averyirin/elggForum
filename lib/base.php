<?php

/**
 * Get latest forum topics
 *
 * @param int $container_guid	Guid of the forum
 * @param int $limit			Number of topics to return
 * @param boolean $count		Return a total number of topics
 * @param boolean $recursive	Recurse into the forum tree (nested forums and topics)
 * @return mixed
 */
function hj_forum_get_latest_topics($container_guid, $limit = 10, $count = false, $recursive = false) {

	$options = array(
		'types' => 'object',
		'subtypes' => array('hjforumtopic', 'hjforum'),
		'count' => $count,
		'limit' => $limit,
		'relationship' => 'descendant',
		'relationship_guid' => $container_guid,
		'inverse_relationship' => true,
		'order_by' => 'e.time_created DESC'
	);

	if (!$recursive) {
		$options['container_guids'] = $container_guid;
	}

	return elgg_get_entities_from_relationship($options);
}

/**
 * Get latest posts
 *
 * @param int $container_guid	Guid of the topic or forum
 * @param int $limit			Number of posts to return
 * @param boolean $count		Return a total number of posts
 * @param boolean $recursive	Recurse into the forum tree (nested forums and topics)
 * @return mixed
 */
function hj_forum_get_latest_posts($container_guid, $limit = 10, $count = false, $recursive = false) {
	$options = array(
		'types' => 'object',
		'subtypes' => array('hjforumpost', 'hjforumtopic'),
		'count' => $count,
		'limit' => $limit,
		'relationship' => 'descendant',
		'relationship_guid' => $container_guid,
		'inverse_relationship' => true,
		'order_by' => 'e.time_created DESC'
	);

	if (!$recursive) {
		$options['container_guids'] = $container_guid;
	}

	return elgg_get_entities_from_relationship($options);
}

/**
 * Notify subscribed users
 * @param int $guid
 */
function hj_forum_notify_subscribed_users($guid) {
	$entity = get_entity($guid);
	//$parentEntity = get_entity($entity->container_guid);
	$subscribers = $entity->getSubscribedUsers();
	$to = array();
	foreach($subscribers as $subscribed){
		$to[] = $subscribed->guid;
	}
	$subtype = $entity->getSubtype();
	
	$from = elgg_get_site_entity()->guid;

	$subject = elgg_echo("hj:forum:new:$subtype");
	
	$subject_link = elgg_view('framework/bootstrap/user/elements/name', array('entity' => $entity->getOwnerEntity()));
	$object_link = elgg_view('framework/bootstrap/object/elements/title', array('entity' => $entity));
	$breadcrumbs = elgg_view('framework/bootstrap/object/elements/breadcrumbs', array('entity' => $entity));
	if (!empty($breadcrumbs)) {
		$breadcrumbs_link = elgg_echo('river:in:forum', array($breadcrumbs));
	}
	$key = "river:create:object:$subtype";
	$summary = elgg_echo($key, array($subject_link, $object_link)) . $breadcrumbs_link;
	$body = elgg_view('framework/bootstrap/object/elements/description', array('entity' => $entity));
	$link = elgg_view('output/url', array(
		'text' => elgg_echo('hj:framework:notification:link'),
		'href' => $entity->getURL(),
		'is_trusted' => true
	));
	$footer = elgg_echo('hj:framework:notification:full_link', array($link));
	
	$message = "<p>$summary</p><p>$body</p><p>$footer</p>";

	notify_user($to, $from, $subject, $message);

}

function hj_forums_get_forums($container_guid = 0){
	$result = false;
		
	if(empty($container_guid)) {
		$container_guid = elgg_get_page_owner_guid();
	}

	$flag = false;
	while($flag != true){
		$container_entity = get_entity($container_guid);
		if(!elgg_instanceof($container_entity, 'group')){
			$container_guid = $container_entity->container_guid;	
		}
		else{
			$flag = true;
		}
	}
	if(!empty($container_guid)) {
		$options = array(
			"type" => "object",
			"subtype" => "hjforum",
			"container_guid" => $container_guid,
			"limit" => false,
			"order_by" => "e.last_action DESC"
		);
		$options["joins"][] = "JOIN " . elgg_get_config("dbprefix") . "objects_entity oe ON oe.guid = e.guid";
		if($forums = elgg_get_entities($options)) {
			$parents = array();
			foreach($forums as $forum){
				$options = array(
					"type" => "object",
					"subtype" => "hjforum",
					"container_guid" => $forum->guid,
					'limit' => false,
					"order_by" => "e.last_action DESC"
				);
				$subForums = elgg_get_entities($options);
				foreach($subForums as $subForum){
					$subForum->parent_guid = $forum->guid;
					array_push($forums, $subForum);

					$options = array(
						"type" => "object",
						"subtype" => "hjforum",
						"container_guid" => $subForum->guid,
						'limit' => false,
						"order_by" => "e.last_action DESC"
					);
					$subSubForums = elgg_get_entities($options);
					foreach($subSubForums as $subSubForum){
						$subSubForum->parent_guid = $subForum->guid;
						array_push($forums, $subSubForum);
					}
				}
			}
			foreach($forums as $forum) {
				$parent_guid = (int) $forum->parent_guid; 

				if(!empty($parent_guid)) {
					if($temp = get_entity($parent_guid)) {
						if($temp->getSubtype() != "hjforum") {
							$parent_guid = 0;
						}
					} else {
						$parent_guid = 0;
					}
				} else {
					$parent_guid = 0;
				}
				if(!array_key_exists($parent_guid, $parents)) {
					$parents[$parent_guid] = array();
				}
				
				$parents[$parent_guid][] = $forum;
			}
			
			$result = hj_forums_sort_forums($parents, 0);				
		}
	}
	
	return $result;
}

function hj_forums_sort_forums($forums, $parent_guid = 0) {		
	$result = false;
	
	if(array_key_exists($parent_guid, $forums)) {
		$result = array();
		
		foreach($forums[$parent_guid] as $subForum) {
			$children = hj_forums_sort_forums($forums, $subForum->getGUID());
			$order = $subForum->order;
			if(empty($order)) {
				$order = 0;
			}
			
			while(array_key_exists($order, $result)) {
				$order++;
			}
			
			$result[$order] = array(
				"forum" => $subForum,
				"children" => $children
			);
		}
		
		//ksort($result);
	}
	
	return $result;
}

function hj_forums_make_menu_items($forums){
	$result = false;
	
	if(!empty($forums) && is_array($forums)){
		$result = array();
		
		foreach($forums as $index => $level){
			if($forum = elgg_extract("forum", $level)){
				$options = array(
					"name" => "forum_" . $forum->getGUID(),
					"text" => $forum->title,
					"href" => elgg_get_site_url()."forum/view/".$forum->getGUID(),
					"priority" => $forum->order
				);

				$forum_menu = ElggMenuItem::factory($options);
				
				if($children = elgg_extract("children", $level)){
					$forum_menu->setChildren(hj_forums_make_menu_items($children));
				}
				
				$result[] = $forum_menu;
			}
		}
	}
	
	return $result;
}
function hj_forum_notify_message($hook, $type, $message, $params) {
    $entity = $params['entity'];
	$to_entity = $params['to_entity'];
	$method = $params['method'];
    
	if (($entity instanceof ElggEntity) && ($entity->getSubtype() == 'hjforum')) {
		$descr = $entity->description;
		$title = $entity->title;
		$owner = $entity->getOwnerEntity();
        
		return elgg_echo('hjforum:notification', array(
			$owner->name,
			$title,
			$descr,
			$entity->getURL()
		));
	}
    
    else if (($entity instanceof ElggEntity) && ($entity->getSubtype() == 'hjforumpost')) {
		$descr = $entity->description;
		$title = $entity->title;
		$owner = $entity->getOwnerEntity();
        
		return elgg_echo('hjforumpost:notification', array(
			$owner->name,
			$title,
			$descr,
			$entity->getURL()
		));
	}
	return null;
}

function hj_forum_topic_notify_message($hook, $type, $message, $params) {
    $entity = $params['entity'];
	$to_entity = $params['to_entity'];
	$method = $params['method'];
    
	if (($entity instanceof ElggEntity) && ($entity->getSubtype() == 'hjforumtopic')) {
		$descr = $entity->description;
		$title = $entity->title;
		$owner = $entity->getOwnerEntity();
        
		return elgg_echo('hjforumtopic:notification', array(
			$owner->name,
			$title,
			$descr,
			$entity->getURL()
		));
	}
}