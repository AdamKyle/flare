import React, {Fragment} from "react";
import MonsterSelection from "../monster-selection";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import FightSection from "../fight-section";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";
import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import MonsterActionsProps from "../../../../lib/game/types/actions/components/monster-actions-props";
import MonsterActionState from "../../../../lib/game/types/actions/components/monster-action-state";
import {isEqual} from "lodash";
import Select from "react-select";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

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
            rank_selected: 1,
        }
    }

    componentDidUpdate(prevProps: Readonly<MonsterActionsProps>, prevState: Readonly<MonsterActionState>, snapshot?: any) {
        if (!isEqual(this.props.monsters, prevProps.monsters)) {
            this.setState({
                monster_to_fight: null
            });
        }
    }

    setupRankFight(component: FightSection) {
        component.setState({
            processing_rank_battle: true
        }, () => {

            if (this.state.monster_to_fight === null) {
                component.setState({
                    processing_rank_battle: false,
                });

                return;
            }

            (new Ajax()).setRoute('set-up-rank-fight/'+this.props.character.id+'/' + this.state.monster_to_fight.id)
                .setParameters({rank: this.state.rank_selected})
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        is_same_monster: false,
                    }, () => {
                        component.setState({
                            processing_rank_battle: false,
                            battle_messages: result.data.messages,
                            character_current_health: result.data.health.character_health,
                            character_max_health: result.data.health.max_character_health,
                            monster_current_health: result.data.health.monster_health,
                            monster_max_health: result.data.health.max_monster_health,
                            setting_up_rank_fight: false,
                            monster_to_fight_id: result.data.monster_id
                        });
                    })

                }, (error: AxiosError) => {
                    component.setState({
                        processing_rank_battle: false,
                    });

                    console.error(error);
                });
        });
    }

    processRankFight(component: FightSection, attackType: string) {
        component.setState({
            processing_rank_battle: true
        }, () => {
            if (this.state.monster_to_fight === null) {
                component.setState({
                    processing_rank_battle: false
                });

                return;
            }

            (new Ajax()).setRoute('fight-ranked-monster/' + this.props.character.id)
                .setParameters({
                    rank: this.state.rank_selected,
                    monster_id: this.state.monster_to_fight.id,
                    attack_type: attackType,
                }).doAjaxCall('post', (result: AxiosResponse) => {
                    component.setState({
                        processing_rank_battle: false,
                        battle_messages: result.data.messages,
                        character_current_health: result.data.health.character_health,
                        monster_current_health: result.data.health.monster_health,
                    });
                }, (error: AxiosError) => {
                    console.error(error);
                });
        })
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

    setRank(data: any) {
        this.setState({
            rank_selected: data.value,
        });
    }

    optionsForRanks() {
        const options = [];

        for (let i = 1; i <= this.props.total_ranks; i++) {
            options.push({
                label: 'Rank ' + i,
                value: i
            });
        }

        return options;
    }

    renderRankSelection() {
        return (
            <div className='mt-2 md:ml-[120px]'>
                <div className='grid grid-cols-3 gap-2'>
                    <div className='cols-start-1 col-span-2'>
                        <Select
                            onChange={this.setRank.bind(this)}
                            options={this.optionsForRanks()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={[{label: 'Rank ' + this.state.rank_selected, value: this.state.rank_selected}]}
                        />
                    </div>
                    <div className='cols-start-3 cols-end-3'>
                        <a href='/information/ranked-fights' target='_blank' className='ml-2 relative top-[5px]'>Help <i
                            className="fas fa-external-link-alt"></i></a>
                    </div>
                </div>
            </div>
        )
    }


    render() {
        return (
            <div className='relative'>
                {
                    this.props.is_rank_fights ?
                        this.renderRankSelection()
                    : null
                }

                <MonsterSelection monsters={this.props.monsters}
                                  update_monster_to_fight={this.setSelectedMonster.bind(this)}
                                  character={this.props.character}
                                  close_monster_section={this.props.close_monster_section}
                />

                {
                    this.props.character.is_dead ?
                        <div className='text-center my-4 lg:ml-[-140px]'>
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
                            is_rank_fight={this.props.is_rank_fights}
                            process_rank_fight={this.processRankFight.bind(this)}
                            setup_rank_fight={this.setupRankFight.bind(this)}
                        />
                    : null
                }
            </div>
        );
    }
}
