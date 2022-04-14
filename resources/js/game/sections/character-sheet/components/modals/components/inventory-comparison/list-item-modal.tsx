import React, {Fragment} from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../../../components/ui/item-name-coloration-text";
import {LineChart} from "../../../../../../components/ui/charts/line-chart";
import Ajax from "../../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../../../../components/ui/loading/component-loading";

export default class ListItemModal extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('market-board/items').setParameters({
            params: {
                item_id: this.props.item.id
            }
        }).doAjaxCall('get', (result: AxiosResponse) => {
            console.log(result.data);
        }, (error: AxiosError) => {

        })
    }

    listItem() {
        this.props.list_item();

        this.props.manage_modal();
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'List item on market'}
                      secondary_actions={{
                          secondary_button_disabled: false,
                          secondary_button_label: 'List item',
                          handle_action: () => this.listItem()
                      }}
            >
                {
                    this.state.loading ?
                        <div className='p-5 mb-2'><ComponentLoading /></div>
                    :
                        <Fragment>
                            <h3 className='mb-4 mt-4'><ItemNameColorationText item={{...this.props.item, ['name']: this.props.item.affix_name}} /></h3>
                            <LineChart dark_chart={this.props.dark_charts}/>
                        </Fragment>
                }
            </Dialogue>
        );
    }
}
