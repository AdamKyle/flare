import React from "react";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import BuildingsToUpgradeSection from "./buildings-to-upgrade-section";
import BuildingsInQueue from "./buildings-in-queue";
import ClickableIconCard from "../../ui/cards/clickable-icon-card";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";

export default class ManageKingdomBuildings extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            show_upgrade_buildings_list: false,
            show_repair_building_list: false,
        };
    }

    manageShowUpgradeBuildingList() {
        this.setState({
            show_upgrade_buildings_list:
                !this.state.show_upgrade_buildings_list,
        });
    }

    manageShowRepairList(): void {
        this.setState({
            show_repair_building_list: !this.state.show_repair_building_list,
        });
    }

    renderActionButtons() {
        return (
            <div className="border-2 border-gray-500 dark:border-gray-600 bg-gray-700 dark:bg-gray-600 mr-auto ml-auto p-4 rounded shadow-lg">
                <ClickableIconCard
                    title={"Upgrade Buildings"}
                    icon_class={"ra ra-forging"}
                    on_click={this.manageShowUpgradeBuildingList.bind(this)}
                >
                    <p className="mb-2">
                        Clicking this card will allow you to select buildings to
                        upgrade. This will send each building request, grouped
                        together, to the associated kingdom. Resources will auto
                        be requested, population is purchased from the kingdom
                        treasury. All automatically. Below will be a table of
                        the request queues. Each kingdom will get a log stating
                        what was or wasn't upgraded.
                    </p>
                </ClickableIconCard>
                <ClickableIconCard
                    title={"Repair Buildings"}
                    icon_class={"ra ra-guarded-tower"}
                    on_click={this.manageShowRepairList.bind(this)}
                >
                    Clicking this card will let you repair broken buildings
                    across your kingdoms on this plane. Resources will auto be
                    requested, population is purchased from the kingdom
                    treasury. All automatically. Below will be a table of the
                    request queues. Each kingdom will get a log stating what was
                    or wasn't repaired.
                </ClickableIconCard>
            </div>
        );
    }

    manageView(): void {
        if (this.state.show_upgrade_buildings_list) {
            this.manageShowUpgradeBuildingList();

            return;
        }

        if (this.state.show_repair_building_list) {
            this.manageShowRepairList();

            return;
        }

        this.props.manage_building_section();
    }

    render() {
        return (
            <div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="relative flex flex-col md:flex-row items-start md:items-center">
                    <h3 className="mb-2 sm:mb-0 sm:mr-4">
                        Oversee your kingdoms buildings
                    </h3>
                    <SuccessOutlineButton
                        button_label={
                            this.state.show_upgrade_buildings_list ||
                            this.state.show_repair_building_list
                                ? "Back to Building Overview"
                                : "Back to council"
                        }
                        on_click={this.manageView.bind(this)}
                        additional_css="w-full sm:w-auto"
                    />
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                {this.state.show_upgrade_buildings_list ? (
                    <BuildingsToUpgradeSection
                        user_id={this.props.user_id}
                        kingdom={this.props.kingdom}
                        repair={false}
                    />
                ) : this.state.show_repair_building_list ? (
                    <BuildingsToUpgradeSection
                        user_id={this.props.user_id}
                        kingdom={this.props.kingdom}
                        repair={true}
                    />
                ) : (
                    <>
                        {this.renderActionButtons()}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h3 className={"my-2"}>Queue Info</h3>
                        <BuildingsInQueue
                            user_id={this.props.user_id}
                            kingdom_id={this.props.kingdom.id}
                            character_id={this.props.kingdom.character_id}
                        />
                    </>
                )}
            </div>
        );
    }
}
