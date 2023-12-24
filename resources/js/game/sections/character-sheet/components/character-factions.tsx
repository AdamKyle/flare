import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {watchForDarkModeTableChange} from "../../../lib/game/dark-mode-watcher";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Table from "../../../components/ui/data-tables/table";
import {formatNumber} from "../../../lib/game/format-number";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class CharacterFactions extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            factions: [],
            dark_tables: false,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        if (this.props.character_id !== null && this.props.finished_loading) {
            (new Ajax()).setRoute('character-sheet/' + this.props.character_id + '/factions').doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    factions: result.data.factions,
                });
            }, (error: AxiosError) => {
                console.error(error);;
            })
        }
    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: any) => row.name,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {row.map_name}
                </span>
            },
            {
                name: 'Title',
                selector: (row: any) => row.title,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {row.title !== null ? row.title : 'N/A'}
                </span>
            },
            {
                name: 'Level',
                selector: (row: any) => row.current_level,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {row.current_level}
                </span>
            },
            {
                name: 'Points',
                selector: (row: any) => row.points_needed,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {formatNumber(row.current_points)} / {formatNumber(row.points_needed)}
                </span>
            },
            {
                name: 'Pledge Loyalty',
                selector: (row: any) => row.id,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    <PrimaryButton button_label={'Pledge Loyalty'} on_click={() => { console.log(row) }} disabled={!row.maxed}/>
                </span>
            },
        ];
    }

    pledgeLoyalty(row: any): void {

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
                        this.state.factions.length > 0 ?
                            <InfoAlert additional_css={'mb-4'}>
                                This tab does not update in real time. You can switch tabs to get the latest data. You can learn more about <a href='/information/factions' target='_blank'>Factions <i
                                className="fas fa-external-link-alt"></i></a> in the help docs. Players who reach the max level (5) of a faction can then <a href='/information/faction-loyalty' target='_blank'>Pledge their loyalty</a>.
                            </InfoAlert>
                            : null
                    }
                    <div className={'max-w-[290px] sm:max-w-[100%] overflow-x-hidden'}>
                        <Table columns={this.buildColumns()} data={this.state.factions} dark_table={this.state.dark_tables} />
                    </div>
                </div>
            </Fragment>
        )
    }
}
