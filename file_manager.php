<?php
/*
Plugin Name: File Manager
Description: Upload and tag files
Version: 0.1.0
Author: Maxwell Simmer
Author URI: http://maxwellsimmer.com
*/


define('FILE_MANAGER_SITE_URL',
  json_decode(
    json_encode(
      $SITEURL,
      JSON_UNESCAPED_SLASHES
    ),
    true
  )[0]
);
// get correct id for plugin
define('FILE_MANAGER_PLUGIN_NAME', basename(__FILE__, '.php'));
define('FILE_MANAGER_VERSION', '0.1.0');
define('FILE_MANAGER_DIR', 'file_manager/');
define('FILE_MANAGER_UPLOADS_PATH', dirname(__FILE__) . '/' . FILE_MANAGER_DIR . 'uploads/');
define('FILE_MANAGER_META_PATH', dirname(__FILE__) . '/' . FILE_MANAGER_DIR . 'uploads/metadata.json');
define('FILE_MANAGER_TAG_LIST', dirname(__FILE__) . '/' . FILE_MANAGER_DIR . 'uploads/tags.txt');
define('FILE_MANAGER_PLUGIN_URI', $SITEURL . $GSADMIN . '/plugins/');
define('FILE_MANAGER_ASSETS_URI', $SITEURL . 'plugins/file_manager/');

// register plugin
register_plugin(
  FILE_MANAGER_PLUGIN_NAME,
  'File Manager',
  FILE_MANAGER_VERSION,
  'Maxwell Simmer',
  'https://github.com/msimmer/tag-manager',
  'Upload and tag files',
  'file_manager',               // page type
  'file_manager_main'           // main fn
);


// activate filter
add_action('header','file_manager_header');
add_action('footer','file_manager_footer');
add_action('nav-tab', 'createNavTab', array('file_manager', FILE_MANAGER_PLUGIN_NAME, 'File Manager', 'view'));
add_action('file_manager-sidebar', 'createSideMenu', array(FILE_MANAGER_PLUGIN_NAME, 'File Actions', 'view'));
add_action('file_manager-sidebar', 'createSideMenu', array(FILE_MANAGER_PLUGIN_NAME, 'Save Changes', 'proxy_update')); // action is hooked into via JS

// backend hooks
add_action('admin-pre-header', 'fm_pre_header');
add_action('admin-pre-header', 'fm_scripts_styles');
add_action('changedata-save','fm_pre_save');

// page save hook
function fm_pre_save(){
  $post_id = $_POST['post-id'];
  $post_tags = explode(',', $_POST['post-metak']);
  $tags_data = file_get_contents(FILE_MANAGER_TAG_LIST);
  $all_tags = (!$tags_data || $tags_data == '') ? array() : unserialize($tags_data);

  // add tags if they don't exist, and add post_id to tag
  foreach ($post_tags as $tag) {
    if (!array_key_exists($tag, $all_tags)) {
      $all_tags[$tag] = array();
    }
    if (!in_array($post_id, $all_tags[$tag])) {
      $all_tags[$tag][] = $post_id;
    }
  }

  // remove post_id from tag list if tag is un-checked, remove tag from all_tags if it's empty
  foreach ($all_tags as $key => $tag) {
    if (!in_array($key, $post_tags) && in_array($post_id, $tag)) {
      $index = array_keys($all_tags[$key], $post_id)[0];
      unset($all_tags[$key][$index]);
      if (empty($all_tags[$key])) {
        unset($all_tags[$key]);
      }
    }
  }

  file_put_contents(FILE_MANAGER_TAG_LIST, serialize($all_tags));
}

// redirects
function fm_pre_header() {
  global $SITEURL;
  if (basename($_SERVER['REQUEST_URI']) == 'upload.php') {
    $files_page = FILE_MANAGER_SITE_URL . 'admin/load.php?id=file_manager&view';
    header("Location: {$files_page}");
    exit;
  }
}

