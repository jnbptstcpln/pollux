Settings = {}

Settings._convert_type = function(type) {
    switch (type) {
        case "float":
        case "int":
            return "number";
        default:
            return "text";
    }
}
Settings._form = function(module, name) {
    return Form.create("<div class='header'><h3>{0}</h3><h2>{1}</h2></div>".format(module, name));
}

Settings.build = function (node) {
    switch (node.component.id) {
        case (node.component.id.match(/logic\.Switch\d*/) || {}).input:
            return Settings.Switch(node)
        case (node.component.id.match(/logic\.Splitter\d*/) || {}).input:
            return Settings.Splitter(node)
        case (node.component.id.match(/logic\.Comparator\d*/) || {}).input:
            return Settings.Comparator(node)
        case (node.component.id.match(/logic\.Assert/) || {}).input:
            return Settings.Assert(node)
        default:
            return Settings.Default(node);

    }
}

Settings.Default = function (node) {
    var form = Settings._form(node.component.module, node.component.name);

    var component = node.component;

    // Inputs
    if (component._inputs.length > 0) {

        if (node.settings["inputs"] === undefined) {
            node.settings["inputs"] = {};
        }

        var fieldset = Form.fieldset("Entrée{0}".format(component._inputs.length > 1 ? "s": ""));
        for (var i in component._inputs) {
            var input = component._inputs[i];
            fieldset.append(Form.input(
                input.name,
                input.name,
                Settings._convert_type(input.type),
                node.settings["inputs"][input.name] !== undefined ? node.settings["inputs"][input.name] : "",
                function(name, value) {
                    if (value.length > 0) {
                        node.settings["inputs"][name] = value;
                    } else {
                        delete node.settings["inputs"][name];
                    }

                },
                Doc.format(input.description)
            ))
        }
        form.append(fieldset)
    }

    // Settings
    if (node.component.settings.length > 0) {
        var fieldset = Form.fieldset("Options");
        for (var i in node.component.settings) {
            var setting = node.component.settings[i];
            fieldset.append(Form.input(
                setting.name,
                setting.name,
                Settings._convert_type(setting.type),
                node.settings[setting.name] !== undefined ? node.settings[setting.name] : "",
                function(name, value) {
                    if (value.length > 0) {
                        node.settings[name] = value;
                    } else {
                        delete node.settings[name];
                    }

                },
                Doc.format(setting.description)
            ))
        }
        form.append(fieldset)
    }

    return form;
}

Settings.Switch = function (node) {
    var form = Settings._form(node.component.module, node.component.name);

    var fieldset_settings = Form.fieldset("Options");
    fieldset_settings.append(
        Form.input(
            'default',
            'Valeur retournée par défaut',
            'text',
            node.settings.default || "",
            function(name, value) {
                node.settings[name] = value;
            },
            Doc.format("Possiblité d'afficher l'entrée avec <{value}>")
        )
    );
    form.append(fieldset_settings);


    if (!node.settings.cases) { node.settings.cases = [] }

    var fieldset_cases = Form.fieldset("Liste des cas").addClass("switch-settings");
    fieldset_cases.append(Form.p(Doc.format("Possiblité d'afficher l'entrée dans le retour en utilisant <{value}>")))
    var cases_container = $.DOM.create("div");

    function switch_settings_case(caseData) {
        var container = $.DOM.create("div").addClass("switch-settings-case")
        container
            .append(
                $.DOM.create("i")
                    .on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (node.settings.cases.length > 0) {
                            node.settings.cases.splice(node.settings.cases.indexOf(this), 1);
                            container.remove();
                        }
                    }.bind(caseData))
            )
            .append(
                $.DOM.create("div")
                    .append(
                        Form.select(
                            'type',
                            'Quand l\'entrée...',
                            {
                                'equals': '==',
                                'greater': '≥',
                                'greater_strict': '>',
                                'lesser': '≤',
                                'lesser_strict': '<',
                                'contains': 'contient',
                                'starts_with': 'commence par',
                                'ends_with': 'termine par',
                                'regex': 'correspond à',
                            },
                            caseData.type,
                            function(name, value) {
                                this[name] = value;
                            }.bind(caseData)
                        )
                    )
                    .append(Form._input(
                        'test',
                        caseData.test || "",
                        function(name, value) {
                            this[name] = value;
                        }.bind(caseData)
                    ))
            )
            .append(Form.textarea(
                'value',
                'Retourner...',
                caseData.value || "",
                function(name, value) {
                    this[name] = value;
                }.bind(caseData)
            ))
        ;
        return container;
    }

    function update() {
        cases_container.children().each(function (i, el) { el.remove() });
        for (var i in node.settings.cases) {
            cases_container.append(switch_settings_case(node.settings.cases[i]));
        }
    }
    update();

    fieldset_cases.append(cases_container);
    fieldset_cases.append(
        $.DOM.create("a")
            .addClass("button")
            .addClass("primary")
            .addClass("block")
            .text("Ajouter un cas")
            .on("click", function(e) {
                e.preventDefault();
                var size = node.settings.cases.push({
                    'type': "equals",
                    'test': '',
                    'value': ''
                })
                cases_container.append(switch_settings_case(size-1));
            })
    )
    form.append(fieldset_cases);
    return form;
}

