var CMTV_CB = window.CMTV_CB || {};

!function($, window, document, _undefined)
{
    "use strict";

    CMTV_CB.ListSorter = XF.extend(XF.ListSorter, {
        __backup: {
            'isValidTarget': '_isValidTarget'
        },

        init: function ()
        {
            var container = this.$target.data('container') ? this.$target.find(this.$target.data('container'))[0] : this.$target[0];

            this.dragula = dragula([container], {
                moves: XF.proxy(this, 'isMoveable'),
                accepts: XF.proxy(this, 'isValidTarget')
            });
        },

        isValidTarget: function (el, target, source, sibling)
        {
            if ($(sibling).hasClass('js-blockDragbefore'))
            {
                return false;
            }

            return this._isValidTarget(el, target, source, sibling);
        }
    });

    XF.Element.register('CMTV-CB-list-sorter', 'CMTV_CB.ListSorter');
}
(jQuery, window, document);