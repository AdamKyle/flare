
import React, {Fragment} from "react";
import {formatNumber} from "../../../../../lib/game/format-number";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";

export default class CurrencyRequirement extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
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
                                <div>
                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                        <h3 className='text-gray-700 dark:text-gray-200'>How to get: Gold Dust</h3>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            Gold dust is gained only by disenchanting items and daily through the Gold Dust lottery system.
                                            Players are encouraged to level crafting and enchanting while exploration is running or manually fighting
                                            as you can then disenchant the items for Gold Dust.
                                        </p>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            Players are also encouraged to turn on Auto Disenchanting in their settings after level 20. This will also then auto disenchant
                                            drops from manual fights, adventures and exploration - as you level the disenchanting skill, you will also level the enchanting skill at half
                                            the XP.
                                        </p>
                                    </PopOverContainer>
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
                                <div>
                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                        <h3 className='text-gray-700 dark:text-gray-200'>How to get: Shards</h3>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            Shards drop from Celestial Entities, which can be conjured for a cost in Gold and Gold Dust, or
                                            through Hunting them on Wednesdays when they ave a 80% chance to spawn by you just moving. Some quests also reward shards.
                                        </p>
                                        <p>
                                            Players use shards and Gold Dust in Alchemy to create powerful potions for short term boons or to create
                                            Holy Oils for late game gear upgrades.
                                        </p>
                                    </PopOverContainer>
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
                                <div>
                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                        <h3 className='text-gray-700 dark:text-gray-200'>How to get: Copper Coins</h3>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            You must have access to Purgatory, from there you will kill any creature to get Copper Coins.
                                            Players should first complete the quest line to access Purgatory Smith Work Bench in the Purgatory Smiths House
                                            in Purgatory. This will allow you to make Holy Items, through Alchemy. These items are required to make
                                            it further down the Purgatory monster list.
                                        </p>
                                    </PopOverContainer>
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
                                <div>
                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                        <h3 className='text-gray-700 dark:text-gray-200'>How to get: {this.props.quest.item.name}</h3>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            {this.props.item_requirements(this.props.quest.item)}
                                        </p>
                                    </PopOverContainer>
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
                                <div>
                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                        <h3 className='text-gray-700 dark:text-gray-200'>How to get: {this.props.quest.secondary_item.name}</h3>
                                        <p className='my-2 text-gray-700 dark:text-gray-200'>
                                            {this.props.item_requirements(this.props.quest.secondary_item)}
                                        </p>
                                    </PopOverContainer>
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
        )
    }
}
