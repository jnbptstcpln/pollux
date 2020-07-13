function FlowEditor(on_loaded) {

    this.flow = null;
    this.selectedNode = null;
    this.selectedLink = null;

    this.mode = "selection";

    this.sourcePort = null;
    this.linkPath = null;

    this.states_undo = [];
    this.states_redo = [];

    // Load component librairies
    this.library = new Library();
    this.library.load(function () {
        // Add module to the leftbar
        var menu = $('#editor .leftbar ul.menu');

        // Get module names
        var modules = Object.keys(this.library.modules);
        // Sort
        modules.sort();
        // Iterate
        for (var i in modules) {
            module = modules[i];
            var li = $.DOM.create("li");
            li.append("<a><span class=\"icon\"><i class=\"fas fa-box-open\"></i></span><span class=\"menu-label\">{0}</span></a>".format(module.toUpperCase()))
            li.append("<div style='--height: 350px'><div class='p-10'></div></div>")
            var ul = $.DOM.create("ul").addClass("component-list");
            for (var i in this.library.modules[module]) {
                var component = this.library.modules[module][i];
                var _li = $.DOM.create("li");
                var span = $.DOM.create("span");
                span.html("<div><span class='module'>{1}</span><span class='name'>{2}</span></div><a href='{0}' class='info'><i class='far fa-info-circle'></i></a>".format(component.id, component.module, component.name));
                span.attr('draggable', true);
                span.attr('data-id', component.id);
                span.on('dragstart', function (event) {
                    event.dataTransfer.setData("component", event.element.attr('data-id'));
                })
                _li.append(span);
                ul.append(_li);
            }
            li.find("div > div.p-10").append(ul);
            menu.append(li);
        }
    }.bind(this));

    /**
     * SETUP KONVA
     */
    this.container = $('#canvas');
    this.stage = new Konva.Stage({
        container: 'canvas',   // id of container <div>
        width: this.container.width(),
        height: this.container.height()
    });
    this.nodes_layer = new Konva.Layer();
    this.stage.add(this.nodes_layer);
    this.links_layer = new Konva.Layer();
    this.stage.add(this.links_layer);
    this.port_layer = new Konva.Layer();
    this.stage.add(this.port_layer);

    /**
     * ADD SOME LISTENER ON STAGE AND LAYER
     */
    this.stage.on('click tap', function(event) {
        if (event.target === this.stage) {
            if (this.mode === "selection") {
                this.clear_selection();
            }
            if (this.mode === "link") {
                this.reset_link_mode();
            }
        }
    }.bind(this));
    this.port_layer.on('click tap', function(event) {
        // User click an output port
        if (event.target.hasName("port-out")) {
            // Change the mode
            this.change_mode("link");
            var portData = this.parse_port_id(event.target.id());
            // Set the source port
            this.sourcePort = this.flow.nodes[portData.node].get_output(portData.port)
            this.sourcePort.select();
            var sourcePosition = this.sourcePort.shape.absolutePosition();
            this.linkPath = new Konva.Line({
                points: [
                    sourcePosition.x, sourcePosition.y,
                    sourcePosition.x, sourcePosition.y
                ],
                stroke: SELECTION_COLOR,
                name: 'link',
                strokeWidth: LINK_WIDTH,
                lineCap: 'round',
                lineJoin: 'round'
            });
            this.links_layer.add(this.linkPath);
            this.draw();
        }

        // User click an input port
        if (event.target.hasName("port-in")) {
            // If we are in "link mode"
            if (this.mode === "link") {
                var portData = this.parse_port_id(event.target.id());
                // Set the source port
                var target = this.flow.nodes[portData.node].get_input(portData.port);
                this.add_link(this.sourcePort, target);
                this.reset_link_mode();
                this.draw();
            }
        }
    }.bind(this))
    this.stage.on('mousemove', function(event) {
        // Link edition
        if (this.mode === "link") {
            // Keep the crosshair cursor
            this.stage.container().style.cursor = 'crosshair';
            var sourcePosition = this.sourcePort.shape.absolutePosition();
            this.linkPath.points([
                sourcePosition.x, sourcePosition.y,
                event.evt.layerX, event.evt.layerY
            ])
            this.draw();
        }
    }.bind(this));
    this.nodes_layer.on('dragmove', function(event) {
        if (this.mode === "move") {
            // To keep the drag-cache in place, we reset its absolute position to 0
            this.nodes_layer.findOne("#drag-cache").absolutePosition({x: 0, y: 0});
            // We update to keep links in place
            this.update();
        }
    }.bind(this));

    // Component library
    this.components = {
        'event.acme.HelloWorld': function() {
            return new Flow.Component("event.acme.HelloWorld", [], [{'name': 'text', 'type': 'string'}], 2)
        },
        'system.Print': function() {
            return new Flow.Component("system.Print", [{'name': 'value', 'type': 'string'}], [{'name': 'value', 'type': 'string'}], 2)
        },
        'math.Addition': function() {
            return new Flow.Component("math.Addition", [{'name': 'a', 'type': 'float'}, {'name': 'b', 'type': 'float'}], [{'name': 'result', 'type': 'float'}], 2);
        },
    }
    /**
     * Get the component corresponding to the given id
     * @param id
     * @returns Component
     */
    this.getComponent = function (id) {
        if (!this.components[id]) {
            throw "Aucun composant ne correspond à '"+id+"'";
        }
        return this.components[id]();
    }

    /**
     * Draw on the layer
     */
    this.draw = function () {
        this.nodes_layer.draw()
        this.links_layer.draw()
        this.port_layer.draw()
    }

    /**
     * Update element position
     */
    this.update = function () {
        for (var i in this.flow.links) {
            var link = this.flow.links[i];
            link.update_paths();
        }

        // Reset the port_layer if we are not in "move mode"
        if (this.mode !== 'move') {
            this.port_layer.destroyChildren();
            for (var i in this.flow.nodes) {
                var node = this.flow.nodes[i];
                for (var j in node.component.inputs) {
                    // Add the hitbox to the port_layer
                    this.port_layer.add(node.component.inputs[j].hitbox());
                }
                for (var j in node.component.outputs) {
                    // Get the port hitbox
                    var hitbox = node.component.outputs[j].hitbox();
                    // Bind to listener to customize the crosshair on hover
                    hitbox.on('mouseenter', function (event) {
                        if (this.mode === "selection") {
                            this.stage.container().style.cursor = 'crosshair';
                        }
                    }.bind(this));
                    hitbox.on('mouseleave', function (event) {
                        if (this.mode === "selection") {
                            this.stage.container().style.cursor = 'default';
                        }
                    }.bind(this));
                    // Add the hitbox to the port_layer
                    this.port_layer.add(hitbox);
                }
            }
        }
        this.draw();
    }

    /**
     * Load a new Flow object inside the editor and render it
     * @param flow
     */
    this.load = function (flow) {
        this.unload();
        this.flow = flow;
        for (var i in this.flow.nodes) {
            this.load_node(this.flow.nodes[i])
        }
        for (var i in this.flow.links) {
            this.load_link(this.flow.links[i])
        }
        this.update();
    }

    /**
     * Load the given node inside the layer
     * @param node
     */
    this.load_node = function(node) {
        this.nodes_layer.add(node.shape);
        node.shape.on('dragmove', this.onNodeMove.bind(this, node.shape));
        node.shape.on('click tap', this.onNodeSelected.bind(this, node.shape));

        // Add listener to node's buttons
        node.set_action('clone', function(event) {
            // cancel event propagation to prevent other click listener to execute (permit to select the new node)
            event.cancelBubble = true;
            var newNode = this.add_node(node.component.id, node.shape.x()+node.component.width/2+30, node.shape.y()+node.component.height/2+30);
            // Select the new node
            this.onNodeSelected(newNode.shape)
        }.bind(this))
        node.set_action('remove', function(event) {
            // cancel event propagation to prevent other click listener to execute
            event.cancelBubble = true;
            this.remove_node(node.id);
        }.bind(this))
        node.set_action('infos', function(event) {
            // cancel event propagation to prevent other click listener to execute
            event.cancelBubble = true;
            alert('La documentation sera bientôt accessible...');
            // TODO: Implement documentation access
        }.bind(this))

        this.update();
    }

    /**
     * Load the given link inside the layer
     * @param link
     */
    this.load_link = function(link) {
        for (var j in link.shapes) {
            this.links_layer.add(link.shapes[j]);
            link.shapes[j].on('click tap', this.onLinkSelected.bind(this, link.shapes[j]))
        }
        this.draw();
    }

    /**
     * Unload the current Flow object and clear the editor
     */
    this.unload = function () {
        if (this.flow) {
            this.nodes_layer.destroyChildren();
            this.links_layer.destroyChildren();
            this.port_layer.destroyChildren();
            this.flow = null;
            this.draw();
        }
    }

    /**
     * Change the current editing mode
     * @param mode
     */
    this.change_mode = function (mode) {
        this.clear_selection();
        this.mode = mode;

        switch (this.mode) {
            case "selection":
                this.stage.container().style.cursor = 'default';
                break;
            case "link":
                this.stage.container().style.cursor = 'crosshair';
                break;
            case "move":
                // Disable dragging on nodes
                this.nodes_layer.getChildren(function (node) {
                    return node.hasName('node');
                }).each(function (node) {
                    node.draggable(false);
                })
                // Change the cursor
                this.stage.container().style.cursor = 'move';
                $('#toggle-move').addClass("active");
                // Enable dragging on the node_layer
                this.nodes_layer.draggable(true);
                this.nodes_layer.add(new Konva.Rect({
                    id: "drag-cache",
                    width: this.container.width()*1/MIN_SCALE,
                    height: this.container.height()*1/MIN_SCALE,
                    fill: "transparent"
                }));
                this.draw();
                break;
        }
    }

    /**
     * Reset the "link mode"
     */
    this.reset_link_mode = function () {
        this.sourcePort.unselect();
        this.sourcePort = null;
        if (this.linkPath) {
            this.linkPath.destroy();
            this.linkPath = null;
        }
        this.change_mode("selection");
    }

    /**
     * Reset the "link mode"
     */
    this.reset_move_mode = function () {
        // Disable dragging on the node_layer
        this.nodes_layer.draggable(false);
        // Enable dragging on nodes
        this.nodes_layer.findOne("#drag-cache").destroy();
        this.nodes_layer.getChildren(function (node) {
            return node.hasName('node');
        }).each(function (node) {
            node.draggable(true);
        })
        $('#toggle-move').removeClass("active");
        this.change_mode("selection");
        this.update();
    }

    /**
     * Clear the currently selected item
     */
    this.clear_selection = function () {
        if (this.selectedNode) {
            this.selectedNode.unselect()
            this.selectedNode = null;
        }
        if (this.selectedLink) {
            this.selectedLink.unselect()
            this.selectedLink = null;
        }
        this.draw();
    }

    /**
     * Save the current state
     */
    this.push_state = function () {
        this.states_redo = [];
        this.states_undo.push(this.flow.export());
    }

    /**
     * Undo by going to the last saved state
     */
    this.undo = function () {
        if (this.states_undo.length > 0) {
            this.states_redo.push(this.flow.export());
            var state = this.states_undo.pop();
            this.open(state);
        }
    }

    /**
     * Redo by going to the last saved "redo" state
     */
    this.redo = function () {
        if (this.states_redo.length > 0) {
            this.states_undo.push(this.flow.export());
            var state = this.states_redo.pop();
            this.open(state);
        }
    }

    /**
     * ===== MANAGE ENTITIES =====
     */

    this.add_node = function (component_id, x, y) {
        // Set default position
        x = x || 400;
        y = y || 300;
        // Save the current state
        this.push_state();
        // Generate a unique node_id
        var node_id = this.flow.generate_node_id();
        // Create component
        var component = this.getComponent(component_id);
        // Create the new node, ajust the position to center the node on x and y
        this.flow.nodes[node_id] = new Flow.Node(node_id, component, {}, x-component.width/2, y-component.height/2);
        // Load the node inside the layer
        this.load_node(this.flow.nodes[node_id]);
        // Return the node instance
        return this.flow.nodes[node_id];
    }

    /**
     * Remove the given node and all connected links
     * @param node_id
     */
    this.remove_node = function (node_id) {
        // Save the current state
        this.push_state();
        // Get the node
        var node = this.flow.nodes[node_id];
        // Remove it from the layer
        node.remove();
        // Delete it from the nodes array
        delete this.flow.nodes[node_id];

        // Get all links from
        var linksFrom = this.flow.get_links_from_node(node_id);
        for (var i in linksFrom) {
            var link = linksFrom[i];
            // Remove it from the layer
            link.remove();
            // Remove it from the links
            delete this.flow.links[i];
        }

        // Get all links from
        var linksTo = this.flow.get_links_to_node(node_id);
        for (var i in linksTo) {
            var link = linksTo[i];
            link.remove_target_to_node(node_id);
        }

        this.draw();
    }

    /**
     * Add a new link
     * @param source
     * @param target
     */
    this.add_link = function (source, target) {
        // Performing some check
        if (source.component.node === target.component.node) {
            alert("Impossible de connecter un élément à lui même.");
            return;
        }
        if (this.flow.is_port_connected(target)) {
            alert("Impossible de connecter plusieurs sortie à une seule entrée.");
            return;
        }

        // Save the current state
        this.push_state();

        if (this.flow.links[source.id()]) {
            // If a link instance already exist we just add the target
            try {
                this.flow.links[source.id()].add_target(target);
                this.load_link(this.flow.links[source.id()]);
            } catch (e) {}
        } else {
            // Else we create a new link instance with the given target
            var targets = {};
            targets[target.id()] = target;
            this.flow.links[source.id()] = new Flow.Link(source, targets);
            this.load_link(this.flow.links[source.id()]);
        }
    }

    /**
     * Remove the given sublink
     * @param sublink_id
     */
    this.remove_sublink = function (sublink_id) {
        // Save the current state
        this.push_state();
        // Get the source's port_id and target's port_id from the sublink's id
        var part = sublink_id.split(LINK_ID_SEPARATOR);
        var link = this.flow.links[part[0]];
        // Remove the sublink to the target
        link.remove_target(part[1]);
        this.draw();
    }

    /**
     * ===== UI LISTENER =====
     */

    $(document).on('click', function (e) {
        var target = $(e.target);
        if (!target.parent('#canvas').exists()) {
            this.clear_selection();
        }
    }.bind(this));

    $(document).on('click', function (e) {
        var target = $(e.target);
        if (!target.parent('#canvas').exists()) {
            this.clear_selection();
        }
    }.bind(this));

    $(document).on('keydown', function (e) {
        if (e.code === "Backspace" || e.code === "Delete") {
            this.onDeleteSelected();
        }
        if (e.code === "Escape") {
            if (this.mode === "link") {
                this.reset_link_mode();
            }
            if (this.mode === "move") {
                this.reset_move_mode();
            }
        }
    }.bind(this));

    $(window).on('resize', function (e) {
        this.stage.width(this.container.width());
        this.stage.height(this.container.width());
        this.update();
    }.bind(this));

    $('#toggle-leftbar').on('click', function(event) {
        event.preventDefault();
        Layout = $('#editor > .layout');
        if (Layout.attr('data-leftbar') !== 'show') {
            this.show_leftbar();
        } else {
            this.hide_leftbar();
        }
    }.bind(this));

    $('#toggle-rightbar').on('click', function(event) {
        event.preventDefault();
        Layout = $('#editor > .layout');
        if (Layout.attr('data-rightbar') !== 'show') {
            this.show_rightbar();
        } else {
            this.hide_rightbar();
        }
    }.bind(this));

    $(".add-node").on('click', function (event) {
        event.preventDefault();
        this.add_node(event.element.attr('data-component'));
    }.bind(this));

    $("#editor > .layout > .leftbar ul.menu").on('click', 'li > a', function (event) {
        event.preventDefault();
        this.show_leftbar();
        var li = event.element.parent("li");
        if (li.hasClass("active")) {
            li.removeClass("active");
        } else {
            $("#editor > .layout > .leftbar ul.menu > li.active").removeClass("active");
            li.addClass("active");
        }
    }.bind(this));

    $("#editor > .layout > .leftbar").on('click', 'ul.component-list li a.info', function (event) {
        event.preventDefault();
        alert("La documentation sera bientôt disponible...");
        // TODO: Implement documentation access
    });

    $("#export").on('click', function (event) {
        event.preventDefault();
        console.log(this.flow.export());
    }.bind(this));

    $("#undo").on('click', function (event) {
        event.preventDefault();
        this.undo();
    }.bind(this));

    $("#redo").on('click', function (event) {
        event.preventDefault();
        this.redo();
    }.bind(this));

    $("#zoom-in").on('click', function (event) {
        event.preventDefault();
        var oldScale = this.nodes_layer.scaleX();
        var newScale = Math.min(MAX_SCALE, 1.25*oldScale);
        this.nodes_layer.scale({ x: newScale, y: newScale });
        this.update();
    }.bind(this));

    $("#zoom-out").on('click', function (event) {
        event.preventDefault();
        var oldScale = this.nodes_layer.scaleX();
        var newScale = Math.max(MIN_SCALE, 0.75*oldScale);
        this.nodes_layer.scale({ x: newScale, y: newScale });
        this.update();
    }.bind(this));

    $("#toggle-move").on('click', function (event) {
        event.preventDefault();
        if (this.mode !== "move") {
            this.change_mode("move");
        } else {
            this.reset_move_mode();
        }
    }.bind(this));

    this.searchTimeout = null;
    $("#search-element").on('input', function (event) {
        var results = $('#search-results');
        results.html("");
        if (this.searchTimeout) { clearTimeout(this.searchTimeout); }
        var value = event.element.get('value');
        if (value.length > 0) {
            this.searchTimeout = setTimeout(function () {
                var components = this.library.search(event.element.get('value'))
                if (components.length > 0) {
                    for (var i in components) {
                        var component = components[i];
                        var li = $.DOM.create("li");
                        var span = $.DOM.create("span");
                        span.html("<div><span class='module'>{1}</span><span class='name'>{2}</span></div><a href='{0}' class='info'><i class='far fa-info-circle'></i></a>".format(component.id, component.module, component.name));
                        span.attr('draggable', true);
                        span.attr('data-id', component.id);
                        span.on('dragstart', function (event) {
                            event.dataTransfer.setData("component", event.element.attr('data-id'));
                        }.bind(this));
                        li.append(span);
                        results.append(li);
                    }
                } else {
                    results.append("<li class='no-result'>Aucun résultat</li>");
                }
            }.bind(this), 750);
        }
    }.bind(this));

    // Prevent default to authorize drop on the container
    this.container.on('dragover', function (event) {
        event.preventDefault();
    })

    this.container.on('drop', function (event) {
        event.preventDefault()

        // Reset special edit mode when drop occur
        switch (this.mode) {
            case "link":
                this.reset_link_mode();
                break;
            case "move":
                this.reset_move_mode();
                break;
        }

        var component = event.dataTransfer.getData("component");
        var offset = this.nodes_layer.position();
        var node = this.add_node(component, event.offsetX-offset.x, event.offsetY-offset.y);
        // Select the node
        this.onNodeSelected(node.shape)

    }.bind(this));

    /**
     * ===== UI =====
     */

    this.show_loading_screen = function() {
        $("body").append("<div id='loading-screen'><div class='text-center'><div><i class='far fa-2x fa-spinner fa-spin'></i></div><p>Chargement...</p></div></div>");
    }

    this.hide_loading_screen = function() {
        var loading_screen = $("body").find("#loading-screen");
        loading_screen.addClass("hide");
        setTimeout(function () {
            loading_screen.remove();
        }, 500)
    }

    this.show_leftbar = function () {
        Layout = $('#editor > .layout');
        Layout.attr('data-leftbar', 'show');
        if (window.innerWidth >= 800) {
            localStorage.setItem('leftbar', 'show');
        }
    }

    this.hide_leftbar = function () {
        Layout = $('#editor > .layout');
        Layout.attr('data-leftbar', 'hide');
        if (window.innerWidth >= 800) {
            localStorage.setItem('leftbar', 'hide');
        }
    }

    this.show_rightbar = function () {
        Layout = $('#editor > .layout');
        Layout.attr('data-rightbar', 'show');
        if (window.innerWidth >= 800) {
            localStorage.setItem('rightbar', 'show');
        }
    }

    this.hide_rightbar = function () {
        Layout = $('#editor > .layout');
        Layout.attr('data-rightbar', 'hide');
        if (window.innerWidth >= 800) {
            localStorage.setItem('rightbar', 'hide');
        }
    }

    /**
     * ===== CALLBACK =====
     */

    this.onNodeMove = function (shape, event) {
        this.update();
    }

    this.onNodeSelected = function (shape, event) {
        if (this.mode === "selection") {
            this.clear_selection();
            this.selectedNode = this.flow.nodes[shape.id()];
            this.selectedNode.select();
            this.draw();
        }
    }

    this.onLinkSelected = function (shape, event) {
        if (this.mode === "selection") {
            this.clear_selection();
            this.selectedLink = this.flow.links[shape.id().split(LINK_ID_SEPARATOR)[0]];
            this.selectedLink.select(shape.id());
            this.draw();
        }
    }

    this.onDeleteSelected = function () {
        if (this.selectedNode) {
            this.remove_node(this.selectedNode.id);
            this.selectedNode = null;
        }
        if (this.selectedLink) {
            this.remove_sublink(this.selectedLink.selectedLinkId);
            this.selectedLink.selectedLinkId = null;
            this.selectedLink = null;
        }
    }

    /**
     * Load inside the editor a new Flow object
     * @param flowData
     */
    this.open = function (flowData) {

        // Wait for the library to be loaded
        if (this.library.loading) {
            this.show_loading_screen();
            setTimeout(function () {
                this.open(flowData);
            }.bind(this), 1000);
            return;
        }

        this.hide_loading_screen();

        if (this.library.error) {
            console.error("Impossible de charger la bibliothèque des composants")
            return;
        }

        var nodes = {};
        var links = {};

        for (var i in flowData.nodes) {
            var nodeData = flowData.nodes[i];
            var x = flowData.positions[nodeData.id].x || 0;
            var y = flowData.positions[nodeData.id].y || 0;
            nodes[nodeData.id] = new Flow.Node(nodeData.id, this.library.get(nodeData.component), nodeData, x, y);
        }

        for (var i in flowData.links) {
            var linkData = flowData.links[i];
            var sourceData = this.parse_port_id(linkData.source);
            var source = nodes[sourceData.node].get_output(sourceData.port);
            var targets = {}
            for (var j in linkData.targets) {
                var targetData = this.parse_port_id(linkData.targets[j]);
                targets[linkData.targets[j]] = nodes[targetData.node].get_input(targetData.port);
            }
            links[linkData.source] = new Flow.Link(source, targets);
        }

        this.load(new Flow(nodes, links))
    }

    /**
     * Parse a port
     * @param id
     * @returns {{node: (*|string), port: (*|string)}}
     */
    this.parse_port_id = function (id) {
        var part = id.split(':');
        return {
            'node': part[0],
            'port': part[1]
        }
    }
}

function Action(redo, undo) {
    this.redo = redo;
    this.undo = undo;
    this.do = this.redo;
}