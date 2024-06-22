import React, { Fragment } from "react";
import { Tab } from "@headlessui/react";
import TabProperties from "../../../lib/ui/types/tabs/tabs-properties";
import clsx from "clsx";
import { isEqual } from "lodash";

export default class Tabs extends React.Component<TabProperties, any> {
    constructor(props: TabProperties) {
        super(props);

        this.state = {
            tabs: [],
        };
    }

    componentDidMount() {
        this.setState({
            tabs: this.props.tabs,
        });
    }

    componentDidUpdate() {
        if (!isEqual(this.state.tabs, this.props.tabs)) {
            this.setState({
                tabs: this.props.tabs,
            });
        }
    }

    renderIcon(
        tab: { [key: string]: string | boolean; key: string; name: string },
        selected: boolean,
    ) {
        if (typeof this.props.icon_key !== "undefined" && !selected) {
            if (this.props.icon_key === "has_logs") {
                if (tab[this.props.icon_key]) {
                    return (
                        <span>
                            {tab.name}{" "}
                            <i className="ra ra-scroll-unfurled text-yellow-600 dark:text-yellow-400"></i>
                        </span>
                    );
                }

                return tab.name;
            }

            if (tab[this.props.icon_key]) {
                return (
                    <span>
                        {tab.name}{" "}
                        <i className="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                    </span>
                );
            }
        } else if (selected) {
            if (
                typeof this.props.icon_key !== "undefined" &&
                typeof tab[this.props.icon_key] !== "undefined"
            ) {
                if (this.props.icon_key === "has_logs") {
                    if (tab[this.props.icon_key]) {
                        return (
                            <span>
                                {tab.name}{" "}
                                <i className="ra ra-scroll-unfurled text-yellow-600 dark:text-yellow-400"></i>
                            </span>
                        );
                    }

                    return tab.name;
                }
            }

            if (
                typeof this.props.icon_key !== "undefined" &&
                typeof this.props.when_tab_changes !== "undefined"
            ) {
                if (tab[this.props.icon_key]) {
                    this.props.when_tab_changes(tab.key);
                }
            }
        }

        return tab.name;
    }

    renderEachTab() {
        return this.state.tabs.map((tab: any) => {
            return (
                <Tab key={tab.key} as={Fragment}>
                    {({ selected }) => (
                        <button
                            type="button"
                            className={clsx(
                                "w-full py-2.5 text-sm font-medium focus:outline-none text-slate-800 dark:text-slate-200 text-center",
                                {
                                    "border-b-2 border-blue-500 dark:border-blue-400":
                                        selected,
                                },
                                {
                                    "hover:border-blue-500 hover:border-b-2 dark:hover:border-blue-400":
                                        !selected,
                                },
                            )}
                            disabled={
                                typeof this.props.disabled !== "undefined"
                                    ? this.props.disabled
                                    : false
                            }
                        >
                            {this.renderIcon(tab, selected)}
                        </button>
                    )}
                </Tab>
            );
        });
    }

    render() {
        return (
            <Tab.Group onChange={this.props.listen_for_change}>
                <Tab.List
                    className={clsx(
                        "w-full grid gap-2 content-center " +
                            this.props.additonal_css,
                        { "md:w-1/3": !this.props.full_width },
                        { "grid-cols-5": this.state.tabs.length === 5 },
                        { "grid-cols-4": this.state.tabs.length === 4 },
                        { "grid-cols-3": this.state.tabs.length === 3 },
                        { "grid-cols-2": this.state.tabs.length === 2 },
                    )}
                >
                    {this.renderEachTab()}
                </Tab.List>
                <Tab.Panels className="mt-5">{this.props.children}</Tab.Panels>
            </Tab.Group>
        );
    }
}
