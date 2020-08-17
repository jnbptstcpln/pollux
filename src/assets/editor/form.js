Form = {};
Form.create = function (html) {
    return $.DOM.create("form").html(html || "");
}
Form.fieldset = function (name) {
    return $.DOM.create("fieldset").html("<legend>{0}</legend>".format(name))
}
Form.input = function (name, label, type, value, oninput, help_text) {
    help_text = help_text ? "<p class='text-help'>{0}</p>".format(help_text) : "";
    var div = $.DOM.create("div").addClass("field").html("<label>{0}</label>{4}<input name='{1}' type='{2}' value='{3}'>".format(label, name, type, value, help_text));
    div.find('input').on('input', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return div;
}
Form.textarea = function (name, label, value, oninput, help_text) {
    help_text = help_text ? "<p class='text-help'>{0}</p>".format(help_text) : "";
    var div = $.DOM.create("div").addClass("field").html("<label>{0}</label>{3}<textarea name='{1}'>{2}</textarea>".format(label, name, value, help_text));
    div.find('textarea').on('input', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return div;
}
Form.select = function (name, label, options, value, oninput, help_text) {

    var options_html = "";
    for (var i in options) {
        options_html += "<option value='{0}' {2}>{1}</option>".format(i, options[i], value == i ? 'selected' : '');
    }

    help_text = help_text ? "<p class='text-help'>{0}</p>".format(help_text) : "";
    var div = $.DOM.create("div").addClass("field").html("<label>{0}</label>{3}<select name='{1}'>{2}</select>".format(label, name, options_html, help_text));
    div.find('select').on('change', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return div;
}
Form.p = function (html) {
    return $.DOM.create("p").html(html);
}
Form.ul = function (items) {
    var ul = $.DOM.create('ul');
    console.log(items);
    for (var i in items) {
        ul.append("<li>{0}</li>".format(items[i]));
    }
    return ul;
}