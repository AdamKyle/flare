export const viewPortWatcher = (component: any) => {
    window.addEventListener('resize', () => {
        component.setState({
            view_port: window.innerWidth || document.documentElement.clientWidth
        });
    });
}
