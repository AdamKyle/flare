import React from "react";
import FactionFame from "../../components/faction-loyalty/faction-fame";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Tabs from "../../components/ui/tabs/tabs";
import CharacterActiveBoons from "../character-sheet/components/character-active-boons";
import ActionTabsProps, { ActionTab } from "./types/action-tabs-props";

export default class ActionTabs extends React.Component<ActionTabsProps, {}> {
    constructor(props: ActionTabsProps) {
        super(props);
    }

    buildTabs(): ActionTab[] {
        const tabs = [
            {
                key: "actions",
                name: "Actions",
                has_warning: this.props.has_exploration_warning ?? false,
            },
        ];

        if (this.props.use_tabs) {
            tabs.push({
                key: "faction-loyalty",
                name: "Faction Loyalty",
                has_warning: this.props.has_faction_loyalty_warning,
            });
        }

        if (this.props.view_port > 932) {
            tabs.push({
                key: "active-boons",
                name: "Active Boons",
            });
        }

        return tabs;
    }

    render() {
        return this.props.use_tabs || this.props.view_port > 932 ? (
            <Tabs
                tabs={this.buildTabs()}
                disabled={false}
                icon_key={"has_warning"}
            >
                <TabPanel key={"actions"}>{this.props.children}</TabPanel>
                {this.props.use_tabs ? (
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
                            is_automation_running={
                                this.props.is_automation_running
                            }
                            is_faction_loyalty_automation_running={
                                this.props.is_faction_loyalty_automation_running
                            }
                            is_delve_running={this.props.is_delve_running}
                            has_faction_loyalty_warning={
                                this.props.has_faction_loyalty_warning
                            }
                            faction_loyalty_warning_notices={
                                this.props.faction_loyalty_warning_notices
                            }
                            update_faction_loyalty_warning={
                                this.props.update_faction_loyalty_warning
                            }
                        />
                    </TabPanel>
                ) : null}
                {this.props.view_port > 932 ? (
                    <TabPanel key={"active-boons"}>
                        <CharacterActiveBoons
                            character_id={this.props.character_id}
                            user_id={this.props.user_id}
                            finished_loading={true}
                        />
                    </TabPanel>
                ) : null}
            </Tabs>
        ) : (
            this.props.children
        );
    }
}
