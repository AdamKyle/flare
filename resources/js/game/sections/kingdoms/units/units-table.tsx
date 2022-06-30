import React from "react";
import Table from "../../../components/ui/data-tables/table";
import UnitsTableProps from "../../../lib/game/kingdoms/types/units-table-props";
import {BuildUnitsColumns} from "../../../lib/game/kingdoms/build-units-columns";
import UnitDetails from "../../../lib/game/kingdoms/unit-details";

export default class UnitsTable extends React.Component<UnitsTableProps, any> {

    constructor(props: any) {
        super(props);
    }

    viewUnit(unit: UnitDetails) {
        this.props.view_unit(unit);
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: UnitDetails) => row.required_building_level < row.recruited_from.level,
                style: {
                    backgroundColor: '#f87171',
                    color: 'white',
                }
            }
        ];
    }

    render() {
        return (
            <Table data={this.props.units}
                   conditional_row_styles={this.createConditionalRowStyles()}
                   columns={BuildUnitsColumns(this.viewUnit.bind(this), this.props.units_in_queue, this.props.current_units)}
                   dark_table={this.props.dark_tables}
            />
        )
    }
}
