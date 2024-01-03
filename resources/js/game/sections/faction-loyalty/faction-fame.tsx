import React from "react";
import DropDown from "../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import FactionLoyaltyState, {FactionLoyaltyNpcListItem} from "./types/faction-loyalty-state";
import FactionLoyaltyProps from "./types/faction-loyalty-props";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import {FactionLoyalty, FactionLoyaltyNpc, FameTasks} from "./deffinitions/faction-loaylaty";
import FactionNpcSection from "./faction-npc-section";
import FactionNpcTasks from "./faction-npc-tasks";
import SuccessAlert from "../../components/ui/alerts/simple-alerts/success-alert";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";

export default class FactionFame extends React.Component<FactionLoyaltyProps, FactionLoyaltyState> {

    constructor(props: FactionLoyaltyProps) {
        super(props);

        this.state = {
            is_loading: true,
            is_processing: false,
            selected_npc: null,
            error_message: null,
            success_message: null,
            npcs: [],
            game_map_name: null,
            faction_loyalty: null,
            selected_faction_loyalty_npc: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('faction-loyalty/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                is_loading: false,
                npcs: result.data.npcs,
                game_map_name: result.data.map_name,
                faction_loyalty: result.data.faction_loyalty,
            }, () => {
                this.setInitialSelectedFactionInfo(result.data.faction_loyalty, result.data.npcs);
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

    manageAssistingNpc(isHelping: boolean) {

        if (!this.state.selected_faction_loyalty_npc) {
            return;
        }

        this.setState({
            error_message: null,
            is_processing: true,
        }, () => {

            if (!this.state.selected_faction_loyalty_npc) {
                this.setState({
                    error_message: null,
                    is_processing: false,
                });

                return;
            }

            (new Ajax()).setRoute('faction-loyalty/' + (isHelping ? 'stop-assisting' : 'assist') + '/'+ this.props.character_id +'/' + this.state.selected_faction_loyalty_npc.id).doAjaxCall('post', (result: AxiosResponse) => {

                this.setState({
                    is_processing: false,
                    success_message: result.data.message,
                    faction_loyalty: result.data.faction_loyalty,
                }, () => {
                    console.log(result.data.faction_loyalty);
                    this.setInitialSelectedFactionInfo(result.data.faction_loyalty, this.state.npcs);
                });
            });
        });
    }

    setInitialSelectedFactionInfo(factionLoyalty: FactionLoyalty, npcs: FactionLoyaltyNpcListItem[]) {
        let helpingNpc = factionLoyalty.faction_loyalty_npcs.filter((factionLoyaltyNpc: FactionLoyaltyNpc) => {
            return factionLoyaltyNpc.currently_helping
        });

        if (helpingNpc.length === 0) {
            helpingNpc = factionLoyalty.faction_loyalty_npcs.filter((factionLoyaltyNpc: FactionLoyaltyNpc) => {
                return factionLoyaltyNpc.npc_id === npcs[0].id;
            });

            this.setState({
                selected_npc: npcs[0],
                selected_faction_loyalty_npc: helpingNpc[0],
            });

            return;
        }

        const factionLoyaltyNpcHelping = helpingNpc[0];

        this.setState({
            selected_npc: npcs.filter((npc: FactionLoyaltyNpcListItem) => {
                return npc.id === factionLoyaltyNpcHelping.npc_id
            })[0],
            selected_faction_loyalty_npc: factionLoyaltyNpcHelping,
        });

        return helpingNpc[0];
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
        console.log(this.state.npcs, this.state.selected_npc?.name);
        return this.state.npcs?.find((npc: FactionLoyaltyNpcListItem) => {
             return npc.name === this.state.selected_npc?.name
        })?.name;
    }

    switchToNpc(npc: FactionLoyaltyNpcListItem) {

        if (!this.state.faction_loyalty) {
            return;
        }

        this.setState({
            selected_npc: npc,
            selected_faction_loyalty_npc: this.state.faction_loyalty.faction_loyalty_npcs.filter((factionLoyaltyNpc: FactionLoyaltyNpc) => {
                return factionLoyaltyNpc.npc_id === npc.id;
            })[0]
        });
    }

    isAssisting(): boolean {
        if (!this.state.selected_faction_loyalty_npc) {
            return false;
        }

        return this.state.selected_faction_loyalty_npc.currently_helping;
    }

    render() {

        if (this.state.is_loading || this.state.faction_loyalty === null) {
            return (
                <div className='w-1/2 m-auto'>
                    <LoadingProgressBar />
                </div>
            );
        }

        if (!this.state.selected_faction_loyalty_npc) {

            return <DangerAlert additional_css={'my-4'}>
                Uh oh. We encountered an error here. Seems there is no Faction Loyalty info for this NPC. Not sure how that
                happened, but I would tell The Creator to investigate how the Faction Loyalty info is fetched for an NPC.
            </DangerAlert>
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
                <div className='my-4'>
                    {
                        this.state.success_message ?
                            <SuccessAlert>
                                {this.state.success_message}
                            </SuccessAlert>
                        : null
                    }
                </div>
                <div className='my-4'>
                    {
                        this.state.is_processing ?
                            <LoadingProgressBar />
                        : null
                    }
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="my-4 flex flex-wrap md:flex-nowrap gap-2">
                    <div className='flex-none mt-[-25px] md:w-1/2'>
                        <div className='w-full relative left-0 flex flex-wrap'>
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
                                {
                                    this.isAssisting() ?
                                        <DangerOutlineButton button_label={'Stop Assisting'} on_click={() => this.manageAssistingNpc(true)} additional_css={'mt-[34px] ml-4'} />
                                    :
                                        <PrimaryOutlineButton button_label={'Assist'} on_click={() => this.manageAssistingNpc(false)} additional_css={'mt-[34px] ml-4'}/>
                                }
                            </div>
                            <div>
                                <div className='mt-[38px] ml-4 font-bold'><span>{this.selectedNpc()}</span></div>
                            </div>
                        </div>

                        <FactionNpcSection faction_loyalty_npc={this.state.selected_faction_loyalty_npc} />
                    </div>
                    <div className='flex-none md:flex-auto w-full md:w-1/2'>
                        <FactionNpcTasks faction_loyalty_npc={this.state.selected_faction_loyalty_npc} />
                    </div>
                </div>
            </div>
        );
    }
}
