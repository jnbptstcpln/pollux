$ = Fulgur;

/**
 * FILEINPUT
 */
$.registerElementExtension('fileinput', function () {
    var input = this;
    var parent = input.parent();
    var identifier_name = input.attr('data-identifier') || "";
    var input_identifier = $('input[name="{0}"]'.format(identifier_name));

    if (!input_identifier.exists()) {
        input_identifier = $.DOM.create('input');
    }

    var container = $.DOM.create('div').addClass('fulgur-fileinput');
    container
        .addClass('layout')
        .addClass('center')
    ;
    var display = $.DOM.create('div');
    container.append(display);

    // Remove input from the DOM
    input.remove();
    // Append input to the container
    container.append(input);
    input.hide();
    // Append the container to input's previous parent
    parent.append(container);

    function render_default_state() {
        display.html('');
        if (input_identifier.get('value').length > 0) {
            if (input.attr('data-href')) {
                display.append('<a class="button" href="{0}">Consulter</a>'.format($(input.attr('data-href').format(input_identifier.get('value')))));
            }
            display.append('<a class="button input" href="#">Modifier</a>');
        } else {
            display.append('<a class="button input" href="#">Sélectionner un fichier</a>');
        }
    }

    function render_input_state() {
        display.html('');
        console.log(display.html());
        var file_label = input.get('files').item(0).name;
        display.append('<h6>Fichier à envoyer :</h6>');
        display.append('<p>{0}</p>'.format(file_label));
        display.append('<div><a class="button small cancel">Annuler</a></div>')
    }

    container.on('click', 'a.input', function(event) {
        event.preventDefault();
        input.show();
        input._DOMElement.click();
        input.hide();
    });

    container.on('click', 'a.cancel', function(event) {
        event.preventDefault();
        input.set('value', '');
        render_default_state();
    });

    input.on('change', function(event) {
        if (input.get('files').length > 0) {
            render_input_state();
        } else {
            render_default_state();
        }
    });

    container.on('dragover', function(event) {
        event.preventDefault();
    });
    container.on('drop', function(event) {
        event.preventDefault();
        if (event.dataTransfer.items) {
            if (event.dataTransfer.items.length > 1) {
                alert("Vous ne pouvez ajouter qu'un seul fichier");
            } else {
                if (event.dataTransfer.items[0].kind === 'file') {
                    input.set('files', event.dataTransfer.files);
                }
            }
        } else {
            // Use DataTransfer interface to access the file(s)
            if (event.dataTransfer.files.length > 1) {
                alert("Vous ne pouvez ajouter qu'un seul fichier");
            } else {
                input.set('files', event.dataTransfer.files);
            }

        }

    });

    render_default_state();
});

/**
 * DATEINPUT
 */
$.registerElementExtension('dateinput', function () {
    var input = this;
    input.on('input', function(event) {
        var value = input.get('value');
        if (event.inputType === "insertText") {
            if (value.length === 2 || value.length === 5) {
                input.set('value', value+"/");
            }
            if (event.data === "/" && value[value.length-2] === "/") {
                input.set('value', value.substring(0, value.length - 1))
            }
        }

    });
});

/**
 * TIMEINPUT
 */
$.registerElementExtension('timeinput', function () {
    var input = this;
    input.on('input', function(event) {
        var value = input.get('value');
        if (event.inputType === "insertText") {
            if (value.length === 2) {
                input.set('value', value+":");
            }
            if (event.data === ":" && value[value.length-2] === ":") {
                input.set('value', value.substring(0, value.length - 1))
            }
        }

    });
});

/**
 * DATATABLE
 */
