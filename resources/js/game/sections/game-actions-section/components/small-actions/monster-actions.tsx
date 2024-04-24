import React from "react";
import MonsterSelection from "../monster-selection";
import FightSection from "../fight-section";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";
import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import MonsterActionsProps from "./types/monster-actions-props";
import MonsterActionState from "./types/monster-action-state";
import { isEqual } from "lodash";
import Select from "react-select";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import Revive from "../fight-section/revive";
import RaidElementInfo from "../fight-section/modals/raid-elemental-info";

export default class MonsterActions extends React.Component<
    MonsterActionsProps,
    MonsterActionState
> {
    private monsterActionManager: MonsterActionsManager;

    constructor(props: MonsterActionsProps) {
        super(props);

        this.monsterActionManager = new MonsterActionsManager(this);

        this.state = {
            monsters: [],
            monster_to_fight: null,
            is_same_monster: false,
            character_revived: false,
            attack_time_out: 0,
            rank_selected: 1,
        };
    }

    componentDidMount() {
        this.setState({
            monsters: this.props.monsters,
        });
    }

    componentDidUpdate() {
        if (this.props.monsters.length > 0 && this.state.monsters.length > 0) {
            const newMonster = this.props.monsters[0];
            const currentMonster = this.state.monsters[0];

            if (newMonster.id !== currentMonster.id) {
                this.setState({
                    monsters: this.props.monsters,
                });
            }
        }
    }

    setSelectedMonster(monster: MonsterType | null) {
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

    setRank(data: any) {
        this.setState({
            rank_selected: data.value,
        });
    }

    render() {
        return (
            <div className="relative">
                <MonsterSelection
                    monsters={this.state.monsters}
                    update_monster_to_fight={this.setSelectedMonster.bind(this)}
                    character={this.props.character}
                    close_monster_section={this.props.close_monster_section}
                />

                <Revive
                    can_attack={this.props.character_statuses.can_attack}
                    is_character_dead={this.props.character.is_dead}
                    character_id={this.props.character.id}
                    revive_call_back={() => {
                        this.setState({
                            character_revived: true,
                        });
                    }}
                />

                {this.props.children}

                {this.state.monster_to_fight !== null ? (
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
                ) : null}
            </div>
        );
    }
}
