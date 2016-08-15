<?php

/**
*
*/
class Site
{

  private $metadata = null;
  private $meta_path = FILE_MANAGER_META_PATH;

  public function show() {
    try {
      if (
        !file_exists($this->meta_path) ||
        !$this->metadata = file_get_contents($this->meta_path)
      ) {
        throw new RuntimeException('Couldn\'t read metadata.');
      }

      if (!$parsed = json_decode($this->metadata)) {
        throw new RuntimeException('Couldn\'t parse metadata.');
      }

    } catch (RuntimeException $e) {
      $e->getMessage();
    }

    return $parsed;
  }

  public function render($content) {

    try {
      if (!$content || gettype($content) != 'array') {
        throw new RuntimeException('Content isn\'t an array.');
      }
      $html = '';
      foreach ($content as $doc) {
        $html .= "<div data-filename='". $doc->file_name ."'";
        $html .= "data-id='". $doc->_id ."'";
        $html .= "data-tags='". json_encode($doc->tags) ."' class='doc'>";
        $html .= "<img src='" . FILE_MANAGER_ASSETS_URI;

        switch ($doc->mime_type) {
          case 'image/jpeg':
          case 'image/png':
          case 'image/gif':
            $html .= $doc->file_path;
            break;
          case 'application/pdf':
            $html .= "images/pdf-placeholder.jpg";
            break;
          case 'application/msword':
            $html .= "images/word-placeholder.png";
            break;
          case 'text/plain':
            $html .= "images/text-placeholder.png";
            break;

          default:
            $html .= "images";
            break;
        }

        $html .= "'>";
        $html .= "<ul class='tags'>";
        foreach ($doc->tags as $tag) {
          $html .= "<li><a data-remove='".$tag."' href='#'>".$tag."</a></li>";
        }
        $html .= "</ul>";
        $html .= "<p>". $doc->name ."</p>";
        $html .= "</div>";
      }

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }

    return $html;

  }

}
