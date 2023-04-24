import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {DialogActions} from "@mui/material";
import DangerButton from "../../../components/ui/buttons/danger-button";
import DatePicker from "react-datepicker";
import {setHours, setMinutes} from "date-fns";

import "react-datepicker/dist/react-datepicker.css";

export default class EventSchedulerEditor extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_event_type: null,
            selected_start_date: setHours(setMinutes(new Date(), 0), 9),
            selected_end_date: setHours(setMinutes(new Date(), 0), 9),
        }
    }

    setEventType(data: any) {
        if (data.value < 0) {
            return;
        }

        this.setState({
            selected_event_type: data.value
        });
    }

    setDescription(event: any) {
        console.log(event);
    }

    setStartDate(date: Date | null) {
        this.setState({
            selected_start_date: date,
        });
    }

    setEndDate(date: Date | null) {
        this.setState({
            selected_end_date: date,
        });
    }

    optionsForEventType() {
        return [{
            label: 'Please select a type',
            value: -1
        }, {
            label: 'Raid Event',
            value: 0,
        }];
    }

    getSelectedEventType() {
        if (this.state.selected_event_type === null) {
            return [{
                label: 'Please select a type',
                value: -1
            }]
        }

        switch(this.state.selected_event_type) {
            case 0:
                return [{
                    label: 'Raid Event',
                    value: 0,
                }];
            default:
                return [{
                    label: 'Please select a type',
                    value: -1
                }]
        }
    }

    filterPassedTime(time: any)  {
        const currentDate = new Date();
        const selectedDate = new Date(time);

        return currentDate.getTime() < selectedDate.getTime();
    };

    saveEvent() {
        this.props.scheduler.close();
    }

    closeEventManagement() {
        this.props.scheduler.close();
    }


    render() {
        return (
            <div className='w-[500px] p-[1rem]'>
                <Select
                    onChange={this.setEventType.bind(this)}
                    options={this.optionsForEventType()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.getSelectedEventType()}
                />

                {
                    this.state.selected_event_type === 0 ?
                        <div className='my-4'>
                            <Select
                                onChange={this.setEventType.bind(this)}
                                options={this.optionsForEventType()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.getSelectedEventType()}
                            />
                        </div>
                    : null
                }

                <div className='my-4'>
                    <div className='my-3'><strong>Description</strong></div>
                    <textarea rows={5} cols={45} onChange={this.setDescription.bind(this)} className='border-2 border-gray-300'/>
                </div>

                <div className='grid md:grid-cols-2 gap-2 mb-8'>
                    <div className='my-4'>
                        <div className='my-3'><strong>Start Date (and time)</strong></div>
                        <DatePicker
                            selected={this.state.selected_start_date}
                            onChange={(date) => this.setStartDate(date)}
                            showTimeSelect
                            filterTime={this.filterPassedTime.bind(this)}
                            dateFormat="MMMM d, yyyy h:mm aa"
                            className={'border-2 border-gray-300 rounded-md p-2'}
                            withPortal
                        />
                    </div>

                    <div className='my-4'>
                        <div className='my-3'><strong>End Date (and time)</strong></div>
                        <DatePicker
                            selected={this.state.selected_end_date}
                            onChange={(date) => this.setEndDate(date)}
                            showTimeSelect
                            filterTime={this.filterPassedTime.bind(this)}
                            dateFormat="MMMM d, yyyy h:mm aa"
                            className={'border-2 border-gray-300 rounded-md p-2'}
                            withPortal
                        />
                    </div>
                </div>

                <div className='absolute bottom-0 right-0'>
                    <DialogActions>
                        <DangerButton button_label={'Cancel'} on_click={this.closeEventManagement.bind(this)} />
                        <PrimaryButton button_label={'Save Event'} on_click={this.saveEvent.bind(this)} />
                    </DialogActions>
                </div>
            </div>
        )
    }
}
