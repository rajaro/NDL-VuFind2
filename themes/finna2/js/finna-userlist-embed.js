/*global VuFind, finna */
finna.userListEmbed = (function userListEmbed() {
  var my = {
    init: function init() {
      $('.public-list-embed.show-all').not(':data(inited)').each(function initEmbed() {
        var embed = $(this);
        embed.data('inited', '1');

        var showMore = embed.find('.show-more');
        var spinner = embed.find('.fa-spinner');
        embed.find('.btn.load-more').on('click', function initLoadMore() {
          spinner.removeClass('hide').show();

          var btn = $(this);

          var id = btn.data('id');
          var offset = btn.data('offset');
          var indexStart = btn.data('start-index');
          var view = btn.data('view');
          var sort = btn.data('sort');

          btn.hide();

          var resultsContainer = embed.find(
            view === 'grid' ? '.search-grid' : '.result-view-' + view
          );

          $.getJSON(
            VuFind.path + '/AJAX/JSON?method=getUserList',
            {
              id: id,
              offset: offset,
              indexStart: indexStart,
              view: view,
              sort: sort,
              method: 'getUserList'
            }
          )
            .done(function onListLoaded(response) {
              showMore.remove();
              $(VuFind.updateCspNonce(response.data.html)).find('.result').each(function appendResult(/*index*/) {
                resultsContainer.append($(this));
              });

              finna.myList.init();
              finna.layout.initCondensedList(resultsContainer);
              finna.layout.initTruncate();
              finna.layout.initImagePaginators();
              finna.openUrl.initLinks(resultsContainer);
              VuFind.itemStatuses.init(resultsContainer);
              finna.itemStatus.initDedupRecordSelection(resultsContainer);
              VuFind.recordVersions.init(resultsContainer);
              VuFind.lightbox.bind(resultsContainer);
              VuFind.cart.init(resultsContainer);
              $.fn.finnaPopup.reIndex();
              VuFind.saveStatuses.init(resultsContainer);
            })
            .fail(function onLoadListFail() {
              btn.show();
              spinner.hide();
            });

          return false;
        });
      });
    }
  };

  return my;
})();
