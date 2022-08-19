import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import GoblinBankModalProps from "../../../lib/game/kingdoms/types/modals/goblin-bank-modal-props";
import GoblinCoinBankModalState from "../../../lib/game/kingdoms/types/modals/goblin-coin-bank-modal-state";
import {formatNumber} from "../../../lib/game/format-number";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class GoblinBankModal extends React.Component<GoblinBankModalProps, GoblinCoinBankModalState> {

    private tabs: {key: string, name: string}[];

    constructor(props: GoblinBankModalProps) {
        super(props);

        this.tabs = [{
            key: 'deposit',
            name: 'Deposit'
        }, {
            key: 'withdrawal',
            name: 'withdrawal',
        }]

        this.state = {
            amount_to_withdraw: '',
            amount_to_deposit: '',
            cost_to_deposit: 0,
            gold_gained: 0,
            error_message: '',
            success_message: '',
            loading: false,
        }
    }

    setAmountToWithdraw(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                success_message: '',
                error_message: '',
                amount_to_withdraw: '',
                gold_gained: 0,
            });
        }

        if (value > 1000) {
            value === 1000;
        }

        if (value > this.props.gold_bars) {
            value = this.props.gold_bars;
        }

        this.setState({
            success_message: '',
            error_message: '',
            amount_to_withdraw: value,
            gold_gained: value * 2000000000,
        });
    }

    setAmountToDeposit(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                success_message: '',
                error_message: '',
                amount_to_deposit: '',
                cost_to_deposit: 0,
            });
        }

        if (value > 1000) {
            value = 1000;
        }

        const newTotal = this.props.gold_bars + value;

        if (newTotal > 1000) {
            value = this.props.gold_bars - this.props.gold_bars
        }

        this.setState({
            success_message: '',
            error_message: '',
            amount_to_deposit: value,
            cost_to_deposit: value * 2000000000,
        });
    }

    closeSuccess() {
        this.setState({
            success_message: '',
        })
    }

    withdraw() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setParameters({
                amount_to_withdraw: this.state.amount_to_withdraw
            }).setRoute('kingdoms/withdraw-bars-as-gold/' + this.props.kingdom_id)
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        success_message: result.data.message
                    })
                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        if (response.status === 422) {
                            this.setState({
                                error_message: response.data.message
                            })
                        }
                    }

                    console.error(error);
                });
        });
    }

    deposit() {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setParameters({
                amount_to_purchase: this.state.amount_to_deposit
            }).setRoute('kingdoms/purchase-gold-bars/' + this.props.kingdom_id)
              .doAjaxCall('post', (result: AxiosResponse) => {
                  this.setState({
                      loading: false,
                      success_message: result.data.message
                  })
              }, (error: AxiosError) => {
                  this.setState({loading: false});

                  if (typeof error.response !== 'undefined') {
                      const response = error.response;

                      if (response.status === 422) {
                          this.setState({
                              error_message: response.data.message
                          })
                      }
                  }

                  console.error(error);
              });
        });

    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Goblin Bank'}
                      primary_button_disabled={this.state.loading}
            >
                <Tabs tabs={this.tabs} disabled={this.state.loading}>
                    <TabPanel key={'deposit'}>
                        <InfoAlert>
                            Cost to buy is 2 Billion Gold per Rune. You may have a total of 1000 Runes.
                        </InfoAlert>
                        <div className='flex items-center my-4'>
                            <label className='w-1/2'>Amount to deposit</label>
                            <div className='w-1/2'>
                                <input type='number'
                                       value={this.state.amount_to_deposit}
                                       onChange={this.setAmountToDeposit.bind(this)}
                                       className='form-control'
                                       disabled={this.props.character_gold === 0}
                                />
                            </div>
                        </div>
                        <dl className='my-4'>
                            <dt>Current Gold Bars</dt>
                            <dd>{formatNumber(this.props.gold_bars)}</dd>
                            <dt>Current Gold</dt>
                            <dd>{formatNumber(this.props.character_gold)}</dd>
                            <dt>Cost in Gold</dt>
                            <dd>{formatNumber(this.state.cost_to_deposit)}</dd>
                        </dl>
                        <PrimaryButton button_label={'Deposit Amount'}
                                       on_click={this.deposit.bind(this)}
                                       disabled={this.state.amount_to_deposit === '' ||
                                                 this.props.character_gold === 0 ||
                                                 this.props.character_gold < this.state.cost_to_deposit
                                      }
                        />
                    </TabPanel>
                    <TabPanel key={'withdrawal'}>
                        <div className='flex items-center my-4'>
                            <label className='w-1/2'>Amount to withdraw</label>
                            <div className='w-1/2'>
                                <input type='number'
                                       value={this.state.amount_to_withdraw}
                                       onChange={this.setAmountToWithdraw.bind(this)}
                                       className='form-control'
                                       disabled={this.props.gold_bars === 0}
                                />
                            </div>
                        </div>
                        <dl className='my-4'>
                            <dt>Current Gold Bars</dt>
                            <dd>{formatNumber(this.props.gold_bars)}</dd>
                            <dt>Gold to gain</dt>
                            <dd>{formatNumber(this.state.gold_gained)}</dd>
                        </dl>
                        <PrimaryButton button_label={'Withdraw Amount'}
                                       on_click={this.withdraw.bind(this)}
                                       disabled={this.state.amount_to_withdraw === '' || this.props.gold_bars === 0}
                        />
                    </TabPanel>
                </Tabs>

                {
                    this.state.error_message !== '' ?
                        <DangerAlert additional_css={'my-4'}>{this.state.error_message}</DangerAlert>
                    : null
                }
                {
                    this.state.success_message !== '' ?
                        <SuccessAlert additional_css={'my-4'} close_alert={this.closeSuccess.bind(this)}>{this.state.success_message}</SuccessAlert>
                    : null
                }
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
            </Dialogue>
        )
    }
}
