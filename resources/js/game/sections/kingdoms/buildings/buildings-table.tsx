import React from "react";
import Table from "../../../components/ui/data-tables/table";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";
import {buildBuildingsColumns} from "../../../lib/game/kingdoms/build-buildings-columns";
import BuildingsTableProps from "resources/js/game/lib/game/kingdoms/types/buildings-table-props";

export default class BuildingsTable extends React.Component<BuildingsTableProps, {}> {

    constructor(props: BuildingsTableProps) {
        super(props);
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

    render() {
        return (
            <Table data={this.props.buildings}
                   columns={buildBuildingsColumns(this.viewBuilding.bind(this), this.props.buildings_in_queue)}
                   dark_table={this.props.dark_tables}
                   conditional_row_styles={this.createConditionalRowStyles()}
            />
        )
    }
}
