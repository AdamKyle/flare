import React from "react";
import TabsProps from "./types/tabs-props";
import TabsState from "./types/tabs-state";
import TabGroupComponent from "./components/tab-group-component";
import TabListComponent from "./components/tab-list-component";
import TabPanelsComponent from "./components/tab-panel-component";

export default class Tabs extends React.Component<TabsProps, TabsState> {
    constructor(props: TabsProps) {
        super(props);
        this.state = {
            selected_index: 0,
        };
    }

    handleChange = (index: number) => {
        this.setState({ selected_index: index });
        const { onChange } = this.props;
        if (onChange) onChange(index);
    };

    render() {
        const { tabs, icons } = this.props;
        const { selected_index } = this.state;

        return (
            <div className="flex h-screen w-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-50">
                <div className="hidden md:flex flex-col w-64 border-r border-gray-200 dark:border-gray-700 top-0">
                    <TabGroupComponent
                        selected_index={selected_index}
                        handle_change={this.handleChange.bind(this)}
                    >
                        <TabListComponent
                            tabs={tabs}
                            selected_index={selected_index}
                            icons={icons}
                            on_tab_change={this.handleChange}
                            additional_css="" // No additional CSS for desktop view
                        />
                    </TabGroupComponent>
                </div>
                <div className="w-full flex-1 flex flex-col">
                    <TabGroupComponent
                        selected_index={selected_index}
                        handle_change={this.handleChange.bind(this)}
                    >
                        <TabListComponent
                            tabs={tabs}
                            selected_index={selected_index}
                            icons={icons}
                            on_tab_change={this.handleChange}
                            additional_css="md:hidden flex justify-center gap-6 px-4 py-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-gray-50 dark:bg-gray-900" // CSS for mobile view
                        />
                        <TabPanelsComponent selected_index={selected_index}>
                            {this.props.children}
                        </TabPanelsComponent>
                    </TabGroupComponent>
                </div>
            </div>
        );
    }
}
