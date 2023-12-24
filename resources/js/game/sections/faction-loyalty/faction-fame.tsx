import React from "react";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import DropDown from "../../components/ui/drop-down/drop-down";

export default class FactionFame extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_npc: null,
        }
    }

    buildNpcList(handler: (type: string) => void) {
        return [
            {
                name: 'NPC Name',
                icon_class: 'ra ra-aura',
                on_click: () => handler('npc-name'),
            },
        ];
    }

    selectedNpc() {
        return 'NPC Name';
    }

    switchToNpc(type: string) {
        this.setState({
            selected_npc: type,
        });
    }

    render() {
        return (
            <div className='py-4'>
                <h2>Surface Fame</h2>
                <p className='my-2'>
                    Earning fame for a faction is as easy as fighting monsters and crafting. When it comes to the kill requirements
                    for factions, only manual kills will count towards fame requirements.
                </p>
                <p className='my-2'>
                    When it comes to crafting, there will be a secondary button called "Craft for fame". Clicking this will
                    craft the item and give it to the npc.
                </p>
                <p className='my-2'>
                    Bounty specific kills must be made manually and on the same plane as the NPC, while crafting tasks
                    can be done any where.
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="my-4">
                    <div>
                        <div className={'w-1/3 relative left-0'}>
                            <DropDown
                                menu_items={this.buildNpcList(
                                    this.switchToNpc.bind(this)
                                )}
                                button_title={"NPCs"}
                                selected_name={this.selectedNpc()}
                            />
                        </div>
                        <div>
                            <OrangeProgressBar primary_label={'NPC Name Fame Lv: 1'} secondary_label={'10/650 Fame'} percentage_filled={20} push_down={false}/>
                        </div>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <div className='grid md:grid-cols-2 gap-2'>
                            <div>
                                <h3 className='my-2'> Bounties </h3>
                                <dl>
                                    <dt>Sewer Rat</dt>
                                    <dd>0/100</dd>
                                    <dt>Satanic Cult Leader</dt>
                                    <dd>0/200</dd>
                                    <dt>Celestial Treasure Goblin [Celestial]</dt>
                                    <dd>0/50</dd>
                                </dl>
                            </div>
                            <div className='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <h3 className='my-2'> Crafting </h3>
                                <dl>
                                    <dt>Craft Me: Broken Dagger</dt>
                                    <dd>0/100</dd>
                                    <dt>Craft Me: Broken Dagger</dt>
                                    <dd>0/100</dd>
                                    <dt>Craft Me: Broken Dagger</dt>
                                    <dd>0/100</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <h4 className='my-4'>Fame Level Requirements</h4>
                    <p className='my-2'>These are the requirements to gain a level in fame. As each one is completed, each point is added to the fame meter to the left.</p>
                    <p className='my-2'>When the meter fills up you will receive a fame level, and a medium unique reward of the max crafting level that can drop for the plane you have pledged loyalty to.</p>
                    <p className='my-2'>Fame can also increase Damage, Healing and AC while on the plane, each level is a 5% bonus towards the respective stats.</p>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                </div>
            </div>
        );
    }
}
