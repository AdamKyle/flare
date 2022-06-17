import React from "react";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import {startCase} from "lodash";
import Select from "react-select";

export default class JoinPvp extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            attack_type: null,
        }
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

    setAttackType(data: any) {
        this.setState({
            attack_type: data.value !== '' ? data.value : null,
        });
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

    joinPvp() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('join-monthly-pvp/' + this.props.character_id).setParameters({
              attack_type: this.state.attack_type,
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    loading: false,
                }, () => {
                    this.props.manage_section();
                });
            }, (error: AxiosError) => {});
        });
    }

    render() {
        return (
            <div className='mt-2 md:ml-[120px]'>
                <div className='mt-2 grid md:grid-cols-3 gap-2'>
                    <div className='md:cols-start-1 md:col-span-2'>
                        <p className='mb-4'>
                            Here you can choose to join in the monthly PVP event. It is suggested you read this
                            <a href='/information/monthly-pvp-event' target='_blank' className='ml-2'>Help Document <i
                                className="fas fa-external-link-alt"></i></a> before continuing.
                        </p>
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
                        {
                            this.state.loading ?
                                <LoadingProgressBar />
                            : null
                        }
                        <PrimaryButton button_label={'Join Tonight\'s Event!'} on_click={this.joinPvp.bind(this)} additional_css={'mt-4'} disabled={this.state.attack_type === null} />
                        <DangerButton button_label={'Close Section'} on_click={this.props.manage_section} additional_css={'mt-4 md:mt-0 md:mt-4 md:ml-4'} />
                    </div>
                </div>
            </div>
        );
    }
}
