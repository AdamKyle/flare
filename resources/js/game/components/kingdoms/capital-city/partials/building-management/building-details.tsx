import React, { ReactNode } from "react";
import DangerOutlineButton from "../../../../ui/buttons/danger-outline-button";
import PrimaryOutlineButton from "../../../../ui/buttons/primary-outline-button";
import Building from "../../deffinitions/building";
import Kingdom from "../../deffinitions/kingdom-with-buildings";
import SuccessOutlineButton from "../../../../ui/buttons/success-outline-button";
import AdditionalBuildingDetails from "./additional-building-details";
import DangerAlert from "../../../../ui/alerts/simple-alerts/danger-alert";

interface BuildingDetailsProps {
    building: Building;
    kingdom: Kingdom;
    toggle_building_queue: (kingdomId: number, buildingId: number) => void;
    has_building_in_queue: (kingdom: Kingdom, buildingId: Building) => boolean;
}

interface BuildingDetailsState {
    additional_building_ids: Set<number>;
}

export default class BuildingDetails extends React.Component<
    BuildingDetailsProps,
    BuildingDetailsState
> {
    constructor(props: BuildingDetailsProps) {
        super(props);

        this.state = {
            additional_building_ids: new Set<number>(),
        };
    }

    manageAdditionalDetails(buildingId: number) {
        const additionalBuildingIds = this.state.additional_building_ids;

        if (additionalBuildingIds.has(buildingId)) {
            additionalBuildingIds.delete(buildingId);

            this.setState({
                additional_building_ids: new Set<number>(),
            });

            return;
        }

        additionalBuildingIds.add(buildingId);

        this.setState({
            additional_building_ids: additionalBuildingIds,
        });
    }

    componentWillUnmount(): void {
        const additionalBuildingIds = this.state.additional_building_ids;

        additionalBuildingIds.clear();

        this.setState({
            additional_building_ids: additionalBuildingIds,
        });
    }

    getAdditionalDetailsButtonLabel(buildingId: number): string {
        if (this.state.additional_building_ids.has(buildingId)) {
            return "Close additional details";
        }

        return "Show additional details";
    }

    isAddToQueueDisabled() {
        if (this.props.building.is_locked) {
            return true;
        }

        if (this.props.building.passive_required_for_building === null) {
            return false;
        }

        return !this.props.building.passive_required_for_building.is_trained;
    }

    renderPassiveSkillAlert(): ReactNode | null {
        if (this.props.building.passive_required_for_building) {
            if (!this.props.building.passive_required_for_building.is_trained) {
                return (
                    <DangerAlert additional_css="my-4">
                        <p>
                            This building cannot be upgraded or repaired because
                            you need to train the{" "}
                            <a
                                href="/information/kingdom-passive-skills"
                                target="_blank"
                            >
                                Kingdom Passive{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            :{" "}
                            <strong>
                                {
                                    this.props.building
                                        .passive_required_for_building.name
                                }
                            </strong>{" "}
                            first to level:{" "}
                            <strong>
                                {
                                    this.props.building
                                        .passive_required_for_building
                                        .required_level
                                }
                            </strong>
                            . Once done so you can then upgrade/repair this
                            building.
                        </p>
                    </DangerAlert>
                );
            }
        }

        return null;
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
                {this.renderPassiveSkillAlert()}
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

                {this.state.additional_building_ids.has(
                    this.props.building.id,
                ) ? (
                    <AdditionalBuildingDetails building={this.props.building} />
                ) : null}

                <div className="flex items-center space-x-4 my-2">
                    <SuccessOutlineButton
                        button_label={this.getAdditionalDetailsButtonLabel(
                            this.props.building.id,
                        )}
                        on_click={() => {
                            this.manageAdditionalDetails(
                                this.props.building.id,
                            );
                        }}
                        additional_css={"my-2"}
                    />

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
                            button_label={"Add to Queue"}
                            on_click={() => {
                                this.props.toggle_building_queue(
                                    this.props.kingdom.kingdom_id,
                                    this.props.building.id,
                                );
                            }}
                            disabled={this.isAddToQueueDisabled()}
                            additional_css={"my-2"}
                        />
                    )}
                </div>
            </div>
        );
    }
}
