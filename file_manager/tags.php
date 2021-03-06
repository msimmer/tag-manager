<?php

date_default_timezone_set('Europe/Berlin');

/**
*
*/
class Utility
{

  public static function find_where($arr, $prop, $val) {
    foreach ($arr as $item) {
      if (
        isset($item->{$prop}) &&
        $item->{$prop} == $val
      ) {
        return $item;
      }
    }
    return false;
  }

  public static function find_all($arr, $prop, $val) {
    $result = [];
    foreach ($arr as $item) {
      if (
        isset($item->{$prop}) &&
        $item->{$prop} == $val
      ) {
        $result[] = $item;
      }
    }
    return $result;
  }

  // check if a document is a member of a collection
  public static function belongs_to($obj, $key) {
    try {
      if (
        !property_exists($obj, 'collections') ||
        !is_array($obj->collections)
      ) {
        throw new RuntimeException('Object has no `collections` property.');
      }

      return (bool) (
        array_search(
          $key,
          $obj->collections
        ) > -1
      );

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }

  // check if a document is tagged with a specific tag
  public static function is_tagged($obj, $key) {
    try {
      if (
        !property_exists($obj, 'tags') ||
        !is_array($obj->tags)
      ) {
        throw new RuntimeException('Object has no `tags` property.');
      }

      return (bool) (
        array_search(
          $key,
          $obj->tags
        ) > -1
      );

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }

  public static function get_key($arr, $obj) {
    foreach ($arr as $key => $val) {
      if (
        property_exists($val, '_id') &&
        $val->_id == $obj->_id
      ) {
        return $key;
      }
    }
    return false;
  }

  public static function template($data, $post, $index) {
    $pinfo = pathinfo($data['name'][$index]);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_name = sprintf('%s.%s',
      sha1_file($data['tmp_name'][$index]),
      $pinfo['extension']
    );
    $publish_date = NULL;
    if ($post->update_publish_date != NULL) {
      $publish_date = $post->update_publish_date;
    } elseif ($post->add_publish_date != NULL) {
      $publish_date = $post->add_publish_date;
    }
    $dimensions = [];
    switch ($finfo->file($data['tmp_name'][$index])) {
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
        $image_size = getimagesize($data['tmp_name'][$index]);
        preg_match_all('~["](\d+)["]~', $image_size[3], $matches);
        $dimensions['width'] = $matches[1][0];
        $dimensions['height'] = $matches[1][1];
        break;

      default:
        break;
    }

    return (object) array(
      '_id' => sha1((string) rand(100000000000,999999999999)),
      'name' => $pinfo['basename'],
      'published' => $post->published || $post->published_add,
      'creation_date' => time(),
      'publish_date' => $publish_date,
      'mime_type' => $finfo->file($data['tmp_name'][$index]),
      'extension' => $pinfo['extension'],
      'file_name' => $file_name,
      'file_path' => 'uploads/'. $file_name,
      'dimensions' => $dimensions,
      'tags' => [],
      'collections' => []
    );
  }

}


/**
*
*/
class Tags
{

  private $files = array();
  private $metadata = NULL;
  private $meta_path = './uploads/metadata.json';
  private $tags_path = './uploads/tags.txt';
  private $tags = NULL;
  private $post = NULL;
  private $trash = array();

  public function __construct(){

    // make sure there's post!
    if (!isset($_POST)) return;

    // store post vars
    $this->parse_post();

    // make sure there's meta!
    $this->assert_meta();

    // update files and include tags with them
    // or
    // select pre-existing files and update their metadata

    // upload any files that have been selected
    $this->upload_files();

    // around here we're also going to want to check if there are pre-existing
    // files that have been selected that we need to update
    $this->updated_selection();

    // update metadata file
    $this->update_meta();

    // update list of all tags
    $this->update_tags_list();

    // remove files if there are any in the trash
    $this->remove_files();

    // redirect to the referring page
    $this->finish();

  }

  private function parse_post() {

    $this->post = (object) array(
      'published' => isset(
        $_POST['published']
      ) &&
      !empty(
        $_POST['published']
      ) ?
      ($_POST['published'] == 'on') ?
      true :
      false :
      false
      ,

      'published_add' => isset(
        $_POST['published_add']
      ) &&
      !empty(
        $_POST['published_add']
      ) ?
      ($_POST['published_add'] == 'on') ?
      true :
      false :
      false
      ,

      'tags' => isset(
        $_POST['tags']
      ) &&
      !empty(
        $_POST['tags']
      ) ?
      preg_split(
        '~\s*,\s*~',
        trim($_POST['tags']),
        -1,
        PREG_SPLIT_NO_EMPTY
      ) :
      NULL
      ,
      'add_publish_date' => isset(
        $_POST['add_publish_date']
      ) &&
      preg_match(
          '~[0-9]{4}\-[0-9]{2}\-[0-9]{2}~',
          $_POST['add_publish_date']
      ) ?
      strtotime($_POST['add_publish_date']) :
      NULL
      ,
      'update_publish_date' => isset(
        $_POST['update_publish_date']
      ) &&
      preg_match(
          '~[0-9]{4}\-[0-9]{2}\-[0-9]{2}~',
          $_POST['update_publish_date']
      ) ?
      strtotime($_POST['update_publish_date']) :
      NULL
      ,
      'max_file_size' => isset(
        $_POST['MAX_FILE_SIZE']
      ) ?
      $_POST['MAX_FILE_SIZE'] :
      1000000
      ,
      'files_update' => isset(
        $_POST['files_update']
      ) &&
      !empty(
        json_decode($_POST['files_update'])
      ) ?
      json_decode($_POST['files_update']) :
      NULL
      ,

      'delete_selected' => isset(
        $_POST['delete_selected']
      ) &&
      !empty(
        $_POST['delete_selected']
      ) ?
      ($_POST['delete_selected'] == 'on') ?
      true :
      false :
      false
      ,

    );

  }

  // utility
  private function readMeta () {

    // check that metadata file exists, if not, create it
    if (!file_exists($this->meta_path)) {
      file_put_contents($this->meta_path, '[]');
    }

    // load metadata JSON and parse, performing checks along the way
    $this->metadata = json_decode(
      file_get_contents(
        $this->meta_path
    ));
    if (
      !is_array($this->metadata) ||
      $this->metadata === NULL
    ) {
      throw new RuntimeException('Couldn\'t read metadata.');
    }
  }

  // make sure meta exists
  private function assert_meta () {
    if (!$this->metadata) $this->readMeta();
  }

  // convenience
  private function find($id) {
    return Utility::find_where($this->metadata, '_id', $id);
  }


  private function upload_files() {

    try {

      // No files were sent, only update existing
      if (
        !isset($_FILES) ||
        !isset($_FILES['user_files']
      )) {
        return;
      }

      // Undefined | Check errors in Multiple Files | $_FILES Corruption Attack
      // If this request falls under any of them, treat it invalid.
      if (!isset($_FILES['user_files']['error'])) {
        throw new RuntimeException('Invalid parameters.');
      }

      foreach ($_FILES['user_files']['error'] as $errors) {
        if (isset($errors[0])) {
          throw new RuntimeException('Invalid parameters.');
        }

        // Check each $_FILES['user_files']['error'] value
        switch ($errors) {
          case UPLOAD_ERR_OK:
            break;
          case UPLOAD_ERR_NO_FILE:
            return; // No files sent
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
          default:
            throw new RuntimeException('Unknown errors.');
        }

      } // foreach

      // Check filesizes
      foreach ($_FILES['user_files']['size'] as $size) {
        if ($size > $this->post->max_file_size) {
          throw new RuntimeException('Exceeded filesize limit.');
        }
      } // foreach


      // Check MIME Types
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      for ($i=0; $i < sizeof($_FILES['user_files']['name']); $i++) {

        if (false === $ext = array_search(
          $finfo->file($_FILES['user_files']['tmp_name'][$i]),
          array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
          ),
          true
        )) {
          throw new RuntimeException('Invalid file format.');
        }

        $this->files[] = Utility::template($_FILES['user_files'], $this->post, $i);

        if (!move_uploaded_file(
          $_FILES['user_files']['tmp_name'][$i],
          $this->files[$i]->file_path
        )) {
          throw new RuntimeException('Failed to move uploaded file.');
        }

      } // for

      echo 'Files are uploaded successfully.';

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }


  // Update pre-existing metadata
  private function updated_selection() {

    try {

      if (!$this->post->files_update) return;

      foreach ($this->post->files_update as $obj) {
        try {
          if (!file_exists('./uploads/' . $obj->file_name)) {
            throw new RuntimeException('Attempting to update a file that doesn\'t exist.');
          }

          if (!$doc = $this->find($obj->_id)) {
            throw new RuntimeException('The _id doesn\'t exist.');
          }

          // remove selected files

          if (
            gettype($this->post->delete_selected) == 'boolean' &&
            $this->post->delete_selected === true
          ) {

            // remove from metadata
            $key = Utility::get_key($this->metadata, $obj);
            if (
              $key !== false &&
              gettype($key) == 'integer'
            ) {
              unset($this->metadata[$key]);
            }

            // slate for deletion
            $this->trash[] = './uploads/' . $obj->file_name;

          } else { // only execute if we're not deleting files

            // set tags
            $doc->tags = $obj->tags;

            // set publish date
            if ($this->post->update_publish_date) {
              $doc->publish_date = $this->post->update_publish_date;
            }

            // set publish status
            if (gettype($this->post->published) == 'boolean') {
              $doc->published = $this->post->published;
            }
          }

        } catch (RuntimeException $e) {
          echo $e->getMessage();
        }
      } // foreach


    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }


  // Add tags to all files uploaded
  private function update_meta() {

    try {

      // start adding tags to metadata
      foreach ($this->files as $doc) {

        if ($this->post->tags) {

          $doc->tags = array_unique(
            array_merge(
              $doc->tags,
              $this->post->tags
          ));
        }

        if (!$this->find($doc->_id)) {
          $this->metadata[] = $doc;
        }

      } // foreach

      // reset array values in case we've removed any documents
      $this->metadata = array_values($this->metadata);

      if (
        !file_put_contents(
          $this->meta_path,
          json_encode(
            $this->metadata,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT
      ))) {
        throw new RuntimeException('Couldn\'t print metadata to file.');
      }

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }

  private function update_tags_list(){

    $files = NULL;

    if (isset($this->files) && !empty($this->files)) {
      $files = $this->files;
    } elseif (isset($this->post->files_update) && !empty($this->post->files_update)) {
      $files = $this->post->files_update;
    } elseif (isset($this->trash) && !empty($this->trash)) {
      $files = $this->trash;
    }

    if (!$files) return;

    foreach ($files as $file) {
      $post_id = $file->_id;
      $post_tags = $file->tags;
      $tags_data = file_get_contents($this->tags_path);
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
        if (
          property_exists($file, 'delete_selected') &&
          $file->delete_selected
        ) {
          if (in_array($key, $post_tags) && in_array($post_id, $tag)) {
            $index = array_keys($all_tags[$key], $post_id)[0];
            unset($all_tags[$key][$index]);
            if (empty($all_tags[$key])) {
              unset($all_tags[$key]);
            }
          }
        } elseif (!in_array($key, $post_tags) && in_array($post_id, $tag)) {
          $index = array_keys($all_tags[$key], $post_id)[0];
          unset($all_tags[$key][$index]);
          if (empty($all_tags[$key])) {
            unset($all_tags[$key]);
          }
        }


      }

      file_put_contents($this->tags_path, serialize($all_tags));
    }
  }

  private function remove_files() {
    foreach ($this->trash as $file_path) {
      try {
        if (!unlink($file_path)) {
          throw new RuntimeException('Couldn\'t delete file.');
        }
      } catch (RuntimeException $e) {
        echo $e->getMessage();
      }
    }
  }

  private function finish(){
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;
  }

}


$tags = new Tags;