// custom header/footer
function file_manager_header() { include(GSPLUGINPATH.'file_manager/header.php'); }
function file_manager_footer() { include(GSPLUGINPATH.'file_manager/footer.php'); }

// main script
function file_manager_main() {
  if (isset($_GET['view'])) {
    include(GSPLUGINPATH.'file_manager/view.php');
  }
}

// conditional script/styles loading
function fm_scripts_styles() {

  // don't bother unless we're on FM plugin page
  if (isset($_GET['id']) && $_GET['id'] == FILE_MANAGER_PLUGIN_NAME) {

    // register/queue plugin styles
    register_style('tm_featherlight_css', FILE_MANAGER_ASSETS_URI . 'vendor/featherlight-1.5.0/featherlight.min.css', '1.5.0', 'screen');
    register_style('tm_featherlight_gallery_css', FILE_MANAGER_ASSETS_URI . 'vendor/featherlight-1.5.0/featherlight.gallery.min.css', '1.5.0', 'screen');
    register_style('tm_styles', FILE_MANAGER_ASSETS_URI . 'stylesheets/styles.css', FILE_MANAGER_VERSION, 'screen');

    queue_style('tm_featherlight_css', GSBACK);
    queue_style('tm_featherlight_gallery_css', GSBACK);
    queue_style('tm_styles', GSBACK);

    // register/queue plugin scripts
    register_script('tm_jquery', FILE_MANAGER_ASSETS_URI . 'bower_components/jquery/dist/jquery.min.js', '^3.1.0', false);
    register_script('tm_jquery_migrate', FILE_MANAGER_ASSETS_URI . 'bower_components/jquery-migrate/index.js', '3.0.0', false);
    register_script('tm_jquery_ui', FILE_MANAGER_ASSETS_URI . 'bower_components/jquery-ui/jquery-ui.min.js', '^1.12.0', false);
    register_script('tm_jquery_event_drag', FILE_MANAGER_ASSETS_URI . 'vendor/jquery.event.drag-2.2/jquery.event.drag-2.2.js', '2.2', false);
    register_script('tm_jquery_event_drag_live', FILE_MANAGER_ASSETS_URI . 'vendor/jquery.event.drag-2.2/jquery.event.drag.live-2.2.js', '2.2', false);
    register_script('tm_jquery_event_drop', FILE_MANAGER_ASSETS_URI . 'vendor/jquery.event.drop-2.2/jquery.event.drop-2.2.js', '2.2', false);
    register_script('tm_jquery_event_drop_live', FILE_MANAGER_ASSETS_URI . 'vendor/jquery.event.drop-2.2/jquery.event.drop.live-2.2.js', '2.2', false);
    register_script('tm_featherlight_js', FILE_MANAGER_ASSETS_URI . 'vendor/featherlight-1.5.0/featherlight.min.js', '1.5.0', false);
    register_script('tm_featherlight_gallery_js', FILE_MANAGER_ASSETS_URI . 'vendor/featherlight-1.5.0/featherlight.gallery.min.js', '1.5.0', false);
    register_script('tm_scripts', FILE_MANAGER_ASSETS_URI . 'javascripts/scripts.js', '0.1.0', false);

    queue_script('tm_jquery', GSBACK);
    queue_script('tm_jquery_migrate', GSBACK);
    queue_script('tm_jquery_ui', GSBACK);
    queue_script('tm_jquery_event_drag', GSBACK);
    queue_script('tm_jquery_event_drag_live', GSBACK);
    queue_script('tm_jquery_event_drop', GSBACK);
    queue_script('tm_jquery_event_drop_live', GSBACK);
    queue_script('tm_featherlight_js', GSBACK);
    queue_script('tm_featherlight_gallery_js', GSBACK);
    queue_script('tm_scripts', GSBACK);
  }
}

// global styles
register_style('tm_global_styles', FILE_MANAGER_ASSETS_URI . 'stylesheets/admin.css', FILE_MANAGER_VERSION, 'screen');
queue_style('tm_global_styles', GSBACK);
