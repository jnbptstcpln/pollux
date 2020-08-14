Doc = {};
Doc.format = function (string) {
    console.log(string);
    string = string.replace(/<([^>]+)>/gi, "<code>$1</code>");
    string = string.replace(/\[([^\]]+)\]/gi, "<span class='badge badge-primary'>$1</span>");
    return string;
}
Doc._table = function (thead, content) {
    var table = $.DOM.create("table").addClass("table");
    table.html("<thead><tr></tr></thead><tbody></tbody>");
    var header = table.find('thead > tr');
    for (var i in thead) {
        header.append("<th>{0}</th>".format(thead[i]));
    }
    var tbody = table.find('tbody');
    for (var j in content) {
        var cells = "";
        for (var k in content[j]) {
            cells += "<td>{0}</td>".format(content[j][k])
        }
        tbody.append("<tr>{0}</tr>".format(cells));
    }
    return table;
}
Doc.inputs_table = function (component) {
    var content = [];
    for (var i in component.inputs) {
        content.push([
            component.inputs[i].name,
            "<code>{0}</code>".format(component.inputs[i].type),
            Doc.format(component.inputs[i].description),
        ])
    }
    return Doc._table(['Nom', 'Type', 'Description'], content)
}
Doc.outputs_table = function (component) {
    var content = [];
    for (var i in component.outputs) {
        content.push([
            component.outputs[i].name,
            "<code>{0}</code>".format(component.outputs[i].type),
            Doc.format(component.outputs[i].description),
        ])
    }
    return Doc._table(['Nom', 'Type', 'Description'], content)
}
Doc.settings_table = function (component) {
    var content = [];
    for (var i in component.settings) {
        content.push([
            component.settings[i].name,
            "<code>{0}</code>".format(component.settings[i].type),
            Doc.format(component.settings[i].description),
        ])
    }
    return Doc._table(['Nom', 'Type', 'Description'], content)
}