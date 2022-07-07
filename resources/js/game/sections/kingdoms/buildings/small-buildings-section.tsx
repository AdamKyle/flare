import React, {Fragment} from "react";
import BuildingInformation from "./building-information";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingsTable from "./buildings-table";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";
import BuildingInQueueDetails from "../../../lib/game/kingdoms/building-in-queue-details";
import SmallBuildingsSectionsProps from "../../../lib/game/kingdoms/types/small-buildings-sections-props";
import SmallBuildingsSectionsState from "../../../lib/game/kingdoms/types/small-buildings-sections-state";

export default class SmallBuildingsSection extends React.Component<SmallBuildingsSectionsProps, SmallBuildingsSectionsState> {

    constructor(props: SmallBuildingsSectionsProps) {
        super(props);

        this.state = {
            view_building: null,
        }
    }

    viewSelectedBuilding(building?: BuildingDetails) {
        this.setState({
            view_building: typeof building !== 'undefined' ? building : null,
        });
    }

    isInQueue() {

        if (this.state.view_building === null) {
            return false;
        }

        if (this.props.kingdom.building_queue.length === 0) {
            return false;
        }

        const self = this;

        return this.props.kingdom.building_queue.filter((queue: BuildingInQueueDetails) => {
            if (self.state.view_building !== null) {
                return queue.building_id === self.state.view_building.id
            }
        }).length > 0;
    }

    render() {
        return (
            <Fragment>

                <div className='text-right cursor-pointer  text-red-500 mb-4'>
                    <button onClick={this.props.close_selected}><i className="fas fa-minus-circle"></i></button>
                </div>

                {
                    this.state.view_building !== null ?
                        <BuildingInformation building={this.state.view_building}
                                             close={this.viewSelectedBuilding.bind(this)}
                                             kingdom_building_time_reduction={this.props.kingdom.building_time_reduction}
                                             kingdom_building_cost_reduction={this.props.kingdom.building_cost_reduction}
                                             kingdom_iron_cost_reduction={this.props.kingdom.iron_cost_reduction}
                                             kingdom_population_cost_reduction={this.props.kingdom.population_cost_reduction}
                                             kingdom_current_population={this.props.kingdom.current_population}
                                             character_id={this.props.kingdom.character_id}
                                             is_in_queue={this.isInQueue()}
                        />
                        :

                        <BasicCard additionalClasses={'overflow-x-auto'}>
                            <BuildingsTable buildings={this.props.kingdom.buildings}
                                            dark_tables={this.props.dark_tables}
                                            view_building={this.viewSelectedBuilding.bind(this)}
                                            buildings_in_queue={this.props.kingdom.building_queue}
                            />
                        </BasicCard>
                }
            </Fragment>
        )
    }
}
