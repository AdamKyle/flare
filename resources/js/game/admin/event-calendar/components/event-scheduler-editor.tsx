import React from "react";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {DialogActions} from "@mui/material";
import DangerButton from "../../../components/ui/buttons/danger-button";
import "react-datepicker/dist/react-datepicker.css";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import EventSchedulerForm from "./event-scheduler-form";

export default class EventSchedulerEditor extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            form_data: {},
        }
    }

    saveEvent() {
        this.props.scheduler.close();
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
                    this.props.is_loading ?
                        <div className='pb-6'>
                            <LoadingProgressBar />
                        </div>
                    :
                        <EventSchedulerForm raids={this.props.raids} update_parent={this.updateParentData.bind(this)} />
                }

                <div className='absolute bottom-0 right-0'>
                    <DialogActions>
                        <DangerButton button_label={'Cancel'} on_click={this.closeEventManagement.bind(this)} disabled={this.props.is_loading}/>
                        <PrimaryButton button_label={'Save Event'} on_click={this.saveEvent.bind(this)} disabled={this.props.is_loading}/>
                    </DialogActions>
                </div>
            </div>
        )
    }
}
