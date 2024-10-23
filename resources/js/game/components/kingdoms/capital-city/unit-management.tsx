import React from "react";
import Tabs from "../../ui/tabs/tabs";
import TabPanel from "../../ui/tabs/tab-panel";
import UnitRecruitment from "./partials/unit-management/unit-recruitment";
import UnitManagementProps from "./types/unit-management-props";
import UnitQueue from "./partials/unit-management/unit-queue";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";

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
            <>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="relative flex justify-between items-center w-full">
                    <h3 className="mb-2 sm:mb-0 sm:mr-4">
                        Oversee your kingdoms units
                    </h3>
                    <SuccessOutlineButton
                        button_label={"Back to council"}
                        on_click={this.props.manage_show_unit_recruitment}
                        additional_css="w-full sm:w-auto"
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

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
            </>
        );
    }
}
