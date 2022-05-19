import React, {Fragment} from "react";
import MonsterSelection from "./monster-selection";
import CraftingSection from "./crafting-section";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import FightSection from "./fight-section";

export default class MainActionSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Fragment>

                {
                    !this.props.character.is_automation_running ?
                        <MonsterSelection monsters={this.props.monsters} update_monster={this.props.set_selected_monster} timer_running={this.props.attack_time_out > 0} character={this.props.character}/>
                    :
                        <div className='mb-4 md:ml-[120px]'>
                            Exploration is running, You cannot fight monsters. <a href='/information/holy-items' target='_blank'>See Exploration Help <i
                            className="fas fa-external-link-alt"></i></a> for more details.
                        </div>
                }



                {
                    this.props.crafting_type !== null ?
                        <CraftingSection remove_crafting={this.props.remove_crafting_type} type={this.props.crafting_type} character_id={this.props.character.id} cannot_craft={this.props.cannot_craft}/>
                    : null
                }

                <div className={'md:ml-[-100px]'}>

                    {
                        this.props.character?.is_dead ?
                            <div className='text-center my-4'>
                                <PrimaryButton button_label={'Revive'} on_click={this.props.revive} additional_css={'mb-4'} disabled={!this.props.character.can_attack}/>
                                <p>
                                    You are dead. Please Revive.
                                </p>
                            </div>
                        : null
                    }

                    {
                        this.props.monster_to_fight !== null && !this.props.character.is_automation_running ?
                            <FightSection
                                set_attack_time_out={this.props.set_attack_timeOut}
                                monster_to_fight={this.props.monster_to_fight}
                                character={this.props.character}
                                is_same_monster={this.props.is_same_monster}
                                reset_same_monster={this.props.reset_same_monster}
                                character_revived={this.props.character_revived}
                                reset_revived={this.props.reset_revived.bind(this)}
                                is_small={false}
                            />
                        : null
                    }
                </div>
            </Fragment>
        )
    }
}
