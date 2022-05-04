import React, {Fragment} from "react";
import {Tab} from "@headlessui/react";
import {classNames} from '../../../lib/ui/css-class-helper';
import TabProperties from "../../../lib/ui/types/tabs/tabs-properties";
import clsx from "clsx";
import PopOverContainer from "../popover/pop-over-container";

export default class Tabs extends React.Component<TabProperties, {}> {

    constructor(props: TabProperties) {
        super(props);
    }

    renderIcon(tab: {[key: string]: string | boolean, key: string, name: string}, selected: boolean) {
        if (typeof this.props.icon_key !== 'undefined' && !selected) {
            if (tab[this.props.icon_key]) {
                return <span>{tab.name} <i className="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i></span>
            }
        } else if (selected) {
            if (typeof this.props.icon_key !== 'undefined' && typeof this.props.when_tab_changes !== 'undefined') {
                if (tab[this.props.icon_key]) {
                    this.props.when_tab_changes(tab.key);
                }
            }
        }

        return tab.name;
    }

    renderEachTab() {
        return this.props.tabs.map((tab) => {
            return (
                <Tab key={tab.key} as={Fragment}>
                    {({selected}) =>
                        <button type='button' className={clsx(
                            'w-full py-2.5 text-sm font-medium focus:outline-none text-slate-800 dark:text-slate-200 text-center',
                            {'border-b-2 border-blue-500 dark:border-blue-400': selected},
                            {'hover:border-blue-500 hover:border-b-2 dark:hover:border-blue-400': !selected}
                        )}>
                            {this.renderIcon(tab, selected)}
                        </button>
                    }
                </Tab>
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
