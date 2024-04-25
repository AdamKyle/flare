import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import BuyPopulationModalProps from "../types/modals/buy-population-modal-props";
import BuyPopulationModalState from "../types/modals/buy-population-modal-state";

export default class BuyPopulationModal extends React.Component<
    BuyPopulationModalProps,
    BuyPopulationModalState
> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            total: "",
            error_message: "",
            cost: 0,
        };
    }

    setAmount(e: React.ChangeEvent<HTMLInputElement>) {
        let totalAmount = parseInt(e.target.value, 10);

        if (totalAmount > 2000000000) {
            totalAmount = 2000000000;
        }

        this.setState({
            total: totalAmount,
            cost: totalAmount * 5,
            error_message: "",
        });
    }

    buyPop() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setParameters({
                        amount_to_purchase: this.state.total,
                    })
                    .setRoute(
                        "kingdoms/purchase-people/" + this.props.kingdom.id,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                },
                                () => {
                                    this.props.handle_close();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response = error.response;

                                let message = response.data.message;

                                if (response.data.error) {
                                    message = response.data.error;
                                }

                                this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Buy Population"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    handle_action: this.buyPop.bind(this),
                    secondary_button_disabled:
                        this.state.total === 0 ||
                        this.props.gold === 0 ||
                        this.state.cost > this.props.gold ||
                        this.state.loading,
                    secondary_button_label: "Buy Population",
                }}
            >
                <p className="mt-4">
                    <strong>Caution:</strong> You may buy a total of 2 billion
                    people at a cost of 5 Gold person. However if, upon the
                    hourly update of kingdoms, you have more then the maximum
                    population your kingdom is allowed, The Old Man will be
                    angry with you.
                </p>

                <p className="my-4">
                    The Old Man will first take the cost of 10,000 gold per
                    additional person over the population limit for the kingdom
                    from your kingdom treasury. No gold? He will take your the
                    amount out of your Gold Bars rounded up. No Gold Bars? He
                    will take the gold out of your pockets. Still no gold? He
                    will destroy your kingdom.
                </p>

                <p className="my-4">
                    <strong>Do not buy more then you can use.</strong>
                </p>
                <div className="flex items-center mb-5">
                    <label className="w-1/2">Population To Buy</label>
                    <div className="w-1/2">
                        <input
                            type="number"
                            value={this.state.total}
                            onChange={this.setAmount.bind(this)}
                            className="form-control"
                            disabled={this.state.loading}
                        />
                    </div>
                </div>
                <div className="my-4">
                    <dl>
                        <dt>Gold on hand:</dt>
                        <dd>{formatNumber(this.props.gold)}</dd>
                        <dt>Total Cost:</dt>
                        <dd>{formatNumber(this.state.cost)}</dd>
                    </dl>
                </div>
                {this.state.error_message !== "" ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