$.registerElementExtension('datatable', function () {
    var table = this;
    var tbody = table.find('tbody');
    if (!tbody.exists()) {
        tbody = $.DOM.create("tbody");
        table.append(tbody);
    }
    var tfoot = table.find('tfoot');
    if (!tfoot.exists()) {
        tfoot = $.DOM.create("tfoot");
        table.append(tfoot);
    }
    var total = null;
    var page_length = 15;
    var page_active = null;
    var pages_cache = {};
    var search_values = {};
    var url = table.attr('data-url');
    var fields = {};
    table.find('thead th').toCollection().each(function(i, el) {
        fields[el.attr('data-name')] = el;
        search_values[el.attr('data-name')] = "";
        if (el.hasClass('control')) {
            el.append("<a class='search'><i class='far fa-search'></i></a>")
        }
    });

    table.on('click', 'thead th.control a.search', function(event) {
        event.preventDefault();
        var th = event.element.parent('th.control');
        th.width(th.width());
        th.attr('data-text', th.text());
        th.html("<div class='field'><label>{0}</label><div class='layout center'><input placeholder='Rechercher...' autocomplete='off'><a class='close'><i class='far fa-times'></i></a></div></div>".format(th.text()));
        th.find('input')._DOMElement.focus();
    });

    table.on('click', 'thead th.control a.close', function(event) {
        event.preventDefault();
        var th = event.element.parent('th.control');
        th.text(th.attr('data-text'));
        th.append("<a class='search'><i class='far fa-search'></i></a>");
        search_values[th.attr('data-name')] = "";
        search();
    });

    var searchTimeout = null;
    table.on('input', 'thead th.control input', function(event) {
        console.log("input");
        clearTimeout(searchTimeout);
        var th = event.element.parent('th.control');
        search_values[th.attr('data-name')] = event.element.get('value');
        setTimeout(search, 500);
    });


    // Load page
    function load_page(page_index) {
        page_active = page_index;
        if (!(page_index in pages_cache)) {
            var loadingTimeout = setTimeout(function(){
                tbody.html('<tr><td colspan="{0}" style="text-align: center"><i class="far fa-spinner fa-spin"></i> Chargement</td></tr>'.format(Object.keys(fields).length));
            }, 500);
            $.api.get(url+"/page/"+page_index, {}, function(response) {
                clearTimeout(loadingTimeout);
                if (response.success) {
                    pages_cache[page_index] = response.payload;
                    render_page(page_index);
                } else {
                    tbody.html('<tr><td colspan="{0}" style="text-align: center"><i class="far fa-exclamation-circle"></i> Une erreur est survenue lors du chargement des données...</td></tr>'.format(Object.keys(fields).length));
                }
            })
        } else {
            render_page(page_index);
        }
    }

    // Render page
    function render_page(page_index) {
        // Pages selector
        table.find('tfoot .pages a.active').removeClass("active");
        table.find('tfoot .pages a[href="'+page_index+'"]').addClass("active");
        render_content(pages_cache[page_index]);
    }

    function search() {
        var perform_search = false;
        for (var i in search_values) {
            if (search_values[i].length > 0) {
                perform_search = true;
            }
        }

        if (!perform_search) {
            load_page(page_active);
            return;
        }

        var loadingTimeout = setTimeout(function(){
            tbody.html('<tr><td colspan="{0}" style="text-align: center"><i class="far fa-spinner fa-spin"></i> Chargement</td></tr>'.format(Object.keys(fields).length));
        }, 2000);

        $.api.get(url+"/search", search_values, function(response) {
            clearTimeout(loadingTimeout);
            if (response.success) {
                render_content(response.payload)
            } else {
                tbody.html('<tr><td colspan="{0}" style="text-align: center"><i class="far fa-exclamation-circle"></i> Une erreur est survenue lors du chargement des données...</td></tr>'.format(Object.keys(fields).length));
            }
        })
    }

    // Render content
    function render_content(content) {
        tbody.html("");
        for (var i in content) {
            var data = content[i];
            var tr = $.DOM.create("tr");
            for (var j in fields) {
                var td = $.DOM.create("td");
                var value = data[j];
                if (typeof value === 'string' || value instanceof String) {
                    td.text(value);
                } else {
                    td.html(value.html);
                }
                tr.append(td);
            }
            tbody.append(tr);
        }
        if (content.length === 0) {
            tbody.html('<tr><td colspan="{0}" style="text-align: center">Aucun élément à afficher</td></tr>'.format(Object.keys(fields).length));
        }
    }

    function render_tfoot() {
        tfoot.html('<tr><td colspan="{0}"></td></tr>'.format(Object.keys(fields).length));
        var td = tfoot.find('td');
        var pages = $.DOM.create("p").addClass("pages");
        var pageTotal = Math.ceil(total/page_length);
        if (pageTotal > 1) {
            for (var i=0; i<pageTotal; i++) {
                var a = $.DOM.create("a");
                a.text(i+1);
                a.attr("href", i);
                pages.append(a);
            }
        }
        td.append(pages);
    }

    table.on('click', 'tfoot .pages a', function (event) {
        event.preventDefault();
        load_page(event.element.attr('href'));
    });

    // Init
    $.api.get(url, {}, function (response) {
        if (response.success) {
            page_length = response.payload.page_length;
            total = response.payload.total;
            render_tfoot();
        } else {
            alert("Une erreur est survenue lors du chargement du tableau");
        }
    });
    load_page(0);
});

