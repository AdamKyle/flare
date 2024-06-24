import React from "react";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import BuildingsToUpgradeSection from "./buildings-to-upgrade-section";

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
            <div className="text-center">
                <PrimaryOutlineButton
                    additional_css={"mr-4"}
                    button_label={"Upgrade"}
                    on_click={this.manageShowUpgradeBuildingList.bind(this)}
                />
                <PrimaryOutlineButton
                    button_label={"Repair"}
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
                    Below you can choose to repair buildings or upgrade
                    buildings. Repairing only shows you damaged buildings, some
                    of these like the Farm and the Walls are essential to
                    rebuild to stop loosing morale per hour. You can also
                    upgrade buildings, to increase their levels. Clicking that
                    button will only show you buildings who do not need to be
                    repaired, are not max level and are unlocked via that
                    passive tree.
                </p>
                <p className="my-2">
                    For upgrading and repairing, population will be
                    automatically purchased if needed, resources will be
                    requested should you have the resources, population and
                    spearmen. Kingdoms with airships will automatically use one
                    when requesting resources.
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
                    this.renderActionButtons()
                )}
            </div>
        );
    }
}
