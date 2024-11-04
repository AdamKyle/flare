import React, { ReactNode } from "react";
import { TabList } from "@headlessui/react";
import TabComponent from "./tab-component";
import { v4 as uuidv4 } from "uuid";
import TabListComponentProps from "../types/components/tab-list-component-props";

const TabListComponent = (props: TabListComponentProps): ReactNode => {
    return (
        <TabList className={props.additional_css}>
            {props.tabs.map((tab, index) => (
                <TabComponent
                    key={uuidv4()}
                    index={index}
                    selected={index === props.selected_index}
                    icon={props.icons[index]}
                    on_click={props.on_tab_change}
                >
                    {tab}
                </TabComponent>
            ))}
        </TabList>
    );
};

export default TabListComponent;
