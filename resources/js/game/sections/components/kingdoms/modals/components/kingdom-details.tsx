import React, {Fragment} from "react";
import Ajax from "../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {formatNumber, percent} from "../../../../../lib/game/format-number";
import KingdomHelpModal from "../kingdom-help-modal";
import KingdomTopSection from "./kingdom-top-section";
import KingdomDetailsProps
    from "../../../../../lib/game/types/map/kingdom-pins/modals/components/kingdom-details-props";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import PrimaryOutlineButton from "../../../../../components/ui/buttons/primary-outline-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import DangerOutlineButton from "../../../../../components/ui/buttons/danger-outline-button";
import AttackKingdomModal from "../../../../kingdoms/modals/attack-kingdom-modal";

export default class KingdomDetails extends React.Component<KingdomDetailsProps, any> {

    constructor(props: KingdomDetailsProps) {
        super(props);

        this.state = {
            kingdom_details: null,
            action_loading: false,
            error_message: '',
            show_attack_dialogue: false,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('kingdom/'+this.props.kingdom_id+'/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            const kingdomData = result.data;

            this.setState({
                kingdom_details: kingdomData
            }, () => {
                this.props.update_loading(kingdomData)
            });

        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    manageAttackKingdom() {
        this.setState({
            show_attack_dialogue: !this.state.show_attack_dialogue
        })
    }

    purchaseKingdom() {
        this.setState({
            action_loading: true,
            error_message: ''
        }, () => {
            this.props.update_action_in_progress();
        });

        (new Ajax()).setRoute('kingdoms/'+this.props.character_id+'/purchase-npc-kingdom')
                    .setParameters({
                        kingdom_id: this.props.kingdom_id
                    })
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        this.setState({action_loading: false}, () => {
                            this.props.update_action_in_progress();
                            this.props.close_modal();
                        });
                    }, (error: AxiosError) => {
                        this.setState({action_loading: false}, () => { this.props.update_action_in_progress(); });

                        if (typeof error.response !== 'undefined') {
                            const response = error.response;

                            if (response.status === 422) {
                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        }

                        console.error(error);
                    });
    }

    manageHelpDialogue(type: 'wall_defence' | 'treas_defence' | 'gb_defence' | 'passive_defence' | 'total_defence' | 'teleport_details') {
        this.setState({
            show_help: !this.state.show_help,
            help_type: type,
        });
    }

    render() {
        if (this.state.kingdom_details === null) {
            return (
                <div className={'h-40'}>
                    <ComponentLoading />
                </div>
            );
        }

        return (
            <Fragment>
                <div>
                    {
                        this.props.show_top_section ?
                            <KingdomTopSection kingdom={this.state.kingdom_details} />
                        : null
                    }
                    <div className='lg:grid lg:grid-cols-2'>
                        <div>
                            <dl>
                                <dt>Wall Defence</dt>
                                <dd>
                                    <div className='flex items-center mb-4'>
                                        {percent(this.state.kingdom_details.walls_defence)}%
                                        <div>
                                            <div className='ml-2'>
                                                <button type={"button"} onClick={() => this.manageHelpDialogue('wall_defence')} className='text-blue-500 dark:text-blue-300'>
                                                    <i className={'fas fa-info-circle'}></i> Help
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </dd>
                                <dt>Treas. Defence</dt>
                                <dd>
                                    <div className='flex items-center mb-4'>
                                        {percent(this.state.kingdom_details.treasury_defence)}%
                                        <div>
                                            <div className='ml-2'>
                                                <button type={"button"} onClick={() => this.manageHelpDialogue('treas_defence')} className='text-blue-500 dark:text-blue-300'>
                                                    <i className={'fas fa-info-circle'}></i> Help
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </dd>
                                <dt>GB. Defence</dt>
                                <dd>
                                    <div className='flex items-center mb-4'>
                                        {percent(this.state.kingdom_details.gold_bars_defence)}%

                                        <div>
                                            <div className='ml-2'>
                                                <button type={"button"} onClick={() => this.manageHelpDialogue('gb_defence')} className='text-blue-500 dark:text-blue-300'>
                                                    <i className={'fas fa-info-circle'}></i> Help
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </dd>
                                <dt>Passive Defence</dt>
                                <dd>
                                    <div className='flex items-center mb-4'>
                                        {percent(this.state.kingdom_details.passive_defence)}%
                                        <div>
                                            <div className='ml-2'>
                                                <button type={"button"} onClick={() => this.manageHelpDialogue('passive_defence')} className='text-blue-500 dark:text-blue-300'>
                                                    <i className={'fas fa-info-circle'}></i> Help
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </dd>
                                <dt>Total Defence</dt>
                                <dd>
                                    <div className='flex items-center mb-4'>
                                        {percent(this.state.kingdom_details.defence_bonus)}%
                                        <div>
                                            <div className='ml-2'>
                                                <button type={"button"} onClick={() => this.manageHelpDialogue('total_defence')} className='text-blue-500 dark:text-blue-300'>
                                                    <i className={'fas fa-info-circle'}></i> Help
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </dd>
                            </dl>
                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                        </div>
                        <div>
                            <dl>
                                <dt>Treasure:</dt>
                                <dd>{formatNumber(this.state.kingdom_details.treasury)}</dd>
                                <dt>Gold Bars:</dt>
                                <dd>{formatNumber(this.state.kingdom_details.gold_bars)}</dd>
                            </dl>
                            {
                                this.props.allow_purchase ?
                                    <div className='mt-4 text-center'>
                                        <PrimaryOutlineButton button_label={'Purchase Kingdom'} on_click={this.purchaseKingdom.bind(this)} />
                                    </div>
                                : null
                            }

                            {
                                !this.state.kingdom_details.is_protected ?
                                    <div className='mt-4 text-center'>
                                        <DangerOutlineButton button_label={'Attack Kingdom'} on_click={this.manageAttackKingdom.bind(this)} />
                                    </div>
                                : null
                            }
                        </div>
                    </div>
                </div>

                {
                    this.state.show_help ?
                        <KingdomHelpModal manage_modal={this.manageHelpDialogue.bind(this)} type={this.state.help_type}/>
                    : null
                }

                {
                    this.state.show_attack_dialogue ?
                        <AttackKingdomModal
                            is_open={true}
                            handle_close={this.manageAttackKingdom.bind(this)}
                            kingdom_to_attack_id={this.props.kingdom_id}
                            character_id={this.props.character_id}
                        />
                    : null
                }

                {
                    this.state.error_message !== '' ?
                        <DangerAlert additional_css={'my-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }

                {
                    this.state.action_loading ?
                        <LoadingProgressBar />
                    : null
                }
            </Fragment>
        );
    }
}
