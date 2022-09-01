import React, {Fragment} from "react";
import Table from "../../../components/ui/data-tables/table";
import {BuildUnitsInMovementColumns} from "../../../lib/game/kingdoms/build-units-in-movement-columns";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import UnitsInMovementTableProps from "../../../lib/game/kingdoms/types/units-in-movement-table-props";
import UnitsInMovementTableState from "../../../lib/game/kingdoms/types/units-in-movement-table-state";

export default class UnitsMovementTable extends React.Component<UnitsInMovementTableProps, UnitsInMovementTableState> {
    constructor(props: UnitsInMovementTableProps) {
        super(props);

        this.state = {
            loading: false,
            error_message: '',
            success_message: '',
        }
    }

    cancelUnitRecruitment(queueId: number) {
        console.log(queueId);
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
                    this.state.success_message !== '' ?
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

                <Table data={this.props.units_in_movement}
                       columns={BuildUnitsInMovementColumns(this.cancelUnitRecruitment.bind(this), this.props.units_in_movement)}
                       dark_table={this.props.dark_tables}
                />
            </Fragment>

        );
    }
}
