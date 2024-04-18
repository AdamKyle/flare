import React from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import EventGoalsTab from "./tabs/event-goals-tab";

type TabDefinition = { key: string; name: string }[];

export default class MapTabs extends React.Component<any, any> {
    private tabs: TabDefinition;

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "map",
                name: "Map",
            },
            {
                key: "event-goals",
                name: "Event Goals",
            },
        ];
    }

    render() {
        return this.props.use_tabs ? (
            <Tabs tabs={this.tabs} disabled={false}>
                <TabPanel key={"map"}>{this.props.children}</TabPanel>
                <TabPanel key={"event-goals"}>
                    <EventGoalsTab character_id={this.props.character_id} user_id={this.props.user_id} />
                </TabPanel>
            </Tabs>
        ) : (
            this.props.children
        );
    }
}
