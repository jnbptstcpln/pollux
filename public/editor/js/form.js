Form = {};
Form.create = function (html) {
    return $.DOM.create("form").html(html || "");
}
Form.fieldset = function (name) {
    return $.DOM.create("fieldset").html("<legend>{0}</legend>".format(name))
}
Form.input = function (name, label, type, value, oninput) {
    var div = $.DOM.create("div").addClass("field").html("<label>{0}</label><input name='{1}' type='{2}' value='{3}'>".format(label, name, type, value));
    div.find('input').on('input', function(e) {
        oninput(e.element.attr('name'), e.element.get('value'));
    });
    return div;
}