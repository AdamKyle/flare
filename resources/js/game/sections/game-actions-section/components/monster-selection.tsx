import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {isEqual} from "lodash";
import MonsterType from "../../../lib/game/types/actions/monster/monster-type";
import MonsterSelectionProps from "./types/monster-selection-props";
import MonsterSelectionState from "./types/monster-selection-state";
import DangerButton from "../../../components/ui/buttons/danger-button";

export default class MonsterSelection extends React.Component<MonsterSelectionProps, MonsterSelectionState> {

    constructor(props: MonsterSelectionProps) {
        super(props);

        this.state = {
            monster_to_fight: null,
            monsters: [],
        }
    }

    componentDidMount() {
        this.setState({
            monsters: this.props.monsters
        });
    }

    componentDidUpdate(prevProps: Readonly<MonsterSelectionProps>, prevState: Readonly<MonsterSelectionState>, snapshot?: any) {
        if (!isEqual(this.state.monsters, this.props.monsters)) {
            this.setState({
                monster_to_fight: null,
                monsters: this.props.monsters,
            });
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
            <div className='mt-4 lg:mt-2 lg:ml-[120px]'>
                <div className='lg:grid lg:grid-cols-3 lg:gap-2'>
                    <div className='lg:cols-start-1 lg:col-span-2'>
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
                    <div className='text-center mt-4 lg:mt-0 lg:text-left lg:cols-start-3 lg:cols-end-3'>
                        <PrimaryButton button_label={'Attack'} on_click={this.attack.bind(this)} disabled={this.isAttackDisabled()}/>

                        {
                            typeof this.props.close_monster_section !== 'undefined' ?
                                <DangerButton button_label={'Close'} on_click={this.props.close_monster_section} additional_css={'ml-4'} />
                            : null
                        }
                    </div>
                </div>
            </div>
        );
    }

}
