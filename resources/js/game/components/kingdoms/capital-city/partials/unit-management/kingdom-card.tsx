import clsx from "clsx";
import React, { ReactNode } from "react";
import KingdomCardProps from "../../types/partials/unit-management/kingdom-card-props";
import UnitQueue from "../../deffinitions/unit-queue";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../ui/alerts/simple-alerts/info-alert";

export default class KingdomCard extends React.Component<KingdomCardProps> {
    constructor(props: KingdomCardProps) {
        super(props);
    }

    showUnitsInQueue(kingdomId: number): ReactNode {
        const unitQueueLength = this.props.unit_queue.filter(
            (queue: UnitQueue) => {
                return queue.kingdom_id === kingdomId;
            },
        ).length;

        if (unitQueueLength <= 0) {
            return null;
        }

        return (
            <div className="mb-4 text-gray-700 dark:text-gray-300">
                Units in Queue:{" "}
                {this.props.get_kingdom_queue_summary(kingdomId)}
            </div>
        );
    }

    render() {
        const kingdom = this.props.kingdom;

        return (
            <div
                key={kingdom.id}
                className="bg-gray-100 dark:bg-gray-700 shadow-md rounded-lg overflow-hidden mb-4"
            >
                <div
                    className="p-4 flex justify-between items-center cursor-pointer"
                    onClick={() => this.props.manage_card_state(kingdom.id)}
                >
                    <div>
                        <div className="text-xl font-semibold">
                            {kingdom.name}
                        </div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                            {kingdom.game_map_name}
                        </div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                            Time from Capital City: {kingdom.time_to_kingdom}{" "}
                            Minute(s)
                        </div>
                        {this.showUnitsInQueue(kingdom.id)}
                    </div>
                    <div>
                        <i
                            className={clsx({
                                "fas fa-chevron-up":
                                    this.props.open_kingdom_ids.has(kingdom.id),
                                "fas fa-chevron-down":
                                    !this.props.open_kingdom_ids.has(
                                        kingdom.id,
                                    ),
                            })}
                        ></i>
                    </div>
                </div>
                {this.props.open_kingdom_ids.has(kingdom.id) && (
                    <div className="p-4">
                        <InfoAlert additional_css="mb-2">
                            You may only cancel unit requests when the order is
                            traveling. When you send the request we travel to
                            this kingdom and deliever the orders, you can see
                            this in your unit queue (above tab). If the kingdom
                            is requesting resources for those units or
                            recruiting those units, you cannot cancel the unit
                            or the entire request because it would throw the
                            kingdom into chaos.
                        </InfoAlert>
                        {kingdom.time_to_kingdom <= 1 ? (
                            <WarningAlert additional_css="mb-2">
                                <p>
                                    This kingdom is a minute away from your
                                    capital city. You wont be able to cancel any
                                    of the unit requests when you send orders to
                                    this kingdom.
                                </p>
                            </WarningAlert>
                        ) : null}
                        <div className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                            <input
                                type="number"
                                value={this.props.get_bulk_input_value(
                                    kingdom.id,
                                )}
                                onChange={(e) =>
                                    this.props.handle_bulk_manage_card_stateamount_change(
                                        e,
                                        kingdom.id,
                                    )
                                }
                                disabled={this.props.is_bulk_queue_disabled()}
                                placeholder="Bulk amount"
                                className={`w-full mb-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-200 disabled:text-gray-500 disabled:border-gray-300 disabled:cursor-not-allowed`}
                            />
                        </div>

                        {this.props
                            .fetch_units_to_show()
                            .map((unitType: string) => (
                                <div
                                    key={unitType}
                                    className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                >
                                    <div className="flex items-center mb-2">
                                        <span className="w-1/3 text-gray-700 dark:text-gray-300">
                                            {unitType}
                                        </span>
                                        <input
                                            type="number"
                                            value={this.props.get_unit_amount(
                                                kingdom.id,
                                                unitType,
                                            )}
                                            onChange={(e) =>
                                                this.props.handle_unit_amount_change(
                                                    kingdom.id,
                                                    unitType,
                                                    e.target.value,
                                                    false,
                                                )
                                            }
                                            placeholder="Amount"
                                            className="w-2/3 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            ))}
                    </div>
                )}
            </div>
        );
    }
}
