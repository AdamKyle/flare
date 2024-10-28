import React, { ReactNode } from "react";
import { TabList } from "@headlessui/react";
import TabComponent from "./tab-component";
import { v4 as uuidv4 } from "uuid";
import TabListComponentProps from "../types/components/tab-list-component-props";

export default class TabListComponent extends React.Component<TabListComponentProps> {
    render() {
        const { tabs, selected_index, icons, on_tab_change, additional_css } =
            this.props;

        return (
            <TabList className={additional_css}>
                {tabs.map((tab, index) => (
                    <TabComponent
                        key={uuidv4()}
                        index={index}
                        selected={index === selected_index}
                        icon={icons[index]}
                        on_click={on_tab_change}
                    >
                        {tab}
                    </TabComponent>
                ))}
            </TabList>
        );
    }
}
