import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import SuccessButton from "../../../components/ui/buttons/success-button";
import {random} from "lodash";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import GamblingSectionState from "../../../lib/game/types/actions/gambling/slots/types/gambling-section-state";
import GamblingSectionProps from "../../../lib/game/types/actions/gambling/slots/types/gambling-section-props";
import clsx from "clsx";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class GamblingSection extends React.Component<GamblingSectionProps, GamblingSectionState> {

    private gamblingTimeOut: any;

    constructor(props: GamblingSectionProps) {
        super(props);

        this.state = {
            loading: true,
            icons: [],
            spinning: false,
            spinningIndexes: [],
            roll: [],
            roll_message: '',
            error_message: '',
            timeoutFor: 0,
            cost: 1000000,
        };

        // @ts-ignore
        this.gamblingTimeOut = Echo.private('slot-timeout-' + this.props.character.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/gambler').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                loading: false,
                icons: response.data.icons,
            });
        }, (error: AxiosError) => {
            console.error(error);
        })

        // @ts-ignore
        this.gamblingTimeOut.listen('Game.Gambler.Events.GamblerSlotTimeOut', (event: any) => {
            this.setState({
                timeoutFor: event.timeoutFor,
            });
        });
    }

    spin() {

        const gold: number = parseFloat(this.props.character.gold.replace(/,/g, ""));

        if (gold < this.state.cost) {
            this.setState({
                error_message: 'You do not have the required gold to take a spin child.'
            });

            return;
        }

        this.setState({
            spinning: true,
            roll_message: '',
            error_message: '',
        }, () => {
            this.spinning();

            setTimeout(() => {
                this.processRoll();
            }, 1000);
        });
    }

    spinning() {
        if (this.state.spinning) {
            const max = this.state.icons.length - 1;
            let i = 0;
            const self = this;
            while (i < 100) {
                (function (i) {
                    setTimeout(function () {
                        self.setState({
                            spinningIndexes: [random(0, max), random(0, max), random(0, max)]
                        });
                    }, i * 300)
                })(i++)
            }
        }
    }

    processRoll() {
        (new Ajax()).setRoute('character/gambler/'+this.props.character.id+'/slot-machine').doAjaxCall('post', (response: AxiosResponse) => {
            this.setState({
                roll: response.data.rolls,
                roll_message: response.data.message,
                spinning: false,
            });
        }, (error: AxiosError) => {
            this.setState({spinning: false});

            if (typeof error.response !== 'undefined') {
                const response = error.response;

                this.setState({
                    error_message: response.data.message,
                });
            }
        });
    }

    renderIcons(index: number) {

        const icon = this.state.icons[index];

        return <div className='text-center mb-10'>
            <i className={icon.icon + ' text-7xl'} style={{color: icon.color}}></i>
            <p className='text-lg mt-2'>{icon.title}</p>
        </div>
    }

    render() {

        console.log(this.state.error_message);

        if (this.state.loading) {
            return <LoadingProgressBar />
        }

        if (this.state.spinning && this.state.spinningIndexes.length > 0) {
            return (
                <div className={clsx('max-w-[450px] m-auto lg:mr-auto', {
                    'ml-[150px]': !this.props.is_small
                })}>
                    <div className='max-h-[150px] overflow-hidden mt-4'>
                        <div className='grid grid-cols-3'>
                            <div>{this.renderIcons(this.state.spinningIndexes[0])}</div>
                            <div>{this.renderIcons(this.state.spinningIndexes[1])}</div>
                            <div>{this.renderIcons(this.state.spinningIndexes[2])}</div>
                        </div>
                    </div>
                    <div className='text-center'>
                        <SuccessButton button_label={'Spin'} on_click={this.spin.bind(this)} disabled={true}/>
                    </div>
                </div>
            )
        }

        return(
            <div className={clsx('max-w-[450px] m-auto lg:mr-auto', {
                'ml-[150px]': !this.props.is_small
            })}>
                <div className='max-h-[150px] overflow-hidden mt-4'>
                    <div className='grid grid-cols-3'>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[0] : 0)}</div>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[1] : 0)}</div>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[2] : 0)}</div>
                    </div>
                </div>
                {
                    this.state.roll_message !== '' ?
                        <SuccessAlert additional_css="mb-4">
                            {this.state.roll_message}
                        </SuccessAlert>
                    : null
                }

                {
                    this.state.error_message !== '' ?
                        <DangerAlert additional_css="mb-4">
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                <div className='text-center'>
                    <div className='flex justify-center mb-2'>
                        <SuccessButton button_label={'Spin'} on_click={this.spin.bind(this)} disabled={!this.props.character.can_spin}/>
                        <DangerButton button_label={'close'} on_click={this.props.close_gambling_section} additional_css={'ml-2'} />
                    </div>
                    <p className='text-sm mb-4'>Cost Per Spin: 1,000,000 Gold</p>
                    <p> <a href='/information/slots' target='_blank' className='ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a></p>

                    {
                        this.state.timeoutFor !== 0 ?
                            <div className='ml-auto mr-auto'>
                                <TimerProgressBar time_remaining={this.state.timeoutFor}
                                                  time_out_label={'Spin TimeOut'}
                                />
                            </div>
                        : null
                    }
                </div>
            </div>
        )
    }

}
