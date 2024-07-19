import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import SettleKingdomModalProps from "../types/map/modals/settle-kingdom-modal-props";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import SettleKingdomModalState from "../types/map/modals/settle-kingdom-modal-state";

export default class SettleKingdomModal extends React.Component<
    SettleKingdomModalProps,
    SettleKingdomModalState
> {
    constructor(props: SettleKingdomModalProps) {
        super(props);

        this.state = {
            kingdom_name: "",
            error_message: "",
            success_message: "",
            loading: false,
        };
    }

    setName(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            kingdom_name: e.target.value,
        });
    }

    canSettleHere(): boolean {
        return (
            this.state.kingdom_name.length < 5 ||
            this.state.kingdom_name.length > 30 ||
            this.state.loading ||
            this.props.can_settle
        );
    }

    settleKingdom() {
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            () => {
                new Ajax()
                    .setRoute("kingdoms/" + this.props.character_id + "/settle")
                    .setParameters({
                        name: this.state.kingdom_name,
                    })
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: response.data.message,
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

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Settle New Kingdom"}
                secondary_actions={{
                    handle_action: this.settleKingdom.bind(this),
                    secondary_button_disabled: this.canSettleHere(),
                    secondary_button_label: "Settle",
                }}
            >
                <p className="mb-4 mt-2">
                    Checkout{" "}
                    <a href="/information/kingdoms" target="_blank">
                        Kingdom's Help{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    for more info. <br />
                    <br />
                    Kingdom names can be 5-30 characters long.
                </p>
                <p className="mb-4">
                    Each additional kingdom beyond the first costs 10,000 Gold.
                    This includes switching Planes.
                    <br />
                    Losing all your kingdoms to war or neglect or abandonment -
                    across all planes, resets the cost to 0.
                </p>
                <div className="flex items-center mb-5">
                    <label className="w-[50px]">Name</label>
                    <div className="w-2/3">
                        <input
                            type="text"
                            value={this.state.kingdom_name}
                            onChange={this.setName.bind(this)}
                            className="form-control"
                            disabled={this.state.loading}
                            minLength={5}
                            maxLength={30}
                        />
                    </div>
                </div>
                {this.state.error_message !== "" ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.state.success_message !== "" ? (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                ) : null}
                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
