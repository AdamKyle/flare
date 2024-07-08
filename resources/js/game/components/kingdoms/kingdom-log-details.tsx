import clsx from "clsx";
import React, { Fragment } from "react";
import InfoAlert from "../../components/ui/alerts/simple-alerts/info-alert";
import BasicCard from "../../components/ui/cards/basic-card";
import { formatNumber } from "../../lib/game/format-number";
import {
    BuildingLogDetails,
    UnitLogDetails,
} from "./deffinitions/kingdom-log-details";
import KingdomLogProps from "./types/kingdom-log-props";
import { capitalize, startCase } from "lodash";

export default class KingdomLogDetails extends React.Component<
    KingdomLogProps,
    {}
> {
    constructor(props: KingdomLogProps) {
        super(props);
    }

    renderBuildingChanges() {
        const changes: any = [];

        this.props.log.old_buildings.forEach(
            (oldBuilding: BuildingLogDetails) => {
                let foundNewBuilding: BuildingLogDetails[] | [] =
                    this.props.log.new_buildings.filter(
                        (newBuilding: { name: string; durability: number }) =>
                            newBuilding.name === oldBuilding.name,
                    );

                if (foundNewBuilding.length > 0) {
                    const newBuilding: BuildingLogDetails = foundNewBuilding[0];

                    if (newBuilding.durability === oldBuilding.durability) {
                        changes.push(
                            <Fragment>
                                <dt>{oldBuilding.name}</dt>
                                <dd>
                                    0% Lost
                                    {this.props.log.is_mine
                                        ? ", New Durability: " +
                                          formatNumber(newBuilding.durability)
                                        : null}
                                </dd>
                            </Fragment>,
                        );
                    } else if (newBuilding.durability === 0) {
                        changes.push(
                            <Fragment>
                                <dt>{oldBuilding.name}</dt>
                                <dd className="text-red-600 dark:text-red-400">
                                    100% Lost
                                    {this.props.log.is_mine
                                        ? ", New Durability: " +
                                          formatNumber(newBuilding.durability)
                                        : null}
                                </dd>
                            </Fragment>,
                        );
                    } else {
                        changes.push(
                            <Fragment>
                                <dt>{oldBuilding.name}</dt>
                                <dd className="text-red-600 dark:text-red-400">
                                    {(
                                        ((oldBuilding.durability -
                                            newBuilding.durability) /
                                            oldBuilding.durability) *
                                        100
                                    ).toFixed(0)}
                                    % Lost
                                    {this.props.log.is_mine
                                        ? ", New Durability: " +
                                          formatNumber(newBuilding.durability)
                                        : null}
                                </dd>
                            </Fragment>,
                        );
                    }
                }
            },
        );

        return changes;
    }

    renderUnitChanges() {
        const changes: any = [];

        this.props.log.old_units.forEach((oldUnit: UnitLogDetails) => {
            let foundNewUnit: UnitLogDetails[] | [] =
                this.props.log.new_units.filter(
                    (newUnit: { name: string; amount: number }) =>
                        newUnit.name === oldUnit.name,
                );

            if (foundNewUnit.length > 0) {
                const newUnit: UnitLogDetails = foundNewUnit[0];

                if (newUnit.amount === oldUnit.amount) {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd>
                                0% Lost
                                {this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                } else if (newUnit.amount === 0) {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd className="text-red-600 dark:text-red-400">
                                100% Lost
                                {this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                } else {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd className="text-red-600 dark:text-red-400">
                                {(
                                    ((oldUnit.amount - newUnit.amount) /
                                        oldUnit.amount) *
                                    100
                                ).toFixed(2)}
                                % Lost
                                {this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                }
            }
        });

        return changes;
    }

    renderUnitsSentChange() {
        const changes: any = [];

        this.props.log.units_sent.forEach((sentUnit: UnitLogDetails) => {
            let foundNewUnit: UnitLogDetails[] | [] =
                this.props.log.units_survived.filter(
                    (newUnit: { name: string; amount: number }) =>
                        newUnit.name === sentUnit.name,
                );

            if (foundNewUnit.length > 0) {
                const newUnit: UnitLogDetails = foundNewUnit[0];

                if (newUnit.amount === sentUnit.amount) {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd>
                                0% Lost
                                {!this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                } else if (newUnit.amount === 0) {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd className="text-red-600 dark:text-red-400">
                                100% Lost
                                {!this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                } else {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd className="text-red-600 dark:text-red-400">
                                {(
                                    ((sentUnit.amount - newUnit.amount) /
                                        sentUnit.amount) *
                                    100
                                ).toFixed(0)}
                                % Lost
                                {!this.props.log.is_mine
                                    ? ", Amount Left: " +
                                      formatNumber(newUnit.amount)
                                    : null}
                            </dd>
                        </Fragment>,
                    );
                }
            }
        });

        return changes;
    }

    shouldShowUnitSentChanges(): boolean {
        return (
            this.props.log.units_sent.length > 0 &&
            this.props.log.units_survived.length > 0
        );
    }

    renderResourcesDeliveredDetails() {
        const resourceKeys = Object.keys(
            this.props.log.additional_details.resource_request_log
                .resource_details,
        );

        return resourceKeys.map((resourceKey) => {
            return (
                <>
                    <dt>{startCase(resourceKey)}</dt>
                    <dd className="text-green-700 dark:text-green-500">
                        +
                        {formatNumber(
                            this.props.log.additional_details
                                .resource_request_log.resource_details[
                                resourceKey
                            ],
                        )}
                    </dd>
                </>
            );
        });
    }

    renderAdditionalResourceMovementLogDetails() {
        const additionalMessages =
            this.props.log.additional_details.resource_request_log
                .additional_messages;

        return additionalMessages.map((message: string) => {
            return <li>{message}</li>;
        });
    }

    renderAdditionalCapitalCityMessages() {
        const additionalMessages = this.props.log.additional_details.messages;

        return additionalMessages.map((message: string) => {
            return <li className={"my-4"}>{message}</li>;
        });
    }

    renderCapitalCityBuildingUpgrade() {
        const buildingData = this.props.log.additional_details.building_data;

        return buildingData.map((data: any) => {
            return (
                <dl
                    className="mb-4 shadow-lg rounded-lg bg-gray-300 mx-auto m-8 p-4 flex dark:bg-gray-700
            dark:text-gray-200"
                >
                    <dt>Building Name</dt>
                    <dd>{data.building_name}</dd>
                    <dt>Type</dt>
                    <dd>{capitalize(data.type)}</dd>
                    <dt>Status</dt>
                    <dd
                        className={clsx({
                            "text-red-700 dark:text-red-500":
                                data.status === "rejected",
                            "text-green-700 dark:text-green-500":
                                data.status === "finished",
                        })}
                    >
                        {capitalize(data.status)}
                    </dd>

                    {data.from_level !== null && data.to_level !== null ? (
                        <>
                            <dt>
                                From <i className="fas fa-arrow-right mx-2"></i>{" "}
                                To Level
                            </dt>
                            <dd className="flex items-center">
                                {data.from_level}{" "}
                                <i className="fas fa-arrow-right mx-2"></i>{" "}
                                {data.to_level}
                            </dd>
                        </>
                    ) : null}
                </dl>
            );
        });
    }

    renderCapitalCityUnitUpgrade() {
        const unitData = this.props.log.additional_details.unit_data;

        return unitData.map((data: any) => {
            return (
                <dl
                    className="mb-4 shadow-lg rounded-lg bg-gray-300 mx-auto m-8 p-4 flex dark:bg-gray-700
            dark:text-gray-200"
                >
                    <dt>Unit Name</dt>
                    <dd>{data.unit_name}</dd>
                    <dt>Amount requested</dt>
                    <dd>{formatNumber(data.amount_requested)}</dd>
                    <dt>Status</dt>
                    <dd
                        className={clsx({
                            "text-red-700 dark:text-red-500":
                                data.status === "rejected",
                            "text-green-700 dark:text-green-500":
                                data.status === "finished",
                        })}
                    >
                        {capitalize(data.status)}
                    </dd>
                </dl>
            );
        });
    }

    render() {
        if (this.props.log.status === "Capital City Building Request") {
            return (
                <BasicCard>
                    <div className="text-right cursor-pointer text-red-500">
                        <button onClick={this.props.close_details}>
                            <i className="fas fa-minus-circle"></i>
                        </button>
                    </div>
                    <h3 className="mb-4">{this.props.log.status}</h3>
                    <p className={"my-4"}>
                        Capital City: {this.props.log.from_kingdom_name} has
                        sent orders to: {this.props.log.to_kingdom_name}. These
                        have now been delivered.
                    </p>

                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <h4>Request Info</h4>
                            <div
                                className={
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 "
                                }
                            ></div>
                            <div className={"my-4"}>
                                {this.renderCapitalCityBuildingUpgrade()}
                            </div>
                        </div>

                        <div>
                            <h4>Messages</h4>
                            <div
                                className={
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 "
                                }
                            ></div>
                            {this.props.log.additional_details.messages.length >
                            0 ? (
                                <InfoAlert additional_css={"my-4"}>
                                    <p className="text-yellow-700 dark:text-yellow-600 mb-4">
                                        There are additional details about this
                                        request from the Capital City:
                                    </p>

                                    <ul className="list-disc ml-4">
                                        {this.renderAdditionalCapitalCityMessages()}
                                    </ul>
                                </InfoAlert>
                            ) : null}
                        </div>
                    </div>
                </BasicCard>
            );
        }

        if (this.props.log.status === "Capital City Unit Request") {
            return (
                <BasicCard>
                    <div className="text-right text-red-500">
                        <button onClick={this.props.close_details}>
                            <i className="fas fa-minus-circle cursor-pointer"></i>
                        </button>
                    </div>
                    <h3 className="mb-4">{this.props.log.status}</h3>
                    <p className={"my-4"}>
                        Capital City: {this.props.log.from_kingdom_name} has
                        sent orders to: {this.props.log.to_kingdom_name}. These
                        have now been delivered.
                    </p>

                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <h4>Request Info</h4>
                            <div
                                className={
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 "
                                }
                            ></div>
                            <div className={"my-4"}>
                                {this.renderCapitalCityUnitUpgrade()}
                            </div>
                        </div>

                        <div>
                            <h4>Messages</h4>
                            <div
                                className={
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 "
                                }
                            ></div>
                            {this.props.log.additional_details.messages.length >
                            0 ? (
                                <InfoAlert additional_css={"my-4"}>
                                    <p className="text-yellow-700 dark:text-yellow-600 mb-4">
                                        There are additional details about this
                                        request from the Capital City:
                                    </p>

                                    <ul className="list-disc ml-4">
                                        {this.renderAdditionalCapitalCityMessages()}
                                    </ul>
                                </InfoAlert>
                            ) : null}
                        </div>
                    </div>
                </BasicCard>
            );
        }

        if (this.props.log.status === "Kingdom requested resources") {
            return (
                <BasicCard>
                    <div className="text-right cursor-pointer text-red-500">
                        <button onClick={this.props.close_details}>
                            <i className="fas fa-minus-circle"></i>
                        </button>
                    </div>
                    <h3 className="mb-4">{this.props.log.status}</h3>
                    <p className={"my-4"}>
                        Kingdom: {this.props.log.to_kingdom_name} has requested
                        resources from: {this.props.log.from_kingdom_name}.
                        These have now been delivered.
                    </p>
                    <dl>{this.renderResourcesDeliveredDetails()}</dl>

                    {this.props.log.additional_details.resource_request_log
                        .additional_messages.length > 0 ? (
                        <div className="my-4">
                            <p className="text-yellow-700 dark:text-yellow-600 mb-4">
                                There are additional details about this trip:
                            </p>

                            <ul className="list-disc ml-4">
                                {this.renderAdditionalResourceMovementLogDetails()}
                            </ul>
                        </div>
                    ) : null}
                </BasicCard>
            );
        }

        if (this.props.log.status === "Kingdom has not been walked") {
            return (
                <BasicCard>
                    <div className="text-right cursor-pointer text-red-500">
                        <button onClick={this.props.close_details}>
                            <i className="fas fa-minus-circle"></i>
                        </button>
                    </div>
                    <div className="my-4">
                        <h3 className="mb-4">{this.props.log.status}</h3>
                        <p className="my-4 text-red-600 dark:text-red-500">
                            You have not visited your kingdom in the last 90
                            days. So it was handed to The Old Man and made into
                            an NPC Kingdom.
                        </p>
                        <dl className="my-4">
                            <dt>Kingdom Name</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.name
                                }
                            </dd>
                            <dt>Kingdom Location</dt>
                            <dd>
                                (X/Y){" "}
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.x
                                }{" "}
                                /{" "}
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.y
                                }
                            </dd>
                            <dt>On Map</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.game_map_name
                                }
                            </dd>
                            <dt>Reason</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.reason
                                }
                            </dd>
                        </dl>
                        <InfoAlert additional_css={"my-4"}>
                            <h4>Walking your kingdoms</h4>
                            <p className="my-4">
                                Kingdoms have to be walked at least once in a 90
                                day period or they get handed over to The Old
                                Man. What it means to walk a kingdom is to
                                physically visit the kingdom to consider it
                                "walked".
                            </p>
                        </InfoAlert>
                    </div>
                </BasicCard>
            );
        }

        if (this.props.log.status === "Kingdom was overpopulated") {
            return (
                <BasicCard>
                    <div className="text-right cursor-pointer text-red-500">
                        <button onClick={this.props.close_details}>
                            <i className="fas fa-minus-circle"></i>
                        </button>
                    </div>
                    <div className="my-4">
                        <h3 className="mb-4">{this.props.log.status}</h3>
                        <p className="my-4 text-red-600 dark:text-red-500">
                            You kingdom was overpopulated. The Old Man took it
                            and demolished it.
                        </p>
                        <dl className="my-4">
                            <dt>Kingdom Name</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.name
                                }
                            </dd>
                            <dt>Kingdom Location</dt>
                            <dd>
                                (X/Y){" "}
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.x
                                }{" "}
                                /{" "}
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.y
                                }
                            </dd>
                            <dt>On Map</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.game_map_name
                                }
                            </dd>
                            <dt>Reason</dt>
                            <dd>
                                {
                                    this.props.log.additional_details
                                        .kingdom_data.reason
                                }
                            </dd>
                        </dl>
                        <InfoAlert additional_css={"my-4"}>
                            <h4>Over Population</h4>
                            <p className="my-4">
                                Kingdoms can purchase additional population for
                                recruiting large amount of units, but one should
                                becarfeul because if you have more then your max
                                at the hourly reset The Old Man will stomp
                                around. he will attempt to:
                            </p>
                            <ul className="my-4 list-disc">
                                <li className="ml-4">
                                    Take the cost our of your gold bars
                                </li>
                                <li className="ml-4">
                                    If you have none, he will take it from your
                                    treasury.
                                </li>
                                <li className="ml-4">
                                    If you have none, he will take it from your
                                    own gold.
                                </li>
                                <li className="ml-4">
                                    If you have none, he will destroy the
                                    kingdom.
                                </li>
                            </ul>
                        </InfoAlert>
                    </div>
                </BasicCard>
            );
        }

        return (
            <BasicCard>
                <div className="text-right cursor-pointer text-red-500">
                    <button onClick={this.props.close_details}>
                        <i className="fas fa-minus-circle"></i>
                    </button>
                </div>
                <div className="my-4">
                    <h3 className="mb-4">{this.props.log.status}</h3>

                    <dl>
                        <dt>Kingdom Attacked (X/Y)</dt>
                        <dd
                            className={clsx({
                                "text-green-600 dark:text-green-400":
                                    !this.props.log.is_mine,
                                "text-red-600 dark:text-red-400":
                                    this.props.log.is_mine,
                            })}
                        >
                            {this.props.log.to_kingdom_name}{" "}
                            {this.props.log.to_x} / {this.props.log.to_y}
                        </dd>
                        <dt>Attacked From (X/Y)</dt>
                        <dd
                            className={clsx({
                                "text-green-600 dark:text-green-400":
                                    this.props.log.is_mine,
                                "text-red-600 dark:text-red-400":
                                    !this.props.log.is_mine,
                            })}
                        >
                            {this.props.log.from_kingdom_name !== null
                                ? this.props.log.from_kingdom_name +
                                  " " +
                                  this.props.log.from_x +
                                  "/" +
                                  this.props.log.from_y
                                : "N/A"}
                        </dd>
                        <dt
                            className={
                                this.props.log.took_kingdom ? "hidden" : ""
                            }
                        >
                            Kingdom Attacked Morale Loss
                        </dt>
                        <dd
                            className={
                                "text-red-600 dark:text-red-400 " +
                                this.props.log.took_kingdom
                                    ? "hidden"
                                    : ""
                            }
                        >
                            {(this.props.log.morale_loss * 100).toFixed(2)} %
                        </dd>
                    </dl>

                    <div
                        className={
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 " +
                            (!this.props.log.took_kingdom ? "hidden" : "")
                        }
                    ></div>

                    <p className={!this.props.log.took_kingdom ? "hidden" : ""}>
                        You now own this kingdom. You took it from the defender.
                        Check your kingdoms list. Any surviving units are now
                        held up here.
                    </p>
                </div>
                <div
                    className={
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 " +
                        this.props.log.took_kingdom
                            ? "hidden"
                            : ""
                    }
                ></div>
                <div className={this.props.log.took_kingdom ? "hidden" : ""}>
                    <div
                        className={
                            "grid md:grid-cols-" +
                            (this.shouldShowUnitSentChanges() ? "3" : "2") +
                            " gap-2"
                        }
                    >
                        <div>
                            <h3 className="mb-4">Building Changes</h3>
                            <dl>{this.renderBuildingChanges()}</dl>
                        </div>
                        {this.props.log.old_units.length === 0 &&
                        this.props.log.new_units.length === 0 ? (
                            <div>
                                <h3 className="mb-4">Unit Changes</h3>
                                <p>There were no changes in kingdom units.</p>
                            </div>
                        ) : (
                            <div>
                                <h3 className="mb-4">Unit Changes</h3>
                                <dl>{this.renderUnitChanges()}</dl>
                            </div>
                        )}

                        {this.shouldShowUnitSentChanges() ? (
                            <div>
                                <h3 className="mb-4">Attacking Unit Changes</h3>
                                <dl>{this.renderUnitsSentChange()}</dl>
                            </div>
                        ) : null}
                    </div>
                </div>
            </BasicCard>
        );
    }
}
