import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import BuildingDetails from "../../lib/game/kingdoms/building-details";
import BuildingInQueueDetails from "../../lib/game/kingdoms/building-in-queue-details";
import UnitDetails from "../../lib/game/kingdoms/unit-details";
import UnitsInQueue from "../../lib/game/kingdoms/units-in-queue";
import KingdomTabs from "./tabs/kingdom-tabs";
import InformationSection from "./information-section";

export default class Kingdom extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            building_to_view: null,
            unit_to_view: null,
        }
    }

    manageViewBuilding(building?: BuildingDetails) {
       this.setState({
           building_to_view: typeof building !== 'undefined' ? building : null
       });
    }

    manageViewUnit(unit?: UnitDetails) {
        this.setState({
            unit_to_view: typeof unit !== 'undefined' ? unit : null
        });
    }

    closeSection() {
        this.setState({
            building_to_view: null,
            unit_to_view: null,
        })
    }

    isInQueue() {

        if (this.state.building_to_view === null) {
            return false;
        }

        if (this.props.kingdom.building_queue.length === 0) {
            return false;
        }


        return this.props.kingdom.building_queue.filter((queue: BuildingInQueueDetails) => {
            console.log(queue.building_id, this.state.building_to_view.id);
            return queue.building_id === this.state.building_to_view.id
        }).length > 0;
    }

    isUnitInQueue() {
        if (this.state.unit_to_view === null) {
            return false;
        }

        if (this.props.kingdom.unit_queue.length === 0) {
            return false;
        }

        return this.props.kingdom.unit_queue.filter((queue: UnitsInQueue) => {
            return queue.game_unit_id === this.state.unit_to_view.id
        }).length > 0;
    }

    render() {
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
                        {
                            this.state.building_to_view !== null || this.state.unit_to_view !== null ?
                                <InformationSection
                                    sections={{
                                        unit_to_view: this.state.unit_to_view,
                                        building_to_view: this.state.building_to_view,
                                    }}
                                    close={this.closeSection.bind(this)}
                                    cost_reduction={{
                                        kingdom_building_time_reduction: this.props.kingdom.building_time_reduction,
                                        kingdom_building_cost_reduction: this.props.kingdom.building_cost_reduction,
                                        kingdom_iron_cost_reduction: this.props.kingdom.iron_cost_reduction,
                                        kingdom_population_cost_reduction: this.props.kingdom.population_cost_reduction,
                                        kingdom_current_population: this.props.kingdom.current_population,
                                        kingdom_unit_cost_reduction: this.props.kingdom.unit_cost_reduction
                                    }}
                                    buildings={this.props.kingdom.buildings}
                                    queue={{
                                        is_building_in_queue: this.isInQueue(),
                                        is_unit_in_queue: this.isUnitInQueue(),
                                    }}
                                    character_id={this.props.kingdom.character_id}
                                    kingdom_id={this.props.kingdom.id}
                                />
                            :
                                <KingdomTabs
                                    kingdom={this.props.kingdom}
                                    dark_tables={this.props.dark_tables}
                                    manage_view_building={this.manageViewBuilding.bind(this)}
                                    manage_view_unit={this.manageViewUnit.bind(this)}
                                />
                        }

                    </div>
                </div>
            </Fragment>
        )
    }
}
