import React, { ReactNode } from "react";
import { TabGroup } from "@headlessui/react";
import TabGroupComponentProps from "../types/components/tab-group-component-props";

const TabGroupComponent = (props: TabGroupComponentProps): ReactNode => {
    return (
        <TabGroup
            selectedIndex={props.selected_index}
            onChange={props.handle_change}
        >
            {props.children}
        </TabGroup>
    );
};

export default TabGroupComponent;
