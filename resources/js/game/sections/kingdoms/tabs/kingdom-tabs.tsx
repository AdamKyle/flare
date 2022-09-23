import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import BuildingsTable from "../buildings/buildings-table";
import UnitsTable from "../units/units-table";
import BasicCard from "../../../components/ui/cards/basic-card";
import React from "react";
import UnitMovement from "../unit-movement";

export default class KingdomTabs extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'buildings',
            name: 'Buildings'
        }, {
            key: 'units',
            name: 'Units',
        },{
            name: 'Unit Movement',
            key: 'unit-movement',
        }]
    }

    render() {
        return (
            <BasicCard>
                <Tabs tabs={this.tabs}>
                    <TabPanel key={'buildings'}>
                        <BuildingsTable buildings={this.props.kingdom.buildings}
                                        dark_tables={this.props.dark_tables}
                                        buildings_in_queue={this.props.kingdom.building_queue}
                                        view_building={this.props.manage_view_building}
                        />
                    </TabPanel>
                    <TabPanel key={'units'}>
                        <UnitsTable units={this.props.kingdom.units}
                                    buildings={this.props.kingdom.buildings}
                                    dark_tables={this.props.dark_tables}
                                    view_unit={this.props.manage_view_unit}
                                    units_in_queue={this.props.kingdom.unit_queue}
                                    current_units={this.props.kingdom.current_units}
                        />
                    </TabPanel>
                    <TabPanel key={'unit-movement'}>
                        <UnitMovement units_in_movement={this.props.kingdom.unitsInMovement} dark_tables={this.props.dark_tables} character_id={this.props.kingdom.character_id} />
                    </TabPanel>
                </Tabs>
            </BasicCard>
        )
    }
}
