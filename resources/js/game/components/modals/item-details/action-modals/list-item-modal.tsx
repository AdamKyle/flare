import React, { Fragment } from "react";
import Dialogue from "../../../ui/dialogue/dialogue";
import ItemNameColorationText from "../../../items/item-name/item-name-coloration-text";
import { MarketBoardLineChart } from "../../../ui/charts/line-chart";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../ui/loading/component-loading";
import { DateTime } from "luxon";
import InfoAlert from "../../../ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../../lib/game/format-number";

export default class ListItemModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            listed_value: 0,
            data: [],
        };
    }

    componentDidMount() {
        if (this.props.item.min_cost > 0) {
            this.setState({
                listed_value: this.props.item.min_cost,
            });
        }

        new Ajax()
            .setRoute("market-board/items")
            .setParameters({
                item_id: this.props.item.item_id,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    const data = result.data.items.map(
                        (item: { listed_at: string; listed_price: number }) => {
                            return {
                                date: DateTime.fromISO(item.listed_at)
                                    .toLocaleString({
                                        weekday: "short",
                                        month: "short",
                                        day: "2-digit",
                                        hour: "2-digit",
                                        minute: "2-digit",
                                        second: "2-digit",
                                    })
                                    .toString(),
                                price: item.listed_price,
                            };
                        },
                    );

                    const now = DateTime.now()
                        .toLocaleString({
                            weekday: "short",
                            month: "short",
                            day: "2-digit",
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                        })
                        .toString();

                    if (data.length === 0) {
                        data.push({
                            date: now,
                            price: 0,
                        });
                    }

                    const chartData = {
                        label: "Listed for (Gold)",
                        color: "#441414",
                        data: data,
                    };

                    this.setState({
                        loading: false,
                        data: [chartData],
                    });
                },
                (error: AxiosError) => {},
            );
    }

    listItem() {
        this.props.list_item(this.state.listed_value);

        this.props.manage_modal();
    }

    setListedPrice(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value) || 0;

        if (this.props.item.min_cost > value) {
            value = this.props.item.min_cost;
        }

        if (value > 2000000000000000) {
            value = 2000000000000000;
        }

        this.setState({
            listed_value: value,
        });
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"List item on market"}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.listed_value <= 0 ||
                        this.state.listed_value === "",
                    secondary_button_label: "List item",
                    handle_action: () => this.listItem(),
                }}
            >
                {this.state.loading ? (
                    <div className="p-5 mb-2">
                        <ComponentLoading />
                    </div>
                ) : (
                    <Fragment>
                        <h3 className="mb-4 mt-4">
                            <ItemNameColorationText
                                custom_width={false}
                                item={{
                                    ...this.props.item,
                                    ["name"]: this.props.item.affix_name,
                                }}
                            />
                        </h3>
                        <MarketBoardLineChart
                            dark_chart={this.props.dark_charts}
                            data={this.state.data}
                            key_for_value={"price"}
                        />
                        <p className="text-xs text-gray-700 dark:text-gray-500 mb-4">
                            If the chart above states 0, then this item has
                            never been listed before or there is no current
                            listing for it.
                        </p>
                        {this.props.item.min_cost > 0 ? (
                            <InfoAlert>
                                Item has a min value of:{" "}
                                {formatNumber(this.props.item.min_cost)} Gold.
                            </InfoAlert>
                        ) : null}
                        <div className="mb-5 mt-5">
                            <label
                                className="label block mb-2"
                                htmlFor="list-for"
                            >
                                List For
                            </label>
                            <input
                                id="list-for"
                                type="number"
                                className="form-control"
                                name="list-for"
                                value={this.state.listed_value}
                                autoFocus
                                onChange={this.setListedPrice.bind(this)}
                            />
                            <p className="text-xs text-gray-700 dark:text-gray-500">
                                If the value is set for you, this means the item
                                cannot be sold for less that listed price.
                            </p>
                        </div>
                    </Fragment>
                )}
            </Dialogue>
        );
    }
}
