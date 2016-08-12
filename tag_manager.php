<?php

header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Europe/Berlin');


/**
*
*/
class Utility
{

  private static $meta_path = './uploads/metadata.txt';

  public static function findWhere($arr, $prop, $val) {
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

  public static function findAll($arr, $prop, $val) {
    $result = [];
    foreach ($arr as $item) {
      if (isset($item->{$prop}) && $item->{$prop} == $val) {
        $result[] = $item;
      }
    }
    return $result;
  }

  // check if a document is a 'member' of a collection
  public static function belongsTo($obj, $key) {
    try {
      if (
        !property_exists($obj, 'collections') ||
        !is_array($obj->collections)
      ) {
        throw new RuntimeException('Object has no `collections` property.');
      }

      return (bool) (
        array_search($key, $obj->collections) > -1
      );

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }

  // check if a document is tagged with a specific tag
  public static function isTagged($obj, $key) {
    try {
      if (
        !property_exists($obj, 'tags') ||
        !is_array($obj->tags)
      ) {
        throw new RuntimeException('Object has no `tags` property.');
      }

      return (bool) (
        array_search($key, $obj->tags) > -1
      );

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }

  public static function template($data, $index) {
    $pinfo = pathinfo($data['name'][$index]);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_name = sprintf('%s.%s',
      sha1_file($data['tmp_name'][$index]),
      $pinfo['extension']
    );
    return (object) array(
      '_id' => sha1((string) rand(100000000000,999999999999)),
      'name' => $pinfo['basename'],
      'created_at' => time(),
      'mime_type' => $finfo->file($data['tmp_name'][$index]),
      'extension' => $pinfo['extension'],
      'file_name' => $file_name,
      'file_path' => 'uploads/'. $file_name,
      'tags' => [],
      'collections' => []
    );
  }

}


/**
*
*/
class TagManager
{

  private $ext = NULL;
  private $files = [];
  private $file_paths = [];
  private $metadata = NULL;
  private $meta_path = './uploads/metadata.txt';
  private $tags = NULL;


  public function __construct(){

    // make sure there's post!
    if (!isset($_POST)) return;

    // make sure there's meta!
    $this->assertMeta();

    // update files and include tags with them
    // or
    // select pre-existing files and update their metadata

    // upload any files that have been selected
    $this->uploadFiles();

    // around here we're also going to want to check if there are pre-existing
    // files that have been selected that we need to update
    $this->updateSelection();

    // update metadata file
    $this->updateMeta();

    // redirect to the referring page
    $this->finish();

  }



  // utility
  public function readMeta () {

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
  public function assertMeta () {
    if (!$this->metadata) $this->readMeta();
  }

  // convenience
  public function find($id) {
    return Utility::findWhere($this->metadata, '_id', $id);
  }


  public function uploadFiles() {

    try {

      // No files were sent, update existing
      if (
        !isset($_FILES) ||
        !isset($_FILES['userfiles']
      )) {
        return;
      }

      // Undefined | Check errors in Multiple Files | $_FILES Corruption Attack
      // If this request falls under any of them, treat it invalid.
      if (!isset($_FILES['userfiles']['error'])) {
        throw new RuntimeException('Invalid parameters.');
      }

      foreach ($_FILES['userfiles']['error'] as $errors) {
        if (isset($errors[0])) {
          throw new RuntimeException('Invalid parameters.');
        }

        // Check each $_FILES['userfiles']['error'] value.
        switch ($errors) {
          case UPLOAD_ERR_OK:
            break;
          case UPLOAD_ERR_NO_FILE:
            return;
            // throw new RuntimeException('No file sent.');
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
          default:
            throw new RuntimeException('Unknown errors.');
        }

      } // foreach

      // You should also check filesize here.
      foreach ($_FILES['userfiles']['size'] as $size) {
        if ($size > 1000000) {
          throw new RuntimeException('Exceeded filesize limit.');
        }
      } // foreach


      // DO NOT TRUST $_FILES['userfiles']['mime'] VALUE !!
      // Check MIME Type by yourself.
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      for ($i=0; $i < sizeof($_FILES['userfiles']['name']); $i++) {

        if (false === $ext = array_search(
          $finfo->file($_FILES['userfiles']['tmp_name'][$i]),
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

        $this->files[] = Utility::template($_FILES['userfiles'], $i);

        if (!move_uploaded_file(
          $_FILES['userfiles']['tmp_name'][$i],
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


  //
  public function updateSelection() {

    try {

      if (
        !isset($_POST) ||
        !isset($_POST['filesupdate']) ||
        empty($objs = json_decode($_POST['filesupdate']))
      ) {
        // throw new RuntimeException('No files to update.');
        return;
      }

      foreach ($objs as $obj) {
        try {
          if (!file_exists('./uploads/' . $obj->file_name)) {
            throw new RuntimeException('Attempting to update a file that doesn\'t exist.');
          }

          if (!$doc = $this->find($obj->_id)) {
            throw new RuntimeException('The _id doesn\'t exist.');
          }

          $doc->tags = $obj->tags;

        } catch (RuntimeException $e) {
          echo $e->getMessage();
        }
      } // foreach


    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }
  }


  // Add tags to all files uploaded
  public function updateMeta(){

    try {

      if (
        !isset($_POST) ||
        !isset($_POST['tags']) ||
        empty($_POST['tags'])
      ) {
        // throw new RuntimeException('No tags.');
        // return;
      } else {
        $this->tags = preg_split(
          '/\s*,\s*/',
          trim($_POST['tags']),
          -1,
          PREG_SPLIT_NO_EMPTY
        );
      }

      // start adding tags to metadata
      foreach ($this->files as $doc) {

        if ($this->tags) {

          $doc->tags = array_unique(
            array_merge(
              $doc->tags,
              $this->tags
          ));
        }

        if (!$this->find($doc->_id)) {
          $this->metadata[] = $doc;
        }

      } // foreach

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

  public function finish(){
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;
  }

}


$tag_manager = new TagManager;
