import React, { ReactNode } from "react";
import { serviceContainer } from "../../../../../lib/containers/core-container";
import FetchUnitQueuesAjax from "../../../ajax/fetch-unit-queues-ajax";
import SuccessAlert from "../../../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../../ui/progress-bars/loading-progress-bar";
import CapitalCityUnitQueueTableEventDefinition from "../../../event-listeners/capital-city-unit-queue-table-event-definition";
import CapitalCityUnitQueuesTableEvent from "../../../event-listeners/capital-city-unit-queues-table-event";
import debounce from "lodash/debounce";
import clsx from "clsx";
import { capitalize } from "lodash";
import TimerProgressBar from "../../../../ui/progress-bars/timer-progress-bar";
import DangerOutlineButton from "../../../../ui/buttons/danger-outline-button";
import { QueueStatus } from "../../enums/queue-status";
import InfoAlert from "../../../../ui/alerts/simple-alerts/info-alert";
import OrangeButton from "../../../../ui/buttons/orange-button";
import { CancellationType } from "../../enums/cancellation-type";
import SendUnitRequestCancellationRequestModal from "../../modals/send-unit-request-cancellation-request-modal";
import { UnitRecruitmentProps } from "../../types/unit-recruitment-props";
import UnitRecruitmentState from "../../types/unit-recruitment-state";
import Unit from "../../deffinitions/unit";

export default class UnitQueue extends React.Component<
    UnitRecruitmentProps,
    UnitRecruitmentState
