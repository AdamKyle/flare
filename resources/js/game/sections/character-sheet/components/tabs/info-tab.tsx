import React, {Fragment} from "react";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import AdditionalInfoModal from "../modals/additional-info-modal";
import InfoTabProps from "../../../../lib/game/character-sheet/types/tabs/info-tab-props";
import {formatNumber} from "../../../../lib/game/format-number";
import OrangeButton from "../../../../components/ui/buttons/orange-button";
import InfoTabState from "../../../../lib/game/character-sheet/types/tabs/info-tab-state";
import CharacterResistances from "../modals/character-resistances";

export default class InfoTab extends React.Component<InfoTabProps, InfoTabState> {

    constructor(props: InfoTabProps) {
        super(props);

        this.state = {
            open_info: false,
            open_resistances: false,
        }
    }

    manageInfoDialogue() {
        this.setState({
            open_info: !this.state.open_info
        })
    }

    manageResistancesDialogue() {
        this.setState({
            open_resistances: !this.state.open_resistances
        })
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        return(
            <Fragment>
                <div className='grid md:grid-cols-2 gap-2'>
                    <div>
                        <dl>
                            <dt>Name:</dt>
                            <dd>{this.props.character.name}</dd>
                            <dt>Race:</dt>
                            <dd>{this.props.character.race}</dd>
                            <dt>Class:</dt>
                            <dd>{this.props.character.class}</dd>
                            <dt>Level:</dt>
                            <dd>{this.props.character.level}/{this.props.character.max_level}</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <dl>
                            <dt>Max Health:</dt>
                            <dd>{formatNumber(this.props.character.health)}</dd>
                            <dt>Total Attack:</dt>
                            <dd>{formatNumber(this.props.character.attack)}</dd>
                            <dt>Heal For:</dt>
                            <dd>{formatNumber(this.props.character.heal_for)}</dd>
                            <dt>AC:</dt>
                            <dd>{formatNumber(this.props.character.ac)}</dd>
                        </dl>
                    </div>
                </div>
                <div className='grid md:grid-cols-1 w-full md:w-[35%]'>
                    <div className='mt-4'>
                        <OrangeButton button_label={'Additional Information'} on_click={this.manageInfoDialogue.bind(this)} additional_css='w-1/2' />
                    </div>
                    <div className='mt-4'>
                        <OrangeButton button_label={'Resistances'} on_click={this.manageResistancesDialogue.bind(this)} additional_css={'w-1/2'}/>
                    </div>
                </div>
                <div className='relative top-[24px]'>
                    <div className="flex justify-between mb-1">
                        <span className="font-medium text-orange-700 dark:text-white text-xs">XP</span>
                        <span className="text-xs font-medium text-orange-700 dark:text-white">{this.props.character.xp}/{this.props.character.xp_next}</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                        <div className="bg-orange-600 h-1.5 rounded-full" style={{width: ((this.props.character.xp / this.props.character.xp_next) * 100) + '%'}}></div>
                    </div>
                </div>

                {
                    this.state.open_info ?
                        <AdditionalInfoModal
                            is_open={this.state.open_info}
                            manage_modal={this.manageInfoDialogue.bind(this)}
                            title={'Additional Information'}
                            character={this.props.character}
                            finished_loading={true}
                        />
                    : null
                }

                {
                    this.state.open_resistances ?
                        <CharacterResistances
                            is_open={this.state.open_resistances}
                            manage_modal={this.manageResistancesDialogue.bind(this)}
                            title={'Resistance Info'}
                            character={this.props.character}
                            finished_loading={true}
                        />
                    : null
                }
            </Fragment>
        )
    }
}
