export var viewPortWatcher = function (component) {
    component.setState(
        {
            view_port:
                window.innerWidth || document.documentElement.clientWidth,
        },
        function () {
            window.addEventListener("resize", function () {
                component.setState({
                    view_port:
                        window.innerWidth ||
                        document.documentElement.clientWidth,
                });
            });
        },
    );
};
//# sourceMappingURL=view-port-watcher.js.map
