import React from "react";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Table from "../../ui/data-tables/table";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import { buildSmallCouncilUnitQueuesTableColumns } from "../table-columns/build-small-council-unit-queues-table-columns";
import FetchUnitQueuesAjax from "../ajax/fetch-unit-queues-ajax";
import CapitalCityUnitQueueTableEventDefinition from "../event-listeners/capital-city-unit-queue-table-event-definition";
import CapitalCityUnitQueuesTableEvent from "../event-listeners/capital-city-unit-queues-table-event";
import { watchForDarkMode } from "../../ui/helpers/watch-for-dark-mode";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SendUnitRequestCancellationRequestModal from "./modals/send-unit-request-cancellation-request-modal";

export default class UnitQueuesTable extends React.Component<any, any> {
    private fetchUnitQueueData: FetchUnitQueuesAjax;

    private updateUnitQueueTableEvent: CapitalCityUnitQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            view_port: 0,
            unit_queues: [],
            dark_tables: false,
            show_cancellation_modal: false,
            success_message: null,
            error_message: null,
            unit_data_for_cancellation: null,
        };

        this.fetchUnitQueueData = serviceContainer().fetch(FetchUnitQueuesAjax);

        this.updateUnitQueueTableEvent =
            serviceContainer().fetch<CapitalCityUnitQueueTableEventDefinition>(
                CapitalCityUnitQueuesTableEvent,
            );

        this.updateUnitQueueTableEvent.initialize(this, this.props.user_id);

        this.updateUnitQueueTableEvent.register();
    }

    componentDidMount() {
        viewPortWatcher(this);
        watchForDarkMode(this);

        this.fetchUnitQueueData.fetchUnitQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.updateUnitQueueTableEvent.listen();
    }

    manageCancelModal(unitId?: number, kingdomId?: number): void {
        let unitData: any = null;

        if (unitId && kingdomId) {
            const foundData = this.state.unit_queues.filter((queue: any) => {
                return (
                    queue.unit_id === unitId && queue.kingdom_id === kingdomId
                );
            });

            if (foundData.length > 0) {
                unitData = foundData[0];
            }
        }

        this.setState({
            show_cancellation_modal: !this.state.show_cancellation_modal,
            unit_data_for_cancellation: unitData,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                {this.state.success_message !== null ? (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                ) : null}

                {this.state.error_message !== null ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}

                <Table
                    columns={buildSmallCouncilUnitQueuesTableColumns(this)}
                    data={this.state.unit_queues}
                    dark_table={this.state.dark_tables}
                />

                {this.state.show_cancellation_modal ? (
                    <SendUnitRequestCancellationRequestModal
                        is_open={this.state.show_cancellation_modal}
                        manage_modal={this.manageCancelModal.bind(this)}
                        queue_data={this.state.unit_data_for_cancellation}
                        character_id={this.props.character_id}
                    />
                ) : null}
            </div>
        );
    }
}
