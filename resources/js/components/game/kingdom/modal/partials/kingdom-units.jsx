import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';

export default class KingdomBuildings extends React.Component {

    constructor(props) {
        super(props);

        this.columns = [
            {
                key: "name",
                text: "Name",
                sortable: true
            },
            {
                key: "level",
                text: "Level",
                sortable: true
            },
            {
                key: "attack",
                text: "Attack",
                sortable: true
            },
            {
                key: "current-amount",
                text: "Current Amount",
                cell: row => <div data-tag="allowRowEvents"><div key={row.id}>{this.getCurrentUnitAmount(row.id)}</div></div>,
                sortable: true
            },
            {
                key: "max-amount",
                text: "Max Recuitable Amount",
                cell: row => <div data-tag="allowRowEvents"><div key={row.id}>{this.props.kingdom.current_population}</div></div>,
                sortable: true
            },
            {
                key: "defence",
                text: "Defence",
                sortable: true
            },
            {
                key: "wood_cost",
                text: "Wood Cost",
                sortable: true
            },
            {
                key: "clay_cost",
                text: "Clay Cost",
                sortable: true
            },
            {
                key: "stone_cost",
                text: "Stone Cost",
                sortable: true
            },
            {
                key: "iron_cost",
                text: "Iron Cost",
                sortable: true
            },
        ];

        this.config = {
            page_size: 5,
            length_menu: [5, 10, 25],
            show_filter: true,
            show_pagination: true,
            pagination: 'advance',
        }
    }

    getCurrentUnitAmount(unitId) {
        return this.props.kingdom.current_units.filter((cu) => cu.game_unit_id === unitId)[0].amount;
    }

    render() {
        return (
            <div className="row mt-2">
                <div className="col-md-12">
                    <ReactDatatable
                        config={this.config}
                        records={this.props.kingdom.recruitable_units}
                        columns={this.columns}
                        onRowClicked={this.props.recruitUnit}        
                    />
                </div>
            </div>
        )
    }
}