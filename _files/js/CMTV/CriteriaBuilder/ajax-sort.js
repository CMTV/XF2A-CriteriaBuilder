var CMTV_CB = window.CMTV_CB || {};

!function($, window, document, _undefined)
{
    "use strict";

    CMTV_CB.AjaxSorter = XF.Element.newHandler({
        sortUrl: null,
        sorterHandler: null,

        init: function ()
        {
            this.sortUrl = this.$target.data('sort-url');
            this.sorterHandler = XF.Element.getHandler(this.$target, 'CMTV-CB-list-sorter');
            this.sorterHandler.dragula.on('drop', XF.proxy(this, 'onDrop'));
        },

        onDrop: function (el, target, source, sibling)
        {
            var data = {
                moved: $(el).data('id'),
                before: $(sibling).data('id')
            };

            XF.ajax('post', this.sortUrl, data, function () {});
        }
    });

    XF.Element.register('ajax-sorter', 'CMTV_CB.AjaxSorter');
}
(jQuery, window, document);