import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class MonsterSelection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_monster: null,
            attack_disabled: true,
        }

    }

    setMonsterToFight(data: any) {
        const foundMonster = this.props.monsters.filter((monster: any) => monster.id === parseInt(data.value));

        if (foundMonster.length > 0) {
            this.setState({
                selected_monster: foundMonster[0],
                attack_disabled: false,
            });
        } else {
            this.setState({
                selected_monster: null,
                attack_disabled: true,
            }, () => {
                this.props.update_monster(null);
            });
        }
    }

    buildMonsters() {
        let monsters = this.props.monsters.map((monster: any) => {
            return {label: monster.name, value: monster.id};
        });

        monsters.unshift({
            label: 'Please Select', value: 0,
        });

        return monsters;
    }

    defaultMonster() {

        if (this.state.selected_monster !== null) {
            return {
                label: this.state.selected_monster.name,
                value: this.state.selected_monster.id,
            }
        }

        return {
            label: 'Please Select Monster',
            value: 0,
        }
    }

    attack() {
        this.props.update_monster(this.state.selected_monster);
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
                        <PrimaryButton button_label={'Attack'} on_click={this.attack.bind(this)} disabled={this.state.attack_disabled || this.props.timer_running}/>
                    </div>
                </div>
            </div>
        )
    }

}
