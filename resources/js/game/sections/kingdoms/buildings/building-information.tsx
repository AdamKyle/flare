import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingInformationProps from "../../../lib/game/kingdoms/types/building-information-props";
import {formatNumber} from "../../../lib/game/format-number";
import Select from "react-select";
import TimeHelpModal from "../modals/time-help-modal";
import UpgradeWithGold from "./upgrade-with-gold";
import UpgradeWithResources from "./upgrade-with-resources";
import BuildingTimeCalculation from "../../../lib/game/kingdoms/calculations/building-time-calculation";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class BuildingInformation extends React.Component<BuildingInformationProps, any> {

    private buildingTimeCalculation: BuildingTimeCalculation;

    constructor(props: BuildingInformationProps) {
        super(props);

        this.state = {
            upgrade_section: null,
            success_message: '',
            error_message: '',
            loading: false,
        }

        this.buildingTimeCalculation = new BuildingTimeCalculation();
    }

    componentDidMount() {
        if (this.props.building.current_durability < this.props.building.max_durability) {
            this.setState({
                upgrade_section: 'repair-building'
            });
        }
    }

    componentDidUpdate() {
        if (this.props.building.current_durability < this.props.building.max_durability && this.state.upgrade_section !== 'repair-building') {
            this.setState({
                upgrade_section: 'repair-building'
            });
        }
    }

    showSelectedForm(data: any) {
        this.setState({
            upgrade_section: data.value,
            paying_with_gold: data.value === 'gold' ? true : false,
        });
    }

    manageHelpDialogue() {
        this.setState({
            show_time_help: !this.state.show_time_help,
        })
    }

    removeSelection() {
        this.setState({
            upgrade_section: null,
        })
    }

    buildingNeedsToBeRepaired() {
        return this.props.building.current_durability < this.props.building.max_durability;
    }

    repairBuilding() {
        this.setState({
            loading: true,
            success_message: '',
            error_message: '',
        }, () => {
            (new Ajax).setRoute('kingdoms/'+this.props.character_id+'/rebuild-building/' + this.props.building.id)
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        success_message: result.data.message,
                        loading: false,
                    });
                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        this.setState({
                            error_message: response.data.message
                        });
                    }

                    console.error(error);
                });
        });

    }

    calculateResourceCostWithReductions(cost: number, is_population: boolean, is_iron: boolean): string {

        if (typeof this.props.kingdom_building_cost_reduction === 'undefined') {
            console.error('this.props.kingdom_building_cost_reduction is undefined.');

            return 'ERROR';
        }

        if (is_iron) {
            return formatNumber((cost - cost * (this.props.kingdom_building_cost_reduction + this.props.kingdom_iron_cost_reduction)).toFixed(0));
        }

        if (is_population) {
            return formatNumber((cost - cost * this.props.kingdom_population_cost_reduction).toFixed(0));
        }

        return formatNumber((cost - cost * this.props.kingdom_building_cost_reduction).toFixed(0))
    }

    renderSelectedSection() {
        switch (this.state.upgrade_section) {
            case 'gold':
                return <UpgradeWithGold character_id={this.props.character_id}
                                        building={this.props.building}
                                        show_help_dialogue={this.manageHelpDialogue.bind(this)}
                                        remove_section={this.removeSelection.bind(this)}
                                        kingdom_building_time_reduction={this.props.kingdom_building_time_reduction}
                                        kingdom_iron_cost_reduction={this.props.kingdom_iron_cost_reduction}
                                        kingdom_population_cost_reduction={this.props.kingdom_population_cost_reduction}
                                        kingdom_current_population={this.props.kingdom_current_population}
                                        kingdom_building_cost_reduction={this.props.kingdom_building_cost_reduction}
                                        is_in_queue={this.props.is_in_queue}
                                        character_gold={this.props.character_gold}

                />
            case 'resources':
                return <UpgradeWithResources
                    character_id={this.props.character_id}
                    building={this.props.building}
                    remove_section={this.removeSelection.bind(this)}
                    is_in_queue={this.props.is_in_queue}
                />
            case 'repair-building':
                return <Fragment>
                    {
                        this.buildingNeedsToBeRepaired() ?
                            <Fragment>
                                <PrimaryButton button_label={'Repair'} on_click={this.repairBuilding.bind(this)} additional_css={'mr-2'} />
                                <DangerButton button_label={'Close section'} disabled={this.buildingNeedsToBeRepaired()} on_click={this.removeSelection.bind(this)} />

                                {
                                    this.state.loading ?
                                        <LoadingProgressBar />
                                    : null
                                }
                            </Fragment>
                        :
                            <Fragment>
                                <p className='my-2'>Building does not need tro be Repaired</p>
                                <DangerButton button_label={'Close section'} on_click={this.removeSelection.bind(this)} />
                            </Fragment>
                    }

                </Fragment>
                return 'repair settings';
            default:
                return null;
        }
    }

    getRebuildTime() {

        let rebuildTime = this.buildingTimeCalculation.calculateRebuildTime(
            this.props.building,
            this.props.kingdom_building_time_reduction
        )

        if (rebuildTime > 60) {
            rebuildTime = rebuildTime / 60;

            return rebuildTime.toFixed(0) + ' Hours';
        }

        return rebuildTime.toFixed(0) + ' Minutes';
    }

    renderCosts() {

        return (
            <dl className='mb-5'>
                <dt>Stone Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.stone_cost, false, false)}</dd>
                <dt>Clay Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.clay_cost, false, false)}</dd>
                <dt>Wood Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.wood_cost, false, false)}</dd>
                <dt>Iron Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.iron_cost, false, true)}</dd>
                <dt>Steel Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.steel_cost, false, true)}</dd>
                <dt>Population Cost:</dt>
                <dd>{this.calculateResourceCostWithReductions(this.props.building.population_required, true, false)}</dd>
                <dt>Time till next level:</dt>
                <dd>
                    {
                        this.state.upgrade_section !== 'repair-building' ?
                            formatNumber(this.buildingTimeCalculation.calculateViewTime(this.props.building, this.state.to_level, this.props.kingdom_building_time_reduction).toFixed(2))
                        :
                            this.getRebuildTime()
                    }
                </dd>
            </dl>
        );
    }

    buildOptions() {
        const options = [
            {
                label: 'Upgrade with resources',
                value: 'resources',
            },
            {
                label: 'Repair Building',
                value: 'repair-building',
            }
        ];

        if (!this.props.building.is_special) {
            options.push({
                label: 'Upgrade with gold',
                value: 'gold',
            })
        }

        return options;
    }

    render() {
        return (
            <Fragment>
                <BasicCard>
                    <div className='text-right cursor-pointer text-red-500'>
                        <button onClick={() => this.props.close()}><i className="fas fa-minus-circle"></i></button>
                    </div>
                    {
                        this.props.building.is_locked ?
                            <DangerAlert>
                                You must train the appropriate Kingdom Passive skill to unlock this building.
                                The skill name is the same as this building name.
                            </DangerAlert>
                        : null
                    }
                    {
                        this.state.success_message !== '' ?
                            <SuccessAlert>
                                {this.state.success_message}
                            </SuccessAlert>
                        : null
                    }
                    {
                        this.state.error_message !== '' ?
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
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
                            <h3>
                                {
                                    this.state.upgrade_section === 'repair-building' ?
                                        'Repair Costs'
                                    :
                                        'Upgrade Costs (For 1 Level)'
                                }
                            </h3>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            {
                                this.props.building.is_maxed && !this.buildingNeedsToBeRepaired() ?
                                    <p>Building is already max level.</p>
                                :
                                    this.props.is_in_queue ?
                                        <p>Building is currently in queue</p>
                                    :
                                        <Fragment>
                                            { this.renderCosts() }

                                            {
                                                this.state.upgrade_section !== null ?
                                                    this.renderSelectedSection()
                                                :
                                                    !this.props.is_in_queue && !this.props.building.is_locked ?
                                                        <Fragment>
                                                            <Select
                                                                onChange={this.showSelectedForm.bind(this)}
                                                                options={this.buildOptions()}
                                                                menuPosition={'absolute'}
                                                                menuPlacement={'bottom'}
                                                                styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                                menuPortalTarget={document.body}
                                                                value={[
                                                                    {label: 'Please Select Upgrade Path', value: ''}
                                                                ]}
                                                            />
                                                            <p className='my-4 text-sm'>
                                                                This building cannot be upgraded with gold.
                                                            </p>
                                                        </Fragment>
                                                    : null
                                            }
                                        </Fragment>
                            }
                        </div>
                    </div>
                </BasicCard>
                {
                    this.state.show_time_help ?
                        <TimeHelpModal
                            is_in_minutes={true}
                            is_in_seconds={false}
                            manage_modal={this.manageHelpDialogue.bind(this)}
                            time={this.state.time_needed}
                        />
                    : null
                }
            </Fragment>
        )
    }
}

