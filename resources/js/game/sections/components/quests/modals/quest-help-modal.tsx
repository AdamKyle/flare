import React, {Fragment} from "react";
import HelpDialogue from "../../../../components/ui/dialogue/help-dialogue";

export default class QuestHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    buildTitle() {
        switch(this.props.type) {
            case 'gold_dust':
                return 'How to get: Gold Dust';
            case 'shards':
                return 'How to get: Shards';
            case 'copper_coins':
                return 'How to get: Copper Coins';
            case 'item_requirement':
                return 'How to get: ' + this.props.quest.item.name
            case 'secondary_item_requirement':
                return 'How to get: ' + this.props.quest.secondary_item.name
            default:
                return null;
        }
    }

    buildGoldDustHelp() {
        return (
            <Fragment>
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
            </Fragment>
        )
    }

    buildShardHelp() {
        return (
            <Fragment>
                <p className='my-2 text-gray-700 dark:text-gray-200'>
                    Shards drop from Celestial Entities, which can be conjured for a cost in Gold and Gold Dust, or
                    through Hunting them on Wednesdays when they ave a 80% chance to spawn by you just moving. Some quests also reward shards.
                </p>
                <p>
                    Players use shards and Gold Dust in Alchemy to create powerful potions for short term boons or to create
                    Holy Oils for late game gear upgrades.
                </p>
            </Fragment>
        );
    }

    buildCopperCoins() {
        return (
            <Fragment>
                <p className='my-2 text-gray-700 dark:text-gray-200'>
                    You must have access to Purgatory, from there you will kill any creature to get Copper Coins.
                    Players should first complete the quest line to access Purgatory Smith Work Bench in the Purgatory Smiths House
                    in Purgatory. This will allow you to make Holy Items, through Alchemy. These items are required to make
                    it further down the Purgatory monster list.
                </p>
            </Fragment>
        );
    }

    buildContent() {
        switch(this.props.type) {
            case 'gold_dust':
                return this.buildGoldDustHelp()
            case 'shards':
                return this.buildShardHelp()
            case 'copper_coins':
                return this.buildCopperCoins()
            case 'item_requirement':
                return <p className='my-2 text-gray-700 dark:text-gray-200'>{this.props.item_requirements(this.props.quest.item)}</p>
            case 'secondary_item_requirement':
                return <p className='my-2 text-gray-700 dark:text-gray-200'>{this.props.item_requirements(this.props.quest.secondary_item)}</p>
            default:
                return null;
        }
    }

    render() {
        return (
          <HelpDialogue is_open={true}
                        manage_modal={this.props.manage_modal}
                        title={this.buildTitle()}>
              {this.buildContent()}
          </HelpDialogue>
        );
    }
}