/**
 * SEARCHTABLE
 */
$.registerElementExtension('searchtable', function () {
    var table = this;
    var tbody = table.find('tbody');

    var fields = {};
    table.find('thead th').toCollection().each(function(i, el) {
        if (el.hasClass('control')) {
            el.append("<a class='search'><i class='far fa-search'></i></a>")
        }
    });

    table.on('click', 'thead th.control a.search', function(event) {
        event.preventDefault();
        var th = event.element.parent('th.control');
        th.width(th.width());
        th.attr('data-text', th.text());
        th.html("<div class='field'><label>{0}</label><div class='layout center'><input placeholder='Rechercher...' autocomplete='off'><a class='close'><i class='far fa-times'></i></a></div></div>".format(th.text()));
        th.find('input')._DOMElement.focus();
    });

    table.on('click', 'thead th.control a.close', function(event) {
        event.preventDefault();
        var th = event.element.parent('th.control');
        th.text(th.attr('data-text'));
        th.append("<a class='search'><i class='far fa-search'></i></a>");
        search();
    });

    var searchTimeout = null;
    table.on('input', 'thead th.control input', function(event) {
        clearTimeout(searchTimeout);
        setTimeout(search, 500);
    });

    function search() {

        var search_values = {};
        var perform_search = false;
        table.find('thead th').toCollection().each(function(i, el) {
            var input = el.find('input');
            if (input.exists()) {
                search_values[i] = input.get('value');
                if (search_values[i].length > 0) {
                    perform_search = true;
                }
            }
        });

        console.log(search_values);

        tbody.find('tr').style("display", null);

        if (perform_search) {
            tbody.find('tr').toCollection().each(function(i, tr) {
                tr.find('th, td').toCollection().each(function(j, td) {
                    if (j in search_values) {
                        if (!td.text().sansAccent().hasSubString(search_values[j].sansAccent())) {
                            tr.style('display', 'none');
                        }
                    }
                });
            });
        }
    }
});

/**
 * CONFIRM
 */
$.registerElementExtension('confirm', function () {
    var el = this;
    el.on('click', function(event) {
        if (!confirm(el.attr('data-message'))) {
            event.preventDefault();
        }
    })
});

/**
 * SIMPLEMDE
 */
$.registerElementExtension('simplemde', function () {
    var textarea = this;
    var container = $.DOM.create("div").addClass("fulgur-simplemde-container");
    textarea._DOMElement.parentNode.insertBefore(container._DOMElement, textarea._DOMElement.nextSibling);
    textarea.remove();
    container.append(textarea);
    var simplemde = new SimpleMDE({
        element: textarea._DOMElement,
        toolbar: ["bold", "italic", "heading", "|", "unordered-list", "ordered-list", "link"],
        spellChecker: false,
    });
    simplemde.codemirror.on("change", function(){
        textarea.text(simplemde.value());
    });
});

/**
 * DATEPICKER
 */
