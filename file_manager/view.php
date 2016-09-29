<?php
/*

View Page

*/

include_once 'show.php'; ?>

<h3 class="floated" style="float:left">File Manager</h3>
<p class="clear">Upload files and modify their metadata here.</p>

<form name="file_manager" id="file_manager" method="POST" action="<?php echo FILE_MANAGER_ASSETS_URI . 'tags.php'; ?>" enctype="multipart/form-data">

  <div id="metadata_window">
    <div class="leftopt">
      <h3 class="options-header">Add Files</h3>
      <p class="inline clearfix">
        <label for="files_update">Add Files:</label>
        <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
        <input type="hidden" name="files_update" value="">
        <input class="text autowidth" name="user_files[]" type="file" multiple>
      </p>
      <p class="inline clearfix">
        <label for="tags">Tags:</label>
        <input class="text autowidth" type="text" name="tags" placeholder="">
      </p>
      <p class="inline clearfix">
        <label for="published_add">Published:</label>
        <input class="checkbox autowidth" type="checkbox" name="published_add">
      </p>
      <p class="inline clearfix">
        <label for="add_publish_date">Publish Date:</label>
        <input class="text autowidth" type="date" name="add_publish_date" placeholder="Publish Date" value="<?php echo date('Y-m-d'); ?>" novalidate>
      </p>
      <p class="inline clearfix">
        <label for="add_submit">Add Files:</label>
        <input class="submit autowidth" id="add_files" type="submit" name="add_submit" value="Submit">
      </p>
    </div>


    <div class="rightopt">
      <h3 class="options-header">Update Selection</h3>
      <p class="inline clearfix">
        <b><span class="doc-count">0</span> Documents Selected</b>
      </p>

      <p class="inline clearfix">
        <label for="update_tags">Tags:</label>
        <input class="text autowidth" type="text" name="update_tags" placeholder="">
      </p>

      <p class="inline clearfix">
        <input class="submit autowidth" type="submit" name="add_tags" value="Add Tags">
      </p>

      <p class="inline clearfix">
        <input class="submit autowidth" type="submit" name="remove_tags" value="Remove Tags">
      </p>

      <p class="inline clearfix">
        <label for="published">Published:</label>
        <input class="checkbox autowidth" type="checkbox" name="published">
      </p>

      <p class="inline clearfix">
        <label for="update_publish_date">Publish Date:</label>
        <input class="text autowidth" type="date" name="update_publish_date" placeholder="Publish Date" value="<?php echo date('Y-m-d'); ?>" novalidate>

        <!-- hidden form submit triggered by sidebar -->
        <input class="hidden" id="update_files" type="submit" name="submit_update" value="Submit">
        <input class="hidden" id="delete_files" type="submit" name="submit_delete" value="Submit">
      </p>

      <p class="inline clearfix">
        <label for="delete_selected">Delete Selection:</label>
        <input class="checkbox autowidth" type="checkbox" name="delete_selected">
      </p>

    </div>
    <div class="clear"></div>

    <div class="leftopt" style="width:100%;">
      <h3 class="options-header">View Collections</h3>
      <ul class="master" data-master=[]></ul>
    </div>

    <div class="clear"></div>

  </div>
</form>


<?php
$site = new Site;
$content = $site->show();
$html = $site->render($content);
echo $html;
echo "<hr style='clear:both;visibility:hidden;'>";?>


<script>
  var fileinput = $('[type=file]');
  var maxsize = parseInt($('[name="MAX_FILE_SIZE"]').val(), 10);
  fileinput.on('change', function(){
    var message = '';
    var oversize = [];
    var files = this.files;
    for (var i = 0; i < files.length; i++) {
      if (files[i].size > maxsize) {
        oversize.push(files[i]);
      }
    }
    if (oversize.length) {
      message += 'The operation could not be completed because the following files are over the allowed limit of ';
      message += maxsize / 1000000 + ' Mb:\n\n';
      for (var i = 0; i < oversize.length; i++) {
        message += oversize[i].name + '\n';
      }
      alert(message);
      this.value = '';
    }
  })
</script>
