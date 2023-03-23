import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import clsx from "clsx";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AddingTheGem from "./adding-the-gem";
import ReplacingAGem from "./replacing-a-gem";

export default class ManageGems extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            when_adding: [],
            when_replacing: [],
            has_gems_on_item: false,
            attached_gems: [],
            tabs: [{
                key: 'add-gem',
                name: 'Add Gem',
            }, {
                key: 'replace_gem',
                name: 'Replace Gem'
            }],

        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('gem-comparison/' + this.props.character_id).setParameters({
            slot_id: this.props.selected_item,
            gem_slot_id: this.props.selected_gem,
        }).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                attached_gems: result.data.attached_gems,
                when_adding: result.data.when_adding,
                when_replacing: result.data.when_replacing,
                has_gem_on_item: result.data.has_gem_on_item,
            })
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Seer Socketing Table'}
            >
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    :
                        <Fragment>
                            <Tabs tabs={this.state.tabs} disabled={false}>
                                <TabPanel key={'add-gem'}>
                                    <AddingTheGem />
                                </TabPanel>
                                <TabPanel key={'replace-gem'}>
                                    <ReplacingAGem />
                                </TabPanel>
                            </Tabs>
                        </Fragment>
                }
            </Dialogue>
        );
    }

}
