<?php
/*
Plugin Name: SocialSuite
Plugin URI: http://www.socialsuite.it
Description: This makes it possible for Socialsuite to manage a Wordpress blog.
Author: Stefano Verna
Version: 0.1
Author URI: http://www.welaika.com
*/

add_filter('xmlrpc_methods', 'socialsuite_extend_xmlrpc');

function socialsuite_extend_xmlrpc($methods) {
    $methods['socialsuite.get_posts'] = 'socialsuite_get_posts';
    $methods['socialsuite.get_comments'] = 'socialsuite_get_comments';
    $methods['socialsuite.get_infos'] = 'socialsuite_get_infos';
    $methods['socialsuite.get_comment'] = 'socialsuite_get_comment';
    $methods['socialsuite.test_credentials'] = 'socialsuite_test_credentials';
    return $methods;
}

function socialsuite_test_credentials($params) {
  $username  = $params[0];
  $password  = $params[1];

  global $wp_xmlrpc_server;
  if (!$user = $wp_xmlrpc_server->login($username, $password)) {
    return $wp_xmlrpc_server->error;
  }

  return array('valid_credentials' => true);
}

function socialsuite_get_infos($params) {
  //Separate Params from Request
  $username  = $params[0];
  $password  = $params[1];

  global $wp_xmlrpc_server;
  // Let's run a check to see if credentials are okay
  if (!$user = $wp_xmlrpc_server->login($username, $password)) {
    return $wp_xmlrpc_server->error;
  }

  $infos = array("siteurl", "name", "description");
  $result = array();
  foreach ($infos as $info) {
    $result[$info] = get_bloginfo($info);
  }

  $user = get_userdatabylogin($username);
  $result["user_data"] = array(
    'email' => $user->user_email,
    'nickname' => $user->nickname,
    'level' => $user->user_level,
    'id' => $user->ID,
    'capabilities' => $user->wp_capabilities
  );

  return $result;
}

function socialsuite_get_comments($params) {
  //Separate Params from Request
  $username  = $params[0];
  $password  = $params[1];
  $post_id   = $params[2];
  $offset    = $params[3];
  $limit     = $params[4];

  global $wp_xmlrpc_server;
  // Let's run a check to see if credentials are okay
  if (!$user = $wp_xmlrpc_server->login($username, $password)) {
    return $wp_xmlrpc_server->error;
  }

  $comments = get_comments(array('status' => 'approve', 'post_id' => $post_id, 'offset' => $offset, 'number' => $limit ));
}


function socialsuite_get_comment($args) {

  $blog_id  = (int) $args[0];
  $username  = $args[1];
  $password  = $args[2];
  $comment_id  = (int) $args[3];

  global $wp_xmlrpc_server;
  if ( !$user = $wp_xmlrpc_server->login($username, $password) )
    return $wp_xmlrpc_server->error;

  if ( !current_user_can( 'moderate_comments' ) )
    return new IXR_Error( 403, __( 'You are not allowed to moderate comments on this site.' ) );

  if ( ! $comment = get_comment($comment_id) )
    return new IXR_Error( 404, __( 'Invalid comment ID.' ) );

  return $comment;
}

function socialsuite_get_posts($params) {
  //Separate Params from Request
  $username  = $params[0];
  $password  = $params[1];
  $offset    = $params[2];
  $limit     = $params[3];

  global $wp_xmlrpc_server;
  // Let's run a check to see if credentials are okay
  if (!$user = $wp_xmlrpc_server->login($username, $password)) {
    return $wp_xmlrpc_server->error;
  }

  $posts = get_posts(array('status' => 'approve', 'post_id' => $post_id, 'offset' => $offset, 'numberposts' => $limit ));
  foreach ($posts as $post) {
    $comments = get_comments(array('status' => 'approve', 'post_id' => $post->ID, 'number' => 3));
    $post->comments = $comments;
    $data = get_userdata($post->post_author);
    $post->author_name = $data->display_name;
    $post->author_url = get_bloginfo("siteurl");
    $post->post_permalink = get_permalink($post->ID);
    $post->author_email = $data->user_email;
  }

  return $posts;
}
