import React from "react";
import {Tab} from "@headlessui/react";
import TabPanelProperties from "../../../lib/ui/types/tabs/tab-panel-properties";

export default class TabPanel extends React.Component<TabPanelProperties, {}> {

    constructor(props: TabPanelProperties) {
        super(props);
    }

    render() {
        return(
            <Tab.Panel
                key={this.props.key}
            >
                {this.props.children}
            </Tab.Panel>
        )
    }
}
