import React from "react";
import Dialogue from "../../ui/dialogue/dialogue";
import Tabs from "../../ui/tabs/tabs";
import TabPanel from "../../ui/tabs/tab-panel";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../../admin/lib/game/format-number";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../admin/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ManageTreasuryModalProps from "../types/modals/manage-treasury-modal-props";
import ManageTreasuryModalState from "../types/modals/manage-treasury-modal-state";

export default class ManageTreasuryModal extends React.Component<
    ManageTreasuryModalProps,
    ManageTreasuryModalState
> {
    private tabs: { key: string; name: string }[];

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "deposit",
                name: "Deposit",
            },
            {
                key: "withdrawal",
                name: "withdrawal",
            },
            {
                key: "mass-embezzle",
                name: "Mass Embezzle",
            },
        ];

        this.state = {
            amount_to_withdraw: "",
            amount_to_deposit: "",
            loading: false,
            success_message: "",
            error_message: "",
        };
    }

    setAmountToDeposit(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_deposit: "",
            });
        }

        if (value > 2000000000) {
            value = 2000000000;
        }

        const newValue = value + this.props.treasury;

        if (newValue > 2000000000) {
            value = 2000000000 - this.props.treasury;
        }

        if (value > this.props.character_gold) {
            value = this.props.character_gold;
        }

        if (value === 0) {
            this.setState({
                success_message: "",
                error_message: "",
                amount_to_deposit: "",
            });

            return;
        }

        this.setState({
            success_message: "",
            error_message: "",
            amount_to_deposit: value,
        });
    }

    setAmountToWithdraw(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_withdraw: "",
            });
        }

        if (value > 2000000000) {
            value = 2000000000;
        }

        if (value > this.props.character_gold) {
            value = value - this.props.character_gold;

            if (value <= 0) {
                value = 0;
            }
        }

        return this.setState({
            success_message: "",
            error_message:
                value === 0 ? "This would cost gold wastage. Not allowed." : "",
            amount_to_withdraw: value === 0 ? "" : value,
        });
    }

    closeSuccess() {
        this.setState({
            success_message: "",
        });
    }

    deposit() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute("kingdoms/deposit/" + this.props.kingdom_id)
                    .setParameters({
                        deposit_amount: this.state.amount_to_deposit,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                let message = response.data.message;

                                if (response.data.error) {
                                    message = response.data.error;
                                }

                                this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }

                            console.error(error);
                        },
                    );
            },
        );
    }

    withdraw() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute("kingdoms/embezzle/" + this.props.kingdom_id)
                    .setParameters({
                        embezzle_amount: this.state.amount_to_withdraw,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                if (response.status === 422) {
                                    this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            }

                            console.error(error);
                        },
                    );
            },
        );
    }

    massEmbezzle() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "kingdoms/mass-embezzle/" + this.props.character_id,
                    )
                    .setParameters({
                        embezzle_amount: this.state.amount_to_withdraw,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                if (response.status === 422) {
                                    this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            }

                            console.error(error);
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
                title={"Manage Treasury"}
                primary_button_disabled={this.state.loading}
            >
                <Tabs tabs={this.tabs} disabled={this.state.loading}>
                    <TabPanel key={"deposit"}>
                        <InfoAlert>
                            Depositing Gold can increase the Morale by 5% per
                            deposit. The minimum amount to gain 5% increase in
                            morale is 10,000,000 Gold. Kingdoms can hold a
                            maximum of 2 billion gold. Depositing gold also
                            increases your treasury defence which increases your
                            over all kingdom defence.
                        </InfoAlert>
                        <div className="flex items-center my-4">
                            <label className="w-1/2">Amount to deposit</label>
                            <div className="w-1/2">
                                <input
                                    type="number"
                                    onChange={this.setAmountToDeposit.bind(
                                        this,
                                    )}
                                    className="form-control"
                                    disabled={this.props.character_gold === 0}
                                />
                            </div>
                        </div>
                        <dl className="my-4">
                            <dt>Current Treasury</dt>
                            <dd>{formatNumber(this.props.treasury)}</dd>
                            <dt>Current Morale</dt>
                            <dd>{(this.props.morale * 100).toFixed(2)}%</dd>
                            <dt>Current Gold</dt>
                            <dd>{formatNumber(this.props.character_gold)}</dd>
                        </dl>
                        <PrimaryButton
                            button_label={"Deposit Amount"}
                            on_click={this.deposit.bind(this)}
                            disabled={
                                this.state.amount_to_deposit === "" ||
                                this.props.character_gold === 0 ||
                                (typeof this.state.amount_to_deposit ===
                                    "number" &&
                                    this.props.character_gold <
                                        this.state.amount_to_deposit)
                            }
                        />
                    </TabPanel>
                    <TabPanel key={"withdrawal"}>
                        <InfoAlert>
                            Withdrawing, or Embezzling, will reduce the morale
                            of the kingdom by 15%, regardless of amount taken.
                            If a kingdom has 15% or less morale, you cannot
                            withdraw gold. Withdrawing gold also reduces your
                            treasury defence, which reduces your overall kingdom
                            defence.
                        </InfoAlert>
                        <div className="flex items-center my-4">
                            <label className="w-1/2">Amount to withdraw</label>
                            <div className="w-1/2">
                                <input
                                    type="number"
                                    onChange={this.setAmountToWithdraw.bind(
                                        this,
                                    )}
                                    className="form-control"
                                    disabled={
                                        this.props.treasury === 0 ||
                                        this.props.morale <= 0.15
                                    }
                                />
                            </div>
                        </div>
                        <dl className="my-4">
                            <dt>Current Treasury</dt>
                            <dd>{formatNumber(this.props.treasury)}</dd>
                            <dt>Current Morale</dt>
                            <dd>{(this.props.morale * 100).toFixed(2)}%</dd>
                        </dl>
                        <PrimaryButton
                            button_label={"Withdraw Amount"}
                            on_click={this.withdraw.bind(this)}
                            disabled={
                                this.state.amount_to_withdraw === "" ||
                                this.props.treasury === 0 ||
                                this.props.morale <= 0.15
                            }
                        />
                    </TabPanel>
                    <TabPanel key={"mass-embezzle"}>
                        <p className="my-4">
                            Mass embezzling can be done from any kingdom, but
                            only applies to all kingdoms on the current plane.
                        </p>
                        <p className="my-4">
                            Each kingdom that gets embezzled from will reduce
                            it's morale by 15%. Low morale kingdoms and kingdoms
                            with no treasury will be skipped. Each kingdom that
                            can be embezzled from will also loose Treasury
                            Defence, which in turn reduces your over all kingdom
                            defence.
                        </p>
                        <p className="my-4">
                            Your server message section, below, will update to
                            show which kingdoms you embezzled from and how much
                            as well as morale reductions.
                        </p>
                        <p className="my-4">
                            Finally, if you have not completed the Goblins Lust
                            for Gold{" "}
                            <a href="/information/quests" target="_blank">
                                quest{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            you will not be able to embezzle and be told as
                            such.
                        </p>

                        <div className="flex items-center my-4">
                            <label className="w-1/2">Amount to withdraw</label>
                            <div className="w-1/2">
                                <input
                                    type="number"
                                    onChange={this.setAmountToWithdraw.bind(
                                        this,
                                    )}
                                    className="form-control"
                                    disabled={this.props.treasury === 0}
                                />
                            </div>
                        </div>
                        <dl className="my-4">
                            <dt>Current Treasury</dt>
                            <dd>{formatNumber(this.props.treasury)}</dd>
                        </dl>

                        <PrimaryButton
                            button_label={"Mass Embezzle"}
                            on_click={this.massEmbezzle.bind(this)}
                        />
                    </TabPanel>
                </Tabs>

                {this.state.error_message !== "" ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                {this.state.success_message !== "" ? (
                    <SuccessAlert
                        additional_css={"my-4"}
                        close_alert={this.closeSuccess.bind(this)}
                    >
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
