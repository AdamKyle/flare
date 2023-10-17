import React from "react";
import { isEqual } from "lodash";
import MonsterType from "../../../lib/game/types/actions/monster/monster-type";
import MonsterSelectionProps from "./types/monster-selection-props";
import MonsterSelectionState from "./types/monster-selection-state";
import CritterSelection from "./fight-section/monster-selection";

export default class MonsterSelection extends React.Component<
    MonsterSelectionProps,
    MonsterSelectionState
> {
    constructor(props: MonsterSelectionProps) {
        super(props);

        this.state = {
            monster_to_fight: null,
            monsters: [],
        };
    }

    componentDidMount() {
        this.setState({
            monsters: this.props.monsters,
        });
    }

    componentDidUpdate() {
        if (!isEqual(this.state.monsters, this.props.monsters)) {
            this.setState({
                monster_to_fight: null,
                monsters: this.props.monsters,
            });
        }
    }

    setMonsterToFight(data: any) {
        const monster: MonsterType | null = this.findMonster(data.value);

        if (monster !== null) {
            this.setState({
                monster_to_fight: monster,
            });
        }
    }

    buildMonsters() {
        if (this.props.monsters === null) {
            return [{ label: "", value: 0 }];
        }

        return this.props.monsters.map((monster: MonsterType) => {
            return { label: monster.name, value: monster.id };
        });
    }

    defaultMonster(): { label: string; value: number }[] {
        if (this.state.monster_to_fight !== null) {
            const monster: MonsterType | null = this.findMonster(
                this.state.monster_to_fight.id
            );

            if (monster !== null) {
                return [{ label: monster.name, value: monster.id }];
            }
        }

        return [{ label: "Please select a monster", value: 0 }];
    }

    findMonster(monsterId: number): MonsterType | null {
        const foundMonster: MonsterType[] | [] = this.props.monsters.filter(
            (monster: MonsterType) => {
                return monster.id === monsterId;
            }
        );

        if (foundMonster.length > 0) {
            return foundMonster[0];
        }

        return null;
    }

    isAttackDisabled() {
        if (this.props.character === null) {
            return false;
        }

        return (
            this.props.character.is_dead ||
            this.props.character.is_automation_running ||
            !this.props.character.can_attack ||
            this.state.monster_to_fight === null
        );
    }

    attack() {
        this.props.update_monster_to_fight(this.state.monster_to_fight);
    }

    render() {
        return (
            <CritterSelection
                set_monster_to_fight={this.setMonsterToFight.bind(this)}
                monsters={this.buildMonsters()}
                default_monster={this.defaultMonster()}
                attack={this.attack.bind(this)}
                is_attack_disabled={this.isAttackDisabled()}
                close_monster_section={this.props.close_monster_section}
            />
        );
    }
}
