import React from "react";
import FactionFame from "../../components/faction-loyalty/faction-fame";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Tabs from "../../components/ui/tabs/tabs";
import ActionTabsProps from "./types/action-tabs-props";

type TabDefinition = { key: string; name: string }[];

export default class ActionTabs extends React.Component<ActionTabsProps, {}> {
    private tabs: TabDefinition;

    constructor(props: ActionTabsProps) {
        super(props);

        this.tabs = [
            {
                key: "actions",
                name: "Actions",
            },
            {
                key: "faction-loyalty",
                name: "Faction Loyalty",
            },
        ];
    }

    render() {
        return this.props.use_tabs ? (
            <Tabs tabs={this.tabs} disabled={false}>
                <TabPanel key={"actions"}>{this.props.children}</TabPanel>
                <TabPanel key={"faction-loyalty"}>
                    <FactionFame
                        user_id={this.props.user_id}
                        character_id={this.props.character_id}
                        update_faction_action_tasks={
                            this.props.update_faction_action_tasks
                        }
                        can_craft={this.props.can_craft}
                        can_attack={this.props.can_attack}
                        character_map_id={this.props.character_map_id}
                    />
                </TabPanel>
            </Tabs>
        ) : (
            this.props.children
        );
    }
}
