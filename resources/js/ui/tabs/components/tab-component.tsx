import React, { ReactNode } from "react";
import { Tab } from "@headlessui/react";
import clsx from "clsx";
import TabComponentProps from "../types/components/tab-component-props";
import { useHandleTabComponentClick } from "./hooks/use-handle-tab-component-click";

const TabComponent = (props: TabComponentProps): ReactNode => {
    const onClick = useHandleTabComponentClick(props);

    return (
        <Tab
            onClick={onClick}
            className={clsx(
                "flex items-center gap-4 rounded-lg py-3 px-4 text-base font-semibold md:w-full",
                props.selected
                    ? "bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200"
                    : "text-gray-900 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors",
            )}
            aria-selected={props.selected}
        >
            <i className={`${props.icon} text-2xl`} />
            <span className="hidden md:inline">{props.children}</span>
        </Tab>
    );
};

export default TabComponent;
