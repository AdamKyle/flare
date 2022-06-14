import React from "react";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";

export default class JoinPvp extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
        }
    }

    joinPvp() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('join-monthly-pvp/' + this.props.character_id).doAjaxCall('post', (response: AxiosResponse) => {
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
                <div className='mt-2 grid grid-cols-3 gap-2'>
                    <div className='cols-start-1 col-span-2'>
                        <p>
                            Here you can choose to join in the monthly PVP event. It is suggested you read this
                            <a href='/information/monthly-pvp-event' target='_blank' className='ml-2'>Help Document <i
                                className="fas fa-external-link-alt"></i></a> before continuing.
                        </p>
                        {
                            this.state.loading ?
                                <LoadingProgressBar />
                            : null
                        }
                        <PrimaryButton button_label={'Join Tonight\'s Event!'} on_click={this.joinPvp.bind(this)} additional_css={'mt-4'} />
                        <DangerButton button_label={'Close Section'} on_click={this.props.manage_section} additional_css={'mt-4 ml-4'} />
                    </div>
                </div>
            </div>
        );
    }
}
