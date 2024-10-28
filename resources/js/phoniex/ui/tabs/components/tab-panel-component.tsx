import React from "react";
import { TabPanels, TabPanel } from "@headlessui/react";
import clsx from "clsx";
import TabPanelsComponentProps from "../types/components/tab-panel-component-props";

export default class TabPanelsComponent extends React.Component<TabPanelsComponentProps> {
    render() {
        const { children, selected_index } = this.props;

        return (
            <TabPanels className="flex-1 pt-4 md:pl-4 md:pr-4 md:pt-0 md:pb-4">
                {React.Children.map(children, (child, index) => (
                    <TabPanel
                        key={index}
                        className={clsx(
                            "rounded-xl pl-4 pr-4 pb-4",
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
