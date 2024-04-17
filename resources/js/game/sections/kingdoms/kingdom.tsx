import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import BuildingDetails from "./buildings/deffinitions/building-details";
import BuildingInQueueDetails from "../../lib/game/kingdoms/building-in-queue-details";
import UnitDetails from "../../lib/game/kingdoms/unit-details";
import UnitsInQueue from "../../lib/game/kingdoms/units-in-queue";
import KingdomTabs from "./tabs/kingdom-tabs";
import InformationSection from "./information-section";
import InfoAlert from "../../components/ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import KingdomEventListener from "../../lib/game/event-listeners/kingdom-event-listener";
import {serviceContainer} from "../../lib/containers/core-container";
import UpdateKingdomListeners from "../../lib/game/event-listeners/game/update-kingdom-listeners";

export default class Kingdom extends React.Component<KingdomProps, any> {

    private updateKingdomListener: KingdomEventListener;

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            loading: true,
            building_to_view: null,
            unit_to_view: null,
            error_message: null,
            kingdom: null,
        }

        this.updateKingdomListener = serviceContainer().fetch<KingdomEventListener>(UpdateKingdomListeners);

        this.updateKingdomListener.initialize(this, this.props.user_id);

        this.updateKingdomListener.register();
    }

    componentDidMount() {
        (new Ajax()).setRoute('player-kingdom/'+this.props.kingdom.character_id+'/' + this.props.kingdom.id)
            .doAjaxCall('GET', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    kingdom: result.data.kingdom,
                });
            }, (error: AxiosError) => {

                this.setState({loading: false});

                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    })
                }
            });

        this.updateKingdomListener.listen();
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

        if (this.state.kingdom.building_queue.length === 0) {
            return false;
        }


        return this.state.kingdom.building_queue.filter((queue: BuildingInQueueDetails) => {
            return queue.building_id === this.state.building_to_view.id
        }).length > 0;
    }

    isUnitInQueue() {
        if (this.state.unit_to_view === null) {
            return false;
        }

        if (this.state.kingdom.unit_queue.length === 0) {
            return false;
        }

        return this.state.kingdom.unit_queue.filter((queue: UnitsInQueue) => {
            return queue.game_unit_id === this.state.unit_to_view.id
        }).length > 0;
    }

    render() {
        if (this.state.loading && this.state.kingdom === null) {
            return <LoadingProgressBar />
        }

        return (
            <Fragment>
                {
                    this.state.kingdom.is_protected ?
                        <InfoAlert additional_css={'mt-4 mb-4'}>
                            Your kingdom is under protection from attacks for the next: {this.state.kingdom.protected_days_left} day(s).
                            This value does not include today.
                        </InfoAlert>
                    : null
                }
                <div className='grid md:grid-cols-2 gap-4'>
                    <BasicCard additionalClasses={'max-h-[700px]'}>
                        <div className='text-right cursor-pointer text-red-500'>
                            <button onClick={this.props.close_details}><i className="fas fa-minus-circle"></i></button>
                        </div>
                        <KingdomDetails kingdom={this.state.kingdom} character_gold={this.props.character_gold} close_details={this.props.close_details}/>
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
                                        kingdom_building_time_reduction: this.state.kingdom.building_time_reduction,
                                        kingdom_building_cost_reduction: this.state.kingdom.building_cost_reduction,
                                        kingdom_iron_cost_reduction: this.state.kingdom.iron_cost_reduction,
                                        kingdom_population_cost_reduction: this.state.kingdom.population_cost_reduction,
                                        kingdom_current_population: this.state.kingdom.current_population,
                                        kingdom_unit_cost_reduction: this.state.kingdom.unit_cost_reduction,
                                        kingdom_unit_time_reduction: this.state.kingdom.unit_time_reduction,
                                    }}
                                    buildings={this.state.kingdom.buildings}
                                    queue={{
                                        is_building_in_queue: this.isInQueue(),
                                        is_unit_in_queue: this.isUnitInQueue(),
                                    }}
                                    character_id={this.state.kingdom.character_id}
                                    kingdom_id={this.state.kingdom.id}
                                    character_gold={this.props.character_gold}
                                    user_id={this.props.user_id}
                                />
                            :
                                <KingdomTabs
                                    kingdom={this.state.kingdom}
                                    kingdoms={this.props.kingdoms}
                                    dark_tables={this.props.dark_tables}
                                    manage_view_building={this.manageViewBuilding.bind(this)}
                                    manage_view_unit={this.manageViewUnit.bind(this)}
                                    view_port={this.props.view_port}
                                    user_id={this.props.user_id}
                                />
                        }

                    </div>
                </div>
            </Fragment>
        )
    }
}
