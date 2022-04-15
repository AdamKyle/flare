import React, {Fragment} from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../../../components/ui/item-name-coloration-text";
import {MarketBoardLineChart} from "../../../../../../components/ui/charts/line-chart";
import Ajax from "../../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../../../../components/ui/loading/component-loading";
import {DateTime} from "luxon";

export default class ListItemModal extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            listed_value: 0,
            data: [],
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('market-board/items').setParameters({
            params: {
                item_id: this.props.item.id
            }
        }).doAjaxCall('get', (result: AxiosResponse) => {
            const data = result.data.items.map((item: { listed_at: string, listed_price: number }) => {
                return {
                    date: (DateTime.fromISO(item.listed_at)).toLocaleString({
                        weekday: 'short',
                        month: 'short',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    }).toString(),
                    price: item.listed_price
                }
            });

            if (data.length === 0) {
                data.push({
                    date: (DateTime.now()).toJSDate(),
                    price: 0
                });
            }

            const chartData = {
                label: 'Listed for (Gold)',
                color: '#441414',
                data: data,
            }

            this.setState({
                loading: false,
                data: [chartData],
            })
        }, (error: AxiosError) => {

        })
    }

    listItem() {
        this.props.list_item(this.state.listed_value);

        this.props.manage_modal();
    }

    setListedPrice(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            listed_value: parseInt(e.target.value) || 0,
        })
    }

    render() {
        console.log(this.state);
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'List item on market'}
                      secondary_actions={{
                          secondary_button_disabled: this.state.listed_value <= 0,
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
                            <MarketBoardLineChart dark_chart={this.props.dark_charts} data={this.state.data} key_for_value={'price'} />
                            <div className="mb-5 mt-5">
                                <label className="label block mb-2" htmlFor="list-for">List For</label>
                                <input id="list-for" type="number" className="form-control" name="list-for" value={this.state.listed_value} autoFocus onChange={this.setListedPrice.bind(this)}/>
                            </div>
                        </Fragment>
                }
            </Dialogue>
        );
    }
}
