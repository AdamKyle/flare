import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import SuccessButton from "../../../components/ui/buttons/success-button";
import {random} from "lodash";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";

export default class GamblingSection extends React.Component<any, any> {

    private gamblingTimeOut: any;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            icons: [],
            spinning: false,
            spinningIndexes: [],
            roll: [],
            roll_message: '',
            timeoutFor: 0,
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
            console.log(event);
            this.setState({
                timeoutFor: event.timeoutFor,
            });
        });
    }

    spin() {
        this.setState({
            spinning: true,
            roll_message: '',
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
            console.error(error);
        })
    }

    renderIcons(index: number) {

        const icon = this.state.icons[index];

        return <div className='text-center mb-10'>
            <i className={icon.icon + ' text-7xl'} style={{color: icon.color}}></i>
            <p className='text-lg mt-2'>{icon.title}</p>
        </div>
    }

    render() {

        if (this.state.loading) {
            return <LoadingProgressBar />
        }

        if (this.state.spinning && this.state.spinningIndexes.length > 0) {
            return (
                <div className='ml-[-50px]'>
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
            <div className='ml-[-50px]'>
                <div className='max-h-[150px] overflow-hidden mt-4'>
                    <div className='grid grid-cols-3'>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[0] : 0)}</div>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[1] : 0)}</div>
                        <div>{this.renderIcons(this.state.roll.length > 0 ? this.state.roll[2] : 0)}</div>
                    </div>
                </div>
                {
                    this.state.roll_message !== '' ?
                        <div className='text-center text-green-500 dark:text-green-400 font-bold my-4'>
                            <p>
                                {this.state.roll_message}
                            </p>
                        </div>
                    : null
                }
                <div className='text-center'>
                    <SuccessButton button_label={'Spin'} on_click={this.spin.bind(this)} additional_css={'mb-5'} disabled={!this.props.character.can_spin}/>

                    {
                        this.state.timeoutFor !== 0 ?
                            <div className='w-1/2 ml-auto mr-auto'>
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