$.registerElementExtension('datepicker', function () {
    var element = this;
    var input = flatpickr(element._DOMElement, {
        locale: 'fr',
        dateFormat: element.attr('data-format') || "d/m/Y",
    });
});

/**
 * DATETIMEPICKER
 */
$.registerElementExtension('datetimepicker', function () {
    var element = this;
    var input = flatpickr(element._DOMElement, {
        enableTime: true,
        locale: 'fr',
        dateFormat: element.attr('data-format') || "d/m/Y H:i",
    });
});

/**
 * AUTOCOMPLETE
 */
$.registerElementExtension('autocomplete', function() {

    var input = this;
    input
        .style('margin-bottom', '0')
    ;
    var parent = input.parent();
    var url = input.attr('data-url');
    var values = input.attr('data-values'); // Au format JSON [value1, value2, ...] ou {key1:value1, key2:value2, ...}
    var loading_mode = input.attr('data-loading-mode') || 'stored'; // "stored" or "fetch"
    var data = [];

    var container = $.DOM.create('div').addClass('fulgur-autocomplete');
    // Remove input from the DOM
    input.remove();
    // Append input to the container
    container.append(input);
    // Append the container to input's previous parent
    parent.append(container);

    var options = $.DOM.create('ul');
    container.append(options);

    // Init data
    if (loading_mode === "stored" && url) {
        $.api.get(url, {}, function(response) {
            if (response.success) {
                data = response.payload;
            }
        });
    } else if (values) {
        data = JSON.parse(values);
    }

    function render_loading() {
        options.html('<li><i class="far fa-spinner fa-spin"></i> Chargement...</li>');
        var nb_options = options.children().length();
        options.get('style').setProperty('--nb-options', nb_options);
        open();
    }

    function render_error() {
        options.html('<li><i class="far fa-exclamation-circle"></i> Une erreur est survenue...</li>');
        var nb_options = options.children().length();
        options.get('style').setProperty('--nb-options', nb_options);
        open();
    }

    function render(value) {
        options.html('');
        if (value.length > 0) {
            for (var i in data) {

                var _label = "";
                var _value = "";

                if (data.constructor == Object) {
                    _value = i;
                    _label = data[i];
                } else {
                    _value = data[i];
                    _label = data[i];
                }

                if (_label.hasSubString(value)) {
                    var li = $.DOM.create('li');
                    li
                        .text(_label)
                        .attr('data-label', _label)
                        .attr('data-value', _value)
                    ;
                    options.append(li);
                }
            }
            var nb_options = options.children().length();
            options.get('style').setProperty('--nb-options', nb_options);
            if (nb_options > 0) {
                open();
            } else {
                close()
            }

        } else {
            close();
        }
    }

    function open() {
        container.addClass('open');
    }

    function close() {
        container.removeClass('open');
    }

    options.on('click', 'li', function(event) {
        close();
        input.trigger('focus');
        console.log(event.element.attr('data-value'));
        input.set('value', event.element.attr('data-value'));
        input.trigger('autocompleted');
    });

    options.on('mouseover', 'li', function(event) {
        Element = event.element;
        console.log(event);
        if (!Element.matches('.active')) {
            options.find('li.active').removeClass('active');
            Element.addClass('active');
        }
    });

    var search_timeout = null;
    input.on('input', function(event) {
        var value = input.get('value');
        if (loading_mode === "fetch") {
            if (search_timeout) {clearTimeout(search_timeout)}
            search_timeout = setTimeout(function() {
                render_loading();
                $.api.get(url, {value: value}, function(response) {
                    if (response.success) {
                        data = response.payload;
                        render(value);
                    } else {
                        render_error()
                    }
                });
            }, 400);
        } else {
            render(value);
        }

    });

    input.on('blur', function(e) {
        setTimeout(close, 150);
    });

    input.on('keydown', function(e) {
        if (e.keyCode === 40) { // ArrowDown
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                options.find('li.active').removeClass('active');
                $(ActiveLi._DOMElement.nextSibling).addClass('active');
            } else {
                options.find('li:first-child').addClass('active');
            }
        }
        if (e.keyCode === 38) { // ArrowUp
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                options.find('li.active').removeClass('active');
                $(ActiveLi._DOMElement.previousSibling).addClass('active');
            } else {
                options.find('li:first-child').addClass('active');
            }
        }
        if (e.keyCode === 13) { // Enter
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                close();
                console.log(e);
                input.trigger('focus');
                input.set('value', ActiveLi.attr('data-value'));
                input.trigger('autocompleted');
            }
        }
    })

});

