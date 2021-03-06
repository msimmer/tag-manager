$(function () {

  var timer;
  var updateDocCount = function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      $('.doc-count').html($('.update').length);
    }, 0);
  };

  var sanitized = function () {
    if ($('input[name=update_tags]').val().match(/^\s*$/)) {
      return;
    }
    var taglist = $('input[name=update_tags]')
      .val()
      .split(',')
      .map(function (item) {
        return item.replace(/^\s*|\s*$/, '')
      });
    return taglist;
  };

  var updateTaglist = function () {
    if (!$('.master').length) {
      return;
    }
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

  var sameHeights = function () {
    var h = 0;
    $('.doc').each(function () {
      if ($(this).height() > h) {
        h = $(this).height();
      }
    });
    $('.doc').height(h);
  };

  var formatDate = function (d) {
    var months = [
      'January', 'February', 'March',
      'April', 'May', 'June', 'July',
      'August', 'September', 'October',
      'November', 'December'
    ];

    var date = new Date(d);
    var day = date.getDate();
    var monthIdx = date.getMonth();
    var year = date.getFullYear();
    return months[monthIdx] + ' ' + day + ', ' + year;
  };


  $(document)
    .drag('start', function (ev, dd) {
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
    .drag('end', function (ev, dd) {
      $(dd.proxy).remove();
    });
  $('.doc')
    .drop('start', function () {
      $(this).addClass('active');
    })
    .drop(function (ev, dd) {
      $(this).toggleClass('update');
    })
    .drop('end', function () {
      $(this).removeClass('active');
      updateDocCount();
    })
  $.drop({
    multi: true
  });


  // write metadata for updated files
  $('#update_files').on('click', function (e) {
    var updates = [];
    $('[data-changed=true], .update').each(function () {
      updates.push({
        _id: $(this).attr('data-id'),
        tags: JSON.parse($(this).attr('data-tags')),
        file_name: $(this).attr('data-filename'),
        publish_date: $('input[name=update_publish_date]').val(),
        published: $('input[name=published]').is(':checked'),
        delete_selected: $('input[name=delete_selected]').is(':checked')
      });
    });

    if ($('input[name=delete_selected]').is(':checked')) {
      var filesToRemove = [];
      $('[data-changed=true], .update').each(function () {
        filesToRemove.push($(this).attr('data-nice-name'));
      });

      if (!confirm('You have chosen to delete the following files, this cannot be undone.\
        \n\n' + filesToRemove.join('\n'))) {
        return false;
      }
    }

    $('input[name=files_update]').val(JSON.stringify(updates));
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

  $(document).on('click', 'input[name=add_tags]', function (e) {
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

  $(document).on('click', 'input[name=remove_tags]', function (e) {
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

  $('a[href="load.php?id=file_manager&proxy_update"]').on('click', function (e) {
    e.preventDefault();
    $('#update_files').trigger('click');
  });

  var flspeed = 50;
  $('a.gallery').featherlightGallery({
    openSpeed: flspeed,
    closeSpeed: flspeed,
    galleryFadeIn: flspeed,
    galleryFadeOut: flspeed,
    beforeOpen: function () {
      $('#finfo').fadeIn(flspeed);
    },
    beforeClose: function () {
      $('#finfo').fadeOut(flspeed);
    },
    beforeContent: function () {
      var path = this.$currentTarget[0].pathname;
      var slash = path.lastIndexOf('/') + 1;
      var src = path.slice(slash);
      var info = $('[data-filename="' + src + '"]').data();
      var data = {
        filename: info.filename,
        niceName: info.niceName,
        publishDate: formatDate(info.publishDate * 1000),
        status: info.status,
        tags: info.tags.join(', ')
      };
      for (var key in data) {
        if ($('[data-lb-' + key + ']').length) {
          $('[data-lb-' + key + ']').text(data[key]);
        }
      }
    }
  });


  // bootstrap
  updateTaglist();
  sameHeights();

});
