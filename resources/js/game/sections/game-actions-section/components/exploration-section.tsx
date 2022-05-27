import React, {Fragment} from "react";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {replace, startCase} from "lodash";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";

export default class ExplorationSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            monster_selected: null,
            time_selected: null,
            attack_type: null,
            move_down_monster_list: null,
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

    setAttackType(data: any) {
        this.setState({
            attack_type: data.value !== '' ? data.value : null,
        });
    }

    setMoveDownList(data: any) {
        this.setState({
            move_down_monster_list: data.value !== '' ? data.value : null,
        });
    }

    timeOptions() {
        return [{
            label: '1 Hour(s)',
            value: 1,
        },{
            label: '2 Hour(s)',
            value: 2,
        },{
            label: '4 Hour(s)',
            value: 4,
        },{
            label: '6 Hour(s)',
            value: 6,
        },{
            label: '8 Hour(s)',
            value: 8,
        }]
    }

    attackTypes() {
        return [{
            label: 'Attack',
            value: 'attack',
        }, {
            label: 'Cast',
            value: 'cast',
        },{
            label: 'Attack and Cast',
            value: 'attack_and_cast',
        },{
            label: 'Cast and Attack',
            value: 'cast_and_attack',
        },{
            label: 'Defend',
            value: 'defend',
        }];
    }

    moveDownTheListEvery() {
        return [{
            label: '5 Levels',
            value: 5,
        }, {
            label: '10 Levels',
            value: 10,
        },{
            label: '20 Levels',
            value: 20,
        }];
    }

    defaultSelectedTime() {
        if (this.state.time_selected != null) {
            return [{
                label: this.state.time_selected + ' Hour(s)',
                value: this.state.time_selected,
            }];
        }

        return [{
            label: 'Please select length of time',
            value: '',
        }]
    }

    defaultAttackType() {
        if (this.state.attack_type !== null) {
            return {
                label: startCase(this.state.attack_type),
                value: this.state.attack_type,
            }
        }

        return {
            label: 'Please select attack type',
            value: '',
        }
    }

    defaultMoveDownList() {
        if (this.state.move_down_monster_list !== null) {
            return {
                label: this.state.move_down_monster_list + ' levels',
                value: this.state.move_down_monster_list,
            }
        }

        return {
            label: 'Please select when to move down the list (optional)',
            value: '',
        }
    }

    startExploration() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('exploration/'+this.props.character.id+'/start').setParameters({
                auto_attack_length: this.state.time_selected,
                move_down_the_list_every: this.state.move_down_monster_list,
                selected_monster_id: this.state.monster_selected.id,
                attack_type: this.state.attack_type,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                console.log(result.data)

                this.setState({
                    loading: false,
                });
            }, (error: AxiosError) => {});
        })
    }

    stopExploration() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('exploration/'+this.props.character.id+'/stop').doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                });
            }, (error: AxiosError) => {});
        })
    }

    render() {
        return(
            <Fragment>
                {
                    this.props.character.is_automation_running ?
                        <Fragment>
                            <div className='mb-4 md:ml-[120px]'>
                                Exploration is running. You can cancel it below. <a href='/information/exploration' target='_blank'>See Exploration Help <i
                                className="fas fa-external-link-alt"></i></a> for more details.
                            </div>

                            {
                                this.state.loading ?
                                    <LoadingProgressBar />
                                    : null
                            }

                            <div className='text-center'>
                                <DangerButton button_label={'Stop Exploration'} on_click={this.stopExploration.bind(this)} disabled={this.state.loading} additional_css={'mr-2'}/>
                                <PrimaryButton button_label={'Close Exploration'} on_click={this.props.manage_exploration} disabled={this.state.loading} />
                            </div>
                        </Fragment>
                    :
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
                                    <div className='mb-3'>
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
                                    <div className='mb-3'>
                                        <Select
                                            onChange={this.setMoveDownList.bind(this)}
                                            options={this.moveDownTheListEvery()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.defaultMoveDownList()}
                                        />
                                    </div>
                                    <div>
                                        <Select
                                            onChange={this.setAttackType.bind(this)}
                                            options={this.attackTypes()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.defaultAttackType()}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className={'lg:text-center md:ml-[-100px] mt-3 mb-3'}>
                                <PrimaryButton button_label={'Explore'} on_click={this.startExploration.bind(this)} disabled={this.state.monster_selected === null || this.state.time_selected === null || this.state.attack_type === null || this.state.loading || this.props.character.is_dead || !this.props.character.can_attack} additional_css={'mr-2'}/>
                                <DangerButton button_label={'Close'} on_click={this.props.manage_exploration} disabled={this.state.loading} />

                                {
                                    this.state.loading ?
                                        <LoadingProgressBar />
                                        : null
                                }

                                <div className='relative top-[24px] italic'>
                                    <p>For more help please the <a href='/information/exploration' target='_blank'>Exploration <i
                                        className="fas fa-external-link-alt"></i></a> help docs.</p>
                                </div>
                            </div>
                        </Fragment>
                }

            </Fragment>
        )
    }
}
