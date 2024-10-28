import React from "react";
import { TabPanels, TabPanel } from "@headlessui/react";
import clsx from "clsx";
import TabPanelsComponentProps from "../types/components/tab-panel-component-props";

export default class TabPanelsComponent extends React.Component<TabPanelsComponentProps> {
    render() {
        const { children, selected_index } = this.props;

        return (
            <TabPanels className="flex-1 p-4">
                {React.Children.map(children, (child, index) => (
                    <TabPanel
                        key={index}
                        className={clsx(
                            "rounded-xl p-4",
                            index === selected_index ? "" : "hidden",
                        )}
                    >
                        {child}
                    </TabPanel>
                ))}
            </TabPanels>
        );
    }
}
