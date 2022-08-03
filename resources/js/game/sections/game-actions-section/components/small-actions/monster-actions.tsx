import React, {Fragment} from "react";
import MonsterSelection from "../monster-selection";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import FightSection from "../fight-section";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";
import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import MonsterActionsProps from "../../../../lib/game/types/actions/components/monster-actions-props";
import MonsterActionState from "../../../../lib/game/types/actions/components/monster-action-state";
import {isEqual} from "lodash";

export default class MonsterActions extends React.Component<MonsterActionsProps, MonsterActionState> {

    private monsterActionManager: MonsterActionsManager;

    constructor(props: MonsterActionsProps) {
        super(props);

        this.monsterActionManager = new MonsterActionsManager(this);

        this.state = {
            monster_to_fight: null,
            is_same_monster: false,
            character_revived: false,
            attack_time_out: 0,
        }
    }

    componentDidUpdate(prevProps: Readonly<MonsterActionsProps>, prevState: Readonly<MonsterActionState>, snapshot?: any) {
        if (!isEqual(this.props.monsters, prevProps.monsters)) {
            this.setState({
                monster_to_fight: null
            });
        }
    }

    setSelectedMonster(monster: MonsterType|null) {
        this.monsterActionManager.setSelectedMonster(monster);
    }

    resetSameMonster() {
        this.monsterActionManager.resetSameMonster();
    }

    setAttackTimeOut(attack_time_out: number) {
        this.monsterActionManager.setAttackTimeOut(attack_time_out);
    }

    resetRevived() {
        this.monsterActionManager.resetRevived();
    }

    revive() {
        this.monsterActionManager.revive(this.props.character.id);
    }

    render() {
        return (
            <Fragment>
                {
                    typeof this.props.close_monster_section !== 'undefined' ?
                        <button type='button' onClick={this.props.close_monster_section}
                                className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'
                        >
                            <i className="fas fa-times-circle"></i>
                        </button>
                    : null
                }

                <MonsterSelection monsters={this.props.monsters}
                                  update_monster_to_fight={this.setSelectedMonster.bind(this)}
                                  character={this.props.character}
                />

                {
                    this.props.character.is_dead ?
                        <div className='text-center my-4 ml-[-140px]'>
                            <PrimaryButton button_label={'Revive'}
                                           on_click={this.revive.bind(this)}
                                           additional_css={'mb-4'}
                                           disabled={!this.props.character_statuses.can_attack}
                            />
                            <p>
                                You are dead. Please Revive.
                            </p>
                        </div>
                    : null
                }

                {this.props.children}

                {
                    this.state.monster_to_fight !== null ?
                        <FightSection
                            set_attack_time_out={this.setAttackTimeOut.bind(this)}
                            monster_to_fight={this.state.monster_to_fight}
                            character={this.props.character}
                            is_same_monster={this.state.is_same_monster}
                            reset_same_monster={this.resetSameMonster.bind(this)}
                            character_revived={this.state.character_revived}
                            reset_revived={this.resetRevived.bind(this)}
                            is_small={this.props.is_small}
                        />
                    : null
                }
            </Fragment>
        );
    }
}
