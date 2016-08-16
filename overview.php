<?php
/*

Overview Page

*/

include_once 'show.php'; ?>

<h3 class="floated" style="float:left">File Manager</h3>
<p class="clear">Upload and tag files here.</p>

<form name="file_manager" id="file_manager" method="POST" action="<?php echo FILE_MANAGER_ASSETS_URI . 'tag_manager.php'; ?>" enctype="multipart/form-data">

  <table class="fixed">
    <tr>
      <th colspan="2">Add Files</th>
    </tr>
    <tr>
      <td>
        <input type="hidden" name="MAX_FILE_SIZE" value="1000000" /> <!-- 1MB -->
        <input type="hidden" name="files_update" value="">
        <input name="user_files[]" type="file" multiple>
      </td>
      <td>
        <input type="text" name="tags" placeholder="Tags">
      </td>
    </tr>
    <tr>
      <td class="header">
        Publish Date
      </td>
      <td>
        <input type="date" name="add_publish_date" placeholder="Publish Date" value="" novalidate>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input id="add_files" type="submit" name="submit" value="Submit">
      </td>
    </tr>
  </table>

  <table class="fixed">
    <tr>
      <th colspan="4">Update Files</th>
    </tr>
    <tr>
      <td class="header">
        Tags
      </td>
      <td>
        <input type="text" name="update_tags" placeholder="Tags">
      </td>
      <td>
        <input type="submit" name="add_tags" value="Add Tags to Selection">
      </td>
      <td>
        <input type="submit" name="remove_tags" value="Remove Tags from Selection">
      </td>
    </tr>
    <tr>
      <td colspan="2" class="header">
        Publish Date
      </td>
      <td colspan="2">
        <input type="date" name="update_publish_date" placeholder="Publish Date" value="" novalidate>
      </td>
    </tr>
    <tr>
      <td colspan="4">
        <input id="update_files" type="submit" name="submit" value="Submit">
      </td>
    </tr>

  </table>
</form>

<table>
  <tr>
    <th>Sort by:</th>
  </tr>
  <tr>
    <td>
      <ul class="master" data-master=[]></ul>
    </td>
  </tr>
</table>

<?php
  $site = new Site;
  $content = $site->show();
  $html = $site->render($content);
  echo $html;
  echo "<hr style='clear:both;visibility:hidden;'>";?>
