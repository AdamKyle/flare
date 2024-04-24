import React, { Fragment } from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BuyPopulationModalProps from "../../../lib/game/kingdoms/types/modals/buy-population-modal-props";
import BuyPopulationModalState from "../../../lib/game/kingdoms/types/modals/buy-population-modal-state";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import clsx from "clsx";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { DateTime } from "luxon";

export default class SmelterModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            total: 0,
            error_message: "",
            time_estimate: 0,
            cost: 0,
        };
    }

    getTimeLeft() {
        const end = DateTime.fromISO(this.props.smelting_completed_at);

        const start = DateTime.now();

        const timeLeft = end.diff(start, "seconds").toObject();

        if (typeof timeLeft === "undefined") {
            return 0;
        }

        if (typeof timeLeft.seconds === "undefined") {
            return 0;
        }

        return parseInt(timeLeft.seconds.toFixed(0));
    }

    setAmount(e: React.ChangeEvent<HTMLInputElement>) {
        let totalAmount = parseInt(e.target.value, 10) || 0;

        if (totalAmount > this.props.max_steel) {
            totalAmount = this.props.max_steel;
        }

        const timeAddition = (totalAmount / 100) >> 0;
        let totalTime = timeAddition * 5;

        this.setState({
            total: totalAmount,
            cost: totalAmount * 2,
            error_message:
                totalAmount < 100 && totalAmount > 0
                    ? "Can only smelt a minimum of 100 iron at a time."
                    : "",
            time_estimate: parseInt(
                (
                    totalTime -
                    totalTime * this.props.smelting_time_reduction
                ).toFixed(2),
            ),
        });
    }

    smeltSteel() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setParameters({
                        amount_to_smelt: this.state.total,
                    })
                    .setRoute("kingdoms/smelt-iron/" + this.props.kingdom_id)
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                },
                                () => {},
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

    cancelSmelting() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "kingdoms/cancel-smelting/" + this.props.kingdom_id,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                },
                                () => {},
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response = error.response;

                                if (response.status === 422) {
                                    this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            }
                        },
                    );
            },
        );
    }

    renderSmeltingOption() {
        return (
            <Fragment>
                <div className="flex items-center mb-5">
                    <label className="w-1/2">Iron to smelt</label>
                    <div className="w-1/2">
                        <input
                            type="number"
                            onChange={this.setAmount.bind(this)}
                            className="form-control"
                            disabled={this.state.loading}
                        />
                    </div>
                </div>
                <div className="my-4">
                    <dl>
                        <dt>Iron on hand:</dt>
                        <dd>{formatNumber(this.props.iron)}</dd>
                        <dt>Iron Cost:</dt>
                        <dd
                            className={clsx({
                                "text-red-500 dark:text-red-400":
                                    this.state.cost > this.props.iron,
                            })}
                        >
                            {formatNumber(this.state.cost)}
                        </dd>
                        <dt>Time Estimate:</dt>
                        <dd>
                            {formatNumber(this.state.time_estimate)} Minutes
                        </dd>
                    </dl>
                </div>
                {this.state.error_message !== "" ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.state.loading ? <LoadingProgressBar /> : null}
            </Fragment>
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Smelt Steel"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    handle_action: this.smeltSteel.bind(this),
                    secondary_button_disabled:
                        this.state.total === 0 ||
                        this.state.total < 100 ||
                        this.props.iron === 0 ||
                        this.state.cost > this.props.iron ||
                        this.state.loading,
                    secondary_button_label: "Smelt Steel",
                }}
            >
                {this.props.smelting_time_left > 0 ? (
                    <Fragment>
                        <p className="my-4">
                            Canceling this smelting request will only give you a
                            percentage of iron back based on the time elapsed.
                        </p>
                        <TimerProgressBar
                            time_remaining={this.getTimeLeft()}
                            time_out_label={
                                "Smelting Steel: " +
                                formatNumber(this.props.smelting_amount)
                            }
                        />
                        <DangerButton
                            button_label={"Stop Smelting"}
                            on_click={this.cancelSmelting.bind(this)}
                            additional_css={"my-4"}
                        />
                        {this.state.error_message !== "" ? (
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}
                        {this.state.loading ? <LoadingProgressBar /> : null}
                    </Fragment>
                ) : (
                    this.renderSmeltingOption()
                )}
            </Dialogue>
        );
    }
}
