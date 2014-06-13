jQuery(function() {
    if (jQuery('#sightmarks-table').html()) {
        var metersPerYard = 0.9144;
//////// Model() //////////////////////////////////////////////////////
        function Model() {
            var items = [];
            var distancesInMeters = [];
            var rawDistances = [];
            var measures = [];
            var sightmarks = [];
            var views = [];
            var name = '';
            var bestFit = function() { return 0; };
            var _this = this;
            var modified = false;

            this.isModified = function() {
                return modified;
            }

            this.setModified = function(val) {
                modified = val;
            }

            this.setItems = function(data, dataName) {
                console.log("model.setItems(%o, %s)", data, dataName);
                items = data;
                name = dataName;
                recalculate();
                notifyAllChanged();
            };

            this.getData = function() {
                return {
                    'distancesInMeters': distancesInMeters,
                    'sightmarks': sightmarks,
                    'rawDistances': rawDistances,
                    'measures': measures,
                    'bestFit': bestFit,
                    'name': name
                };
            };

            this.getItem = function(index) {
                return items[index];
            }

            this.getItems = function() {
                return items;
            }

            this.addItem = function(item) {
                console.log("model.addItem(%o)", item);
                items.push(item);
                recalculate();
                notifyItemAdded(items.length - 1);
            };

            this.deleteItem = function(index) {
                items.splice(index, 1);
                recalculate();
                notifyItemDeleted(index);
            };

            this.attach = function(view) {
                views.push(view);
            };


            function notifyItemAdded(index) {
                for(var i = 0; i < views.length; ++i) {
                    views[i].itemAdded(_this, index);
                }
            }

            function notifyItemDeleted(index) {
                for(var i = 0; i < views.length; ++i) {
                    views[i].itemDeleted(_this, index);
                }
            }

            function notifyAllChanged() {
                console.log("model.notifyAllChanged()");
                for(var i = 0; i < views.length; ++i) {
                    console.log("model.notifyChanged(%d)", i);
                    views[i].allChanged(_this);
                }
            }

            function toMeters(distance, measure) {
                if (measure == 'm') {
                    return distance;
                }
                return distance * metersPerYard;
            }

            function recalculate() {
                distancesInMeters = [];
                rawDistances = [];
                measures = [];
                sightmarks = [];
                for(var i = 0; i < items.length; ++i) {
                    var item = items[i];
                    distancesInMeters.push(toMeters(item.distance,
                                                    item.measure));
                    rawDistances.push(item.distance);
                    measures.push(item.measure);
                    sightmarks.push(item.sightmark);
                }
                if (items.length > 1) {
                    bestFit = lineOfBestFit(distancesInMeters, sightmarks);
                } else {
                    bestFit = function () { return 0; };
                }
            }
        }

//////// ItemController(model, items) //////////////////////////////
        function ItemController(model, items) {
            var _this = this;

            var tips = items.validateTips;
            var distance = items.formDistance;
            var sightmark = items.formSightmark;
            var allFormFields = jQuery([]).add(distance).add(sightmark);
            var addButton = items.addButton;
            var deleteButton = items.deleteButton;
            var itemsTable = items.itemsTable;

            function updateTips( t ) {
                tips.text( t )
                    .addClass( "ui-state-highlight" );
                setTimeout(function() {
                    tips.removeClass( "ui-state-highlight", 1500 );
                }, 500 );
            }

            function checkRegex( o, regexp, n ) {
                if ( !( regexp.test( o.val() ) ) ) {
                    o.addClass( "ui-state-error" );
                    updateTips( n );
                    return false;
                } else {
                    return true;
                }
            }

            var dialog = items.dialog.dialog({
                autoOpen: false,
                modal: true,
                buttons: {
                    Add: function() {
                        var valid = true;
                        allFormFields.removeClass("ui-state-error");
                        distance.val(distance.val().trim());
                        sightmark.val(sightmark.val().trim());
                        valid = valid && checkRegex(distance,
                            /^\d+[ym]$/,
                            "distance must be a whole number" +
                            " followed by 'y' or 'm', i.e. 30y or 40m");
                        valid = valid && checkRegex(sightmark,
                            /^\d+(\.\d+)?$/,
                            "sightmark must be a whole number" +
                            " or a floating point," +
                            " i.e. 80 or 102.5");
                        if (valid) {
                            var distanceNum = /\d+/.exec(distance.val());
                            var distanceMeasure = /[ym]/.exec(distance.val());
                            jQuery(this).dialog("close");
                            model.addItem({
                                'distance': Number(distanceNum[0]),
                                'measure': distanceMeasure[0],
                                'sightmark': Number(sightmark.val())
                            });
                            model.setModified(true);
                        }
                    },
                    Cancel: function() {
                        jQuery(this).dialog("close");
                    }
                }
            });

            addButton.button().click(function() {
                distance.val('');
                sightmark.val('');
                items.dialog.dialog("open");
            });

            jQuery(itemsTable.table().body()).on('click', 'tr', function() {
                var jQthis = jQuery(this);
                if(jQthis.hasClass('selected')) {
                    jQthis.removeClass('selected');
                } else {
                    itemsTable.$('tr.selected').removeClass('selected');
                    jQthis.addClass('selected');
                }
            });

            deleteButton.button().click(function() {
                var index = itemsTable.row('.selected').index();
                if (index !== undefined && index !== null && !jQuery.isArray(index)) {
                    console.log("index is %o", index);
                    model.deleteItem(index);
                    model.setModified(true);
                }
            });
        }

//////// PersistenceController(model, storage, items) /////////////////
        function PersistenceController(model, storage, items) {

            var tips = items.validateTips;
            var name = items.name;
            var select = items.select;
            var deleteButton = items.deleteButton.button();
            var saveButton = items.saveButton.button();
            var restoreButton = items.restoreButton.button();
            var title = items.title;
            var confirmed = false;

            function updateTips( t ) {
                tips.text( t )
                    .addClass( "ui-state-highlight" );
                setTimeout(function() {
                    tips.removeClass( "ui-state-highlight", 1500 );
                }, 500 );
            }

            function checkLength( o ) {
                if ( o.val().length == 0 ) {
                    o.addClass( "ui-state-error" );
                    updateTips( "Name cannot be empty" );
                    return false;
                } else {
                    return true;
                }
            }

            function addToStorage(name) {
                var data = model.getItems();
                console.log("storage.set(%s, %o)", name, data);
                storage.set(name, data);
                console.log("storage now %o", storage.data());
                populateSelect(name);
            }

            function removeFromStorage(name) {
                storage.remove(name);
                populateSelect();
                model.setModified(false);
            }

            function populateSelect(newName) {
                var names = [];
                var name;
                var html = '';
                var index;
                for (name in storage.data()) {
                    names.push(name);
                }
                names.sort();
                var selected = ' selected="selected"';
                for (index in names) {
                    name = names[index];
                    if (newName) {
                        if (name == newName) {
                            html = html.concat('<option', selected,
                                ' value="', name, '">', name, '</option>');
                        }
                        else {
                            html = html.concat('<option',
                                ' value="', name, '">', name, '</option>');
                        }
                    }
                    else {
                        html = html.concat('<option', selected,
                            ' value="', name, '">', name, '</option>');
                        selected = '';
                    }
                }
                select.html(html);
                select.change();
            }

            items.dialog.dialog({
                autoOpen: false,
                modal: true,
                buttons: {
                    Save: function() {
                        var valid;
                        name.removeClass("ui-state-error");
                        name.val(name.val().trim().replace(/[<>]/ig, ''));
                        valid = checkLength(name);
                        if (valid) {
                            jQuery(this).dialog("close");
                            addToStorage(name.val());
                        }
                    },
                    Cancel: function() {
                        jQuery(this).dialog("close");
                    }
                }
            });

            deleteButton.click(function() {
                var name = items.select.val();
                if (name && name.length > 0) {
                    removeFromStorage(name);
                }
            });

            saveButton.click(function() {
                name.val(select.val());
                items.dialog.dialog("open");
            });

            restoreButton.click(function() {
                select.change();
            });

            select.change(function() {
                var name = items.select.val();
                console.log("select.change: " + name);
                if (name && name.length > 0) {
                    var data = storage.get(name);
                    console.log("storage.get(%s) => %o", name, data);
                    model.setItems(data, name);
                    title.html(name);
                } else {
                    model.setItems([], '');
                    title.html('');
                }
                model.setModified(false);
            });

            this.start = function() {
                populateSelect();
            }

        }

//////// ItemsView(items) /////////////////////////////////////////////
        function ItemsView(items) {

            var itemsTable = items.itemsTable;

            this.itemAdded = function(model, index) {
                console.log("items.itemAdded");
                var item = model.getItem(index);
                itemsTable.row.add([
                    String(item.distance) + item.measure,
                    item.sightmark
                ]).draw();
            };

            this.itemDeleted = function(model, index) {
                console.log("items.itemDeleted");
                itemsTable.row(index).remove().draw();
            };

            this.allChanged = function(model) {
                console.log("items.allChanged");
                var items = model.getItems();
                itemsTable.clear();
                for (var i = 0; i < items.length; ++i) {
                    var item = items[i];
                    itemsTable.row.add([
                        String(item.distance) + item.measure,
                        item.sightmark
                    ]);
                }
                itemsTable.draw();
            };
        }

//////// GraphView(items) /////////////////////////////////////////////
        function GraphView(items) {
            var canvas = items.canvas;
            var ctx = canvas[0].getContext("2d");
            var width = Number(canvas.attr('width'));
            var height = Number(canvas.attr('height'));
            var minY = 0;
            var maxY = height;

            function setYScale(y1, y2) {
                if (y1 > y2) {
                    var tmp = y1;
                    y1 = y2;
                    y2 = tmp;
                }
                minY = y1;
                maxY = y2;
            }

            function distance(x) {
                return x * width / 100;
            }

            function yTransform(y) {
                var result = height - height * ((y - minY) / (maxY - minY));
                return result;
            }

            function clear() {
                ctx.clearRect(0,0,width,height);
                minY = 0;
                maxY = height;
            }

            function point(x, y, color) {
                ctx.lineWidth = 0.5;
                ctx.strokeStyle = 'black';
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.arc(distance(x),yTransform(y),5,0,2*Math.PI);
                ctx.fill();
                ctx.stroke();
            }

            function line(x1, y1, x2, y2) {
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'blue';
                ctx.beginPath();
                ctx.moveTo(distance(x1), yTransform(y1));
                ctx.lineTo(distance(x2), yTransform(y2));
                ctx.stroke();
            }

            function redraw(model) {
                var data = model.getData();
                var y0 = data.bestFit(0);
                var y100 = data.bestFit(100);
                clear();
                if (data.distancesInMeters.length > 1) {
                    setYScale(y0, y100);
                }
                for (var i = 0; i < data.distancesInMeters.length; ++i) {
                    point(data.distancesInMeters[i],
                          data.sightmarks[i],
                          data.measures[i] == 'y' ? "white" : "yellow");
                }
                if (data.distancesInMeters.length > 1) {
                    line(0, y0, 100, y100);
                }
            }

            this.allChanged = function(model) {
                console.log("graph.allChanged");
                redraw(model);
            }

            this.itemAdded = function(model, index) {
                console.log("graph.itemAdded");
                redraw(model);
            }

            this.itemDeleted = function(model, index) {
                console.log("graph.itemDeleted");
                redraw(model);
            }
        }

//////// EstimateView(items) /////////////////////////////////////////////
        function EstimateView(items) {
            var table = items.table.DataTable({
                paging: false,
                ordering: false,
                info: false,
                filter: false
            });
            var tableTools = new jQuery.fn.dataTable.TableTools( table, {
                "buttons": [ "print" ]
            });
            jQuery(tableTools.fnContainer())
                .appendTo('#sightmarks-estimate-table_wrapper > div');

            function makeRow(index, metricDistances, imperialDistances,
                             bestFit) {
                var metricDistance = '', imperialDistance = '',
                    metricEstimate = '', imperialEstimate = '';
                if (index < metricDistances.length) {
                    metricDistance =
                        String(metricDistances[index]).concat("m");
                    metricEstimate =
                        bestFit(metricDistances[index]).toFixed(1);
                }
                if (index < imperialDistances.length) {
                    imperialDistance =
                        String(imperialDistances[index]).concat("y");
                    imperialEstimate =
                        bestFit(imperialDistances[index] * metersPerYard)
                            .toFixed(1);
                }
                return [
                    metricDistance, metricEstimate,
                    imperialDistance, imperialEstimate
                ];
            }

            function redraw(model) {
                var data = model.getData();
                table.clear();
                if (data.measures.length > 1) {
                    var metricDistances =
                        [10, 15, 18, 20, 25, 30, 40, 50, 60, 70, 90];
                    var imperialDistances =
                        [15, 20, 25, 30, 40, 50, 60, 80, 100];
                    var max = metricDistances.length;
                    if (max < imperialDistances.length) {
                        max = imperialDistances.length;
                    }
                    for (var i = 0; i < max; ++i) {
                        table.row.add(
                            makeRow(i, metricDistances, imperialDistances,
                                    data.bestFit)
                        );
                    }
                }
                table.draw();
            }

            this.allChanged = function(model) {
                console.log("estimateView.allChanged");
                redraw(model);
            }

            this.itemAdded = function(model, index) {
                console.log("estimateView.itemAdded");
                redraw(model);
            }

            this.itemDeleted = function(model, index) {
                console.log("estimateView.itemDeleted");
                redraw(model);
            }
        }

///////////////////////////////////////////////////////////////////////

        jQuery('.rhac-sightmarks-simple-dialog').dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                OK: function () {
                    jQuery(this).dialog( "close" );
                }
            }
        });

        var model = new Model();

        var itemsTable = jQuery('#sightmarks-table').DataTable({
            paging: false,
            ordering: false,
            info: false,
            filter: false
        });

        var itemController = new ItemController(model, {
            validateTips: jQuery('#sightmark-tip'),
            formDistance: jQuery('#sightmark-distance'),
            formSightmark: jQuery('#sightmark-sightmark'),
            dialog: jQuery('#sightmark-dialog'),
            addButton: jQuery('#sightmark-add-button'),
            deleteButton: jQuery('#sightmark-delete-button'),
            itemsTable: itemsTable
        });

        var persistence = persist('rhac-sightmarks-v1',
                                  '#sightmarks-quota-exceeded-dialog',
                                  '#sightmarks-old-browser-dialog');

        var persistenceController = new PersistenceController(model,
                                                            persistence, {
            deleteButton: jQuery('#sightmarks-delete-button'),
            saveButton: jQuery('#sightmarks-save-button'),
            restoreButton: jQuery('#sightmarks-restore-button'),
            select: jQuery('#sightmarks-select'),
            dialog: jQuery('#sightmarks-save-dialog'),
            name: jQuery('#sightmarks-name'),
            validateTips: jQuery('#sightmarks-tip'),
            title: jQuery('.sightmarks-title'),
            confirmDialog: jQuery('#sightmarks-confirm-dialog')
        });

        var itemsView = new ItemsView({
            itemsTable: itemsTable
        });

        model.attach(itemsView);

        var graphView = new GraphView({
            canvas: jQuery('#sightmarks-canvas')
        });

        model.attach(graphView);

        var estimateView = new EstimateView({
            table: jQuery('#sightmarks-estimate-table')
        });

        model.attach(estimateView);

        persistenceController.start();
    }
});
