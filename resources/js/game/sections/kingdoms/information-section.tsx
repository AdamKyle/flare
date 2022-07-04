import React from "react";
import InformationSectionProps from "../../lib/game/kingdoms/information-section-props";
import BuildingInformation from "./buildings/building-information";
import UnitInformation from "./units/unit-information";

export default class InformationSection extends React.Component<InformationSectionProps, any> {

    constructor(props: InformationSectionProps) {
        super(props);
    }

    render() {
        if (this.props.sections.building_to_view !== null) {
            return <BuildingInformation building={this.props.sections.building_to_view}
                                        close={this.props.close}
                                        kingdom_building_time_reduction={this.props.cost_reduction.kingdom_building_time_reduction}
                                        kingdom_building_cost_reduction={this.props.cost_reduction.kingdom_building_cost_reduction}
                                        kingdom_iron_cost_reduction={this.props.cost_reduction.kingdom_iron_cost_reduction}
                                        kingdom_population_cost_reduction={this.props.cost_reduction.kingdom_population_cost_reduction}
                                        kingdom_current_population={this.props.cost_reduction.kingdom_current_population}
                                        character_id={this.props.character_id}
                                        is_in_queue={this.props.queue.is_building_in_queue}
            />
        }

        if (this.props.sections.unit_to_view !== null) {
            return <UnitInformation unit={this.props.sections.unit_to_view}
                                    close={this.props.close}
                                    kingdom_building_time_reduction={this.props.cost_reduction.kingdom_building_time_reduction}
                                    kingdom_building_cost_reduction={this.props.cost_reduction.kingdom_building_cost_reduction}
                                    kingdom_iron_cost_reduction={this.props.cost_reduction.kingdom_iron_cost_reduction}
                                    kingdom_population_cost_reduction={this.props.cost_reduction.kingdom_population_cost_reduction}
                                    kingdom_current_population={this.props.cost_reduction.kingdom_current_population}
                                    unit_cost_reduction={this.props.cost_reduction.kingdom_unit_cost_reduction}
                                    character_id={this.props.character_id}
                                    is_in_queue={this.props.queue.is_unit_in_queue}
                                    kingdom_id={this.props.kingdom_id}
                                    buildings={this.props.buildings}
            />
        }

        return null;
    }
}
