import React, {Fragment} from "react";
import Select from "react-select";
import DatePicker from "react-datepicker";
import {setHours, setMinutes} from "date-fns";

export default class EventSchedulerForm extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_event_type: null,
            event_description: null,
            selected_raid: null,
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
        }, () => {
            this.props.update_parent(this.state);
        });
    }

    setDescription(event: React.ChangeEvent<HTMLTextAreaElement>) {
        this.setState({
            event_description: event.target.value,
        }, () => {
            this.props.update_parent(this.state);
        });
    }

    setStartDate(date: Date | null) {
        this.setState({
            selected_start_date: date,
        }, () => {
            this.props.update_parent(this.state);
        });
    }

    setEndDate(date: Date | null) {
        this.setState({
            selected_end_date: date,
        }, () => {
            this.props.update_parent(this.state);
        });
    }

    setRaidEvent(data: any) {

        if (data.value === 0) {
            return;
        }

        this.setState({
            selected_raid: data.value
        }, () => {
            this.props.update_parent(this.state);
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

    optionsForRaids() {
        const raids = this.props.raids.map((raid: any) => {
            return {
                label: raid.name,
                value: raid.id,
            }
        });

        raids.unshift({
            label: 'Please Select a raid',
            value: 0,
        })

        return raids;
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

    getSelectedRaid() {
        const foundRaid = this.props.raids.find((raid: any) => raid.id === this.state.selected_raid);

        if (typeof foundRaid !== 'undefined') {
            return [{
                label: foundRaid.name,
                value: foundRaid.id,
            }]
        }

        return [{
            label: 'Please select a raid',
            value: 0,
        }]
    }

    filterPassedTime(time: any)  {
        const currentDate = new Date();
        const selectedDate = new Date(time);

        return currentDate.getTime() < selectedDate.getTime();
    };

    render() {
        return (
            <Fragment>
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
                                onChange={this.setRaidEvent.bind(this)}
                                options={this.optionsForRaids()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.getSelectedRaid()}
                            />
                        </div>
                        : null
                }

                <div className='my-4'>
                    <div className='my-3 dark:text-gray-300'><strong>Description</strong></div>
                    <textarea rows={5} cols={45} onChange={this.setDescription.bind(this)} className='border-2 border-gray-300 p-4'/>
                </div>

                <div className='grid md:grid-cols-2 gap-2 mb-8'>
                    <div className='my-4'>
                        <div className='my-3 dark:text-gray-300'><strong>Start Date (and time)</strong></div>
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
                        <div className='my-3 dark:text-gray-300'><strong>End Date (and time)</strong></div>
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
            </Fragment>
        )
    }
}
