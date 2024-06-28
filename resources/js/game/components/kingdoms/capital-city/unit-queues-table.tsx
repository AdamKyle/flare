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
import { buildSmallCouncilUnitQueuesTableColumns } from "../table-columns/build-small-council-unit-queues-table-columns";

export default class UnitQueuesTable extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            view_port: 0,
        };
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    render() {
        return (
            <div>
                <Table
                    columns={buildSmallCouncilUnitQueuesTableColumns(this)}
                    data={this.props.unit_queues}
                    dark_table={false}
                />
            </div>
        );
    }
}
