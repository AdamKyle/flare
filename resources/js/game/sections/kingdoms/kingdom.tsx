import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import BuildingsTable from "./buildings/buildings-table";
import BuildingDetails from "../../lib/game/kingdoms/building-details";
import BuildingInformation from "./buildings/building-information";
import UnitsTable from "./units/units-table";
import BuildingInQueueDetails from "../../lib/game/kingdoms/building-in-queue-details";

export default class Kingdom extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            building_to_view: null,
        }
    }

    manageViewBuilding(building?: BuildingDetails) {
       this.setState({
           building_to_view: typeof building !== 'undefined' ? building : null
       });
    }

    isInQueue() {

        if (this.state.building_to_view === null) {
            return false;
        }

        if (this.props.kingdom.building_queue.length === 0) {
            return false;
        }


        return this.props.kingdom.building_queue.filter((queue: BuildingInQueueDetails) => {
            return queue.building_id === this.state.building_to_view.id
        }).length > 0;
    }

    render() {

        if (this.state.building_to_view !== null) {
            return <BuildingInformation building={this.state.building_to_view}
                                        close={this.manageViewBuilding.bind(this)}
                                        update_kingdoms={this.props.update_kingdoms}
                                        kingdom_building_time_reduction={this.props.kingdom.building_time_reduction}
                                        kingdom_building_cost_reduction={this.props.kingdom.building_cost_reduction}
                                        kingdom_iron_cost_reduction={this.props.kingdom.iron_cost_reduction}
                                        kingdom_building_pop_cost_reduction={this.props.kingdom.population_cost_reduction}
                                        kingdom_current_population={this.props.kingdom.current_population}
                                        character_id={this.props.kingdom.character_id}
                                        is_in_queue={this.isInQueue()}
            />
        }

        return (
            <Fragment>
                <div className='grid md:grid-cols-2 gap-4'>
                    <BasicCard additionalClasses={'max-h-[600px]'}>
                        <div className='text-right cursor-pointer text-red-500'>
                            <button onClick={this.props.close_details}><i className="fas fa-minus-circle"></i></button>
                        </div>
                        <KingdomDetails kingdom={this.props.kingdom} />
                    </BasicCard>

                    <div>
                        <BasicCard additionalClasses={'overflow-x-scroll'}>
                            <BuildingsTable buildings={this.props.kingdom.buildings}
                                            dark_tables={this.props.dark_tables}
                                            buildings_in_queue={this.props.kingdom.building_queue}
                                            view_building={this.manageViewBuilding.bind(this)}
                            />
                        </BasicCard>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <BasicCard>
                            <UnitsTable units={this.props.kingdom.units} dark_tables={this.props.dark_tables} />
                        </BasicCard>
                    </div>

                </div>
            </Fragment>
        )
    }
}
