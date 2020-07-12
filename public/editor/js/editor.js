
var Editor = new FlowEditor();
var flowData = {
    "nodes": [
        {
            "id": "node1",
            "component": "event.acme.HelloWorld"
        },
        {
            "id": "node2",
            "component": "system.Print"
        },
        {
            "id": "node3",
            "component": "math.Addition"
        }
    ],
    "links": [
        {
            "source": "node1:text",
            "targets": [
                "node2:value"
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
            'y': 100
        },
        "node3": {
            'x': 450,
            'y': 300
        }
    }
};

Editor.open(flowData)