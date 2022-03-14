import React from "react";
import Select from "react-select";
import PopOverContainer from "../../../components/ui/popover/pop-over-container";

export default class FightSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_monster: null,
        }

    }

    setMonsterToFight(data: any) {
        const foundMonster = this.props.monsters.filter((monster: any) => monster.id === parseInt(data.value));

        if (foundMonster.length > 0) {
            this.setState({
                selected_monster: foundMonster[0],
            });
        }
    }

    buildMonsters() {
        return this.props.monsters.map((monster: any) => {
            return {label: monster.name, value: monster.id};
        })
    }

    defaultMonster() {

        if (this.state.selected_monster !== null) {
            return {
                label: this.state.selected_monster.name,
                value: this.state.selected_monster.id,
            }
        }

        return {
            label: this.props.monsters[0].name,
            value: this.props.monsters[0].id,
        }
    }

    render() {
        return (
            <div className='mt-2'>
                <div className='flex items-center'>
                    <div className='grow w-14'>
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
                </div>

            </div>
        )
    }

}
