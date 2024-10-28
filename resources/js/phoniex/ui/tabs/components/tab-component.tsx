import React from "react";
import { Tab } from "@headlessui/react";
import clsx from "clsx";
import TabComponentProps from "../types/components/tab-component-props";

export default class TabComponent extends React.Component<TabComponentProps> {
    handleClick = () => {
        const { index, on_click } = this.props;
        on_click(index);
    };

    render() {
        const { selected, icon, children } = this.props;

        return (
            <Tab
                onClick={this.handleClick}
                className={clsx(
                    "flex items-center gap-4 rounded-lg py-3 px-4 text-base font-semibold md:w-full",
                    selected
                        ? "bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200"
                        : "text-gray-900 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors",
                )}
                aria-selected={selected}
            >
                <i className={`${icon} text-2xl`} />
                <span className="hidden md:inline">{children}</span>
            </Tab>
        );
    }
}
