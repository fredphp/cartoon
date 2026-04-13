define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'jstree'],
    function ($, undefined, Backend, Table, Form, Template, jstree) {
        var SELECTED_NODE = null;
        var KEYDATA = null;
        var Controller = {
            jstreeoptions: function () {
                var options = {
                    "types": {
                        "root": {
                            "icon": "/assets/addons/faredis/img/redis_16.png"
                        },
                        "db": {
                            "icon": "/assets/addons/faredis/img/db_16.png"
                        },
                        "loading": {
                            "icon": "/assets/addons/faredis/img/loading.gif"
                        },
                        "key": {
                            "icon": "/assets/addons/faredis/img/key_16.png"
                        }
                    },
                    "plugins": ["types", "wholerow", "contextmenu", "search"],
                    "contextmenu": {
                        "items": Controller.menu
                    },
                    "state": {
                        'opened': true
                    },
                    'core': {
                        "data": {
                            "url": "faredis/index",
                        },
                        "check_callback": true,
                    }
                };
                return options;
            },
            jstreeevent: function () {
                $('#tree').bind("select_node.jstree", function (event, data) {
                    var inst = data.instance;
                    var selectedNode = inst.get_node(data.selected);
                    SELECTED_NODE = selectedNode;
                    var level = $("[id^='" + selectedNode.id + "']").attr('aria-level');
                    if (!level || typeof (level) == "undefined") {
                        level = $("[id^='" + selectedNode.id + "'] a").attr('aria-level');
                    }
                    $('.addkey').hide();
                    $('.string').hide();
                    $('.set').hide();
                    $('.hash').hide();
                    $('.zset').hide();
                    if (level == 2) {
                        $('#c-newdb').val(selectedNode.id);
                        $('.addkey').show();
                        if (selectedNode.children.length > 0) {
                            return;
                        }
                        inst.set_type(selectedNode, 'loading');
                        Controller.loadChild(inst, selectedNode);
                    } else if (level == 3) {
                        var tmp = selectedNode.id.split('_');
                        var db = tmp[0];
                        var key = selectedNode.text;
                        Controller.loadValue(db, key);
                    }
                });
            },
            isJSON: function (str) {
                if (typeof str == 'string') {
                    try {
                        var obj = JSON.parse(str);
                        if (typeof obj == 'object' && obj) {
                            return true;
                        } else return false;
                    } catch (e) {
                        return false;
                    }
                }
            },
            jsonFormat: function (el) {
                if (Controller.isJSON($(el).val())) {
                    $(el).val(JSON.stringify(eval('(' + $(el).val() + ')'), null, 4));
                }
            },
            loadChild: function (inst, selectedNode) {
                $.post("faredis/index/keys", {
                    db: selectedNode.id - 1
                }, function (res) {
                    selectedNode.children = [];
                    $.each(res, function (i, item) {
                        inst.create_node(selectedNode, item, 'last');
                    });
                    inst.open_node(selectedNode);
                    inst.set_type(selectedNode, 'db');
                });
            },
            loadValue: function (db, key) {
                $.post('faredis/index/getValue', {
                    db: db,
                    key: key
                }, function (res) {
                    //reset
                    $('#c-value').val('');
                    $('.size').text('');
                    $('.set-value').val('');
                    $('.hash-key').val('');
                    $('.hash-value').val('');
                    $('.zset-value').val('');
                    $('.zset-score').val('');
                    KEYDATA = res;
                    $('.p-title').text("db" + db + "::" + key);
                    $('.c-key').val(key);
                    $('.c-db').val(db);
                    $(".ttl").text(res.ttl);
                    switch (res.type) {
                        case 1: //string
                            $('.string').show();
                            $('.set').hide();
                            $('.hash').hide();
                            $('.zset').hide();
                            $('#c-value').val(res.value);
                            Controller.jsonFormat($('#c-value'));
                            break;
                        case 2: //set
                        case 3: //list
                            $('.type-lb').text(res.type == 2 ? "SET:" : "LIST:");
                            $('.set').show();
                            $('.string').hide();
                            $('.hash').hide();
                            $('.zset').hide();
                            // $('#set-value').val(res.value);
                            var html = '';
                            if (res.type == 2) {
                                res.value.forEach(v => {
                                    html += "<option value='" + v + "'>" + v + "</option>"
                                });
                            } else {
                                var lidx = 0;
                                res.value.forEach(v => {
                                    html += "<option value='" + lidx++ + "'>" + v + "</option>"
                                });
                            }
                            $('#set-list').html(html);
                            $('.size').text(res.value.length);
                            break;
                        case 4:
                            $('.type-lb').text("ZSET:");
                            $('.hash').hide();
                            $('.string').hide();
                            $('.set').hide();
                            $('.zset').show();
                            let count4 = 0;
                            Object.keys(res.value).forEach(v => {
                                html += "<option value='" + v + "'>" + v + "</option>"
                                count4++;
                            });
                            $('#zset-list').html(html);
                            $('.size').text(count4);
                            break;
                        case 5: //hash
                            $('.type-lb').text("HASH:");
                            $('.hash').show();
                            $('.string').hide();
                            $('.set').hide();
                            $('.zset').hide();
                            var html = '';
                            let count = 0;
                            Object.keys(res.value).forEach(v => {
                                html += "<option value='" + v + "'>" + v + "</option>"
                                count++;
                            });
                            $('#hash-list').html(html);
                            $('.size').text(count);
                            break;
                        default:
                            $('.string').hide();
                            $('.set').hide();
                            break;
                    }
                });
            },
            menu: function (node) {
                var items = {
                    "reload": {
                        "label": "重载",
                        "icon": "fa fa-refresh",
                        "action": function (data) {
                            console.log('reload');
                            var inst = $.jstree.reference(data.reference);
                            obj = inst.get_node(data.reference);
                            $.post('faredis/index/reloadDb?db=' + obj.id, null, function (res) {
                                console.log(res);
                                $("#tree").jstree('set_text', obj, res.text);
                                Controller.loadChild(inst, obj);
                            })
                        }
                    },
                    "flush": {
                        "label": "清空",
                        "icon": "fa fa-trash",
                        "action": function (data) {
                            console.log('flush');
                            var inst = $.jstree.reference(data.reference);
                            obj = inst.get_node(data.reference);
                            console.log(obj);
                            Layer.confirm("确认清空吗？", function () {
                                $.post("faredis/index/flushDb", {
                                    db: obj.id
                                }, function (res) {
                                    Layer.closeAll('dialog');
                                    $("#tree").jstree('set_text', SELECTED_NODE, "DB" + (obj.id - 1) + "(0)");
                                    var childNodes = inst.get_children_dom(obj);
                                    $.each(childNodes, function (index, item) {
                                        inst.delete_node(item);
                                    })
                                });
                            });
                        }
                    },

                };
                if (node.type == 'db') {
                    return items;
                }
            },
            index: function () {
                $('#tree').jstree(Controller.jstreeoptions());
                Controller.jstreeevent();
                $('.rds_del').on('click', function () {
                    Layer.confirm("确认删除？", function () {
                        $.post("faredis/index/delKey", {
                            db: KEYDATA.db,
                            key: KEYDATA.key
                        }, function (res) {
                            Layer.closeAll('dialog');
                            $("#tree").jstree('set_text', SELECTED_NODE, KEYDATA.key + "(Removed)");
                        });
                    });

                });
                $('.rds_ttl').on('click', function () {
                    Layer.prompt({
                            title: "修改TTL",
                            value: KEYDATA.ttl
                        },
                        function (value, index, ele) {
                            $.get("faredis/index/ttl", {
                                db: KEYDATA.db,
                                key: KEYDATA.key,
                                ttl: value
                            }, function (res) {
                                Layer.close(index);
                                $(".ttl").text(value);
                            });
                        }
                    )
                });
                $('.rds_rename').on('click', function () {
                    Layer.prompt({
                            title: "重命名",
                            value: KEYDATA.key
                        },
                        function (value, index, ele) {
                            $.get("faredis/index/rename", {
                                db: KEYDATA.db,
                                key: KEYDATA.key,
                                newkey: value
                            }, function (res) {
                                Layer.close(index);
                                $("#tree").jstree('set_text', SELECTED_NODE, value);
                            });
                        }
                    )
                });
                //set list 
                $('#set-list').on('click', function () {
                    if ($('.type-lb').first().text() == 'LIST:') {
                        $('.set-value').val($(this).find("option:selected").text());
                    } else
                        $('.set-value').val($(this).val());
                    Controller.jsonFormat($('.set-value'));
                });

                $('.rds_add_row').on('click', function () {
                    console.log('add row')
                    Layer.prompt({
                            title: "增加新项",
                            placeholder: '请输入新项的值'
                        },
                        function (value, index, ele) {
                            $.get("faredis/index/addValue", {
                                db: KEYDATA.db,
                                key: KEYDATA.key,
                                value: value
                            }, function (res) {
                                Layer.close(index);
                                var treeNode = $('#tree').jstree(true).get_selected(true)[0];
                                $("#tree").jstree("deselect_all", true);
                                $('#tree').jstree('select_node', treeNode.id);
                            });
                        }
                    )
                });

                $('.rds_add_row_hash').on('click', function () {
                    Layer.prompt({
                            title: "增加新项（key/value）",
                            placeholder: '请输入新项的Key'
                        },
                        function (value, index, ele) {
                            $.get("faredis/index/addValue", {
                                db: KEYDATA.db,
                                key: KEYDATA.key,
                                hash_key: value,
                                value: $('#hash_value').val()
                            }, function (res) {
                                Layer.close(index);
                                var treeNode = $('#tree').jstree(true).get_selected(true)[0];
                                $("#tree").jstree("deselect_all", true);
                                $('#tree').jstree('select_node', treeNode.id);
                            });
                        });
                    $('.layui-layer-content').append('<br/><input type="text" id="hash_value" class="layui-layer-input" placeholder="请输入value值" />')
                });

                $('.rds_add_row_zset').on('click', function () {
                    Layer.prompt({
                            title: "增加新项",
                            placeholder: '请输入新项的Key'
                        },
                        function (value, index, ele) {
                            $.get("faredis/index/addValue", {
                                db: KEYDATA.db,
                                key: KEYDATA.key,
                                value: value,
                                zset_score: $('#add_row_zset_score').val()
                            }, function (res) {
                                Layer.close(index);
                                var treeNode = $('#tree').jstree(true).get_selected(true)[0];
                                $("#tree").jstree("deselect_all", true);
                                $('#tree').jstree('select_node', treeNode.id);
                            });
                        });
                    $('.layui-layer-content').append('<br/><input type="text" id="add_row_zset_score" class="layui-layer-input" placeholder="请设置value的score" />')
                });

                $('.rds_delete_row').on('click', function () {
                    var type = $(this).data('type');
                    console.log("rds_delete_row", $('#' + type).find("option:selected").text());
                    $.get("faredis/index/delValue", {
                        db: KEYDATA.db,
                        key: KEYDATA.key,
                        value: $('#' + type).find("option:selected").text(),
                    }, function (res) {
                        if (res.code == 1) {
                            var treeNode = $('#tree').jstree(true).get_selected(true)[0];
                            $("#tree").jstree("deselect_all", true);
                            $('#tree').jstree('select_node', treeNode.id);
                        } else {
                            Layer.alert("操作失败");
                        }

                    });
                });
                //hash
                $('#hash-list').on('click', function () {
                    let key = $(this).val();
                    $('.hash-key').val(key);
                    $('.hash-value').val(KEYDATA.value[key]);
                    Controller.jsonFormat($('.hash-value'));
                });
                //zset
                $('#zset-list').on('click', function () {
                    let key = $(this).val();
                    $('.zset-value').val(key);
                    $('.zset-score').val(KEYDATA.value[key]);
                });


                $('#search_input').keyup(function () {
                    $("#tree").jstree(true).search($('#search_input').val());
                });

                $('.newsource').hide();
                $('.hash-filed').hide();
                $('#c-newtype').on('changed.bs.select', function (e) {
                    if (e.target.value == 'zset') {
                        $('.newsource').show();
                    } else {
                        $('.newsource').hide();
                    }
                    if (e.target.value == 'hash') {
                        $('.hash-filed').show();
                    } else {
                        $('.hash-filed').hide();
                    }
                });

                Form.api.bindevent($("form[role=form]"), function (data, ret) {
                    var treeNode = $('#tree').jstree(true).get_selected(true)[0];
                    $("#tree").jstree("deselect_all", true);
                    $('#tree').jstree('select_node', treeNode.id);
                });
                Form.api.bindevent($("form[role=form-add]"), function (data, ret) {
                    Layer.confirm('新增成功，是否重新加载该库下所有keys?', function () {
                        Layer.closeAll();
                        $.post('faredis/index/reloadDb?db=' + SELECTED_NODE.id, null, function (res) {
                            var inst = $.jstree.reference(SELECTED_NODE);
                            $("#tree").jstree('set_text', SELECTED_NODE, res.text);
                            Controller.loadChild(inst, SELECTED_NODE);
                        })
                    })
                    $('#add-reset').trigger('click');
                });
            }

        };
        return Controller;
    });