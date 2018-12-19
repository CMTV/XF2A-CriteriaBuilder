var CMTV_CB = window.CMTV_CB || {};

!function ($, window, document, _undefined)
{
    "use strict";

    /* ============================================================================================================== */
    /* ELEMENT HANDLERS */
    /* ============================================================================================================== */

    CMTV_CB.ParamAddEdit = XF.Element.newHandler({
        options: {
            paramsListElement: '.params tbody',
            paramShowcaseElement: '[data-xf-init="param-showcase"]'
        },

        $paramsList: null,
        $paramShowcaseHandler: null,

        init: function ()
        {
            var $form = this.$target;
            $form.on('ajax-submit:response', XF.proxy(this, 'ajaxResponse'));

            this.$paramsList = $(this.options.paramsListElement);
            this.$paramShowcaseHandler = XF.Element.getHandler($(this.options.paramShowcaseElement), 'param-showcase');
        },

        ajaxResponse: function (e, data)
        {
            console.log(data);

            if (data.errors || data.exception)
            {
                return;
            }

            e.preventDefault();

            var self = this;
            XF.setupHtmlInsert(data.html, function ($html)
            {
                if (data.insert)
                {
                    self.$paramsList.append($html);
                    self.$paramShowcaseHandler.add(data.id);
                }
                else
                {
                    self.$paramsList.find('[data-id="' + data.id + '"]').replaceWith($html);
                }
            });

            XF.hideParentOverlay(this.$target);
        }
    });

    CMTV_CB.ParamDelete = XF.Element.newHandler({
        options: {
            paramsListElement: '.params tbody',
            paramShowcaseElement: '[data-xf-init="param-showcase"]'
        },

        $paramsList: null,
        $paramShowcaseHandler: null,

        init: function ()
        {
            var $form = this.$target;
            $form.on('ajax-submit:response', XF.proxy(this, 'ajaxResponse'));

            this.$paramsList = $(this.options.paramsListElement);
            this.$paramShowcaseHandler = XF.Element.getHandler($(this.options.paramShowcaseElement), 'param-showcase');
        },

        ajaxResponse: function (e, data)
        {
            if (data.errors || data.exception)
            {
                return;
            }

            e.preventDefault();

            this.$paramsList.find('[data-id="' + data.id + '"]').remove();
            this.$paramShowcaseHandler.remove(data.id);

            XF.hideParentOverlay(this.$target);
        }
    });

    CMTV_CB.ParamShowcase = XF.Element.newHandler({
        options: {
            paramElement: '.param-quick-view',
        },

        init: function ()
        {
            this.ensureNotEmpty();
        },

        add: function(id)
        {
            var self = this;
            XF.ajax('post', this.$target.data('quick-view-url'), { id: id }, function (data)
            {
                XF.setupHtmlInsert(data.html, function ($html)
                {
                    self.$target.append($html);
                });

                self.ensureNotEmpty();
            });
        },

        remove: function (id)
        {
            this.$target.find(this.options.paramElement + '[data-id="' + id + '"]').remove();
            this.ensureNotEmpty();
        },

        ensureNotEmpty: function ()
        {
            if (this.$target.find(this.options.paramElement).length === 0)
            {
                this.$target.hide();
            }
            else
            {
                this.$target.show();
            }
        }
        
        // TODO handle reordering
    });

    XF.Element.register('param-add-edit', 'CMTV_CB.ParamAddEdit');
    XF.Element.register('param-delete', 'CMTV_CB.ParamDelete');
    XF.Element.register('param-showcase', 'CMTV_CB.ParamShowcase');
}
(jQuery, window, document);