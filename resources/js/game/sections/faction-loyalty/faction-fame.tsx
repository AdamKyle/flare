import React from "react";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import DropDown from "../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import FactionLoyaltyState, {FactionLoyaltyNpcListItem} from "./types/faction-loyalty-state";
import FactionLoyaltyProps from "./types/faction-loyalty-props";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import {formatNumber} from "../../lib/game/format-number";
import {FameTasks} from "./deffinitions/faction-loaylaty-npc";

export default class FactionFame extends React.Component<FactionLoyaltyProps, FactionLoyaltyState> {

    constructor(props: FactionLoyaltyProps) {
        super(props);

        this.state = {
            is_loading: true,
            selected_npc: null,
            error_message: null,
            npcs: [],
            game_map_name: null,
            faction_loyalty_npc: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('faction-loyalty/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                is_loading: false,
                npcs: result.data.npcs,
                selected_npc: result.data.npcs[0],
                game_map_name: result.data.map_name,
                faction_loyalty_npc: result.data.faction_loyalty,
            })
        }, (error: AxiosError) => {
            this.setState({is_loading: false});

            if (error.response) {
                this.setState({
                    error_message: error.response.data.message,
                });
            }
        });
    }

    buildNpcList(handler: (npc: any) => void) {
        return this.state.npcs.map((npc: FactionLoyaltyNpcListItem) => {
            return  {
                name: npc.name,
                icon_class: 'ra ra-aura',
                on_click: () => handler(npc),
            };
        });
    }

    selectedNpc(): string | undefined {
        return this.state.npcs?.find((npc: FactionLoyaltyNpcListItem) => {
             return npc.name === this.state.selected_npc?.name
        })?.name;
    }

    switchToNpc(npc: FactionLoyaltyNpcListItem) {
        this.setState({
            selected_npc: npc,
        });
    }

    renderTasks(fameTasks: FameTasks[], bounties: boolean) {
        return fameTasks.filter((fameTask: FameTasks) => {
            return bounties ? fameTask.type === 'bounty' : fameTask.type !== 'bounty';
        }).map((fameTask: FameTasks) => {
            return <>
                <dt>{bounties ? fameTask.monster_name : fameTask.item_name}</dt>
                <dd>{fameTask.current_amount} / {fameTask.required_amount}</dd>
            </>
        })
    }

    render() {

        if (this.state.is_loading || this.state.faction_loyalty_npc === null) {
            return (
                <div className='w-1/2 m-auto'>
                    <LoadingProgressBar />
                </div>
            );
        }

        if (this.state.error_message !== null) {
            return <DangerAlert additional_css={'my-4'}>
                {this.state.error_message}
            </DangerAlert>
        }

        return (
            <div className='py-4'>
                <h2>{this.state.game_map_name} Loyalty</h2>
                <p className='my-4'>
                    Below you can select an NPC to assist. Each NPC will have it's own set of tasks to complete.
                    Crafting tasks can be done any where, bounty tasks must be done manually and on the map
                    of the NPC you are assisting.
                </p>
                <p className='my-4'>
                    In order to gain fame, you must assist the NPC and by completing their tasks you will level the fame and gain
                    the rewards as indicated but multiplied by the level of the npc's fame. You may only assist one NPC at a time
                    and can freely switch at anytime.
                </p>
                <p className='my-4'>
                    <a href="/information/faction-loyalty" target="_blank" className='my-2'>
                        Learn more about Faction Loyalties <i
                        className="fas fa-external-link-alt"></i>
                    </a>
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="my-4 flex flex-wrap md:flex-nowrap gap-2">
                    <div className='flex-none mt-[-25px] md:w-1/2'>
                        <div className='w-full md:w-2/3 relative left-0 flex flex-wrap'>
                            <div>
                                <DropDown
                                    menu_items={this.buildNpcList(
                                        this.switchToNpc.bind(this)
                                    )}
                                    button_title={"NPCs"}
                                    selected_name={this.selectedNpc()}
                                />
                            </div>
                            <div>
                                <PrimaryOutlineButton button_label={'Assist'} on_click={() => {}} additional_css={'mt-[34px] ml-4'}/>
                            </div>
                            <div>
                                <div className='mt-[38px] ml-4 font-bold'><span>{this.selectedNpc()}</span></div>
                            </div>
                        </div>

                        <h4>Rewards (when fame levels up)</h4>
                        <dl className='my-2'>
                            <dt>XP</dt>
                            <dd>{formatNumber(this.state.faction_loyalty_npc.current_level > 0 ? this.state.faction_loyalty_npc.current_level * 1000 : 1000)}</dd>
                            <dt>Gold</dt>
                            <dd>{formatNumber(this.state.faction_loyalty_npc.current_level > 0 ? this.state.faction_loyalty_npc.current_level * 1000000 : 1000000)}</dd>
                            <dt>Gold Dust</dt>
                            <dd>
                                {formatNumber(this.state.faction_loyalty_npc.current_level > 0 ? this.state.faction_loyalty_npc.current_level * 1000 : 1000)}
                            </dd>
                            <dt>Shards</dt>
                            <dd>
                                {formatNumber(this.state.faction_loyalty_npc.current_level > 0 ? this.state.faction_loyalty_npc.current_level * 1000 : 1000)}
                            </dd>
                            <dt>Item Reward</dt>
                            <dd><a href='/information/random-enchants' target='_blank'>Medium Unique Item <i
                                className="fas fa-external-link-alt"></i></a></dd>
                        </dl>

                        <h4>Kingdom Item Defence Bonus</h4>
                        <p className='my-4'>
                            Slowly accumulates as you level this NPC's fame.
                        </p>
                        <dl>
                            <dt>Defence Bonus per level</dt>
                            <dd>{this.state.faction_loyalty_npc.kingdom_item_defence_bonus}%</dd>
                            <dt>Current Defence Bonus</dt>
                            <dd>{this.state.faction_loyalty_npc.current_kingdom_item_defence_bonus}%</dd>
                        </dl>
                    </div>
                    <div className='flex-none md:flex-auto w-full md:w-1/2'>
                        <div>
                            <OrangeProgressBar primary_label={'NPC Name Fame Lv: 1'} secondary_label={this.state.faction_loyalty_npc.current_fame + '/' + this.state.faction_loyalty_npc.next_level_fame + ' Fame'} percentage_filled={(this.state.faction_loyalty_npc.current_fame / this.state.faction_loyalty_npc.next_level_fame) * 100} push_down={false}/>
                        </div>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <div>
                            <div>
                                <h3 className='my-2'> Bounties </h3>
                                <dl>
                                    {this.renderTasks(this.state.faction_loyalty_npc.faction_loyalty_npc_tasks.fame_tasks, true)}
                                </dl>
                            </div>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <h3 className='my-2'> Crafting </h3>
                                <dl>
                                    {this.renderTasks(this.state.faction_loyalty_npc.faction_loyalty_npc_tasks.fame_tasks, false)}
                                </dl>
                            </div>
                        </div>
                        <p className='my-4'>Bounties must be completed on the respective plane and manually. Automation will not work for this.</p>
                    </div>
                </div>
            </div>
        );
    }
}
