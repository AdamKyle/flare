import React from "react";
import OrangeButton from "../../../../ui/buttons/orange-button";
import Building from "../../deffinitions/building";
import BuildingDetails from "./building-details";
import OpenKingdomCardForBuildingManagementProps from "../../types/partials/building-management/open-kingdom-card-for-building-management-props";
import OpenKingdomCardForBuildingManagementState from "../../types/partials/building-management/open-kingdom-card-for-building-management-state";

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
