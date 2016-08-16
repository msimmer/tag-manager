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
    $html = '';
    try {
      if (!$content || gettype($content) != 'array') {
        throw new RuntimeException('Content isn\'t an array.');
      }
      foreach ($content as $doc) {
        $html .= "<div data-filename='". $doc->file_name ."'";
        $html .= "data-id='". $doc->_id ."'";
        if (property_exists($doc, 'publish_date')) {
          $html .= "data-publish-date='". $doc->publish_date ."'";
        }
        if (property_exists($doc, 'published')) {
          $html .= "data-status='". ($doc->published ? 'Published' : 'Draft') ."'";
        }
        $html .= "data-nice-name='". $doc->name ."'";
        $html .= "data-tags='". json_encode($doc->tags) ."' class='doc'>";
        $html .= "<div class='tr'>";

        switch ($doc->mime_type) {
          case 'image/jpeg':
          case 'image/png':
          case 'image/gif':
            $img_src = $doc->file_path;
            break;
          case 'application/pdf':
            $img_src = "images/pdf-placeholder.jpg";
            break;
          case 'application/msword':
            $img_src = "images/word-placeholder.png";
            break;
          case 'text/plain':
            $img_src = "images/text-placeholder.png";
            break;

          default:
            $img_src = "images";
            break;
        }

        $html .= "<a href='" . FILE_MANAGER_ASSETS_URI . $img_src . "' class='tc gallery'>";
        $html .= "<img src='" . FILE_MANAGER_ASSETS_URI . $img_src;

        $html .= "'>";
        $html .= "</a>"; // .tc
        $html .= "</div>"; // .tr

        $html .= "<div class='tr'>";
        $html .= "<div class='tc'>";

        $html .= "<ul class='tags'>";
        foreach ($doc->tags as $tag) {
          $html .= "<li><a data-remove='".$tag."' href='#'>".$tag."</a></li>";
        }
        $html .= "</ul>";
        if (property_exists($doc, 'published')) {
          $html .= "<p><b>Status</b>: ". ($doc->published ? 'Published' : 'Draft') ."</p>";
        }
        if (
          property_exists($doc, 'publish_date') &&
          gettype($doc->publish_date) == 'integer'
        ) {
          $html .= "<p><b>Published on</b>: ". date('Y-m-d', $doc->publish_date) ."</p>";
        }
        $html .= "<p><b>File Name</b>:". $doc->name ."</p>";

        $html .= "</div>"; // .tc
        $html .= "</div>"; // .tr
        $html .= "</div>"; // .doc
      }

    } catch (RuntimeException $e) {
      echo $e->getMessage();
    }

    return $html;

  }

}
