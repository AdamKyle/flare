import React, { ReactNode } from "react";
import { TabPanels, TabPanel } from "@headlessui/react";
import clsx from "clsx";
import TabPanelsComponentProps from "../types/components/tab-panel-component-props";

const TabPanelComponent = (props: TabPanelsComponentProps): ReactNode => {
    return (
        <TabPanels className="flex-1 pt-4 md:pl-4 md:pr-4 md:pt-0 md:pb-4">
            {React.Children.map(props.children, (child, index) => (
                <TabPanel
                    key={index}
                    className={clsx(
                        "rounded-xl pl-4 pr-4 pb-4",
                        index === props.selected_index ? "" : "hidden",
                    )}
                >
                    {child}
                </TabPanel>
            ))}
        </TabPanels>
    );
};

export default TabPanelComponent;