Settings.Splitter = function (node) {
    var form = Settings._form(node.component.module, node.component.name);

    if (!node.settings.case) { node.settings.case = {'type': 'equals', 'test': ''} }

    var fieldset_cases = Form.fieldset("Test à effectuer").addClass("switch-settings");
    var cases_container = $.DOM.create("div");

    function switch_settings_case(caseData) {
        var container = $.DOM.create("div").addClass("switch-settings-case")
        container
            .append(
                $.DOM.create("div")
                    .append(
                        Form.select(
                            'type',
                            'Quand l\'entrée...',
                            {
                                'equals': '==',
                                'greater': '≥',
                                'greater_strict': '>',
                                'lesser': '≤',
                                'lesser_strict': '<',
                                'contains': 'contient',
                                'starts_with': 'commence par',
                                'ends_with': 'termine par',
                                'regex': 'correspond à',
                            },
                            caseData.type,
                            function(name, value) {
                                this[name] = value;
                            }.bind(caseData)
                        )
                    )
                    .append(Form._input(
                        'test',
                        caseData.test || "",
                        function(name, value) {
                            this[name] = value;
                        }.bind(caseData)
                    ))
            )
        ;
        return container;
    }

    function update() {
        cases_container.children().each(function (i, el) { el.remove() });
        cases_container.append(switch_settings_case(node.settings.case));
    }
    update();

    fieldset_cases.append(cases_container);
    form.append(fieldset_cases);

    return form;
}

