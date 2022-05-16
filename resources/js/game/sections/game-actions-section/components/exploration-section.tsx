import React, {Fragment} from "react";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class ExplorationSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            monster_selected: null,
            time_selected: null,
        }
    }

    setMonsterToFight(data: any) {
        const foundMonster = this.props.monsters.filter((monster: any) => monster.id === parseInt(data.value));

        if (foundMonster.length > 0) {
            this.setState({
                monster_selected: foundMonster[0],
            });
        } else {
            this.setState({
                monster_selected: null,
            });
        }
    }

    monsterOptions() {
        let monsters = this.props.monsters.map((monster: any) => {
            return {label: monster.name, value: monster.id};
        });

        monsters.unshift({
            label: 'Please Select', value: 0,
        });

        return monsters;
    }

    defaultSelectedMonster() {
        if (this.state.monster_selected !== null) {
            return {
                label: this.state.monster_selected.name,
                value: this.state.monster_selected.id,
            }
        }

        return {
            label: 'Please Select Monster',
            value: '',
        }
    }

    setLengthOfTime(data: any) {
        this.setState({
            time_selected: data.value !== '' ? data.value : null,
        });
    }

    timeOptions() {
        return [{
            label: '1 Hour(s)',
            value: 1,
        }, {
            label: '4 Hour(s)',
            value: 4,
        },{
            label: '8 Hour(s)',
            value: 8,
        }]
    }

    defaultSelectedTime() {
        if (this.state.time_selected != null) {
            return [{
                label: this.state.time_selected + ' Hour(s)',
                value: this.state.time_selected,
            }];
        }

        return [{
            label: 'Please Select',
            value: '',
        }]
    }

    startExploration() {
        console.log('Start exploration here .... ');
    }

    render() {
        return(
            <Fragment>
                <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                    <div className='cols-start-1 col-span-2'>
                        <div className='mb-3'>
                            <Select
                                onChange={this.setMonsterToFight.bind(this)}
                                options={this.monsterOptions()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.defaultSelectedMonster()}
                            />
                        </div>
                        <div>
                            <Select
                                onChange={this.setLengthOfTime.bind(this)}
                                options={this.timeOptions()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.defaultSelectedTime()}
                            />
                        </div>
                    </div>
                </div>

                <div className={'lg:text-center md:ml-[-100px] mt-3 mb-3'}>
                    <PrimaryButton button_label={'Explore'} on_click={this.startExploration.bind(this)} disabled={this.state.monster_selected === null || this.state.time_selected === null } additional_css={'mr-2'}/>
                    <DangerButton button_label={'Close'} on_click={this.props.manage_exploration} />


                    <div className='relative top-[24px] italic'>
                        <p>For more help please the <a href='/information/exploration' target='_blank'>Exploration <i
                            className="fas fa-external-link-alt"></i></a> help docs.</p>
                    </div>
                </div>
            </Fragment>
        )
    }
}
