import React from "react";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import BuildingsToUpgradeSection from "./buildings-to-upgrade-section";
import BuildingQueuesTable from "./building-queues-table";

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
            <div className="text-center flex flex-col md:flex-row items-center justify-center">
                <PrimaryOutlineButton
                    additional_css="mr-0 md:mr-4 lg:text-5xl md:text-4xl sm:text-3xl text-xl lg:py-6 md:py-4 sm:py-3 py-2 lg:px-10 md:px-8 sm:px-6 px-4"
                    button_label="Upgrade"
                    on_click={this.manageShowUpgradeBuildingList.bind(this)}
                />
                <div className="border-t-2 md:border-t-0 md:border-r-2 border-gray-500 h-0 md:h-16 mx-0 md:mx-4 my-4 md:my-0"></div>
                <PrimaryOutlineButton
                    additional_css="lg:text-5xl md:text-4xl sm:text-3xl text-xl lg:py-6 md:py-4 sm:py-3 py-2 lg:px-10 md:px-8 sm:px-6 px-4"
                    button_label="Repair"
                    on_click={this.manageShowRepairList.bind(this)}
                />
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
                <div className="flex items-center relative">
                    <h3>Oversee your kingdoms buildings</h3>
                    <SuccessOutlineButton
                        button_label={
                            this.state.show_upgrade_buildings_list ||
                            this.state.show_repair_building_list
                                ? "Back to Building Overview"
                                : "Back to council"
                        }
                        on_click={this.manageView.bind(this)}
                        additional_css={"absolute right-0"}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p className="my-2">
                    Choose to repair or upgrade buildings. Repairing shows only
                    damaged buildings, like the Farm and Walls, which are
                    essential to rebuild to prevent morale loss. Upgrading shows
                    buildings that are not damaged, not max level, and unlocked
                    through the passive tree.
                </p>
                <p className="my-2">
                    When upgrading or repairing, population will be purchased if
                    needed, and resources will be requested if available.
                    Kingdoms with airships will automatically use one when
                    requesting resources.
                </p>

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
                        <BuildingQueuesTable
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
