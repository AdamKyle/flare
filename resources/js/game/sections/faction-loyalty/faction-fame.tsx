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
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="my-4 flex flex-wrap md:flex-nowrap gap-4">
                    <div className='flex-none mt-[-25px]'>
                        <div className={'w-full md:w-1/3 relative left-0'}>
                            <DropDown
                                menu_items={this.buildNpcList(
                                    this.switchToNpc.bind(this)
                                )}
                                button_title={"NPCs"}
                                selected_name={this.selectedNpc()}
                            />
                        </div>

                        <h4>Rewards</h4>
                        <dl className='my-2'>
                            <dt>XP Per Level</dt>
                            <dd>500</dd>
                            <dt>Currencies Per Level</dt>
                            <dd>1500</dd>
                            <dt>Item Reward</dt>
                            <dd>Medium Unique</dd>
                        </dl>

                        <a href="/information/faction-loyalty" target="_blank" className='my-2'>
                            What is all this? <i
                            className="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div className='flex-none md:flex-auto w-full md:w-3/4'>
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
                                    <dt>Broken Dagger</dt>
                                    <dd>0/100</dd>
                                    <dt>Broken Dagger</dt>
                                    <dd>0/100</dd>
                                    <dt>Broken Dagger</dt>
                                    <dd>0/100</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
