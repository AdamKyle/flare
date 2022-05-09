
import React, {Fragment} from "react";
import {formatNumber} from "../../../../../lib/game/format-number";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import QuestHelpModal from "../quest-help-modal";

export default class CurrencyRequirement extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            help_type: null,
            open_help: false,
        }
    }

    manageHelpDialogue(type?: string) {
        this.setState({
            help_type: typeof type !== 'undefined' ? type : null,
            open_help: !this.state.open_help
        });
    }

    render() {
        return (
            <Fragment>
                <dl>
                    {
                        this.props.quest.gold_cost !== null ?
                            <Fragment>
                                <dt>Gold Cost:</dt>
                                <dd>{formatNumber(this.props.quest.gold_cost)}</dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.gold_dust_cost !== null ?
                            <Fragment>
                                <dt>Gold Dust Cost:</dt>
                                <dd className='flex items-center'>
                                    <span>{formatNumber(this.props.quest.gold_dust_cost)}</span>
                                    <div className='ml-2'>
                                        <button type={"button"} onClick={() => this.manageHelpDialogue('gold_dust')} className='text-blue-500 dark:text-blue-300'>
                                            <i className={'fas fa-info-circle'}></i> Help
                                        </button>
                                    </div>
                                </dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.shard_cost !== null ?
                            <Fragment>
                                <dt>Shards Cost:</dt>
                                <dd className='flex items-center'>
                                    <span>{formatNumber(this.props.quest.shard_cost)}</span>
                                    <div className='ml-2'>
                                        <button type={"button"} onClick={() => this.manageHelpDialogue('shards')} className='text-blue-500 dark:text-blue-300'>
                                            <i className={'fas fa-info-circle'}></i> Help
                                        </button>
                                    </div>
                                </dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.copper_coin_cost > 0 ?
                            <Fragment>
                                <dt>Copper Coin Cost:</dt>
                                <dd className='flex items-center'>
                                    <span>{formatNumber(this.props.quest.copper_coin_cost)}</span>
                                    <div className='ml-2'>
                                        <button type={"button"} onClick={() => this.manageHelpDialogue('copper_coins')} className='text-blue-500 dark:text-blue-300'>
                                            <i className={'fas fa-info-circle'}></i> Help
                                        </button>
                                    </div>
                                </dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.item !== null ?
                            <Fragment>
                                <dt>Required Item:</dt>
                                <dd className='flex items-center'>
                                    <span>{this.props.quest.item.name}</span>
                                    <div className='ml-2'>
                                        <button type={"button"} onClick={() => this.manageHelpDialogue('item_requirement')} className='text-blue-500 dark:text-blue-300'>
                                            <i className={'fas fa-info-circle'}></i> Help
                                        </button>
                                    </div>
                                </dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.secondary_item !== null ?
                            <Fragment>
                                <dt>Secondary Required Item:</dt>
                                <dd className='flex items-center'>
                                    <span>{this.props.quest.secondary_item.name}</span>
                                    <div className='ml-2'>
                                        <button type={"button"} onClick={() => this.manageHelpDialogue('secondary_item_requirement')} className='text-blue-500 dark:text-blue-300'>
                                            <i className={'fas fa-info-circle'}></i> Help
                                        </button>
                                    </div>
                                </dd>
                            </Fragment>
                            :
                            null
                    }
                    {
                        this.props.quest.access_to_map_id !== null ?
                            <Fragment>
                                <dt>Plane Access Required:</dt>
                                <dd>{this.props.quest.required_plane.name}</dd>
                            </Fragment>
                            : null
                    }
                    {
                        this.props.quest.faction_game_map_id !== null ?
                            <Fragment>
                                <dt>Plane Faction Name (Map to fight on)</dt>
                                <dd>{this.props.quest.faction_map.name}</dd>
                                <dt>Level required</dt>
                                <dd>{this.props.quest.required_faction_level}</dd>
                            </Fragment>
                            : null
                    }
                </dl>

                {
                    this.state.open_help && this.state.help_type !== null ?
                        <QuestHelpModal manage_modal={this.manageHelpDialogue.bind(this)} type={this.state.help_type} item_requirements={this.props.item_requirements} quest={this.props.quest}/>
                    : null
                }
            </Fragment>
        )
    }
}
