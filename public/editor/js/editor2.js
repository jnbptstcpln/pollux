
var Editor = new FlowEditor();
var flowData = {
    "nodes": [
        {
            "id": "node1",
            "component": "api.oceane.RechercheRessource"
        },
        {
            "id": "node2",
            "component": "system.Print"
        },
        {
            "id": "node3",
            "component": "system.Exit"
        }
    ],
    "links": [
        {
            "source": "node1:ressource",
            "targets": [
                "node2:value"
            ]
        },
        {
            "source": "node2:value",
            "targets": [
                "node3:value"
            ]
        }
    ],
    "positions": {
        "node1": {
            'x': 400,
            'y': 100
        },
        "node2": {
            'x': 700,
            'y': 150
        },
        "node3": {
            'x': 1000,
            'y': 125
        }
    }
};

Editor.open(flowData)