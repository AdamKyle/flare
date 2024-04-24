import React, { Fragment } from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import InfoTab from "./tabs/info-tab";
import CharacterTabsProps from "../../../lib/game/character-sheet/types/character-tabs-props";
import CharacterActiveBoons from "./character-active-boons";
import CharacterFactions from "./character-factions";

export default class CharacterTabs extends React.Component<
    CharacterTabsProps,
    any
> {
    private tabs: { name: string; key: string }[];

    constructor(props: CharacterTabsProps) {
        super(props);

        this.tabs = [
            {
                key: "info",
                name: "Info",
            },
            {
                key: "active-boons",
                name: "Active Boons",
            },
            {
                key: "factions",
                name: "Factions",
            },
        ];
    }

    render() {
        return (
            <Fragment>
                <Tabs tabs={this.tabs} full_width={true}>
                    <TabPanel key={"info"}>
                        <InfoTab
                            view_port={0}
                            character={this.props.character}
                            finished_loading={this.props.finished_loading}
                            manage_addition_data={
                                this.props.manage_addition_data
                            }
                        />
                    </TabPanel>
                    <TabPanel key={"active-boons"}>
                        <CharacterActiveBoons
                            character_id={this.props.character?.id}
                            finished_loading={this.props.finished_loading}
                        />
                    </TabPanel>
                    <TabPanel key={"factions"}>
                        <CharacterFactions
                            update_pledge_tab={this.props.update_pledge_tab}
                            character_id={this.props.character?.id}
                            finished_loading={this.props.finished_loading}
                            is_pledged={
                                this.props.character?.can_see_pledge_tab
                            }
                            pledged_faction_id={
                                this.props.character?.pledged_to_faction_id
                            }
                            update_faction_action_tasks={
                                this.props.update_faction_action_tasks
                            }
                        />
                    </TabPanel>
                </Tabs>
            </Fragment>
        );
    }
}
