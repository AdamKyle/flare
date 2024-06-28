import React from "react";
import FetchBuildingQueuesAjax from "../ajax/fetch-building-queues-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import Table from "../../ui/data-tables/table";
import { buildSmallCouncilBuildingsQueuesTableColumns } from "../table-columns/build-small-council-building-queues-table-columns";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import CapitalCityBuildingQueueTableEventDefinition from "../event-listeners/capital-city-building-queue-table-event-definition";
import CapitalCityBuildingQueuesTableEvent from "../event-listeners/capital-city-building-queues-table-event";

export default class BuildingQueuesTable extends React.Component<any, any> {
    private fetchBuildingQueueAjax: FetchBuildingQueuesAjax;

    private queueListener: CapitalCityBuildingQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            building_queues: [],
            view_port: 0,
        };

        this.fetchBuildingQueueAjax = serviceContainer().fetch(
            FetchBuildingQueuesAjax,
        );

        this.queueListener =
            serviceContainer().fetch<CapitalCityBuildingQueueTableEventDefinition>(
                CapitalCityBuildingQueuesTableEvent,
            );

        this.queueListener.initialize(this, this.props.user_id);

        this.queueListener.register();
    }

    componentDidMount() {
        viewPortWatcher(this);

        this.fetchBuildingQueueAjax.fetchQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.queueListener.listen();
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
                    columns={buildSmallCouncilBuildingsQueuesTableColumns(this)}
                    data={this.state.building_queues}
                    dark_table={false}
                />
            </div>
        );
    }
}
