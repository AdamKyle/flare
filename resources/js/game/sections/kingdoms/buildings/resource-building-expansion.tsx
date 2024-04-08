import React from "react";
import BuildingDetails from "./deffinitions/building-details";
import ResourceBuildingExpansionProps from "./types/resource-building-expansion-props";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import ResourceBuildingExpansionState from "./types/resource-building-expansion-state";
import clsx from "clsx";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {formatNumber} from "../../../lib/game/format-number";
import Echo from "laravel-echo";
import {serviceContainer} from "../../../lib/containers/core-container";
import GameEventListeners from "../../../lib/game/event-listeners/game-event-listeners";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";

export default class ResourceBuildingExpansion extends React.Component<ResourceBuildingExpansionProps, ResourceBuildingExpansionState> {

    private gameEventListener: CoreEventListener;

    private updateExpansionDetails: any;

    constructor(props: ResourceBuildingExpansionProps) {
        super(props);

        this.state = {
            loading: true,
            expanding: false,
            error_message: null,
            success_message: null,
            time_remaining_for_expansion: 0,
            expansion_details: null,
        }

        this.gameEventListener = serviceContainer().fetch(CoreEventListener);

        this.gameEventListener.initialize();

        this.updateExpansionDetails = this.gameEventListener.getEcho().private("update-building-expansion-details-" + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('kingdom/building-expansion/details/'+this.props.building.id+'/' + this.props.character_id)
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    expansion_details: result.data.expansion_details,
                    time_remaining_for_expansion: result.data.time_left,
                })
            }, (error: AxiosError) => {

                this.setState({ loading: false });

                if (typeof error.response != 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    });
                }
            });

        this.updateExpansionDetails.listen(
            "Game.Kingdoms.Events.UpdateBuildingExpansion",
            (event: any) => {
                this.setState({
                    expansion_details: event.kingdomBuildingExpansion,
                    time_remaining_for_expansion: event.timeLeft
                });
            }
        );
    }

    canNotExpand(building: BuildingDetails): boolean {
        if (!building.is_maxed) {
            return true;
        }

        if (this.props.building_needs_to_be_repaired) {
            return true;
        }

        if (this.state.loading) {
            return true;
        }

        if (this.state.expansion_details !== null) {
            if (this.state.expansion_details.expansions_left <= 0) {
                return true;
            }
        }

        if (this.state.time_remaining_for_expansion > 0) {
            return true;
        }

        return this.state.expanding;
    }

    expandBuilding() {
        this.setState({
            error_message: null,
            success_message: null,
            expanding: true,
        }, () => {
            (new Ajax()).setRoute(
                'kingdom/building-expansion/expand/'+this.props.building.id+'/' + this.props.character_id
            ).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    expanding: false,
                    success_message: result.data.message,
                    time_remaining_for_expansion: result.data.time_left
                })
            }, (error: AxiosError) => {
                this.setState({ expanding: false });

                if (typeof error.response != 'undefined') {
                    const response = error.response;

                    let message = response.data.message;

                    if (response.data.error) {
                        message = response.data.error;
                    }

                    this.setState({
                        loading: false,
                        error_message: message,
                    });
                }
            })
        })
    }

    getResourceType(): string {

        if (this.props.building.wood_increase > 0) {
            return 'wood';
        }

        if (this.props.building.iron_increase > 0) {
            return 'iron';
        }

        if (this.props.building.clay_increase > 0) {
            return 'clay';
        }

        if (this.props.building.stone_increase > 0) {
            return 'stone';
        }

        if (this.props.building.is_farm) {
            return 'population';
        }

        return 'UNKNOWN'
    }

    render() {
        return (
            <>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <h3>Expand</h3>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <p className='my-2'>
                    Once a building reaches it max level and does not need to be repaired, one can expand its production to produce
                    more resources allowing you to recruit more units.
                </p>
                <p className='my-2'>
                    <a href='/information/kingdom-resource-expansion' target='_blank'>Learn more about expanding resource buildings <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                {
                    this.state.expansion_details !== null ?
                        this.state.expansion_details.expansions_left === 0 ?
                            <p className='my-4 text-green-700 dark:text-green-500'>
                                This building cannot be expanded anymore.
                            </p>
                        :
                            <>
                                <dl className='my-4'>
                                    <dt>Current expansions</dt>
                                    <dd>{this.state.expansion_details.expansion_count}</dd>
                                    <dt>Expansions left</dt>
                                    <dd>{this.state.expansion_details.expansions_left}</dd>
                                    <dt>Time required (Minutes)</dt>
                                    <dd>{this.state.expansion_details.minutes_until_next_expansion}</dd>
                                    <dt>Will gain additional: {this.getResourceType()} per expansion</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_increases)}</dd>
                                </dl>
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3>Cost for next expansion</h3>
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <dl className='my-4'>
                                    <dt>Stone cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_costs.stone)}</dd>
                                    <dt>Clay cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_costs.clay)}</dd>
                                    <dt>Iron cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_costs.iron)}</dd>
                                    <dt>Steel cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_costs.steel)}</dd>
                                    <dt>Population cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.resource_costs.population)}</dd>
                                    <dt>Gold bars cost</dt>
                                    <dd>{formatNumber(this.state.expansion_details.gold_bars_cost)}</dd>
                                </dl>
                            </>
                    : null
                }
                <DangerAlert additional_css={clsx({
                    'hidden': this.state.error_message === null,
                    'my-4': this.state.error_message !== null
                })}>
                    {this.state.error_message}
                </DangerAlert>
                <SuccessAlert additional_css={clsx({
                    'hidden': this.state.success_message === null,
                    'my-4': this.state.error_message !== null
                })}>
                    {this.state.success_message}
                </SuccessAlert>
                {
                    this.state.expanding ?
                        <LoadingProgressBar />
                    : null
                }
                <PrimaryOutlineButton button_label={'Expand Production'} on_click={this.expandBuilding.bind(this)} disabled={this.canNotExpand(this.props.building)} additional_css={'my-2'} />
                <TimerProgressBar time_remaining={this.state.time_remaining_for_expansion} time_out_label={'Expanding'} />
            </>
        )
    }
}
