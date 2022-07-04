import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingInformationProps from "../../../lib/game/kingdoms/types/building-information-props";
import {formatNumber} from "../../../lib/game/format-number";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Select from "react-select";
import TimeHelpModal from "../modals/time-help-modal";
import UpgradeWithGold from "./upgrade-with-gold";
import UpgradeWithResources from "./upgrade-with-resources";
import BuildingTimeCalculation from "../../../lib/game/kingdoms/calculations/building-time-calculation";

export default class BuildingInformation extends React.Component<BuildingInformationProps, any> {

    private buildingTimeCalculation: BuildingTimeCalculation;

    constructor(props: BuildingInformationProps) {
        super(props);

        this.state = {
            upgrade_section: null
        }

        this.buildingTimeCalculation = new BuildingTimeCalculation();
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

    calculateResourceCostWithReductions(cost: number, is_population: boolean, is_iron: boolean): string {

        if (typeof this.props.kingdom_building_cost_reduction === 'undefined') {
            console.error('this.props.kingdom_building_cost_reduction is undefined.');

            return 'ERROR';
        }

        if (is_iron) {
            cost = cost - cost * this.props.kingdom_iron_cost_reduction;
        }

        if (is_population) {
            cost = cost - cost * this.props.kingdom_population_cost_reduction;
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

                />
            case 'resources':
                return <UpgradeWithResources
                    character_id={this.props.character_id}
                    building={this.props.building}
                    remove_section={this.removeSelection.bind(this)}
                    is_in_queue={this.props.is_in_queue}
                />
            default:
                return null;
        }
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
                            <InfoAlert>
                                You must train the appropriate Kingdom Passive skill to unlock this building.
                                The skill name is the same as this building name.
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
                                                <dd>{this.calculateResourceCostWithReductions(this.props.building.stone_cost, false, false)}</dd>
                                                <dt>Clay Cost:</dt>
                                                <dd>{this.calculateResourceCostWithReductions(this.props.building.clay_cost, false, false)}</dd>
                                                <dt>Wood Cost:</dt>
                                                <dd>{this.calculateResourceCostWithReductions(this.props.building.wood_cost, false, false)}</dd>
                                                <dt>Iron Cost:</dt>
                                                <dd>{this.calculateResourceCostWithReductions(this.props.building.iron_cost, false, true)}</dd>
                                                <dt>Population Cost:</dt>
                                                <dd>{this.calculateResourceCostWithReductions(this.props.building.population_required, true, false)}</dd>
                                                <dt>Time till next level:</dt>
                                                <dd>{formatNumber(this.buildingTimeCalculation.calculateViewTime(this.props.building, this.state.to_level, this.props.kingdom_building_time_reduction).toFixed(2))} Minutes</dd>
                                            </dl>

                                            {
                                                this.state.upgrade_section !== null ?
                                                    this.renderSelectedSection()
                                                :
                                                    !this.props.is_in_queue ?
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

