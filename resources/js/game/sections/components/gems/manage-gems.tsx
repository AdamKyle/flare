import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AddingTheGem from "./adding-the-gem";
import ReplacingAGem from "./replacing-a-gem";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import {formatNumber} from "../../../lib/game/format-number";
import SeerActions from "../../../lib/game/actions/seer-camp/seer-actions";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";

export default class ManageGems extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            gem_to_attach: [],
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
            trading_with_seer: false,
            error_message: null,

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
                gem_to_attach: result.data.gem_to_attach,
                when_replacing: result.data.when_replacing,
                has_gem_on_item: result.data.has_gem_on_item,
            })
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    doAction(action: 'attach-gem' | 'replace-gem') {
        if (action === 'attach-gem') {
            this.setState({
                trading_with_seer: true,
                error_message: null
            }, () => {
                SeerActions.attachGemToItem(this, this.props.selected_item, this.props.selected_gem)
            });
        }
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Seer Socketing Table'}
                      primary_button_disabled={this.state.trading_with_seer}
            >
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    :
                        <Fragment>
                            <InfoAlert additional_css='my-4'>
                                <span className='text-[18px]'>
                                    <strong>The cost for Attaching or replacing is: {formatNumber(this.props.cost)} Gold Bars.</strong>
                                </span>
                            </InfoAlert>
                            {
                                this.state.trading_with_seer ?
                                    <div className='my-4'>
                                        <LoadingProgressBar />
                                    </div>
                                : null
                            }
                            {
                                this.state.error_message !== null ?
                                    <DangerAlert additional_css='my-4'>
                                        {this.state.error_message}
                                    </DangerAlert>
                                : null
                            }
                            <Tabs tabs={this.state.tabs} disabled={false}>
                                <TabPanel key={'add-gem'}>
                                    <AddingTheGem gem_to_add={this.state.gem_to_attach} do_action={this.doAction.bind(this)} action_disabled={this.state.trading_with_seer}/>
                                </TabPanel>
                                <TabPanel key={'replace-gem'}>
                                    <ReplacingAGem when_replacing={this.state.when_replacing} gems_you_have={this.state.attached_gems} action_disabled={this.state.trading_with_seer}/>
                                </TabPanel>
                            </Tabs>
                        </Fragment>
                }
            </Dialogue>
        );
    }

}
