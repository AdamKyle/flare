import React from "react";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import GetCapitalCityGoldBarData from "../ajax/get-capital-city-gold-bar-data";
import { serviceContainer } from "../../../lib/containers/core-container";
import GoldBarManagementProps from "./types/gold-bar-management-props";
import GoldBarManagementState from "./types/gold-bar-management-state";
import TabPanel from "../../ui/tabs/tab-panel";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import PrimaryButton from "../../ui/buttons/primary-button";
import Tabs from "../../ui/tabs/tabs";
import { parseInt } from "lodash";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import CapitalCityManageGoldBarsAjax from "../ajax/capital-city-manage-gold-bars-ajax";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";

export default class GoldBarManagement extends React.Component<
    GoldBarManagementProps,
    GoldBarManagementState
> {
    private fetchGoldBarData: GetCapitalCityGoldBarData;

    private manageGoldBars: CapitalCityManageGoldBarsAjax;

    private tabs: { key: string; name: string }[];

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            gold_bar_data: null,
            amount_of_gold_bars_to_buy: "",
            amount_of_gold_bars_to_sell: "",
            max_gold_bars_allowed: 0,
            processing: false,
        };

        this.tabs = [
            {
                key: "deposit",
                name: "Deposit",
            },
            {
                key: "withdrawal",
                name: "withdrawal",
            },
        ];

        this.fetchGoldBarData = serviceContainer().fetch(
            GetCapitalCityGoldBarData,
        );

        this.manageGoldBars = serviceContainer().fetch(
            CapitalCityManageGoldBarsAjax,
        );
    }

    componentDidMount() {
        this.fetchGoldBarData.fetchData(
            this,
            this.props.character_id,
            this.props.kingdom.id,
        );
    }

    calculateCost(): number {
        return (
            (this.state.amount_of_gold_bars_to_buy as number) * 2_000_000_000
        );
    }

    calculateGain(): number {
        return (
            (this.state.amount_of_gold_bars_to_sell as number) * 2_000_000_000
        );
    }

    setAmountToWithdraw(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                success_message: null,
                error_message: null,
                amount_of_gold_bars_to_sell: 0,
            });
        }

        if (this.state.gold_bar_data === null) {
            return this.setState({
                success_message: null,
                error_message: null,
                amount_of_gold_bars_to_sell: 0,
            });
        }

        if (value > this.state.gold_bar_data.total_gold_bars) {
            value = this.state.gold_bar_data.total_gold_bars;
        }

        this.setState({
            success_message: null,
            error_message: null,
            amount_of_gold_bars_to_sell: value,
        });
    }

    setAmountToDeposit(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        this.setAmount(value);
    }

    setAmount(value: number) {
        if (value === 0) {
            return this.setState({
                success_message: null,
                error_message: null,
                amount_of_gold_bars_to_buy: 0,
            });
        }

        if (this.state.gold_bar_data === null) {
            return this.setState({
                success_message: null,
                error_message: null,
                amount_of_gold_bars_to_sell: 0,
            });
        }

        const allowedGoldBars = this.state.gold_bar_data.total_kingdoms * 1000;

        if (value > allowedGoldBars) {
            value = allowedGoldBars;
        }

        const newTotal = this.state.gold_bar_data.total_gold_bars + value;

        if (newTotal > allowedGoldBars) {
            value = allowedGoldBars - this.state.gold_bar_data.total_gold_bars;
        }

        this.setState({
            success_message: null,
            error_message: null,
            amount_of_gold_bars_to_buy: value,
        });
    }

    isInputDisabled(amount: number) {
        if (this.state.gold_bar_data === null) {
            return true;
        }

        if (!this.state.gold_bar_data.goblin_banks_level_five) {
            return true;
        }

        return amount === 0;
    }

    deposit() {
        if (this.state.amount_of_gold_bars_to_buy === "") {
            return;
        }

        if ((this.state.amount_of_gold_bars_to_buy as number) <= 0) {
            return;
        }

        this.setState({
            error_message: null,
            success_message: null,
            processing: true,
        });

        this.manageGoldBars.depositGoldBars(
            this,
            this.props.character_id,
            this.props.kingdom.id,
            this.state.amount_of_gold_bars_to_buy as number,
        );
    }

    withdraw() {
        if (this.state.amount_of_gold_bars_to_sell === "") {
            return;
        }

        if ((this.state.amount_of_gold_bars_to_sell as number) <= 0) {
            return;
        }

        this.setState({
            error_message: null,
            success_message: null,
            processing: true,
        });

        this.manageGoldBars.withdrawGoldBars(
            this,
            this.props.character_id,
            this.props.kingdom.id,
            this.state.amount_of_gold_bars_to_sell as number,
        );
    }

    manageView() {
        this.props.manage_gold_bar_management();
    }

    render() {
        if (this.state.loading || this.state.gold_bar_data === null) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="flex items-center relative">
                    <h3>Manage Gold Bars</h3>
                    <SuccessOutlineButton
                        button_label={"Back to council"}
                        on_click={this.manageView.bind(this)}
                        additional_css={"absolute right-0"}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                {!this.state.gold_bar_data.goblin_banks_level_five ? (
                    <WarningAlert additional_css="my-4">
                        You cannot use this feature as every single kingdom on
                        this plane needs a level 5 Goblin Bank. You can unlock
                        this building through the kingdom passive tree and then
                        use the building section to order all kingdoms start
                        building and leveling the Goblin Bank.
                    </WarningAlert>
                ) : null}
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-4"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                {this.state.processing ? (
                    <div className="my-4">
                        <LoadingProgressBar />
                    </div>
                ) : null}
                <Tabs tabs={this.tabs} disabled={this.state.loading}>
                    <TabPanel key={"deposit"}>
                        <InfoAlert>
                            <p>
                                Cost to buy is 2 Billion Gold per Rune. You may
                                have a total of 1000 Gold Bars per kingdom.
                                Depositing Gold Bars will deposit across all
                                your kingdoms on this plane.
                            </p>
                            <p className="my-2">
                                Please note that depositing gold bars will not
                                deposit into this kingdom, it will do so for all
                                OTHER kingdoms on this plane that you own.
                            </p>
                        </InfoAlert>
                        <div className="flex items-center my-4">
                            <label className="w-1/2">Amount to deposit</label>
                            <div className="w-1/2">
                                <input
                                    type="number"
                                    value={
                                        this.state.amount_of_gold_bars_to_buy
                                    }
                                    onChange={this.setAmountToDeposit.bind(
                                        this,
                                    )}
                                    className="form-control"
                                    disabled={this.isInputDisabled(
                                        this.state.gold_bar_data.character_gold,
                                    )}
                                />
                            </div>
                        </div>
                        <dl className="my-4">
                            <dt>Current Gold Bars</dt>
                            <dd>
                                {formatNumber(
                                    this.state.gold_bar_data.total_gold_bars,
                                )}
                            </dd>
                            <dt>Current Gold</dt>
                            <dd>
                                {formatNumber(
                                    this.state.gold_bar_data.character_gold,
                                )}
                            </dd>
                            <dt>Cost in Gold</dt>
                            <dd>{formatNumber(this.calculateCost())}</dd>
                        </dl>
                        <PrimaryButton
                            button_label={"Deposit Amount"}
                            on_click={this.deposit.bind(this)}
                            disabled={
                                (this.state
                                    .amount_of_gold_bars_to_buy as number) <=
                                    0 ||
                                (this.state.gold_bar_data
                                    .character_gold as number) <= 0
                            }
                        />
                    </TabPanel>
                    <TabPanel key={"withdrawal"}>
                        <InfoAlert>
                            Withdrawing Gold Bars will subtract them from all,
                            but this kingdom, kingdoms you own on this plane.
                        </InfoAlert>
                        <div className="flex items-center my-4">
                            <label className="w-1/2">Amount to withdraw</label>
                            <div className="w-1/2">
                                <input
                                    type="number"
                                    value={
                                        this.state.amount_of_gold_bars_to_sell
                                    }
                                    onChange={this.setAmountToWithdraw.bind(
                                        this,
                                    )}
                                    className="form-control"
                                    disabled={this.isInputDisabled(
                                        this.state.gold_bar_data
                                            .total_gold_bars,
                                    )}
                                />
                            </div>
                        </div>
                        <dl className="my-4">
                            <dt>Current Gold Bars</dt>
                            <dd>
                                {formatNumber(
                                    this.state.gold_bar_data.total_gold_bars,
                                )}
                            </dd>
                            <dt>Gold to gain</dt>
                            <dd>{formatNumber(this.calculateGain())}</dd>
                        </dl>
                        <PrimaryButton
                            button_label={"Withdraw Amount"}
                            on_click={this.withdraw.bind(this)}
                            disabled={
                                (this.state
                                    .amount_of_gold_bars_to_sell as number) <=
                                    0 ||
                                (this.state.gold_bar_data
                                    .total_gold_bars as number) <= 0
                            }
                        />
                    </TabPanel>
                </Tabs>
            </div>
        );
    }
}
