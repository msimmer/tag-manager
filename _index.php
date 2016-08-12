<!DOCTYPE html>
<html>
<head>
  <title></title>
  <script src="../../bower_components/jquery/dist/jquery.min.js"></script>
  <script src="../../vendor/jquery.migrate/jquery.migrate.js"></script>
  <script src="../../vendor/jquery.ui/jquery-ui.min.js"></script>

  <script src="../../vendor/jquery.event.drag-2.2/jquery.event.drag-2.2.js"></script>
  <script src="../../vendor/jquery.event.drag-2.2/jquery.event.drag.live-2.2.js"></script>
  <script src="../../vendor/jquery.event.drop-2.2/jquery.event.drop-2.2.js"></script>
  <script src="../../vendor/jquery.event.drop-2.2/jquery.event.drop.live-2.2.js"></script>

  <script src="javascripts/scripts.js"></script>

  <link rel="stylesheet" type="text/css" href="../../vendor/Skeleton-2.0-1.4/css/normalize.css">
  <link rel="stylesheet" type="text/css" href="../../vendor/Skeleton-2.0-1.4/css/skeleton.css">
  <link rel="stylesheet" type="text/css" href="stylesheets/styles.css">
</head>
<body>

<header class="container">
  <nav>
    <ul class="master" data-master=[]></ul>
  </nav>
</header>

<div class="container">
  <div class="twelve columns">
    <form name="addfile" id="addfile" method="POST" action="tag_manager.php" enctype="multipart/form-data">
      <label>Add files:</label>
      <input type="hidden" name="MAX_FILE_SIZE" value="1000000" /> <!-- 1MB -->
      <input type="hidden" name="filesupdate" value="">
      <br/><input name="userfiles[]" type="file" multiple>
      <br/><input type="text" name="tags" placeholder="Tags">
      <br/><input id="addFileSubmit" type="submit" name="submit" value="Add New Files">
      <hr>
      <input type="text" name="updateTags" placeholder="Tags">
      <br/><input type="submit" name="addTags" value="Add Tags to Selection">
      <br/><input type="submit" name="removeTags" value="Remove Tags from Selection">
      <br/><input id="updateFileSubmit" type="submit" name="submit" value="Update Files">
    </form>
  </div>
</div>


<div class="container">

  <?php

    include_once 'show.php';

    $site = new Site;
    $content = $site->show();
    $html = $site->render($content);
    echo $html;
    ?>
</div>
</body>
</html>
