import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {watchForDarkModeTableChange} from "../../../lib/game/dark-mode-watcher";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import {DateTime} from "luxon";
import Table from "../../../components/ui/data-tables/table";
import {formatNumber} from "../../../lib/game/format-number";
import InventoryUseDetails from "./modals/inventory-use-details";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";

export default class CharacterActiveBoons extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            boons: [],
            dark_tables: false,
            show_usable_details: false,
            item_to_use: null,
            removing_boon: false,
            error_message: null,
            success_message: null,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        if (this.props.finished_loading && this.props.character_id !== null) {

            (new Ajax()).setRoute('character-sheet/' + this.props.character_id + '/active-boons').doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    boons: result.data.active_boons,
                });
            }, (error: AxiosError) => {
                console.error(error);
            })
        }
    }

    manageBoon(row?: any) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof row !== 'undefined' ? row.boon_applied : null,
        });
    }

    removeBoon(boonId: number) {
        this.setState({
            removing_boon: true,
            success_message: null,
            error_message: null,
        }, () => {
            (new Ajax()).setRoute('character-sheet/'+this.props.character_id+'/remove-boon/' + boonId)
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        removing_boon: false,
                        boons: result.data.boons,
                        success_message: result.data.message,
                    })
                }, (error: AxiosError) => {

                    let message = 'UNKNOWN ERROR - CHECK CONSOLE!';

                    if (error.response !== undefined) {
                        const response: AxiosResponse = error.response;

                        message = response.data.message;
                    }

                    this.setState({
                        removing_boon: false,
                        error_message: message,
                    });

                    console.error(error.response);
                });
        });

    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: { boon_applied: { name: string; } }) => row.boon_applied.name,
                sortable: true,
                cell: (row: { id: number; boon_applied: { name: string; }; }) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    <button onClick={() => this.manageBoon(row)} className='text-sky-600 dark:text-sky-300'>{row.boon_applied.name}</button>
                </span>
            },
            {
                name: 'Time Remaining',
                selector: (row: { started: string; completed: string }) => row.started,
                sortable: true,
                cell: (row: { started: string; complete: string }) => <span
                    key={row.started + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {this.getLabel(row.started, row.complete)}
                </span>
            },
            {
                name: 'Actions',
                selector: (row: { id: number; boon_applied: { id: number; } }) => row.boon_applied.id,
                sortable: true,
                cell: (row: { id: number; boon_applied: { id: number; }}) => <span
                    key={row.boon_applied.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    <DangerButton button_label={'Remove Boon'} on_click={() => this.removeBoon(row.id)} />
                </span>
            },
        ]
    }

    getLabel(startedAt: string, completedAt: string): string {
        let label = 'seconds';

        const started   = DateTime.now();
        const completed = DateTime.fromISO(completedAt);

        let time  = completed.diff(started, ['seconds']).toObject().seconds;

        if (typeof time === 'undefined') {
            return 'Error';
        }

        if (time / 3600 >= 1) {
            label = formatNumber(time / 3600) + ' hour(s)';
        } else if (time / 60 >= 1) {
            label = formatNumber(time / 60)  + ' minute(s)';
        }

        return label;
    }

    render() {
        if (this.state.loading) {
            return (
                <LoadingProgressBar />
            )
        }

        return (
            <Fragment>

                <div className='my-5'>
                    {
                        this.state.boons.length > 0 ?
                            <InfoAlert>
                                This tab does not update in real time. You can switch tabs to get the latest data.
                            </InfoAlert>
                        : null
                    }
                    {
                        this.state.removing_boon ?
                            <LoadingProgressBar />
                        : null
                    }
                    {
                        this.state.success_message !== null ?
                            <SuccessAlert additional_css={'my-4'}>
                                <p>{this.state.success_message}</p>
                            </SuccessAlert>
                        : null
                    }
                    {
                        this.state.error_message !== null ?
                            <DangerAlert additional_css={'my-4'}>
                                <p>{this.state.error_message}</p>
                            </DangerAlert>
                        : null
                    }
                    <p className='my-4 text-center'>
                        <a href="/information/alchemy" target="_blank">
                            What are boons and how do I get them? <i
                            className="fas fa-external-link-alt"></i>
                        </a>
                    </p>
                    {
                        this.state.boons.length > 0 ?
                            <div className='max-w-[390px] md:max-w-full overflow-x-hidden'>
                                <Table columns={this.buildColumns()} data={this.state.boons} dark_table={this.state.dark_tables} />
                            </div>
                        :
                            <p className='my-4 text-center'>
                                No Active Boons.
                            </p>
                    }

                </div>

                {
                    this.state.show_usable_details && this.state.item_to_use !== null ?
                        <InventoryUseDetails
                            is_open={this.state.show_usable_details}
                            manage_modal={this.manageBoon.bind(this)}
                            item={this.state.item_to_use}
                        />
                        : null
                }
            </Fragment>
        )
    }
}
