import React, { ReactNode } from "react";
import TabsProps from "./types/tabs-props";
import TabGroupComponent from "./components/tab-group-component";
import TabListComponent from "./components/tab-list-component";
import TabPanelsComponent from "./components/tab-panel-component";
import useTabsState from "./hooks/use-tab-state";

const Tabs = (props: TabsProps): ReactNode => {
    const { tabState, handleChange } = useTabsState(props);

    return (
        <div className="flex h-screen w-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-50">
            <div className="hidden md:flex flex-col w-64 border-r border-gray-200 dark:border-gray-700 top-0">
                <TabGroupComponent
                    selected_index={tabState.selected_index}
                    handle_change={handleChange}
                >
                    <TabListComponent
                        tabs={props.tabs}
                        selected_index={tabState.selected_index}
                        icons={props.icons}
                        on_tab_change={handleChange}
                    />
                </TabGroupComponent>
            </div>
            <div className="w-full flex-1 flex flex-col">
                <TabGroupComponent
                    selected_index={tabState.selected_index}
                    handle_change={handleChange}
                >
                    <TabListComponent
                        tabs={props.tabs}
                        selected_index={tabState.selected_index}
                        icons={props.icons}
                        on_tab_change={handleChange}
                        additional_css="md:hidden flex justify-center gap-6 px-4 py-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-gray-50 dark:bg-gray-900" // CSS for mobile view
                    />
                    <TabPanelsComponent
                        selected_index={tabState.selected_index}
                    >
                        {props.children}
                    </TabPanelsComponent>
                </TabGroupComponent>
            </div>
        </div>
    );
};

export default Tabs;
