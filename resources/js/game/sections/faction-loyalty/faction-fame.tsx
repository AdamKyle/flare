import React from "react";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import DropDown from "../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import FactionLoyaltyState, {FactionLoyaltyNpc} from "./types/faction-loyalty-state";
import FactionLoyaltyProps from "./types/faction-loyalty-props";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";

export default class FactionFame extends React.Component<FactionLoyaltyProps, FactionLoyaltyState> {

    constructor(props: FactionLoyaltyProps) {
        super(props);

        this.state = {
            is_loading: true,
            selected_npc: null,
            error_message: null,
            npcs: [],
            game_map_name: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('faction-loyalty/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                is_loading: false,
                npcs: result.data.npcs,
                selected_npc: result.data.npcs[0],
                game_map_name: result.data.map_name,
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
        return this.state.npcs.map((npc: FactionLoyaltyNpc) => {
            return  {
                name: npc.name,
                icon_class: 'ra ra-aura',
                on_click: () => handler(npc),
            };
        });
    }

    selectedNpc(): string | undefined {
        console.log(this.state.npcs?.find((npc: FactionLoyaltyNpc) => {
            return npc.name === this.state.selected_npc?.name
        }), this.state.selected_npc, this.state.npcs);
        return this.state.npcs?.find((npc: FactionLoyaltyNpc) => {
             return npc.name === this.state.selected_npc?.name
        })?.name;
    }

    switchToNpc(npc: FactionLoyaltyNpc) {
        this.setState({
            selected_npc: npc,
        });
    }

    render() {

        if (this.state.is_loading) {
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
                    Here you can see your NPC Fame for this map. Completing the objectives will
                    earn points towards the NPC fame level. Leveling up the Fame for each NPC provides Bonus
                    defence to your kingdoms as well as currency, XP and Unique items.
                </p>
                <p className='my-4'>
                    <a href="/information/faction-loyalty" target="_blank" className='my-2'>
                        What is all this? <i
                        className="fas fa-external-link-alt"></i>
                    </a>
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="my-4 flex flex-wrap md:flex-nowrap gap-4">
                    <div className='flex-none mt-[-25px]'>
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
                                <PrimaryOutlineButton button_label={'Remember'} on_click={() => {}} additional_css={'mt-[34px] ml-4'}/>
                            </div>
                        </div>

                        <h4>Rewards (per level)</h4>
                        <dl className='my-2'>
                            <dt>XP Per Level</dt>
                            <dd>500</dd>
                            <dt>Currencies Per Level</dt>
                            <dd>1500</dd>
                            <dt>Item Reward</dt>
                            <dd>Medium Unique</dd>
                        </dl>
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
