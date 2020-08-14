// COMPONENT SIZING
WIDTH_DEFAULT = 50
WIDTH_UNIT = 25;
HEIGHT_UNIT = 35;
COMPONENT_HEADER_HEIGHT = 30;
PORT_SIZE = 5;

// LINK
LINK_WIDTH = 3;
LINK_ID_SEPARATOR = "::";

// COLORS
PRIMARY_COLOR = "#0050D7";
SELECTION_COLOR = "#63d0f5";
COMPONENT_BORDER = "#dddddd";
COMPONENT_FILL = "#f9f9f9";
LINK_COLOR= "#ff7800";
PORT_COLOR = "#ff7800";

// SCALING
MIN_SCALE = 0.5;
MAX_SCALE = 1.5;

// ACTIONS
ACTIONS = {
    'clone': "\uf24d",
    'remove': "\uf2ed",
    'infos': "\uf05a"
};
ACTIONS_BUTTON_SIZE = 30;
ACTIONS_BUTTON_SPACING = 5;

function Flow(nodes, links, settings) {
    this.nodes = nodes || {};
    this.links = links || {};
    this.settings = settings || {};

    /**
     * Search all links that start from the given node_id
     * @param node_id
     * @returns {[]}
     */
    this.get_links_to_node = function(node_id) {
        var links = {};
        for (var i in this.links) {
            var link = this.links[i];
            for (var j in link.targets) {
                var target = link.targets[j];
                if (target.id().startsWith(node_id+":")) {
                    links[i] = link;
                    break;
                }
            }
        }
        return links;
    }

    /**
     * Search all links that goes to the given node_id
     * @param node_id
     */
    this.get_links_from_node = function(node_id) {
        var links = {};
        for (var i in this.links) {
            if (i.startsWith(node_id+":")) {
                links[i] = this.links[i];
            }
        }
        return links;
    }

    /**
     * Generate a unique node_id
     * @returns {string}
     */
    this.generate_node_id = function () {
        var acc = Object.keys(this.nodes).length+1;
        while (this.nodes["node"+acc]) {
            acc += 1;
        }
        return "node"+acc;
    }

    /**
     * Tell if the given port is connected
     * @param port
     */
    this.is_port_connected = function (port) {
        if (port.direction === "out") {
            if (this.links[port.id()] && Object.keys(this.links[port.id()].targets).length > 0) {
                return true;
            }
        } else {
            var links = this.get_links_to_node(port.component.node.id);
            for (var i in links) {
                var link = links[i];
                if (link.targets[port.id()]) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Export the flow
     * @returns {{nodes: [], links: [], positions: {}}}
     */
    this.export = function() {

        var flowData = {
            'nodes': [],
            'links': [],
            'positions': {},
            'settings': this.settings,
        };

        for (var i in this.nodes) {
            var node = this.nodes[i];
            flowData.nodes.push(node.export());
            flowData.positions[node.id] = node.position();
        }

        for (var i in this.links) {
            var link = this.links[i];
            var linkData = link.export();
            // Export the link if it has at least one target
            if (linkData.targets.length > 0) {
                flowData.links.push(linkData);
            }
        }

        return flowData;
    }
}

Flow.Node = function(id, component, settings, x, y) {

    this.id = id;
    this.component = component;
    this.component.node = this;
    this.component.update_port_id();

    this.settings = settings;

    this.shape = new Konva.Group({
        x: x,
        y: y,
        name: 'node',
        id: this.id,
        draggable: true
    });
    this.shape.add(component.shape);

    this.selectionRect = new Konva.Rect({
        width: this.component.width,
        height: this.component.height,
        stroke: SELECTION_COLOR,
        visible: false
    })
    this.shape.add(this.selectionRect);

    /**
     * Return the corresponding input port
     * @param name
     * @returns {*}
     */
    this.get_input = function (name) {
        return this.component.inputs[name];
    }

    /**
     * Return the corresponding output port
     * @param name
     * @returns {*}
     */
    this.get_output = function (name) {
        return this.component.outputs[name];
    }

    /**
     * Show selection indicator
     */
    this.select = function () {
        this.component.main_rect.stroke(SELECTION_COLOR);
        this.component.actions.show();
    }

    /**
     * Hide selection indicator
     */
    this.unselect = function () {
        this.component.main_rect.stroke(COMPONENT_BORDER);
        this.component.actions.hide();
    }

    /**
     *
     */
    this.remove = function () {
        this.shape.destroy();
    }

    /**
     * Export the node
     * @returns {{settings: *, component: *, id: *}}
     */
    this.export = function () {
        var data = {};
        Object.assign(data,
            {
                'id': this.id,
                'component': this.component.id
            },
            this.settings
        )
        return data;
    }

    /**
     * Set a callback for click event on the button corresponding to the given name
     * @param name
     * @param callback
     */
    this.set_action = function(name, callback) {
        if (this.component.actions.buttons[name]) {
            this.component.actions.buttons[name].callback = callback;
        }
    }

    /**
     * Return the current position of the node
     * @returns {{x: *, y: *}}
     */
    this.position = function () {
        return {
            'x': this.shape.x(),
            'y': this.shape.y(),
        }
    }

    /**
     * Build a form inside the given container to edit node's settings
     * @param sidebar
     */
    this.build_settings_form = function(sidebar) {
        var form = Form.create("<div class='header'><h3>{0}</h3><h2>{1}</h2></div>".format(this.component.module, this.component.name));

        // Inputs
        if (this.component._inputs.length > 0) {

            if (this.settings["inputs"] === undefined) {
                this.settings["inputs"] = {};
            }

            var fieldset = Form.fieldset("Entrée{0}".format(this.component._inputs.length > 1 ? "s": ""));
            for (var i in this.component._inputs) {
                var input = this.component._inputs[i];

                var type = "text";
                switch (input.type) {
                    case "float":
                    case "int":
                        type = "number";
                        break;
                }

                fieldset.append(Form.input(
                    input.name,
                    input.name,
                    type,
                    this.settings["inputs"][input.name] !== undefined ? this.settings["inputs"][input.name] : "",
                    function(name, value) {
                        if (value.length > 0) {
                            this.settings["inputs"][name] = value;
                        } else {
                            delete this.settings["inputs"][name];
                        }

                    }.bind(this),
                    Doc.format(setting.description)
                ))
            }
            form.append(fieldset)
        }

        // Settings
        if (this.component.settings.length > 0) {
            var fieldset = Form.fieldset("Options");
            for (var i in this.component.settings) {
                var setting = this.component.settings[i];

                var type = "text";
                switch (setting.type) {
                    case "float":
                    case "int":
                        type = "number";
                        break;
                }

                fieldset.append(Form.input(
                    setting.name,
                    setting.name,
                    type,
                    this.settings[setting.name] !== undefined ? this.settings[setting.name] : "",
                    function(name, value) {
                        if (value.length > 0) {
                            this.settings[name] = value;
                        } else {
                            delete this.settings[name];
                        }

                    }.bind(this),
                    Doc.format(setting.description)
                ))
            }
            form.append(fieldset)
        }

        // Settings

        sidebar.append(form);
    }

}

Flow.Component = function(id, inputs, outputs, size, settings) {

    this.id = id;
    this.node = null;
    this._inputs = inputs
    this.inputs = {};
    this._outputs = outputs
    this.outputs = {};
    this.size = parseInt(size);

    this.settings = settings;

    var parts = id.split(".")
    this.name = parts.pop();
    this.module = parts.join(".");

    this.shape = new Konva.Group({
        name: 'component'
    });

    // Configuration depending on size
    switch (size) {
        case 0:
            this.width = Math.round((Math.max(1, parseInt(size)) * WIDTH_UNIT + WIDTH_DEFAULT) * ((this._inputs.length > 0 && this._outputs.length > 0) ? 1.5 : 1));
            this.height = Math.max(this._inputs.length, this._outputs.length, 1) * HEIGHT_UNIT/2;

            var text = new Konva.Text({
                text: this.name.toUpperCase(),
                align: alignement,
                fontSize: 10,
                fontFamily: 'Muli',
                verticalAlign: 'middle',
                height: this.height,
                fill: "#555555",
                padding: 8
            })

            var alignement = "center";
            this.width = (text.width() + 10) + 3 * PORT_SIZE;
            if (this._inputs.length > 0 && this._outputs.length === 0) {
                alignement = "right"
                this.width = (text.width() + 5) + 2 * PORT_SIZE;
            } else if (this._inputs.length === 0 && this._outputs.length > 0) {
                alignement = "left"
                this.width = (text.width() + 5) + 2* PORT_SIZE;
            }

            this.main_rect = new Konva.Rect({
                width: this.width,
                height: this.height,
                fill: COMPONENT_FILL,
                stroke: COMPONENT_BORDER,
                strokeWidth: 2,
                cornerRadius: 15
            });
            this.shape.add(this.main_rect);
            this.shape.add(new Konva.Text({
                text: this.name.toUpperCase(),
                align: alignement,
                fontSize: 10,
                fontFamily: 'Muli',
                verticalAlign: 'middle',
                width: this.width,
                height: this.height,
                fill: "#555555",
                padding: 8
            }));

            break;

        default:
            this.width = Math.round((Math.max(1, parseInt(size)) * WIDTH_UNIT + WIDTH_DEFAULT) * ((this._inputs.length > 0 && this._outputs.length > 0) ? 2 : 1.25));
            this.height = Math.max(this._inputs.length, this._outputs.length, 1) * HEIGHT_UNIT + COMPONENT_HEADER_HEIGHT;

            this.main_rect = new Konva.Rect({
                width: this.width,
                height: this.height,
                fill: COMPONENT_FILL,
                stroke: COMPONENT_BORDER,
                strokeWidth: 2,
                cornerRadius: 5
            });
            this.shape.add(this.main_rect);
            this.shape.add(new Konva.Text({
                text: this.module.toUpperCase(),
                align: 'center',
                fontSize: 10,
                fontFamily: 'Muli',
                width: this.width,
                height: this.height,
                verticalAlign: 'top',
                fill: "#aaaaaa",
                padding: 7
            }));
            this.shape.add(new Konva.Text({
                text: this.name,
                align: 'center',
                fontSize: 15,
                fontFamily: 'Muli',
                width: this.width,
                height: this.height - 15,
                y: 15,
                verticalAlign: 'top',
                padding: 7
            }));

            break;
    }

    // Register action
    this.actions = new Flow.Component.Actions(this);

    if (this._inputs.length > 0) {
        var offset = (this.height - this._inputs.length*PORT_SIZE - (size > 0 ? COMPONENT_HEADER_HEIGHT : 0)) / (this._inputs.length+1);
        for (var i in this._inputs) {
            var index = parseInt(i);
            var input = this._inputs[i];
            this.inputs[input.name] = new Flow.Port(input.name, 'input', 0, offset*(index+1) + index*PORT_SIZE+PORT_SIZE/2 + (size > 0 ? COMPONENT_HEADER_HEIGHT : 0), 'in');
            this.inputs[input.name].component = this;
            this.shape.add(this.inputs[input.name].shape);
            if (this.size > 0) {
                this.shape.add(this.inputs[input.name].text(this));
            }
        }
    }

    if (this._outputs.length > 0) {
        var offset = (this.height - (this._outputs.length)*PORT_SIZE - (size > 0 ? COMPONENT_HEADER_HEIGHT : 0)) / (this._outputs.length+1);
        for (var i in this._outputs) {
            var index = parseInt(i);
            var output = this._outputs[i];
            this.outputs[output.name] = new Flow.Port(output.name, 'output', this.width, offset*(index+1) + index*PORT_SIZE+PORT_SIZE/2 + (size > 0 ? COMPONENT_HEADER_HEIGHT : 0), 'out')
            this.outputs[output.name].component = this;
            this.shape.add(this.outputs[output.name].shape);
            if (this.size > 0) {
                this.shape.add(this.outputs[output.name].text(this));
            }
        }
    }

    /**
     * Update all the port id
     */
    this.update_port_id = function () {
        for (var i in this.inputs) {
            this.inputs[i].shape.id(this.inputs[i].id());
        }
        for (var i in this.outputs) {
            this.outputs[i].shape.id(this.outputs[i].id());
        }
    }
}

Flow.Component.Actions = function (component) {

    this.component = component;
    this.buttons = {};

    this.shape = new Konva.Group({
        y: -ACTIONS_BUTTON_SIZE*1.1,
        x: this.component.width/2 - Object.keys(ACTIONS).length*(ACTIONS_BUTTON_SIZE+ACTIONS_BUTTON_SPACING)/2,
        name: 'component-actions',
        visible: false
    });

    var acc = 0;
    for (var action in ACTIONS) {
        var button = new Flow.Component.Actions.Button(action, ACTIONS[action], acc*(ACTIONS_BUTTON_SIZE+ACTIONS_BUTTON_SPACING));
        this.buttons[action] = button;
        this.shape.add(button.shape);
        acc += 1;
    }

    // Add action to component
    this.component.shape.add(this.shape);

    this.show = function() {
        this.shape.show();
    }

    this.hide = function() {
        this.shape.hide();
    }
}

Flow.Component.Actions.Button = function (name, icon, x) {

    this.callback = null;

    this.shape = new Konva.Group({
        x: x,
        name: name
    });
    this.background = new Konva.Circle({
        x: ACTIONS_BUTTON_SIZE/2,
        y: ACTIONS_BUTTON_SIZE/2,
        radius: ACTIONS_BUTTON_SIZE/2,
        name: name,
        fill: 'white'
    });
    this.icon = new Konva.Text({
        text: icon, // Clone
        align: 'center',
        fontSize: 15,
        fontFamily: '"Font Awesome 5 Pro"',
        width: ACTIONS_BUTTON_SIZE,
        height: ACTIONS_BUTTON_SIZE,
        verticalAlign: 'middle'
    })

    this.shape.add(this.background);
    this.shape.add(this.icon);

    this.shape.on('mouseenter', function(e) {
        this.background.fill(PRIMARY_COLOR);
        this.icon.fill('white');
        this.shape.draw();
        this.shape.getStage().container().style.cursor = "pointer";
    }.bind(this));
    this.shape.on('mouseleave', function(e) {
        this.background.fill('white');
        this.icon.fill('black');
        this.shape.getLayer().draw();
        this.shape.getStage().container().style.cursor = "default";
    }.bind(this));
    this.shape.on('click tap', function(e) {
        if (this.callback) {
            this.callback(e)
        }
    }.bind(this))
}

Flow.Port = function(name, type, x, y, direction) {

    this.name = name;
    this.component = null;
    this.type = type; // Type of data
    this.x = x;
    this.y = y;
    this.direction = direction;

    this.shape = new Konva.Circle({
        x: this.x + (this.direction === 'in' ? 10 : -10 ),
        y: this.y,
        radius: PORT_SIZE,
        name: 'port-'+this.direction,
        fill: PORT_COLOR
    })

    this.text = function(component) {
        var offset = 20;
        return new Konva.Text({
            y: this.y-PORT_SIZE,
            x: this.direction === 'in' ? offset : 0 ,
            text: this.name.toLowerCase(),
            align: this.direction === 'in' ? 'left' : 'right' ,
            fontSize: 12,
            fontFamily: 'Muli',
            width: component.width - offset,
            height: 2*PORT_SIZE,
            verticalAlign: 'middle'
        })
    }

    this.hitbox = function () {
        return new Konva.Circle({
            id: this.id(),
            x: this.shape.absolutePosition().x,
            y: this.shape.absolutePosition().y,
            radius: PORT_SIZE*1.5,
            name: 'port-'+this.direction,
            fill: 'transparent'
        })
    }

    /**
     * Select the port
     */
    this.select = function () {
        this.shape.fill(SELECTION_COLOR);
    }

    /**
     * Unselect the port
     */
    this.unselect = function () {
        this.shape.fill(PORT_COLOR);
    }

    /**
     * Return port id (node_id:port_name)
     * @returns {string}
     */
    this.id = function () {
        return this.component.node.id+":"+this.name;
    }

}

Flow.Link = function (source, targets) {
    this.source = source;
    this.targets = targets;
    this.target_mapping = {};
    this.shapes = {}
    this.selectedLinkId = null;

    /**
     * Compute the path from source to the target
     * @param target
     * @returns {*[]}
     */
    this.compute_points = function(target) {
        var sourcePosition = this.source.shape.absolutePosition();
        var targetPosition = target.shape.absolutePosition();

        if (targetPosition.x >= sourcePosition.x - (sourcePosition.x-targetPosition.x)/2) {
            return [
                sourcePosition.x, sourcePosition.y,
                sourcePosition.x + Math.min(150, Math.max(50, Math.abs(targetPosition.x-sourcePosition.x)/2)), sourcePosition.y,
                targetPosition.x - Math.min(150, Math.max(50, Math.abs(targetPosition.x-sourcePosition.x)/2)), targetPosition.y,
                targetPosition.x, targetPosition.y
            ];
        } else if (Math.abs(targetPosition.y - sourcePosition.y) < HEIGHT_UNIT) {
            return [
                sourcePosition.x, sourcePosition.y,
                sourcePosition.x + Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), sourcePosition.y-100,
                targetPosition.x - Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), targetPosition.y-100,
                targetPosition.x, targetPosition.y
            ];
        } else if (targetPosition.y >= sourcePosition.y) {
            return [
                sourcePosition.x, sourcePosition.y,
                sourcePosition.x + Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), sourcePosition.y+Math.min(100, Math.max(75, Math.abs(targetPosition.y-sourcePosition.y)/2)),
                targetPosition.x - Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), targetPosition.y-Math.min(100, Math.max(75, Math.abs(targetPosition.y-sourcePosition.y)/2)),
                targetPosition.x, targetPosition.y
            ];
        } else {
            return [
                sourcePosition.x, sourcePosition.y,
                sourcePosition.x + Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), sourcePosition.y-Math.min(100, Math.max(75, Math.abs(targetPosition.y-sourcePosition.y)/2)),
                targetPosition.x - Math.min(150, Math.max(200, Math.abs(targetPosition.x-sourcePosition.x)/2)), targetPosition.y+Math.min(100, Math.max(75, Math.abs(targetPosition.y-sourcePosition.y)/2)),
                targetPosition.x, targetPosition.y
            ];
        }


    }

    /**
     * Load the link and create all paths
     */
    this.load = function () {
        this.target_mapping = {};
        for (var i in this.targets) {
            var target = this.targets[i];
            var id = this.source.id() + LINK_ID_SEPARATOR + target.id();
            this.target_mapping[id] = i;
            this.shapes[id] = new Konva.Line({
                points: this.compute_points(target),
                stroke: LINK_COLOR,
                name: 'link',
                id: id,
                strokeWidth: LINK_WIDTH,
                lineCap: 'round',
                lineJoin: 'round',
                bezier: true
            });
        }
    }

    this.load();

    /**
     * Reload all the lines
     */
    this.reload = function() {
        this.remove();
        this.load();
    }

    /**
     * Show selection indicator
     */
    this.select = function (id) {
        this.selectedLinkId = id;
        // Select the source port
        this.source.select();
        // Select the target port
        this.targets[this.target_mapping[id]].select();
        // Change line color
        this.shapes[this.selectedLinkId].stroke(SELECTION_COLOR);
    }

    /**
     * Hide selection indicator
     */
    this.unselect = function () {
        if (this.selectedLinkId) {
            // Select the source port
            this.source.unselect();
            // Unselect the target port
            if (this.targets[this.target_mapping[this.selectedLinkId]]) {
                this.targets[this.target_mapping[this.selectedLinkId]].unselect();
            }
            // Change the link color
            if (this.shapes[this.selectedLinkId]) {
                this.shapes[this.selectedLinkId].stroke(LINK_COLOR)
            }
            this.selectedLinkId = null;
        }
    }

    /**
     * Remove the link
     */
    this.remove = function () {
        for (var i in this.shapes) {
            this.shapes[i].destroy();
            delete this.shapes[i];
        }
    }

    /**
     * Destroy all shapes
     */
    this.destroy = function () {
        for (var i in this.shapes) {
            this.shapes[i].destroy();
            delete this.shapes[i];
        }
    }

    /**
     * Export the link
     * @returns {{source: *, targets: []}}
     */
    this.export = function () {
        var targets = [];
        for (var j in this.targets) {
            targets.push(this.targets[j].id());
        }
        return {
            'source': this.source.id(),
            'targets': targets
        };
    }

    /**
     * Remove all the sublink for the given node_id
     * @param node_id
     */
    this.remove_target_to_node = function (node_id) {
        for (var i in this.targets) {
            if (i.startsWith(node_id+":")) {
                this.remove_target(i);
            }
        }
    }

    /**
     * Remove the sublink to the target with given port_id
     * @param port_id
     */
    this.remove_target = function(port_id) {
        // Unselect before deleting to update the UI
        this.unselect();
        if (this.targets[port_id]) {
            // Delete the port from target list
            delete this.targets[port_id];
            for (var j in this.target_mapping) {
                if (this.target_mapping[j] === port_id) {
                    // Use the mapping to get the shape and destroy it
                    this.shapes[j].destroy();
                    // Clean the shapes array
                    delete this.shapes[j];
                    // Delete from the mapping
                    delete this.target_mapping[j];
                }
            }
        }
    }

    /**
     * Add the given port to targets
     * @param port
     */
    this.add_target = function (port) {
        if (this.targets[port.id()]) {
            throw "Ce lien existe déjà";
        }
        this.targets[port.id()] = port;
        this.reload();
    }

    /**
     * Update all the lines coordinates
     */
    this.update_paths = function () {
        for (var i in this.shapes) {
            var line = this.shapes[i];
            line.points(this.compute_points(this.targets[this.target_mapping[i]]))
        }
    }
}