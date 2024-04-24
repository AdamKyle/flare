import React, { Fragment } from "react";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import { snakeCase } from "lodash";
import { formatNumber } from "../../../../lib/game/format-number";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";

export default class Shop extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            fetching: true,
            loading: false,
            item_selected: null,
            error_message: "",
            success_message: "",
            items: [],
            gold_dust_cost: 0,
            gold_cost: 0,
            copper_coin_cost: 0,
            shards_cost: 0,
        };
    }

    componentDidMount() {
        new Ajax()
            .setRoute("specialty-shop/" + this.props.character_id)
            .setParameters({
                type: this.props.type,
            })
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState({
                        fetching: false,
                        items: response.data.items,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        fetching: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response = error.response;

                        this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    closeSection() {
        if (this.props.type === "Hell Forged") {
            this.props.close_hell_forged();
        }

        if (this.props.type === "Purgatory Chains") {
            this.props.close_purgatory_chains();
        }

        if (this.props.type === "Twisted Earth") {
            this.props.close_twisted_earth();
        }
    }

    setItemToBuy(data: any) {
        let foundItem = this.state.items.filter((item: any) => {
            return item.id === data.value;
        });

        if (foundItem.length === 0) {
            return;
        }

        foundItem = foundItem[0];

        this.setState({
            item_selected: foundItem.id,
            gold_dust_cost:
                foundItem.gold_dust_cost !== null
                    ? foundItem.gold_dust_cost
                    : 0,
            gold_cost: foundItem.cost !== null ? foundItem.cost : 0,
            copper_coin_cost:
                foundItem.copper_coin_cost !== null
                    ? foundItem.copper_coin_cost
                    : 0,
            shards_cost:
                foundItem.shards_cost !== null ? foundItem.shards_cost : 0,
        });
    }

    getItemsToSelect() {
        return this.state.items.map((item: any) => {
            return {
                label: item.name + " (" + item.type + ")",
                value: item.id,
            };
        });
    }

    defaultValue() {
        if (this.state.item_selected !== null) {
            let foundItem = this.state.items.filter((item: any) => {
                return item.id === this.state.item_selected;
            });

            if (foundItem.length !== 0) {
                foundItem = foundItem[0];

                return {
                    label: foundItem.name + " (" + foundItem.type + ")",
                    value: foundItem.id,
                };
            }
        }

        return {
            label: "Please select",
            value: 0,
        };
    }

    purchase() {
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            () => {
                new Ajax()
                    .setRoute(
                        "specialty-shop/purchase/" + this.props.character_id,
                    )
                    .setParameters({
                        type: this.props.type,
                        item_id: this.state.item_selected,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message:
                                    "Item has been purchased! Check server messages to see a link for the new item! For mobile players you can tap on Chat Tabs and select Server Messages.",
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    renderCost() {
        const costs = [];

        if (this.state.gold_cost !== 0) {
            costs.push(
                <Fragment>
                    <dt>Gold Cost</dt>
                    <dd>{formatNumber(this.state.gold_cost)}</dd>
                </Fragment>,
            );
        }

        if (this.state.shards_cost !== 0) {
            costs.push(
                <Fragment>
                    <dt>Shards Cost</dt>
                    <dd>{formatNumber(this.state.shards_cost)}</dd>
                </Fragment>,
            );
        }

        if (this.state.gold_dust_cost !== 0) {
            costs.push(
                <Fragment>
                    <dt>Gold Dust Cost</dt>
                    <dd>{formatNumber(this.state.gold_dust_cost)}</dd>
                </Fragment>,
            );
        }

        if (this.state.copper_coin_cost !== 0) {
            costs.push(
                <Fragment>
                    <dt>Copper Coin Cost</dt>
                    <dd>{formatNumber(this.state.copper_coin_cost)}</dd>
                </Fragment>,
            );
        }

        return costs;
    }

    render() {
        return (
            <div className="mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]">
                <div className="cols-start-1 col-span-2">
                    {this.state.fetching ? (
                        <LoadingProgressBar />
                    ) : (
                        <Fragment>
                            <div>
                                <Select
                                    onChange={this.setItemToBuy.bind(this)}
                                    options={this.getItemsToSelect()}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.defaultValue()}
                                />

                                {this.state.error_message !== "" ? (
                                    <DangerAlert additional_css={"my-3"}>
                                        {this.state.error_message}
                                    </DangerAlert>
                                ) : null}

                                {this.state.success_message !== "" ? (
                                    <SuccessAlert additional_css={"my-3"}>
                                        {this.state.success_message}
                                    </SuccessAlert>
                                ) : null}

                                {this.state.item_selected !== null ? (
                                    <dl className="my-3">
                                        {this.renderCost()}
                                    </dl>
                                ) : null}

                                {this.state.loading ? (
                                    <LoadingProgressBar />
                                ) : null}

                                <div
                                    className={
                                        "text-center md:ml-[-100px] my-3"
                                    }
                                >
                                    <PrimaryButton
                                        button_label={"Purchase Item"}
                                        on_click={this.purchase.bind(this)}
                                        disabled={
                                            this.state.loading ||
                                            this.state.item_selected === null ||
                                            this.props.is_dead
                                        }
                                    />
                                    <DangerButton
                                        button_label={"Close"}
                                        on_click={this.closeSection.bind(this)}
                                        additional_css={"ml-2"}
                                        disabled={
                                            this.state.loading ||
                                            this.props.cannot_craft
                                        }
                                    />
                                    <a
                                        href="/information/gear-progression"
                                        target="_blank"
                                        className="ml-2"
                                    >
                                        Help{" "}
                                        <i className="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </Fragment>
                    )}
                </div>
            </div>
        );
    }
}
