import React from "react";
import {Tab} from "@headlessui/react";
import {classNames} from '../../../lib/ui/css-class-helper';
import TabProperties from "../../../lib/ui/types/tabs/tabs-properties";
import clsx from "clsx";
import PopOverContainer from "../popover/pop-over-container";

export default class Tabs extends React.Component<TabProperties, {}> {

    constructor(props: TabProperties) {
        super(props);
    }

    renderEachTab() {
        return this.props.tabs.map((tab) => {
            return (
                <Tab key={tab.key}
                     className={({selected}) => classNames(
                         'w-full py-2.5 text-sm font-medium',
                         'focus:outline-none text-slate-800 dark:text-slate-200',
                         selected
                             ? 'border-b-2 border-blue-500 dark:border-blue-400'
                             : 'hover:border-blue-500 hover:border-b-2 dark:hover:border-blue-400'
                     )}
                >{tab.name}</Tab>
            )
        });
    }

    render() {
        return(
            <Tab.Group>
                <Tab.List className={clsx("w-full grid grid-cols-4 gap-2 content-center", {'md:w-1/3': !this.props.full_width})}>
                    {this.renderEachTab()}
                </Tab.List>
                <Tab.Panels className="mt-5">
                    {this.props.children}
                </Tab.Panels>
            </Tab.Group>
        )
    }
}
