import React, { Fragment } from "react";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import { parseInt } from "lodash";
import Ajax from "../../../../admin/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";

export default class RecruitWithResources extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            show_time_help: false,
            cost_in_gold: 0,
        };
    }

    recruitUnits() {
        this.setState(
            {
                error_message: null,
                success_message: null,
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "kingdoms/" +
                            this.props.kingdom_id +
                            "/recruit-units/" +
                            this.props.unit.id,
                    )
                    .setParameters({
                        amount:
                            this.state.amount_to_recruit === ""
                                ? 1
                                : this.state.amount_to_recruit,
                        recruitment_type: "resources",
                    })
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: response.data.message,
                                amount_to_recruit: "",
                                show_time_help: false,
                                cost_in_gold: 0,
                                time_needed: 0,
                            });
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    setResourceAmount(e: React.ChangeEvent<HTMLInputElement>) {
        if (typeof this.props.unit_cost_reduction === "undefined") {
            this.setState({
                error_message:
                    "Cannot determine cost. Unit Cost Reduction Is Undefined.",
            });

            return;
        }

        const value = parseInt(e.target.value) || 0;

        if (value === 0) {
            return this.setState({
                amount_to_recruit: "",
            });
        }

        const amount = this.getAmountToRecruit(value);

        if (amount === 0) {
            this.props.set_resource_amount(0, 0);

            return;
        }

        let timeNeeded = this.props.unit.time_to_recruit * amount;
        timeNeeded =
            timeNeeded - timeNeeded * this.props.kingdom_unit_time_reduction;

        this.props.set_resource_amount(amount, timeNeeded);

        this.setState({
            amount_to_recruit: amount,
        });
    }

    getAmountToRecruit(numberToRecruit: number) {
        if (numberToRecruit === 0) {
            return 0;
        }

        numberToRecruit = Math.abs(numberToRecruit);

        const currentMax = this.props.unit.max_amount;

        if (numberToRecruit > currentMax) {
            numberToRecruit = currentMax;
        }

        return numberToRecruit;
    }

    getAmount() {
        return parseInt(this.state.amount_to_recruit) || 1;
    }

    render() {
        return (
            <Fragment>
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"mb-5"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"mb-5"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                <div className="flex items-center mb-5">
                    <label className="w-[50px] mr-4">Amount</label>
                    <div className="w-2/3">
                        <input
                            type="text"
                            value={this.state.amount_to_recruit}
                            onChange={this.setResourceAmount.bind(this)}
                            className="form-control"
                            disabled={this.state.loading}
                        />
                    </div>
                </div>
                {this.state.loading ? <LoadingProgressBar /> : null}
                <PrimaryButton
                    button_label={"Recruit Units"}
                    additional_css={"mr-2"}
                    on_click={this.recruitUnits.bind(this)}
                    disabled={
                        this.state.amount_to_recruit <= 0 || this.state.loading
                    }
                />
                <DangerButton
                    button_label={"Cancel"}
                    on_click={this.props.remove_selection.bind(this)}
                    disabled={this.state.loading}
                />
            </Fragment>
        );
    }
}
