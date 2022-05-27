import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {watchForDarkModeTableChange} from "../../../lib/game/dark-mode-watcher";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import {DateTime} from "luxon";
import Table from "../../../components/ui/data-tables/table";
import {formatNumber} from "../../../lib/game/format-number";
import InventoryUseDetails from "./modals/inventory-use-details";

export default class CharacterActiveBoons extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            boons: [],
            dark_tables: false,
            show_usable_details: false,
            item_to_use: null,
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
                console.log(error);
            })
        }
    }

    manageBoon(row?: any) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof row !== 'undefined' ? row.boon_applied : null,
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
                <div className="relative top-[20px]">
                    <ComponentLoading/>
                </div>
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
                    <Table columns={this.buildColumns()} data={this.state.boons} dark_table={this.state.dark_tables} />
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
