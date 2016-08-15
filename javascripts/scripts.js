$(function () {
  $(document)
    .drag("start", function (ev, dd) {
      return $('<div class="selection" />')
        .css('opacity', .65)
        .appendTo(document.body);
    })
    .drag(function (ev, dd) {
      setTimeout(function () {
        $(dd.proxy).css({
          top: Math.min(ev.pageY, dd.startY),
          left: Math.min(ev.pageX, dd.startX),
          height: Math.abs(ev.pageY - dd.startY),
          width: Math.abs(ev.pageX - dd.startX)
        });
      }, 0);
    })
    .drag("end", function (ev, dd) {
      $(dd.proxy).remove();
    });
  $('.doc')
    .drop("start", function () {
      $(this).addClass("active");
    })
    .drop(function (ev, dd) {
      $(this).toggleClass("update");
    })
    .drop("end", function () {
      $(this).removeClass("active");
    });
  $.drop({
    multi: true
  });

  // toggle on click
  $('.doc').on('click', function () {
    $(this).toggleClass('update');
  });


  // write metadata for updated files
  $('#updateFileSubmit').on('click', function (e) {
    var updates = [];
    $('[data-changed=true]').each(function () {
      updates.push({
        _id: $(this).attr('data-id'),
        tags: JSON.parse($(this).attr('data-tags')),
        file_name: $(this).attr('data-filename')
      });
    });
    $('input[name=filesupdate]').val(JSON.stringify(updates));
  });



  $(document).on('click', '[data-remove]', function (e) {
    e.preventDefault();
    var doc = $(this).closest('.doc');
    var tag = $(this).attr('data-remove');
    var tags = JSON.parse(doc.attr('data-tags'));
    tags.splice(tags.indexOf(tag), 1);
    doc.attr('data-tags', JSON.stringify(tags));
    doc.attr('data-changed', true);
    $(this).parent('li').remove();
  });

  var sanitized = function () {
    if ($('input[name=updateTags]').val().match(/^\s*$/)) {
      return;
    }
    var taglist = $('input[name=updateTags]').val().split(',');
    taglist = taglist.map(function (item) {
      return item.replace(/^\s*|\s*$/, '')
    });
    return taglist;
  };

  var updateTaglist = function () {
    $('.master').empty();
    var master = JSON.parse($('.master').attr('data-master'));
    $('[data-tags]').each(function () {
      var tags = JSON.parse($(this).attr('data-tags'));
      tags.forEach(function (tag) {
        if (master.indexOf(tag) < 0) {
          master.push(tag);
        }
      });
    });
    master.forEach(function (tag) {
      $('.master').append(
        $('<li><a data-sort="' + tag + '" href="#">' + tag + '</a></li>')
      );
    });
  };

  $(document).on('click', '.master li a', function (e) {
    e.preventDefault();
    $('.update').removeClass('update');
    $('.master').find('a').not($(this)).removeClass('active-sort');
    $(this).toggleClass('active-sort');
    var active = $('.active-sort').attr('data-sort');
    if (!active) {
      return $('[data-tags]').show();
    }
    $('[data-tags]').each(function () {
      var tags = JSON.parse($(this).attr('data-tags'));
      if (tags.indexOf(active) < 0) {
        $(this).hide();
      } else {
        $(this).show();
      }
    });
  });

  $(document).on('click', 'input[name=addTags]', function (e) {
    e.preventDefault();
    var taglist = sanitized();
    $('.update').each(function () {
      var _this = $(this);
      var tags = JSON.parse(_this.attr('data-tags'));
      taglist.forEach(function (tag) {
        if (tags.indexOf(tag) < 0) {
          tags.push(tag);
          _this.attr('data-changed', true);
          _this.find('.tags').append(
            $('<li><a data-remove="' + tag + '" href="#">' + tag + '</a></li>')
          );
        }
      });
      _this.attr('data-tags', JSON.stringify(tags));
    });
    updateTaglist();
  });

  $(document).on('click', 'input[name=removeTags]', function (e) {
    e.preventDefault();
    var taglist = sanitized();
    $('.update').each(function () {
      var _this = $(this);
      var tags = JSON.parse(_this.attr('data-tags'));
      taglist.forEach(function (tag) {
        if (tags.indexOf(tag) > -1) {
          tags.splice(tags.indexOf(tag), 1);
          _this.attr('data-changed', true);
          _this.find('[data-remove=' + tag + ']').closest('li').remove();
        }
      });
      _this.attr('data-tags', JSON.stringify(tags));
    });
    updateTaglist();
  });

  var sameHeights = function () {
    var h = 0;
    $('.doc').each(function () {
      if ($(this).height() > h) {
        h = $(this).height();
      }
    });
    $('.doc').height(h);
  };


  // bootstrap
  updateTaglist();
  sameHeights();

});
