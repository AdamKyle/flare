import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingInformationProps from "../../../lib/game/kingdoms/types/building-information-props";
import {formatNumber} from "../../../lib/game/format-number";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import WarningAlert from "../../../components/ui/alerts/simple-alerts/warning-alert";
import clsx from "clsx";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {upperFirst} from "lodash";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class BuildingInformation extends React.Component<BuildingInformationProps, any> {

    constructor(props: BuildingInformationProps) {
        super(props);

        this.state = {
            upgrade_section: null,
            to_level: '',
            paying_with_gold: false,
            loading: false,
            cost_in_gold: 0,
            show_additional_population_message: false,
            additional_population_cost: 0,
            additional_population_needed: 0,
            time_needed: 0,
            error_message: null,
            success_message: null,
        }
    }

    showSelectedForm(data: any) {
        this.setState({
            upgrade_section: data.value,
            paying_with_gold: data.value === 'gold' ? true : false,
        });
    }

    closeSection() {
        this.setState({
            upgrade_section: null
        });
    }

    setGoldLevels(e: React.ChangeEvent<HTMLInputElement>) {

        let toLevel = parseInt(e.target.value) || '';

        if (typeof toLevel === 'string') {
            return;
        }

        toLevel = Math.abs(toLevel);

        if (toLevel > (this.props.building.max_level - this.props.building.level)) {
            toLevel = this.props.building.max_level;
        }

        this.setState({
            to_level: toLevel
        }, () => {
            this.calculateGoldAndPopulation();
        })
    }

    calculateGoldAndPopulation() {
        let populationNeeded     = this.props.building.population_required * this.state.to_level;
        populationNeeded         = populationNeeded - populationNeeded * this.props.kingdom_building_pop_cost_reduction
        let requiresAdditional   = false;
        let cost                 = 0;
        let additionalCost       = 0;
        let additionalPopulation = 0;
        let timeNeeded           = this.props.building.raw_time_to_build;

        if (this.props.kingdom_current_population < populationNeeded) {
            requiresAdditional = true;

            additionalCost       = (populationNeeded - this.props.kingdom_current_population) * this.props.building.upgrade_cost;
            additionalPopulation = (populationNeeded - this.props.kingdom_current_population);
        }

        cost = populationNeeded * this.props.building.upgrade_cost;

        cost           = cost - cost * this.props.kingdom_building_cost_reduction;
        additionalCost = additionalCost - additionalCost * this.props.kingdom_building_cost_reduction;

        for (let i = this.state.to_level; i > 0; i--) {
            timeNeeded = timeNeeded + timeNeeded * this.props.building.raw_time_increase;
        }

        timeNeeded = timeNeeded - timeNeeded * this.props.kingdom_building_time_reduction;

        this.setState({
            cost_in_gold: (cost + additionalCost),
            show_additional_population_message: requiresAdditional,
            additional_population_cost: additionalCost,
            additional_population_needed: additionalPopulation,
            time_needed: timeNeeded,
        });
    }

    upgradeBuilding() {

        this.setState({
            error_message: null,
            success_message: null,
            loading: true,
        }, () => {
            (new Ajax()).setRoute('kingdoms/'+this.props.character_id+'/upgrade-building/' + this.props.building.id).setParameters({
                to_level: this.state.to_level !== '' ? this.state.to_level : 1,
                paying_with_gold: this.state.paying_with_gold,
            }).doAjaxCall('post', (response: AxiosResponse) => {
                console.log(response.data);

                this.setState({loading: false, success_message: response.data.message});
                this.props.update_kingdoms(response.data.kingdom);
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

    removeSelection() {
        this.setState({
            upgrade_section: null,
            to_level: '',
            paying_with_gold: false,
            loading: false,
            cost_in_gold: 0,
            show_additional_population_message: false,
            additional_population_cost: 0,
            additional_population_needed: 0,
            time_needed: 0,
            error_message: null,
            success_message: null,
        })
    }

    renderFutureResourceValues() {
        const resourceValues = [
            'future_clay_increase',
            'future_defence_increase',
            'future_durability_increase',
            'future_iron_increase',
            'future_population_increase',
            'future_stone_increase',
            'future_wood_increase',
        ];

        return resourceValues.map((value: string) => {
            const buildingValue = this.props.building[value];

            if (buildingValue !== null && typeof buildingValue === 'number') {
                if (buildingValue > 0.0) {
                    return (
                        <Fragment>
                            <dt>
                                {upperFirst(value.replace('future_', '').replace('_', ' '))}
                            </dt>
                            <dd>
                                {formatNumber(buildingValue)}
                            </dd>
                        </Fragment>
                    )
                }
            }
        }).filter((element: any) => {
            return typeof element !=='undefined';
        });
    }

    renderResourceUpgrade() {
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
                <div className={'grid grid-cols-2 gap-4'}>
                    <div>
                        <h3>After Level Up</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            {this.renderFutureResourceValues()}
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Cost To Level</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Wood Cost</dt>
                            <dd>{this.props.building.wood_cost}</dd>
                            <dt>Clay Cost</dt>
                            <dd>{this.props.building.clay_cost}</dd>
                            <dt>Stone Cost</dt>
                            <dd>{this.props.building.stone_cost}</dd>
                            <dt>Iron Cost</dt>
                            <dd>{this.props.building.iron_cost}</dd>
                            <dt>Population Cost</dt>
                            <dd>{this.props.building.population_required}</dd>
                            <dt>Time To Build (Minutes)</dt>
                            <dd>{this.props.building.time_increase}</dd>
                        </dl>
                    </div>
                </div>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                <PrimaryButton button_label={'Upgrade'} additional_css={'mr-2'} on_click={this.upgradeBuilding.bind(this)} disabled={this.state.loading}/>
                <DangerButton button_label={'Cancel'} on_click={this.removeSelection.bind(this)} disabled={this.state.loading}/>
            </Fragment>
        )
    }

    renderGoldSection() {
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
                        <input type='text' value={this.state.to_level} onChange={this.setGoldLevels.bind(this)} className='form-control' disabled={this.state.loading} />
                    </div>
                </div>
                {
                    this.state.show_additional_population_message ?
                        <div className='mt-4'>
                            <WarningAlert>
                                You require additional population, therefore, you will have an additional population cost.
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
                    <dd>{formatNumber((this.state.time_needed))}</dd>
                </dl>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                <PrimaryButton button_label={'Purchase Levels'} additional_css={'mr-2'} on_click={this.upgradeBuilding.bind(this)} disabled={this.state.to_level <= 0 || this.state.loading}/>
                <DangerButton button_label={'Cancel'} on_click={this.removeSelection.bind(this)} disabled={this.state.loading}/>
            </Fragment>
        )
    }

    renderSelectedSection() {
        switch (this.state.upgrade_section) {
            case 'gold':
                return this.renderGoldSection();
            case 'resources':
                return this.renderResourceUpgrade();
            default:
                return null;
        }
    }

    render() {
        return (
            <BasicCard>
                <div className='text-right cursor-pointer text-red-500'>
                    <button onClick={() => this.props.close()}><i className="fas fa-minus-circle"></i></button>
                </div>
                {
                    this.props.building.is_locked ?
                        <InfoAlert>
                            You must train the appropriate Kingdom Passive skill to unlock this building.
                            The skill name is the same s this building name.
                        </InfoAlert>
                    : null
                }
                <div className={'grid md:grid-cols-2 gap-4 mb-4 mt-4'}>
                    <div>
                        <h3>Basic Info</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Level:</dt>
                            <dd>{this.props.building.level}/{this.props.building.max_level}</dd>
                            <dt>Durability:</dt>
                            <dd>{formatNumber(this.props.building.current_durability)}/{formatNumber(this.props.building.max_durability)}</dd>
                            <dt>Defence:</dt>
                            <dd>{formatNumber(this.props.building.current_defence)}</dd>
                            <dt>Morale Loss (per hour):</dt>
                            <dd>{(this.props.building.morale_decrease * 100).toFixed(2)}%</dd>
                            <dt>Morale Gain (per hour):</dt>
                            <dd>{(this.props.building.morale_increase * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Upgrade Costs (For 1 Level)</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        {
                            this.props.building.is_maxed ?
                                <p>Building is already max level.</p>
                            :
                                this.props.is_in_queue ?
                                    <p>Building is currently in queue</p>
                                :
                                    <Fragment>
                                        <dl className='mb-5'>
                                            <dt>Stone Cost:</dt>
                                            <dd>{formatNumber(this.props.building.stone_cost)}</dd>
                                            <dt>Clay Cost:</dt>
                                            <dd>{formatNumber(this.props.building.clay_cost)}</dd>
                                            <dt>Wood Cost:</dt>
                                            <dd>{formatNumber(this.props.building.wood_cost)}</dd>
                                            <dt>Iron Cost:</dt>
                                            <dd>{formatNumber(this.props.building.iron_cost)}</dd>
                                            <dt>Population Cost:</dt>
                                            <dd>{formatNumber(this.props.building.population_required)}</dd>
                                            <dt>Time till next level:</dt>
                                            <dd>{formatNumber(this.props.building.time_increase)} Minutes</dd>
                                        </dl>

                                        {
                                            this.state.upgrade_section !== null ?
                                                this.renderSelectedSection()
                                            :
                                                <Select
                                                    onChange={this.showSelectedForm.bind(this)}
                                                    options={[
                                                        {
                                                            label: 'Upgrade with gold',
                                                            value: 'gold',
                                                        },
                                                        {
                                                            label: 'Upgrade with resources',
                                                            value: 'resources',
                                                        }
                                                    ]}
                                                    menuPosition={'absolute'}
                                                    menuPlacement={'bottom'}
                                                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                    menuPortalTarget={document.body}
                                                    value={[
                                                        {label: 'Please Select Upgrade Path', value: ''}
                                                    ]}
                                                />
                                        }
                                    </Fragment>
                        }
                    </div>
                </div>
            </BasicCard>
        )
    }
}

