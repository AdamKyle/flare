import React from "react";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {DialogActions} from "@mui/material";
import DangerButton from "../../../components/ui/buttons/danger-button";
import "react-datepicker/dist/react-datepicker.css";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import EventSchedulerForm from "./event-scheduler-form";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";

export default class EventSchedulerEditor extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            form_data: {},
            error_message: null,
            is_saving: false,
        }
    }

    saveEvent() {

        if (!this.state.form_data.hasOwnProperty('selected_start_date')) {
            this.setState({
                error_message: 'Missing start date of the event.'
            });

            return;
        }

        if (!this.state.form_data.hasOwnProperty('selected_end_date')) {
            this.setState({
                error_message: 'Missing end date of the event.'
            });

            return;
        }

        this.setState({
            is_saving: true,
        });

        (new Ajax).setRoute('admin/create-new-event').setParameters(this.state.form_data).doAjaxCall('post',
            (result: AxiosResponse) => {
                this.props.scheduler.close();
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    this.setState({
                        error_message: error.response.data.message,
                        is_saving: false
                    })
                }

                console.error(error);
            });
    }

    closeEventManagement() {
        this.props.scheduler.close();
    }

    updateParentData(formData: any) {
        this.setState({
            form_data: formData
        });
    }


    render() {
        return (
            <div className='w-[500px] p-[1rem] dark:bg-gray-800'>
                <h4 className='my-4 font-bold text-blue-600'>Manage Event</h4>

                {
                    this.state.is_saving ?
                        <LoadingProgressBar />
                    : null
                }

                {
                    this.props.is_loading ?
                        <div className='pb-6'>
                            <LoadingProgressBar />
                        </div>
                    :
                        <EventSchedulerForm raids={this.props.raids} event_data={this.props.scheduler.edited} update_parent={this.updateParentData.bind(this)} />
                }

                <div className='absolute bottom-0 right-0'>
                    <DialogActions>
                        <DangerButton button_label={'Cancel'} on_click={this.closeEventManagement.bind(this)} disabled={this.props.is_loading || this.state.is_saving}/>
                        <PrimaryButton button_label={'Save Event'} on_click={this.saveEvent.bind(this)} disabled={this.props.is_loading || this.state.is_saving}/>
                    </DialogActions>
                </div>
            </div>
        )
    }
}
