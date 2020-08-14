function Library() {

    this.error = false;
    this.loading = false;
    this.loaded = false;
    this.components = {};
    this.modules = {};

    this.load = function (callback) {
        this.loading = true;
        this.loaded = false;
        this.error = false;
        this.components = {};
        this.modules = {};
        $.GET('/editor/library/components')
            .success(function (response) {
                var rep = JSON.parse(response.text);
                if (rep.success) {
                    components = rep.payload;
                    for (var i in components) {
                        this.components[components[i].id] = components[i];
                        var part = this.components[components[i].id].id.split(".");
                        this.components[components[i].id].name = part.pop();
                        this.components[components[i].id].module = part.join(".");
                        if (!this.modules[this.components[components[i].id].module]) {
                            this.modules[this.components[components[i].id].module] = [];
                        }
                        this.modules[this.components[components[i].id].module].push(this.components[components[i].id]);
                    }
                    this.loading = false;
                    this.loaded = true;
                    if (callback) {
                        callback();
                    }
                } else {
                    alert("Une erreur est survenue lors de la récupération de la biliothèque des composants");
                    console.log(response);
                }
            }.bind(this))
            .error(function (response) {
                this.loading = false;
                this.error = true;
                alert("Une erreur est survenue lors de la récupération de la biliothèque des composants");
                console.log(response);
            }.bind(this))
            .send()
        ;
    }

    /**
     * Get an instance of the given component id
     * @param component_id
     * @returns {Flow.Component}
     */
    this.get = function (component_id) {
        if (this.components[component_id]) {
            var componentData = this.components[component_id];
            return new Flow.Component(
                componentData.id,
                componentData.inputs,
                componentData.outputs,
                componentData.size,
                componentData.settings
            );
        }
        throw "Aucun composant correspondant pour '"+component_id+"'";
    }

    /**
     * Search a component with its id containing the given value
     * @param value
     * @returns {[]}
     */
    this.search = function(value) {
        var components = [];
        console.log(this.components);
        for (var i in this.components) {
            if (this.components[i].name.hasSubString(value)) {
                components.unshift(this.components[i])
            } else if (this.components[i].module.hasSubString(value)) {
                components.push(this.components[i]);
            } else if (i.hasSubString(value)) {
                components.push(this.components[i]);
            }
        }
        return components;
    }

}