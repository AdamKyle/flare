import React, { Fragment } from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import UnitDetails from "../../../lib/game/kingdoms/deffinitions/unit-details";
import UnitsInQueue from "../../../lib/game/kingdoms/deffinitions/units-in-queue";
import SmallUnitSectionProps from "../../../lib/game/kingdoms/types/small-unit-section-props";
import SmallUnitsSectionsState from "../../../lib/game/kingdoms/types/small-units-sections-state";
import UnitInformation from "./unit-information";
import UnitsTable from "./units-table";

export default class SmallUnitsSection extends React.Component<SmallUnitSectionProps, SmallUnitsSectionsState> {

    constructor(props: SmallUnitSectionProps) {
        super(props);

        this.state = {
            unit_to_view: null,
        }
    }

    closeSection() {
        this.setState({
            unit_to_view: null,
        })
    }

    viewSelectedBuilding(unit?: UnitDetails) {
        this.setState({
            unit_to_view: typeof unit !== 'undefined' ? unit : null,
        });
    }

    isUnitInQueue() {
        if (this.state.unit_to_view === null) {
            return false;
        }

        if (this.props.kingdom.unit_queue.length === 0) {
            return false;
        }

        return this.props.kingdom.unit_queue.filter((queue: UnitsInQueue) => {
            if (this.state.unit_to_view !== null) {
                return queue.game_unit_id === this.state.unit_to_view.id
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
                    this.state.unit_to_view !== null ?
                        <UnitInformation unit={this.state.unit_to_view}
                                         close={this.closeSection.bind(this)}
                                         kingdom_building_cost_reduction={this.props.kingdom.building_cost_reduction}
                                         kingdom_iron_cost_reduction={this.props.kingdom.iron_cost_reduction}
                                         kingdom_population_cost_reduction={this.props.kingdom.population_cost_reduction}
                                         kingdom_current_population={this.props.kingdom.current_population}
                                         kingdom_unit_time_reduction={this.props.kingdom.unit_time_reduction}
                                         unit_cost_reduction={this.props.kingdom.unit_cost_reduction}
                                         character_id={this.props.kingdom.character_id}
                                         is_in_queue={this.isUnitInQueue()}
                                         kingdom_id={this.props.kingdom.id}
                                         buildings={this.props.kingdom.buildings}
                                         character_gold={this.props.character_gold}
                        />
                        :
                        <BasicCard>
                            <UnitsTable units={this.props.kingdom.units}
                                        buildings={this.props.kingdom.buildings}
                                        dark_tables={this.props.dark_tables}
                                        view_unit={this.viewSelectedBuilding.bind(this)}
                                        units_in_queue={this.props.kingdom.unit_queue}
                                        current_units={this.props.kingdom.current_units}
                            />
                        </BasicCard>
                }
            </Fragment>
        )
    }
}