Settings.Comparator = function (node) {
    var form = Settings._form(node.component.module, node.component.name);

    var fieldset_settings = Form.fieldset("Options");
    fieldset_settings.append(
        Form.input(
            'default',
            'Valeur retournée par défaut',
            'text',
            node.settings.default || "",
            function(name, value) {
                node.settings[name] = value;
            },
            Doc.format("Possiblité d'afficher les entrées avec <{value1}> et <{value2}>")
        )
    );
    form.append(fieldset_settings);


    if (!node.settings.cases) { node.settings.cases = [] }

    var fieldset_cases = Form.fieldset("Comparaison des entrées").addClass("switch-settings");
    fieldset_cases.append(Form.p(Doc.format("Possiblité d'afficher les entrées dans le retour en utilisant <{value1}> et <{value2}>")))
    var cases_container = $.DOM.create("div");

    function switch_settings_case(caseData) {
        var container = $.DOM.create("div").addClass("switch-settings-case")
        container
            .append(
                $.DOM.create("i")
                    .on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (node.settings.cases.length > 0) {
                            node.settings.cases.splice(node.settings.cases.indexOf(this), 1);
                            container.remove();
                        }
                    }.bind(caseData))
            )
            .append(
                $.DOM.create("div")
                    .style('margin-bottom', '15px')
                    .append(
                        Form.select(
                            'type',
                            'Quand l\'entrée 1...',
                            {
                                'equals': '==',
                                'greater': '≥',
                                'greater_strict': '>',
                                'lesser': '≤',
                                'lesser_strict': '<',
                                'contains': 'contient',
                                'starts_with': 'commence par',
                                'ends_with': 'termine par',
                                'regex': 'correspond à',
                            },
                            caseData.type,
                            function(name, value) {
                                this[name] = value;
                            }.bind(caseData)
                        )
                    )
                    .append(Form._label("...L'entrée 2"))
            )
            .append(Form.textarea(
                'value',
                'Retourner...',
                caseData.value || "",
                function(name, value) {
                    this[name] = value;
                }.bind(caseData)
            ))
        ;
        return container;
    }

    function update() {
        cases_container.children().each(function (i, el) { el.remove() });
        for (var i in node.settings.cases) {
            cases_container.append(switch_settings_case(node.settings.cases[i]));
        }
    }
    update();

    fieldset_cases.append(cases_container);
    fieldset_cases.append(
        $.DOM.create("a")
            .addClass("button")
            .addClass("primary")
            .addClass("block")
            .text("Ajouter un cas")
            .on("click", function(e) {
                e.preventDefault();
                var size = node.settings.cases.push({
                    'type': "equals",
                    'value': ''
                })
                cases_container.append(switch_settings_case(size-1));
            })
    )
    form.append(fieldset_cases);
    return form;
}

Settings.Assert = function (node) {
    var form = Settings._form(node.component.module, node.component.name);

    var fieldset_settings = Form.fieldset("Options");
    fieldset_settings
        .append(
            Form.select(
                'exit',
                'Si le test est négatif',
                {
                    'continue': "Continuer l'exécution du processus",
                    'exit': "Terminer l'exécution du processus",
                },
                node.settings.exit || "exit",
                function(name, value) {
                    node.settings[name] = value;
                }
            )
        )
        .append(
            Form.input(
                'message',
                'Message en cas d\'arrêt',
                'text',
                node.settings.message || "",
                function(name, value) {
                    if (value.length > 0) {
                        node.settings[name] = value;
                    } else {
                        delete node.settings[name];
                    }
                },
                Doc.format("Message à afficher en cas d'arrêt (possibilité d'afficher l'entrée avec <{value}>)")
            )
        )
    ;
    form.append(fieldset_settings);

    if (!node.settings.case) { node.settings.case = {'type': 'equals', 'test': ''} }

    var fieldset_cases = Form.fieldset("Test à effectuer").addClass("switch-settings");
    var cases_container = $.DOM.create("div");

    function switch_settings_case(caseData) {
        var container = $.DOM.create("div").addClass("switch-settings-case")
        container
            .append(
                $.DOM.create("div")
                    .append(
                        Form.select(
                            'type',
                            'Continuer quand l\'entrée est...',
                            {
                                'equals': '==',
                                'greater': '≥',
                                'greater_strict': '>',
                                'lesser': '≤',
                                'lesser_strict': '<',
                                'contains': 'contient',
                                'starts_with': 'commence par',
                                'ends_with': 'termine par',
                                'regex': 'correspond à',
                            },
                            caseData.type,
                            function(name, value) {
                                this[name] = value;
                            }.bind(caseData)
                        )
                    )
                    .append(Form._input(
                        'test',
                        caseData.test || "",
                        function(name, value) {
                            this[name] = value;
                        }.bind(caseData)
                    ))
            )
        ;
        return container;
    }

    function update() {
        cases_container.children().each(function (i, el) { el.remove() });
        cases_container.append(switch_settings_case(node.settings.case));
    }
    update();

    fieldset_cases.append(cases_container);
    form.append(fieldset_cases);

    return form;
}