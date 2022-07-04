import React, {Fragment} from "react";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../../components/ui/alerts/simple-alerts/warning-alert";
import {formatNumber} from "../../../lib/game/format-number";
import clsx from "clsx";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import BuildingTimeCalculation from "../../../lib/game/kingdoms/calculations/building-time-calculation";

export default class UpgradeWithGold extends React.Component<any, any> {

    private buildingTimeCalculation: BuildingTimeCalculation;

    constructor(props: any) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            to_level: '',
            paying_with_gold: false,
            loading: false,
            cost_in_gold: 0,
            show_additional_population_message: false,
            additional_population_cost: 0,
            additional_population_needed: 0,
            time_needed: 0,
        }

        this.buildingTimeCalculation = new BuildingTimeCalculation();
    }

    upgradeBuilding() {

        if (this.state.to_level === '' || this.state.to_level < 0) {
            return this.setState({
                to_level: '',
                error_message: 'You must enter a valid level.'
            })
        }

        this.setState({
            error_message: null,
            success_message: null,
            loading: true,
        }, () => {
            (new Ajax()).setRoute('kingdoms/'+this.props.character_id+'/upgrade-building/' + this.props.building.id).setParameters({
                to_level: this.state.to_level,
                paying_with_gold: true
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: response.data.message,
                    to_level: '',
                    paying_with_gold: false,
                    cost_in_gold: 0,
                    show_additional_population_message: false,
                    additional_population_cost: 0,
                    additional_population_needed: 0,
                    time_needed: 0,
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        loading: false,
                        error_message: response.data.message
                    });
                }
            });
        });
    }

    setGoldLevels(e: React.ChangeEvent<HTMLInputElement>) {

        let toLevel = parseInt(e.target.value) || '';

        if (typeof toLevel === 'string') {
            return this.reset();
        }

        toLevel = Math.abs(toLevel);

        const maxLevel = (this.props.building.max_level - this.props.building.level);

        if (toLevel > maxLevel) {
            toLevel = maxLevel;
        }

        this.setState({
            to_level: toLevel
        }, () => {
            this.calculateGoldAndPopulation();
        })
    }

    calculateGoldAndPopulation() {
        if (typeof this.props.kingdom_building_cost_reduction === 'undefined') {
            return 0;
        }

        let populationNeeded     = this.props.building.population_required * this.state.to_level;
        populationNeeded         = populationNeeded - populationNeeded * this.props.kingdom_population_cost_reduction
        let requiresAdditional   = false;
        let cost: number;
        let additionalCost       = 0;
        let additionalPopulation = 0;

        if (this.props.kingdom_current_population < populationNeeded) {
            requiresAdditional = true;

            additionalCost       = (populationNeeded - this.props.kingdom_current_population) * this.props.building.additional_pop_cost;
            additionalPopulation = (populationNeeded - this.props.kingdom_current_population);
        }

        cost = populationNeeded * this.props.building.upgrade_cost;

        cost           = cost - cost * this.props.kingdom_building_cost_reduction;
        additionalCost = additionalCost - additionalCost * this.props.kingdom_building_cost_reduction;

        this.setState({
            cost_in_gold: (cost + additionalCost),
            show_additional_population_message: requiresAdditional,
            additional_population_cost: additionalCost,
            additional_population_needed: additionalPopulation,
            time_needed: formatNumber(this.buildingTimeCalculation.calculateTimeNeeded(
                this.props.building,
                this.state.to_level,
                this.props.kingdom_building_time_reduction,
            ).toFixed(0)),
        });
    }

    reset() {
        this.setState({
            success_message: null,
            error_message: null,
            to_level: '',
            paying_with_gold: false,
            loading: false,
            cost_in_gold: 0,
            show_additional_population_message: false,
            additional_population_cost: 0,
            additional_population_needed: 0,
            time_needed: 0,
        })
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css={'mb-5'}>
                            {this.state.success_message}
                        </SuccessAlert>
                        : null
                }
                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'mb-5'}>
                            {this.state.error_message}
                        </DangerAlert>
                        : null
                }

                <div className='flex items-center mb-5'>
                    <label className='w-[50px]'>Levels</label>
                    <div className='w-2/3'>
                        <input type='text' value={this.state.to_level} onChange={this.setGoldLevels.bind(this)} className='form-control' disabled={this.state.loading || this.props.is_in_queue} />
                    </div>
                </div>
                {
                    this.state.show_additional_population_message ?
                        <div className='mt-4'>
                            <WarningAlert>
                                You require additional population, therefore, you will have an additional population cost. This cost is reflected in your Gold Cost.
                            </WarningAlert>
                        </div>
                        : null
                }
                <dl className='mt-4 mb-4'>
                    <dt>Gold Cost</dt>
                    <dd>{formatNumber(this.state.cost_in_gold)}</dd>
                    <dt>Additional Population Cost</dt>
                    <dd className={clsx({
                        'text-red-500 dark:text-red-400': this.state.additional_population_cost > 0
                    })}>{formatNumber(this.state.additional_population_cost)}</dd>
                    <dt>Additional Population Needed</dt>
                    <dd className={clsx({
                        'text-red-500 dark:text-red-400': this.state.additional_population_cost > 0
                    })}>{formatNumber(this.state.additional_population_needed)}</dd>
                    <dt>Time Needed (Minutes)</dt>
                    <dd className='flex items-center'>
                        <span>{formatNumber(this.state.time_needed)}</span>
                        <div>
                            <div className='ml-2'>
                                <button type={"button"} onClick={() => this.props.show_help_dialogue} className='text-blue-500 dark:text-blue-300'>
                                    <i className={'fas fa-info-circle'}></i> Help
                                </button>
                            </div>
                        </div>
                    </dd>
                </dl>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                        : null
                }
                <PrimaryButton button_label={'Purchase Levels'} additional_css={'mr-2'} on_click={this.upgradeBuilding.bind(this)} disabled={this.state.to_level <= 0 || this.state.loading || this.props.is_in_queue}/>
                <DangerButton button_label={'Cancel'} on_click={this.props.remove_section} disabled={this.state.loading}/>
            </Fragment>
        )
    }
}
