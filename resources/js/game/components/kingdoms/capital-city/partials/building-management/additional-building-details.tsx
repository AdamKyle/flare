import React, { ReactNode } from "react";
import Building from "../../deffinitions/building";
import UnitForBuilding from "../../deffinitions/unit-for-building";
import InfoAlert from "../../../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";

interface AdditionalBuildingDetailsProps {
    building: Building;
}

enum ResourceType {
    WOOD = "wood",
    CLAY = "clay",
    STONE = "stone",
    IRON = "iron",
    STEEL = "steel",
    POPULATION = "population",
}

export default class AdditionalBuildingDetails extends React.Component<
    AdditionalBuildingDetailsProps,
    any
> {
    constructor(props: AdditionalBuildingDetailsProps) {
        super(props);
    }

    renderRepairDetails() {
        return (
            <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {this.props.building.morale_decrease > 0 ? (
                    <WarningAlert additional_css="my-4 text-gray-700 dark:text-gray-300">
                        <p className="my-2">
                            You are currently loosing:{" "}
                            <span className="text-red-700 dark:text-red-500">
                                {(
                                    this.props.building.morale_decrease * 100
                                ).toFixed(0)}
                                %
                            </span>{" "}
                            morale per hour (in addition to other buildings that
                            may also have a morale decrease per hour such as
                            churches and keeps).
                        </p>
                    </WarningAlert>
                ) : null}

                <p className="flex justify-between">
                    <strong className="text-gray-800 dark:text-gray-200">
                        Durability will become:
                    </strong>
                    <span className="text-green-700 dark:text-green-500">
                        {this.props.building.max_durability} (Current Max DUR)
                    </span>
                </p>

                {this.props.building.morale_increase !== 0 ? (
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Morale will increase by:
                        </strong>
                        <span className="text-green-700 dark:text-green-500">
                            +
                            {(
                                this.props.building.morale_increase * 100
                            ).toFixed(0)}
                            %
                        </span>
                    </p>
                ) : null}
            </div>
        );
    }

    renderResouceIncrease(): ReactNode[] | [] {
        return Object.values(ResourceType)
            .map((resource: string): ReactNode => {
                const resourcekey = `${resource}_increase` as keyof Building;
                const increaseValue = this.props.building[
                    resourcekey
                ] as number;

                if (increaseValue > 0) {
                    return (
                        <p className="flex justify-between">
                            <strong className="text-gray-800 dark:text-gray-200">
                                Max {resource + " increased by:"}
                            </strong>
                            <span className="text-green-700 dark:text-green-500">
                                +{increaseValue}
                            </span>
                        </p>
                    );
                }

                return null;
            })
            .filter((element: ReactNode | null) => element !== null);
    }

    hasResourceIncrease(): boolean {
        return Object.values(ResourceType).some((resource: string) => {
            const resourceKey = `${resource}_increase` as keyof Building;
            const increaseValue = this.props.building[resourceKey] as number;
            return increaseValue > 0;
        });
    }

    renderUnitsForBuilding(): ReactNode[] {
        return this.props.building.units_for_building.map(
            (unitForBuilding: UnitForBuilding) => {
                return (
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            {unitForBuilding.unit_name} At level:
                        </strong>
                        <span>
                            {unitForBuilding.at_building_level}{" "}
                            {this.props.building.level >
                            unitForBuilding.at_building_level ? (
                                <i className="fas fa-check text-green-700 dark:text-green-500"></i>
                            ) : (
                                <i className="fas fa-times text-red-700 dark:text-red-500"></i>
                            )}
                        </span>
                    </p>
                );
            },
        );
    }

    renderUpgradeDetails(): ReactNode {
        return (
            <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {this.renderResouceIncrease()}

                {!this.hasResourceIncrease() &&
                this.props.building.units_for_building.length <= 0 ? (
                    <InfoAlert additional_css="my-4 text-gray-700 dark:text-gray-300">
                        <p className="my-2">
                            This building doesn't increase morale, let you
                            recruit units or increase any resources. Perhaps the
                            description tells you more info
                        </p>
                    </InfoAlert>
                ) : null}

                {this.props.building.morale_increase > 0 ? (
                    <p className="flex justify-between">
                        <strong className="text-gray-800 dark:text-gray-200">
                            Morale will increase by:
                        </strong>
                        <span className="text-green-700 dark:text-green-500">
                            +
                            {(
                                this.props.building.morale_increase * 100
                            ).toFixed(0)}
                            %
                        </span>
                    </p>
                ) : null}

                {this.props.building.units_for_building.length > 0 ? (
                    <>
                        <p className="text-gray-700 dark:text-gray-300 my-4">
                            These are the units you can recruit, if theres a{" "}
                            <i className="fas fa-check text-green-700 dark:text-green-500"></i>{" "}
                            than you are able to recruit for this kingdom. If
                            theres an{" "}
                            <i className="fas fa-times text-red-700 dark:text-red-500"></i>
                            , then you cannot until your building is the
                            required level.
                        </p>
                        {this.renderUnitsForBuilding()}
                    </>
                ) : null}
            </div>
        );
    }

    render() {
        return (
            <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                {this.props.building.current_durability <
                this.props.building.max_durability
                    ? this.renderRepairDetails()
                    : this.renderUpgradeDetails()}
            </div>
        );
    }
}
