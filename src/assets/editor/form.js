Form = {};
Form.create = function (html) {
    return $.DOM.create("form").html(html || "");
}
Form.fieldset = function (name) {
    return $.DOM.create("fieldset").html("<legend>{0}</legend>".format(name))
}
Form._label = function(html) {
    return $.DOM.create("label").html(html);
}
Form.input = function (name, label, type, value, oninput, help_text) {
    var div = $.DOM.create("div").addClass("field");
    div.append(Form._label(label));
    if (help_text) { div.append(Form._help_text(help_text)) }
    div.append(Form._input(name, value, oninput));
    return div;
}
Form._input = function(name, value, oninput) {
    var input = $.DOM.create('input').attr('name', name);
    input.set('value', value)
    input.on('input', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return input;
}
Form.textarea = function (name, label, value, oninput, help_text) {
    var div = $.DOM.create("div").addClass("field");
    div.append(Form._label(label));
    if (help_text) { div.append(Form._help_text(help_text)) }
    div.append(Form._textarea(name, value, oninput));
    return div;
}
Form._textarea = function (name, value, oninput) {
    var textarea = $.DOM.create('textarea').attr('name', name);
    textarea.set('value', value)
    textarea.on('input', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return textarea;
}
Form.select = function (name, label, options, value, oninput, help_text) {
    var div = $.DOM.create("div").addClass("field");
    div.append(Form._label(label));
    if (help_text) { div.append(Form._help_text(help_text)) }
    div.append(Form._select(name, options, value, oninput));
    return div;
}
Form._select = function (name, options, value, oninput) {
    var options_html = "";
    for (var i in options) {
        options_html += "<option value='{0}' {2}>{1}</option>".format(i, options[i], value === i ? 'selected' : '');
    }
    var select = $.DOM.create("select").attr('name', name).html(options_html);
    select.on('change', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return select;
}
Form.p = function (html) {
    return $.DOM.create("p").html(html);
}
Form._help_text = function (html) {
    return $.DOM.create('p').addClass("text-help").html(html);
}
Form.ul = function (items) {
    var ul = $.DOM.create('ul');
    console.log(items);
    for (var i in items) {
        ul.append("<li>{0}</li>".format(items[i]));
    }
    return ul;
}