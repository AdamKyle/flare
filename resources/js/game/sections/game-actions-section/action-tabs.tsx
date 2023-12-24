import React from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import ActionTabsProps from "./types/action-tabs-props";
import FactionFame from "../faction-loyalty/faction-fame";

type TabDefinition = { key: string; name: string }[];

export default class ActionTabs extends React.Component<ActionTabsProps, { }> {

    private tabs: TabDefinition;

    constructor(props: ActionTabsProps) {
        super(props);

        this.tabs = [{
            key: 'actions',
            name: 'Actions',
        }, {
            key: 'faction-loyalty',
            name: 'Faction Loyalty'
        }]
    }

    render() {
        return this.props.use_tabs ? (
            <Tabs tabs={this.tabs} disabled={false}>
                <TabPanel key={"actions"}>{this.props.children}</TabPanel>
                <TabPanel key={"faction-loyalty"}>
                    <FactionFame />
                </TabPanel>
            </Tabs>
        ) : (
            this.props.children
        );
    }
}
