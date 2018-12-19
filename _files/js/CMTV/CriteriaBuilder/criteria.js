var CMTV_CB = window.CMTV_CB || {};

!function ($, window, document, _undefined)
{
    "use strict";

    CMTV_CB.Criterion = XF.Element.newHandler({
        options: {
            checkboxElement: '.criterion-checkbox',
            criteriaContainerElement: '.criteria-container',
            optionsElement: '.options'
        },

        $checkbox: null,
        $options: null,

        init: function ()
        {
            var $categoryTitle = this.$target.closest(this.options.criteriaContainerElement).prev();

            this.$options = this.$target.find(this.options.optionsElement);

            this.$checkbox = this.$target.find(this.options.checkboxElement);
            this.$checkbox.on('change', XF.proxy(this, 'onChange'));

            if (this.isChecked())
            {
                this.$options.show();
                this.enableOptions();
            }
            else
            {
                this.$options.hide();
                this.disableOptions();
            }
        },

        onChange: function ()
        {
            if (this.isChecked())
            {
                this.$target.addClass('selected');
                this.$options.stop().slideDown(200);
                this.enableOptions();
            }
            else
            {
                this.$target.removeClass('selected');
                this.$options.stop().slideUp(200);
                this.disableOptions();
            }
        },

        isChecked: function ()
        {
            return this.$checkbox[0].checked;
        },
        
        enableOptions: function ()
        {
            this.$options.find(':input').prop('disabled', false);
        },
        
        disableOptions: function ()
        {
            this.$options.find(':input').prop('disabled', true);
        }
    });

    XF.Element.register('criterion', 'CMTV_CB.Criterion');
}
(jQuery, window, document);