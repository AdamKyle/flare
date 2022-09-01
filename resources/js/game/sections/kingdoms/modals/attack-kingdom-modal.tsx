import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class AttackKingdomModal extends React.Component<any, any> {

    private tabs: {key: string, name: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'use-items',
            name: 'Use items'
        }, {
            key: 'send-units',
            name: 'Send Units',
        }]

        this.state = {
            loading: false,
            fetching_data: true,
            items_to_use: [],
            kingdoms: [],
        }
    }

    componentDidMount() {
        (new Ajax).setRoute('fetch-attacking-data/' + this.props.kingdom_to_attack_id + '/' + this.props.character_id)
                  .doAjaxCall('get', (result: AxiosResponse) => {
                      console.log(result.data);
                  }, (error: AxiosError) => {
                      console.error(error);
                  });
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Attack Kingdom'}
                      primary_button_disabled={this.state.loading}

            >
                {
                    this.state.fetching_data ?
                        <div className='py-4'>
                            <ComponentLoading />
                        </div>
                    :
                        <Tabs tabs={this.tabs} disabled={this.state.loading}>
                            <TabPanel key={'use-items'}>
                                Items Selection
                            </TabPanel>

                            <TabPanel key={'send-units'}>
                                Units Selections
                            </TabPanel>
                        </Tabs>
                }

            </Dialogue>
        );
    }
}