> {
    private fetchUnitQueueAjax: FetchUnitQueuesAjax;
    private unitQueueListener: CapitalCityUnitQueueTableEventDefinition;

    constructor(props: UnitRecruitmentProps) {
        super(props);

        this.fetchUnitQueueAjax = serviceContainer().fetch(FetchUnitQueuesAjax);

        this.unitQueueListener =
            serviceContainer().fetch<CapitalCityUnitQueueTableEventDefinition>(
                CapitalCityUnitQueuesTableEvent,
            );

        this.unitQueueListener.initialize(this, this.props.user_id);
        this.unitQueueListener.register();

        this.state = {
            loading: true,
            unit_queues: [],
            filtered_unit_queues: [],
            search_query: "",
            error_message: "",
            success_message: "",
            open_kingdom_ids: new Set<number>(),
            cancellation_modal: {
                open: false,
                type: null,
                unit_details: null,
                kingdom_id: null,
                queue_id: null,
            },
        };
    }

    componentDidMount(): void {
        this.fetchUnitQueueAjax.fetchUnitQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.unitQueueListener.listen();
    }

    componentDidUpdate(prevProps: any, prevState: any): void {
        if (prevState.unit_queues !== this.state.unit_queues) {
            this.updateFilteredUnitData();
        }
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredUnitData();
    }, 300);

    toggleDetails(kingdomId: number): void {
        this.setState((prevState: UnitRecruitmentState) => {
            const newOpenKingdomIds = new Set(prevState.open_kingdom_ids);
            if (newOpenKingdomIds.has(kingdomId)) {
                newOpenKingdomIds.delete(kingdomId);
            } else {
                newOpenKingdomIds.add(kingdomId);
            }
            return { open_kingdom_ids: newOpenKingdomIds };
        });
    }

    updateFilteredUnitData(): void {
        const searchTerm = this.state.search_query.toLowerCase().trim();
        let openKingdomIds = new Set<number>();

        let filteredUnitData = this.state.unit_queues.filter((kingdom) => {
            return (
                (kingdom.kingdom_name.toLowerCase().includes(searchTerm) ||
                    kingdom.map_name.toLowerCase().includes(searchTerm)) &&
                kingdom.unit_requests.length > 0
            );
        });

        if (filteredUnitData.length <= 0 && searchTerm.length > 0) {
            filteredUnitData = this.state.unit_queues
                .map((kingdom) => {
                    const matchingUnits = kingdom.unit_requests.filter((unit) =>
                        unit.unit_name.toLowerCase().includes(searchTerm),
                    );

                    if (matchingUnits.length > 0) {
                        openKingdomIds.add(kingdom.kingdom_id);
                        return { ...kingdom, unit_requests: matchingUnits };
                    }
                    return null;
                })
                .filter((kingdom) => kingdom !== null);
        }

        openKingdomIds = this.resolveOpenKingdomIds(openKingdomIds);

        this.setState({
            filtered_unit_queues: filteredUnitData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    resolveOpenKingdomIds(newOpenKingdomIds: Set<number>): Set<number> {
        const oldOpenKingdomIds = this.state.open_kingdom_ids;

        if (newOpenKingdomIds.size === 0 && oldOpenKingdomIds.size > 0) {
            oldOpenKingdomIds.forEach((id) => {
                const kingdomExists = this.state.unit_queues.some(
                    (kingdom) => kingdom.kingdom_id === id,
                );
                if (!kingdomExists) {
                    oldOpenKingdomIds.delete(id);
                }
            });
            return oldOpenKingdomIds;
        }

        if (newOpenKingdomIds.size === oldOpenKingdomIds.size) {
            return oldOpenKingdomIds;
        }

        return newOpenKingdomIds.size > oldOpenKingdomIds.size
            ? newOpenKingdomIds
            : oldOpenKingdomIds;
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>): void {
        const searchTerm = event.target.value;
        this.setState({ search_query: searchTerm });
        this.debouncedUpdateFilteredData();
    }

    renderUnitStatus(unit: any): string {
        return capitalize(unit.secondary_status);
    }

    openCancellationModal(
        kingdom: any,
        unit: any | null,
        type: CancellationType,
    ) {
        this.setState({
            cancellation_modal: {
                open: true,
                type: type,
                unit_details: unit,
                kingdom_id: kingdom.kingdom_id,
                queue_id: kingdom.queue_id,
            },
        });
    }

    closeCancellationModal() {
        this.setState({
            cancellation_modal: {
                open: false,
                type: null,
                unit_details: null,
                kingdom_id: null,
                queue_id: null,
            },
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        console.log(this.state.cancellation_modal);

        return (
            <div className="md:p-4">
                {this.state.success_message && (
                    <SuccessAlert additional_css="my-2">
                        {this.state.success_message}
                    </SuccessAlert>
                )}

                {this.state.error_message && (
                    <DangerAlert additional_css="my-2">
                        {this.state.error_message}
                    </DangerAlert>
                )}

                <input
                    type="text"
                    value={this.state.search_query}
                    onChange={(e) => this.handleSearchChange(e)}
                    placeholder="Search by kingdom or unit name"
                    className="w-full my-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom or unit name"
                />

                {this.state.filtered_unit_queues.map((kingdom: any) => (
                    <div key={kingdom.kingdom_id} className="mb-4">
                        <div
                            className={clsx(
                                "p-4 bg-gray-100 dark:bg-gray-700 shadow-md cursor-pointer",
                                {
                                    "rounded-lg":
                                        !this.state.open_kingdom_ids.has(
                                            kingdom.kingdom_id,
                                        ),
                                    "rounded-t-lg":
                                        this.state.open_kingdom_ids.has(
                                            kingdom.kingdom_id,
                                        ),
                                },
                            )}
                            onClick={() =>
                                this.toggleDetails(kingdom.kingdom_id)
                            }
                        >
                            <div className="flex justify-between items-center">
                                <div>
                                    <h2 className="text-xl font-bold dark:text-white">
                                        {kingdom.kingdom_name}
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300">
                                        Status:{" "}
                                        <span
                                            className={clsx({
                                                "text-green-700 dark:text-green-500":
                                                    [
                                                        QueueStatus.TRAVELING,
                                                        QueueStatus.RECRUITING,
                                                        QueueStatus.FINISHED,
                                                    ].includes(kingdom.status),
                                                "text-red-700 dark:text-red-500":
                                                    [
                                                        QueueStatus.REJECTED,
                                                        QueueStatus.CANCELLED,
                                                    ].includes(kingdom.status),
                                                "text-blue-700 dark:text-500": [
                                                    QueueStatus.REQUESTING,
                                                ].includes(kingdom.status),
                                            })}
                                        >
                                            {capitalize(kingdom.status)}
                                        </span>
                                    </p>
                                </div>
                                <i
                                    className={`fas fa-chevron-${this.state.open_kingdom_ids.has(kingdom.kingdom_id) ? "down" : "up"} text-gray-500 dark:text-gray-400`}
                                />
                            </div>
                            <TimerProgressBar
                                time_remaining={kingdom.total_time}
                                time_out_label={"Total Time Left"}
                            />
                        </div>

                        {this.state.open_kingdom_ids.has(
                            kingdom.kingdom_id,
                        ) && (
                            <div className="bg-gray-300 dark:bg-gray-600 p-4">
                                <InfoAlert additional_css="my-2">
                                    <p className="mb-2">
                                        You may only cancel unit recruitment
                                        when the order is traveling and has at
                                        least more then 1 minute in time left.
                                        Trying to do so at any other time such
                                        as requesting resources or recruiting
                                        can throw the kingdom into chaos.
                                    </p>
                                    <p>
                                        Unit that are canceled or rejected will
                                        be at the bottom of the list. Once the
                                        entire process is finished, the log will
                                        indicate why some were rejected or that
                                        you canceled some unit requests.
                                    </p>
                                </InfoAlert>

                                <OrangeButton
                                    on_click={() => {
                                        this.openCancellationModal(
                                            kingdom,
                                            null,
                                            CancellationType.CANCEL_ALL,
                                        );
                                    }}
                                    button_label={"Cancel All Requests"}
                                    additional_css="my-4 w-full"
                                    disabled={
                                        kingdom.total_time <= 60 ||
                                        kingdom.status !== QueueStatus.TRAVELING
                                    }
                                />
                                {kingdom.unit_requests.map((unit: Unit) => (
                                    <div
                                        key={unit.queue_id}
                                        className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                    >
                                        <h3 className="text-lg font-semibold dark:text-white">
                                            {unit.unit_name}
                                        </h3>
                                        <p className="text-gray-700 dark:text-gray-300 mb-2">
                                            Amount to Recruit:{" "}
                                            {unit.amount_to_recruit}
                                        </p>
                                        <p className="text-gray-700 dark:text-gray-300 mb-2">
                                            Status:{" "}
                                            <span
                                                className={clsx({
                                                    "text-green-700 dark:text-green-500":
                                                        [
                                                            QueueStatus.TRAVELING,
                                                            QueueStatus.RECRUITING,
                                                        ].includes(
                                                            unit.secondary_status,
                                                        ),
                                                    "text-red-700 dark:text-red-500":
                                                        [
                                                            QueueStatus.REJECTED,
                                                            QueueStatus.CANCELLED,
                                                        ].includes(
                                                            unit.secondary_status,
                                                        ),
                                                    "text-blue-700 dark:text-500":
                                                        [
                                                            QueueStatus.REQUESTING,
                                                        ].includes(
                                                            unit.secondary_status,
                                                        ),
                                                })}
                                            >
                                                {this.renderUnitStatus(unit)}
                                            </span>
                                        </p>
                                        <DangerOutlineButton
                                            on_click={() => {
                                                this.openCancellationModal(
                                                    kingdom,
                                                    unit,
                                                    CancellationType.SINGLE_CANCEL,
                                                );
                                            }}
                                            button_label={"Cancel Request"}
                                            disabled={
                                                kingdom.total_time <= 60 ||
                                                unit.secondary_status !==
                                                    QueueStatus.TRAVELING
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}

                {this.state.filtered_unit_queues.length <= 0 && (
                    <p>There are no units in queue</p>
                )}

                {this.state.cancellation_modal.open ? (
                    <SendUnitRequestCancellationRequestModal
                        kingdom_id={this.state.cancellation_modal.kingdom_id}
                        character_id={this.props.character_id}
                        is_open={this.state.cancellation_modal.open}
                        manage_modal={this.closeCancellationModal.bind(this)}
                        cancellation_type={this.state.cancellation_modal.type}
                        queue_id={this.state.cancellation_modal.queue_id}
                        unit_details={
                            this.state.cancellation_modal.unit_details
                        }
                    />
                ) : null}
            </div>
        );
    }
}
