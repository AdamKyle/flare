import React from "react";
import {Tab} from "@headlessui/react";
import {classNames} from '../../../lib/ui/css-class-helper';
import TabProperties from "../../../lib/ui/types/tabs/tabs-properties";

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
                         'focus:outline-none text-slate-800',
                         selected
                             ? 'border-b-2 border-blue-500'
                             : 'hover:border-blue-500 hover:border-b-2'
                     )}
                >{tab.name}</Tab>
            )
        });
    }

    render() {
        return(
            <Tab.Group>
                <Tab.List className="w-full md:w-1/3 grid grid-cols-3 gap-2 content-center">
                    {this.renderEachTab()}
                </Tab.List>
                <Tab.Panels className="mt-5">
                    {this.props.children}
                </Tab.Panels>
            </Tab.Group>
        )
    }
}
