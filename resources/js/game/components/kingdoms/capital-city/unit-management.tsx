import React from "react";
import Tabs from "../../ui/tabs/tabs";
import TabPanel from "../../ui/tabs/tab-panel";
import UnitRecruitment from "./partials/unit-management/unit-recruitment";
import UnitManagementProps from "./types/unit-management-props";
import UnitQueue from "./partials/unit-management/unit-queue";

export default class UnitManagement extends React.Component<UnitManagementProps> {
    private tabs: { key: string; name: string }[];

    constructor(props: UnitManagementProps) {
        super(props);

        this.tabs = [
            {
                key: "unit-recruitment",
                name: "Unit Recruitment",
            },
            {
                key: "unit-queues",
                name: "Unit Queue",
            },
        ];
    }

    render() {
        return (
            <Tabs tabs={this.tabs}>
                <TabPanel key={"unit-recruitment"}>
                    <UnitRecruitment
                        user_id={this.props.user_id}
                        kingdom={this.props.kingdom}
                        manage_unit_section={
                            this.props.manage_show_unit_recruitment
                        }
                    />
                </TabPanel>
                <TabPanel key={"unit-queue"}>
                    <UnitQueue
                        user_id={this.props.user_id}
                        character_id={this.props.kingdom.character_id}
                        kingdom_id={this.props.kingdom.id}
                    />
                </TabPanel>
            </Tabs>
        );
    }
}