/**
 * KEYWORDS_INPUT
 */
$.registerElementExtension('categoryinput', function() {
    var container = this.parent();
    var keywords = [];
    var input_hidden = this;
    var values = JSON.parse(input_hidden.attr('data-values')) || [];
    var input_display = $.DOM.create('input')
        .attr('placeholder', 'Ajouter un élément...')
        .addClass('small')
        .attr('data-values', input_hidden.attr('data-values'))
        .attr('data-label', 'label')
    ;

    function add() {
        var keyword = input_display.get('value').trim();
        if (keyword.length > 0) {
            if (keywords.indexOf(keyword) < 0) {
                keywords.push(keyword);
                update();
                label_container.append(render(keyword));
            }
            input_display.set('value', '');
        }
    }

    input_display.on('autocompleted', function(event) {
        add();
    });

    var label_container = $.DOM.create('span').addClass('fulgur-categoryinput-labelcontainer');
    label_container.on('click', 'span.delete', function(event) {
        var label =  event.element.parent('span.badge');
        var keyword = label.attr('data-value');
        console.log(keyword, keywords.indexOf(keyword));
        if (keywords.indexOf(keyword) >= 0) {
            keywords.splice(keywords.indexOf(keyword), 1);
            update();
        }
        label.remove();
    });

    var local_container = $.DOM.create('div').addClass('fulgur-categoryinput');

    try {
        keywords = JSON.parse(input_hidden.get('value'));
    } catch (e) {}

    local_container.append(label_container);
    local_container.append(input_display);
    container.append(local_container);

    function update() {
        input_hidden.set('value', JSON.stringify(keywords));
    }

    function render(keyword) {
        var value = keyword;
        var label = keyword;
        if (values.constructor == Object) {
            label = values[keyword];
        }
        return $.format('<span class="badge mb-5" data-value="{0}">{1} <span class="delete"><i class="fas fa-times-circle"></i></span></span>', value, label);
    }

    function draw() {
        label_container.html('');
        for (i in keywords) {
            label_container.append(render(keywords[i]));
        }
    }

    input_display.autocomplete();

    container.find('div.fulgur-autocomplete').style('max-width', '200px');

    update();
    draw();
});

/**
 * POSTLINK
 */
$.registerElementExtension('postlink', function() {
    var link = this;
    link.on('click', function(event) {
        event.preventDefault();
        FORM = $.DOM.create('form');
        FORM
            .attr('action', event.element.attr('href'))
            .attr('method', 'post')
        ;
        document.body.appendChild(FORM._DOMElement);
        FORM._DOMElement.submit();
    });
});

/**
 * JSON
 */
$.registerElementExtension('json', function() {
    var el = this;
    var text = el.text();
    try {
        el.text(JSON.stringify(JSON.parse(text), null, 4))
    } catch (e) {
        el.text(text);
    }
});

/**
 * AJAXPOST
 */
$.registerElementExtension('ajaxpost', function() {
    var link = this;
    var ongoing = false;
    link.on('click', function(event) {
        event.preventDefault();
        if (!ongoing) {
            ongoing = true;
            $.api.post(
                event.element.attr('href'),
                JSON.parse(event.element.attr('data-data') || "{}"),
                function (rep) {
                    ongoing = false;
                    if (!rep.success) {
                        console.log(rep);
                        alert("Une erreur est survenue...");
                    }
                }
            )
        }
    });
});