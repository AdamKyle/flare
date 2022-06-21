import React from "react";
import Table from "../../../components/ui/data-tables/table";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";
import {buildBuildingsColumns} from "../../../lib/game/kingdoms/build-buildings-columns";
import KingdomDetails from "../kingdom-details";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingInformationProps from "../../../lib/game/kingdoms/types/building-information-props";
import {formatNumber} from "../../../lib/game/format-number";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";

export default class BuildingInformation extends React.Component<BuildingInformationProps, any> {

    constructor(props: BuildingInformationProps) {
        super(props);

        this.state = {
            show_upgrade_building_modal: false,
        }
    }

    upgradeBuilding() {
        this.setState({
            show_upgrade_building_modal: !this.state.show_upgrade_building_modal,
        });
    }

    render() {
        return (
            <BasicCard additionalClasses={'w-full md:w-2/3 ml-auto mr-auto'}>
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
                                <dl>
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
                                </dl>
                        }
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <div className={'text-center'}>
                    <PrimaryOutlineButton
                        button_label={'Upgrade Building'}
                        on_click={this.upgradeBuilding.bind(this)}
                        disabled={this.props.building.is_locked}
                    />
                </div>
            </BasicCard>
        )
    }
}
