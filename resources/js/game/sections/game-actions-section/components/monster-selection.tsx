import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {isEqual} from "lodash";
import MonsterType from "../../../lib/game/types/actions/monster/monster-type";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import MonsterSelectionProps from "../../../lib/game/types/actions/components/monster-selection-props";
import MonsterSelectionState from "../../../lib/game/types/actions/components/monster-selection-state";

export default class MonsterSelection extends React.Component<MonsterSelectionProps, MonsterSelectionState> {

    constructor(props: MonsterSelectionProps) {
        super(props);

        this.state = {
            monster_to_fight: null,
        }
    }

    setMonsterToFight(data: any) {
        const monster: MonsterType|null = this.findMonster(data.value);

        if (monster !== null) {
            this.setState({
                monster_to_fight: monster,
            });
        }
    }

    buildMonsters() {
        if (this.props.monsters === null) {
            return [{label: '', value: 0}];
        }

        return this.props.monsters.map((monster: MonsterType) => {
            return {label: monster.name, value: monster.id};
        });
    }

    defaultMonster(): {label: string, value: number}[] {

        if (this.state.monster_to_fight !== null) {
            const monster: MonsterType|null = this.findMonster(this.state.monster_to_fight.id);

            if (monster !== null) {
                return [{ label: monster.name, value: monster.id}];
            }
        }

        return [{label: 'Please Select', value: 0}];
    }

    findMonster(monsterId: number): MonsterType|null {
        const foundMonster: MonsterType[]|[] = this.props.monsters.filter((monster: MonsterType) => {
            return monster.id === monsterId;
        })

        if (foundMonster.length > 0) {
            return foundMonster[0];
        }

        return null;
    }

    isAttackDisabled() {
        if (this.props.character === null) {
            return false;
        }

        return this.props.character.is_dead ||
            this.props.character.is_automation_running ||
            !this.props.character.can_attack ||
            this.state.monster_to_fight === null;
    }

    attack() {
        this.props.update_monster_to_fight(this.state.monster_to_fight);
    }

    render() {
        return (
            <div className='mt-2 md:ml-[120px]'>
                <div className='grid grid-cols-3 gap-2'>
                    <div className='cols-start-1 col-span-2'>
                        <Select
                            onChange={this.setMonsterToFight.bind(this)}
                            options={this.buildMonsters()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.defaultMonster()}
                        />
                    </div>
                    <div className='cols-start-3 cols-end-3'>
                        <PrimaryButton button_label={'Attack'} on_click={this.attack.bind(this)} disabled={this.isAttackDisabled()}/>
                    </div>
                </div>
            </div>
        );
    }

}
