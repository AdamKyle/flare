import React, {Fragment} from "react";
import Table from "../../../components/ui/data-tables/table";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";
import {buildBuildingsColumns} from "../../../lib/game/kingdoms/build-buildings-columns";
import BuildingsTableProps from "resources/js/game/lib/game/kingdoms/types/buildings-table-props";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BuildingsTableState from "../../../lib/game/kingdoms/types/buildings-table-state";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class BuildingsTable extends React.Component<BuildingsTableProps, BuildingsTableState> {

    constructor(props: BuildingsTableProps) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            loading: false,
        }
    }

    viewBuilding(building: BuildingDetails) {
        this.props.view_building(building);
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: BuildingDetails) => row.is_locked,
                style: {
                    backgroundColor: '#f87171',
                    color: 'white',
                }
            }
        ];
    }

    cancelBuildingQueue(queueId: number|null) {
        if (queueId === null) {
            return;
        }

        this.setState({
            loading: true,
            success_message: null,
            error_message: null,
        }, () => {
            (new Ajax()).setRoute('kingdoms/building-upgrade/cancel').setParameters({
                queue_id: queueId
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    success_message: response.data.message,
                    loading: false,
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                        loading: false,
                    });
                }
            });
        });
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.error_message !== null ?
                        <div className='mt-4 mb-4'>
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        </div>
                    : null
                }
                {
                    this.state.success_message !== null ?
                        <div className='mt-4 mb-4'>
                            <SuccessAlert>
                                {this.state.success_message}
                            </SuccessAlert>
                        </div>
                    : null
                }
                {
                    this.state.loading ?
                        <div className='mt-4 mb-4'>
                            <LoadingProgressBar />
                        </div>
                    : null
                }
                <Table data={this.props.buildings}
                       columns={buildBuildingsColumns(this.viewBuilding.bind(this), this.cancelBuildingQueue.bind(this), this.props.buildings_in_queue)}
                       dark_table={this.props.dark_tables}
                       conditional_row_styles={this.createConditionalRowStyles()}
                />
            </Fragment>
        )
    }
}
