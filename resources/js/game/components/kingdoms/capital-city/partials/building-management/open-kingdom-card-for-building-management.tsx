import React from "react";
import OrangeButton from "../../../../ui/buttons/orange-button";
import Building from "../../deffinitions/building";
import BuildingDetails from "./building-details";
import OpenKingdomCardForBuildingManagementProps from "../../types/partials/building-management/open-kingdom-card-for-building-management-props";
import OpenKingdomCardForBuildingManagementState from "../../types/partials/building-management/open-kingdom-card-for-building-management-state";
import InfoAlert from "../../../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";

export default class OpenKingdomCardForBuildingManagement extends React.Component<
    OpenKingdomCardForBuildingManagementProps,
    OpenKingdomCardForBuildingManagementState
> {
    constructor(props: OpenKingdomCardForBuildingManagementProps) {
        super(props);
    }

    render() {
        return (
            <div className="bg-gray-300 dark:bg-gray-600 p-4">
                <InfoAlert additional_css="my-2">
                    You may only cancel building requests when the order is
                    traveling. When you send the request we travel to this
                    kingdom and deliever the orders, you can see this in your
                    building queue (Click: Back to Building Overview). If the
                    kingdom is requesting resources for those buildings or
                    recruiting those buildings, you cannot cancel the building
                    or the entire request because it would throw the kingdom
                    into chaos.
                </InfoAlert>
                {this.props.kingdom.total_travel_time <= 1 ? (
                    <WarningAlert additional_css="mb-2">
                        <p>
                            This kingdom is a minute away from your capital
                            city. You wont be able to cancel any of the bulding
                            requests when you send orders to this kingdom.
                        </p>
                    </WarningAlert>
                ) : null}
                <OrangeButton
                    on_click={() =>
                        this.props.toggle_queue_all_buildings(
                            this.props.kingdom.kingdom_id,
                        )
                    }
                    button_label={
                        this.props.building_queue.find(
                            (item: any) =>
                                item.kingdomId ===
                                this.props.kingdom.kingdom_id,
                        )?.buildingIds.length ===
                        this.props.kingdom.buildings.length
                            ? "Remove All from Queue"
                            : "Add All to Queue"
                    }
                    additional_css="w-full mb-4"
                />
                {this.props.kingdom.buildings.map((building: Building) => (
                    <BuildingDetails
                        building={building}
                        kingdom={this.props.kingdom}
                        toggle_building_queue={this.props.toggle_building_queue}
                        has_building_in_queue={this.props.has_building_in_queue}
                    />
                ))}
            </div>
        );
    }
}
