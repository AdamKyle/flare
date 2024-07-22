import React, { Fragment } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../../lib/containers/core-container";
import CancelBuildingInQueueAjax from "../ajax/cancel-building-in-queue-ajax";
import { buildBuildingsColumns } from "../table-columns/build-buildings-columns";
import BuildingsTableProps from "../types/buildings-table-props";
import UpgradeTablesState from "../types/upgrade-tables-state";
import BuildingDetails from "./deffinitions/building-details";

export default class BuildingsTable extends React.Component<
    BuildingsTableProps,
    UpgradeTablesState
> {
    private cancelBuildingQueueAjax: CancelBuildingInQueueAjax;

    constructor(props: BuildingsTableProps) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            loading: false,
        };

        this.cancelBuildingQueueAjax = serviceContainer().fetch(
            CancelBuildingInQueueAjax,
        );
    }

    viewBuilding(building: BuildingDetails) {
        this.props.view_building(building);
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: BuildingDetails) => row.is_locked,
                style: {
                    backgroundColor: "#f4a0a0",
                    color: "white",
                },
            },
        ];
    }

    cancelBuildingQueue(queueId: number | null) {
        if (queueId === null) {
            return;
        }

        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.cancelBuildingQueueAjax.cancelQueue(this, queueId);
            },
        );
    }

    render() {
        return (
            <Fragment>
                {this.state.error_message !== null ? (
                    <div className="mt-4 mb-4">
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                    </div>
                ) : null}
                {this.state.success_message !== null ? (
                    <div className="mt-4 mb-4">
                        <SuccessAlert>
                            {this.state.success_message}
                        </SuccessAlert>
                    </div>
                ) : null}
                {this.state.loading ? (
                    <div className="mt-4 mb-4">
                        <LoadingProgressBar />
                    </div>
                ) : null}
                <div
                    className={"max-w-[390px] md:max-w-full overflow-x-hidden"}
                >
                    <Table
                        data={this.props.buildings}
                        columns={buildBuildingsColumns(
                            this.viewBuilding.bind(this),
                            this.cancelBuildingQueue.bind(this),
                            this.props.buildings_in_queue,
                            this.props.view_port,
                        )}
                        dark_table={this.props.dark_tables}
                        conditional_row_styles={this.createConditionalRowStyles()}
                    />
                </div>
            </Fragment>
        );
    }
}
