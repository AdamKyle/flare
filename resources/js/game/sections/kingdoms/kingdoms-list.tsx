import React, {Fragment} from "react";
import KingdomListProps from "../../lib/game/kingdoms/types/kingdom-list-props";
import ComponentLoading from "../../components/ui/loading/component-loading";
import Table from "../../components/ui/data-tables/table";
import {buildKingdomsColumns} from "../../lib/game/kingdoms/build-kingdoms-columns";
import KingdomDetails from "../../lib/game/kingdoms/kingdom-details";
import {watchForDarkModeTableChange} from "../../lib/game/dark-mode-watcher";
import KingdomListState from "../../lib/game/kingdoms/types/kingdom-list-state";
import BasicCard from "../../components/ui/cards/basic-card";
import Kingdom from "./kingdom";
import SmallKingdom from "./small-kingdom";
import {isEqual} from "lodash";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import {buildLogsColumns} from "../../lib/game/kingdoms/build-logs-columns";
import KingdomLogDetailsView from "./kingdom-log-details";
import KingdomLogDetails from "../../lib/game/kingdoms/kingdom-log-details";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";

export default class KingdomsList extends React.Component<KingdomListProps, KingdomListState> {

    private tabs: {name: string, key: string, has_logs?: boolean;}[];

    constructor(props: KingdomListProps) {
        super(props);

        this.tabs = [{
            name: 'Kingdoms',
            key: 'kingdoms',
        }, {
            name: 'Logs',
            key: 'kingdom-logs',
            has_logs: false,
        }]

        this.state = {
            loading: true,
            dark_tables: false,
            selected_kingdom: null,
            selected_log: null,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        const self = this;

        setTimeout(function(){
            self.setState({
                loading: false,
            })
        }, 500);

        this.updateIcon();
    }

    componentDidUpdate() {
        const foundKingdom = this.props.my_kingdoms.filter((kingdom: KingdomDetails) => {
            if (this.state.selected_kingdom === null) {
                return;
            }

            return kingdom.id === this.state.selected_kingdom.id;
        });

        if (foundKingdom.length > 0) {
            const kingdom: KingdomDetails = foundKingdom[0];

            if (!isEqual(kingdom, this.state.selected_kingdom)) {
                this.setState({
                    selected_kingdom: kingdom
                })
            }
        }

        this.updateIcon();
    }

    updateIcon() {
        if (this.props.logs.length > 0) {
            const hasUnReadLogs = this.props.logs.filter((log: KingdomLogDetails) => {
                return !log.opened
            });

            if (hasUnReadLogs.length > 0) {
                this.tabs[this.tabs.length - 1].has_logs = true;
            } else {
                this.tabs[this.tabs.length - 1].has_logs = false;
            }
        }
    }

    viewKingdomDetails(kingdom: KingdomDetails) {
        this.setState({
            selected_kingdom: kingdom,
        });
    }

    viewLogs(log: KingdomLogDetails) {
        if (!log.opened) {
            (new Ajax).setRoute('kingdom/opened-log/'+log.character_id+'/'+log.id).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    selected_log: log,
                })
            }, (error: AxiosError) => {
                console.error(error);
            });
        } else {
            this.setState({
                selected_log: log,
            });
        }
    }

    deleteLog(log: KingdomLogDetails) {
        (new Ajax).setRoute('kingdom/delete-log/'+log.character_id+'/'+log.id).doAjaxCall('post', (result: AxiosResponse) => {
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    closeKingdomDetails() {
        this.setState({
            selected_kingdom: null,
        });
    }

    closeLogDetails() {
        this.setState({
            selected_log: null,
        });
    }

    render() {
        if (this.state.loading) {
            return (
                <LoadingProgressBar />
            );
        }

        return (
            <Fragment>
                {
                    this.state.selected_kingdom !== null ?
                        this.props.view_port < 1600 ?
                            <SmallKingdom close_details={this.closeKingdomDetails.bind(this)}
                                          kingdom={this.state.selected_kingdom}
                                          dark_tables={this.state.dark_tables}
                                          character_gold={this.props.character_gold}
                            />
                        :
                            <Kingdom close_details={this.closeKingdomDetails.bind(this)}
                                     kingdom={this.state.selected_kingdom}
                                     dark_tables={this.state.dark_tables}
                                     character_gold={this.props.character_gold}
                            />
                    : this.state.selected_log !== null ?
                        <KingdomLogDetailsView
                            close_details={this.closeLogDetails.bind(this)}
                            log={this.state.selected_log}
                        />
                    :
                        <BasicCard additionalClasses={'overflow-x-auto'}>
                            <Tabs tabs={this.tabs} icon_key={'has_logs'}>
                                <TabPanel key={'kingdoms'}>
                                    {
                                        this.props.my_kingdoms.length > 0 ?
                                            <div className={'max-w-[290px] sm:max-w-[100%] overflow-x-hidden'}>
                                                <Table data={this.props.my_kingdoms}
                                                       columns={buildKingdomsColumns(this.viewKingdomDetails.bind(this))}
                                                       dark_table={this.state.dark_tables}
                                                />
                                            </div>
                                        :
                                            <Fragment>
                                                <p className='my-4 text-center'>
                                                    No Settled Kingdoms.
                                                </p>
                                                <p className='text-center'>
                                                    <a href="/information/kingdoms" target="_blank">
                                                        What are and how to get kingdoms. <i
                                                        className="fas fa-external-link-alt"></i>
                                                    </a>
                                                </p>
                                            </Fragment>
                                    }
                                </TabPanel>
                                <TabPanel key={'kingdom-logs'}>
                                    {
                                        this.props.logs.length > 0 ?
                                            <div className={'max-w-[290px] sm:max-w-[100%] overflow-x-hidden'}>
                                                <Table data={this.props.logs}
                                                       columns={buildLogsColumns(this.viewLogs.bind(this), this.deleteLog.bind(this))}
                                                       dark_table={this.state.dark_tables}
                                                />
                                            </div>
                                        :
                                            <p className='my-4 text-center'>
                                                No Logs.
                                            </p>
                                    }
                                </TabPanel>
                            </Tabs>
                        </BasicCard>
                }
            </Fragment>
        )
    }

}
