import React from "react";
import {DialogActions} from "@mui/material";
import DangerButton from "../../components/ui/buttons/danger-button";
import EventSchedulerModalProps from "../types/components/event-scheduler-modal-props";

export default class EventSchedulerModal extends React.Component<EventSchedulerModalProps, {}> {

    constructor(props: EventSchedulerModalProps) {
        super(props);
    }

    closeEventManagement() {
        this.props.scheduler.close();
    }

    render() {
        return (
            <div className='w-[500px] p-[1rem] dark:bg-gray-800'>
                <p className='text-red-600 dark:text-red-500'>
                    You are not allowed to add events.
                </p>

                <div className='absolute bottom-0 right-0'>
                    <DialogActions>
                        <DangerButton button_label={'Close'} on_click={this.closeEventManagement.bind(this)}/>
                    </DialogActions>
                </div>
            </div>
        )
    }
}
