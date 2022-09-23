import React, {Fragment} from "react";
import Table from "../../../components/ui/data-tables/table";
import {BuildUnitsInMovementColumns} from "../../../lib/game/kingdoms/build-units-in-movement-columns";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import UnitsInMovementTableProps from "../../../lib/game/kingdoms/types/units-in-movement-table-props";
import UnitsInMovementTableState from "../../../lib/game/kingdoms/types/units-in-movement-table-state";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";

export default class UnitsMovementTable extends React.Component<UnitsInMovementTableProps, UnitsInMovementTableState> {
    constructor(props: UnitsInMovementTableProps) {
        super(props);

        this.state = {
            loading: false,
            error_message: '',
        }
    }

    cancelUnitRecruitment(queueId: number) {
        this.setState({
            error_message: '',
            loading: true,
        }, () => {
            (new Ajax).setRoute('recall-units/'+queueId+'/' + this.props.character_id).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                })
            }, (error: AxiosError) => {
                this.setState({loading: false});

                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    });
                }
            })
        });
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.error_message !== '' ?
                        <div className='mt-4 mb-4'>
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
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

                <Table data={this.props.units_in_movement}
                       columns={BuildUnitsInMovementColumns(this.cancelUnitRecruitment.bind(this), this.props.units_in_movement)}
                       dark_table={this.props.dark_tables}
                />
            </Fragment>

        );
    }
}
