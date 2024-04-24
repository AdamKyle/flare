export const viewPortWatcher = (component: any) => {
    component.setState(
        {
            view_port:
                window.innerWidth || document.documentElement.clientWidth,
        },
        () => {
            window.addEventListener("resize", () => {
                component.setState({
                    view_port:
                        window.innerWidth ||
                        document.documentElement.clientWidth,
                });
            });
        },
    );
};
