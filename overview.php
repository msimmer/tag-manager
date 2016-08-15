<?php
/*

Overview Page

*/

include_once 'show.php'; ?>

<h3 class="floated" style="float:left">File Manager</h3>
<p class="clear">Upload and tag files here.</p>

<form name="fileManager" id="fileManager" method="POST" action="tag_manager.php" enctype="multipart/form-data">
  <table>
    <tr>
      <th colspan="3">Add New Files</th>
    </tr>
    <tr>
      <td>
        <input type="hidden" name="MAX_FILE_SIZE" value="1000000" /> <!-- 1MB -->
        <input type="hidden" name="filesupdate" value="">
        <input name="userfiles[]" type="file" multiple>
      </td>
      <td>
        <input type="text" name="tags" placeholder="Tags">
      </td>
      <td>
        <input id="addFileSubmit" type="submit" name="submit" value="Add New Files">
      </td>
    </tr>
  </table>

  <table>
    <tr>
      <th colspan="4">Update Existing Files</th>
    </tr>
    <tr>
      <td>
        <input type="text" name="updateTags" placeholder="Tags">
      </td>
      <td>
        <input type="submit" name="addTags" value="Add Tags to Selection">
      </td>
      <td>
        <input type="submit" name="removeTags" value="Remove Tags from Selection">
      </td>
      <td>
        <input id="updateFileSubmit" type="submit" name="submit" value="Update Files">
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
