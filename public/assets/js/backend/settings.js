define(['jquery', 'bootstrap', 'backend', 'form'], function ($, undefined, Backend, Form) {

    var Controller = {
        index: function () {
            // 为每个插件的配置表单绑定事件
            $(".settings-form form[role=form]").each(function () {
                Form.api.bindevent($(this));
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
