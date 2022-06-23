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
        console.log(unit);
    }

    render() {
        return (
            <Table data={this.props.units} columns={BuildUnitsColumns(this.viewUnit.bind(this))} dark_table={this.props.dark_tables}/>
        )
    }
}
