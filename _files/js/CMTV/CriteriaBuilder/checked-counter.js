var CMTV_CB = window.CMTV_CB || {};

!function ($, window, document, _undefined)
{
    "use strict";

    CMTV_CB.CheckedCounter = XF.Element.newHandler({
        options: {
            counterContainer: '.js-counterContainer',
            counterValue: '.js-counterValue',
            hideZero: false,

            checkbox: '.js-countable input:checkbox',
        },

        $counterContainer: null,
        $counterValue: null,

        $checkboxes: null,

        init: function ()
        {
            this.$counterContainer = this.$target.find(this.options.counterContainer);
            this.$counterValue = this.$target.find(this.options.counterValue);

            this.$checkboxes = this.$target.find(this.options.checkbox);
            this.$checkboxes.on('change', XF.proxy(this, 'redraw'));

            this.redraw();
        },
        
        redraw: function ()
        {
            var checked = 0;

            this.$checkboxes.each(function () {
                if ($(this).is(':checked'))
                {
                    checked++;
                }
            });

            this.$counterValue.html(checked);

            if (checked > 0)
            {
                this.$counterContainer.show();
            }
            else if (checked === 0 && this.options.hideZero)
            {
                this.$counterContainer.hide();
            }
        }
    });

    XF.Element.register('checked-counter', 'CMTV_CB.CheckedCounter');
}
(jQuery, window, document);