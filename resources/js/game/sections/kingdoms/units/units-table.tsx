import React from "react";
import Table from "../../../components/ui/data-tables/table";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";
import {buildBuildingsColumns} from "../../../lib/game/kingdoms/build-buildings-columns";

export default class UnitsTable extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    viewBuilding(building: BuildingDetails) {
        console.log(building);
    }

    render() {
        return (
            <Table data={this.props.buildings} columns={buildBuildingsColumns(this.viewBuilding.bind(this))} dark_table={this.props.dark_tables}/>
        )
    }
}
