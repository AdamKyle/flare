import React from "react";
import ViewWatcherDeffinition from "./interfaces/view-watcher-definition";

/**
 * Watches for window inner width change
 *
 * @param component
 *
 * @example
 * When using this function you need to pass in a react component that has any props, but the state must
 * contain a view_port as a number.
 *
 *  ```js
 *  interface ExampleState {
 *    view_port: number;
 *  }
 *
 *  export default class YourComponentName extends React.Component<YourPropInterface, ExampleState> {}
 *  ```
 *
 *  Then in your class component - with in the `componentDidMount`, you can call: `viewWatcher(this)` and when the
 *  inner width of the window changes we will store the current width of the inner window.
 */
export const viewWatcher = <P, S extends ViewWatcherDeffinition>(
    component: React.Component<P, S>,
) => {
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
