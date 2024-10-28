import React from "react";
import { TabGroup } from "@headlessui/react";
import TabGroupComponentProps from "../types/components/tab-group-component-props";

export default class TabGroupComponent extends React.Component<TabGroupComponentProps> {
    constructor(props: TabGroupComponentProps) {
        super(props);
    }

    render() {
        return (
            <TabGroup
                selectedIndex={this.props.selected_index}
                onChange={this.props.handle_change}
            >
                {this.props.children}
            </TabGroup>
        );
    }
}
