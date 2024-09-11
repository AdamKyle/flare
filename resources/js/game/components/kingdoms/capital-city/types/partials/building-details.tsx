import React from "react";
import DangerOutlineButton from "../../../../ui/buttons/danger-outline-button";
import PrimaryOutlineButton from "../../../../ui/buttons/primary-outline-button";
import Building from "../../deffinitions/building";
import Kingdom from "../../deffinitions/kingdom";

interface BuildingDetailsProps {
    building: Building;
    kingdom: Kingdom;
    toggle_building_queue: (kingdomId: number, buildingId: number) => void;
    has_building_in_queue: (kingdom: Kingdom, buildingId: Building) => boolean;
}

interface BuildingDetailsState {}

export default class BuildingDetails extends React.Component<
    BuildingDetailsProps,
    BuildingDetailsState
> {
    constructor(props: BuildingDetailsProps) {
        super(props);
    }

    render() {
        return (
            <div
                key={this.props.building.id}
                className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
            >
                <h3 className="text-lg font-semibold dark:text-white">
                    {this.props.building.name}
                </h3>
                <p className="text-gray-700 dark:text-gray-300">
                    {this.props.building.description}
                </p>
                <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Level:
                        </strong>
                        <span>
                            {this.props.building.level} /{" "}
                            {this.props.building.max_level}
                        </span>
                    </p>
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Defense:
                        </strong>
                        <span>
                            {this.props.building.current_defence} /{" "}
                            {this.props.building.max_defence}
                        </span>
                    </p>
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Durability:
                        </strong>
                        <span>
                            {this.props.building.current_durability} /{" "}
                            {this.props.building.max_durability}
                        </span>
                    </p>
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Cost:
                        </strong>
                        <span>
                            <strong>Wood</strong>:{" "}
                            {this.props.building.wood_cost},{" "}
                            <strong>Stone</strong>:{" "}
                            {this.props.building.stone_cost},{" "}
                            <strong>Clay</strong>:{" "}
                            {this.props.building.clay_cost},{" "}
                            <strong>Iron</strong>:{" "}
                            {this.props.building.iron_cost}
                        </span>
                    </p>
                </div>

                {this.props.has_building_in_queue(
                    this.props.kingdom,
                    this.props.building,
                ) ? (
                    <DangerOutlineButton
                        button_label={"Remove from Queue"}
                        on_click={() => {
                            this.props.toggle_building_queue(
                                this.props.kingdom.kingdom_id,
                                this.props.building.id,
                            );
                        }}
                        additional_css={"my-2"}
                    />
                ) : (
                    <PrimaryOutlineButton
                        button_label={"Add to queue"}
                        on_click={() => {
                            this.props.toggle_building_queue(
                                this.props.kingdom.kingdom_id,
                                this.props.building.id,
                            );
                        }}
                        additional_css={"my-2"}
                    />
                )}
            </div>
        );
    }
}
