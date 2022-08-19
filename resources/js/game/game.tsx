import React, { Fragment } from 'react';
import clsx from "clsx";
import GameProps from './lib/game/types/game-props';
import Tabs from './components/ui/tabs/tabs';
import TabPanel from "./components/ui/tabs/tab-panel";
import BasicCard from "./components/ui/cards/basic-card";
import MapSection from "./sections/map/map-section";
import GameState from "./lib/game/types/game-state";
import CharacterTopSection from "./sections/character-top-section/character-top-section";
import Quests from "./sections/components/quests/quests";
import ManualProgressBar from "./components/ui/progress-bars/manual-progress-bar";
import FetchGameData from "./lib/game/ajax/FetchGameData";
import CharacterSheet from "./sections/character-sheet/character-sheet";
import GameChat from "./sections/chat/game-chat";
import ForceNameChange from "./sections/force-name-change/force-name-change";
import SmallerActions from "./sections/game-actions-section/smaller-actions";
import QuestType from "./lib/game/types/quests/quest-type";
import ScreenRefresh from './sections/screen-refresh/screen-refresh';
import KingdomsList from "./sections/kingdoms/kingdoms-list";
import KingdomDetails from "./lib/game/kingdoms/kingdom-details";
import Actions from "./sections/game-actions-section/actions";
import PositionType from "./lib/game/types/map/position-type";
import {removeCommas} from "./lib/game/format-number";
import CharacterCurrenciesType from "./lib/game/character/character-currencies-type";

export default class Game extends React.Component<GameProps, GameState> {

    private tabs: {name: string, key: string}[];

    private characterTopBar: any;

    private characterAttacks: any;

    private characterStatus: any;

    private characterAttackData: any;

    private forceNameChange: any;

    private unlockAlchemySkill: any;

    private updateCraftingTypes: any;

    private kingdomUpdates: any;

    constructor(props: GameProps) {
        super(props)

        this.tabs = [{
            key: 'game',
            name: 'Game'
        }, {
            key: 'character-sheet',
            name: 'Character Sheet',
        }, {
            key: 'quests',
            name: 'Quests'
        }, {
            key: 'kingdoms',
            name: 'Kingdoms'
        }]

        this.state = {
            view_port: 0,
            character_status: null,
            loading: true,
            finished_loading: false,
            character_currencies: null,
            secondary_loading_title: 'Fetching character sheet ...',
            percentage_loaded: 0,
            celestial_id: 0,
            character: null,
            kingdoms: [],
            quests: null,
            position: null,
            disable_tabs: false,
        }

        // @ts-ignore
        this.characterTopBar = Echo.private('update-top-bar-' + this.props.userId);

        // @ts-ignore
        this.characterAttacks = Echo.private('update-character-attacks-' + this.props.userId);

        // @ts-ignore
        this.characterStatus = Echo.private('update-character-status-' + this.props.userId);

        // @ts-ignore
        this.characterAttackData = Echo.private('update-character-attack-' + this.props.userId);

        // @ts-ignore
        this.forceNameChange = Echo.private('force-name-change-' + this.props.userId);

        // @ts-ignore
        this.unlockAlchemySkill = Echo.private('unlock-skill-' + this.props.userId);

        // @ts-ignore
        this.updateCraftingTypes = Echo.private('update-location-base-crafting-options-' + this.props.userId);

        // @ts-ignore
        this.kingdomUpdates = Echo.private('update-kingdom-' + this.props.userId);
    }

    componentDidMount() {
        this.setState({
            view_port: window.innerWidth || document.documentElement.clientWidth
        });

        window.addEventListener('resize', () => {
            this.setState({
                view_port: window.innerWidth || document.documentElement.clientWidth
            });
        });

        (new FetchGameData(this)).setUrls([
            {url: 'character-sheet/' +this.props.characterId, name: 'character-sheet'},
            {url: 'quests/' + this.props.characterId, name: 'quests'},
            {url: 'player-kingdoms/' + this.props.characterId, name: 'kingdoms'},
        ]).doAjaxCalls();

        // @ts-ignore
        this.characterTopBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event: any) => {
            this.setState({
                character: {...this.state.character, ...event.characterSheet}
            }, () => {
                if (event.characterSheet.is_banned) {
                    location.reload();
                }
            });
        });

        // @ts-ignore
        this.characterAttacks.listen('Game.Core.Events.UpdateCharacterAttacks', (event: any) => {
            this.setState({
                character: {...this.state.character, ...event.characterAttacks}
            });
        });

        // @ts-ignore
        this.characterStatus.listen('Game.Battle.Events.UpdateCharacterStatus', (event: any) => {
            this.setState({
                character_status: event.characterStatuses,
                character: {...this.state.character, ...event.characterStatuses}
            });
        });

        // @ts-ignore
        this.characterAttackData.listen('Flare.Events.UpdateCharacterAttackBroadcastEvent', (event: any) => {
            this.setState({
                character: {...this.state.character, ...event.attack}
            });
        });

        // @ts-ignore
        this.unlockAlchemySkill.listen('Game.Quests.Events.UnlockSkillEvent', () => {
            const character = JSON.parse(JSON.stringify(this.state.character));

            character.is_alchemy_locked = false;

            this.setState({
                character: character
            });
        });

        // @ts-ignore
        this.updateCraftingTypes.listen('Game.Maps.Events.UpdateLocationBasedCraftingOptions', (event: any) => {
            const character = JSON.parse(JSON.stringify(this.state.character));

            character.can_use_work_bench = event.canUseWorkBench;
            character.can_access_queen = event.canUseQueenOfHearts;

            this.setState({
                character: character
            });
        })

        // @ts-ignore
        this.kingdomUpdates.listen('Game.Kingdoms.Events.UpdateKingdom', (event: { kingdom: KingdomDetails }) => {
            const eventKingdom = event.kingdom;

            if (Array.isArray(eventKingdom)) {
                this.setState({
                    kingdoms: eventKingdom
                });

                return;
            }

            let currentKingdoms  = JSON.parse(JSON.stringify(this.state.kingdoms));

            const index = currentKingdoms.findIndex((kingdom: KingdomDetails) => kingdom.id === eventKingdom.id);

            if (index > -1) {
                currentKingdoms[index] = eventKingdom;
            }

            this.setState({
                kingdoms: currentKingdoms,
            });
        });
    }

    updateDisabledTabs() {
        this.setState({
            disable_tabs: !this.state.disable_tabs,
        });
    }

    updateCharacterStatus(characterStatus: any): void {
        this.setState({character_status: characterStatus});
    }

    updateCharacterCurrencies(currencies: CharacterCurrenciesType): void {
        this.setState({character_currencies: currencies});
    }

    setCharacterPosition(position: PositionType) {
        this.setState({
            position: position,
        })
    }

    updateCharacterQuests(quests: QuestType) {
        console.log('updating character quests: ', quests);
        this.setState({
            quests: quests
        });
    }

    updateQuestPlane(plane: string) {
        if (this.state.quests !== null) {
            const quests: QuestType = JSON.parse(JSON.stringify(this.state.quests));

            quests.player_plane = plane;

            this.setState({
                quests: quests
            });
        }
    }

    updateCelestial(celestialId: number | null) {
        this.setState({
            celestial_id: celestialId !== null ? celestialId : 0,
        });
    }

    updateFinishedLoading() {
        this.setState({
            finished_loading: true,
        })
    }

    renderLoading() {
        return  (
            <div className='flex h-screen justify-center items-center max-w-md m-auto mt-[-150px]'>
                <div className='w-full'>
                    <ManualProgressBar label={'Loading game ...'} secondary_label={this.state.secondary_loading_title} percentage_left={this.state.percentage_loaded} show_loading_icon={true} />
                </div>
            </div>
        );
    }

    render() {

        if (this.state.loading) {
            return this.renderLoading();
        }

        if (this.state.quests === null) {
            return this.renderLoading();
        }

        if (this.state.character === null) {
            return this.renderLoading();
        }

        if (this.state.character_currencies === null) {
            return this.renderLoading();
        }

        if (this.state.character_status === null) {
            return this.renderLoading();
        }

        return (
            <Fragment>

                <ScreenRefresh user_id={this.state.character.user_id} />

                <Tabs tabs={this.tabs} disabled={!this.state.finished_loading || this.state.disable_tabs} additonal_css={clsx({
                    'ml-[40px]': this.state.view_port >= 1600
                })}>
                    <TabPanel key={'game'}>
                        <div className={clsx("grid lg:grid-cols-3 gap-3", {
                            'ml-[40px]': this.state.view_port >= 1600
                        })}>
                            <div className="w-full col-span-3 lg:col-span-2">
                                <BasicCard additionalClasses={'mb-10'}>
                                    <CharacterTopSection character={this.state.character}
                                                         view_port={this.state.view_port}
                                                         update_character_status={this.updateCharacterStatus.bind(this)}
                                                         update_character_currencies={this.updateCharacterCurrencies.bind(this)}
                                    />
                                </BasicCard>
                                <BasicCard additionalClasses={clsx('min-h-60', {
                                    'max-w-[500px] ml-auto mr-auto': this.state.view_port < 1600
                                })}>
                                    {
                                        this.state.view_port < 1600 ?
                                            <SmallerActions
                                                character={this.state.character}
                                                character_status={this.state.character_status}
                                                character_position={this.state.position}
                                                character_currencies={this.state.character_currencies}
                                                celestial_id={this.state.celestial_id}
                                                update_celestial={this.updateCelestial.bind(this)}
                                                update_plane_quests={this.updateQuestPlane.bind(this)}
                                                update_character_position={this.setCharacterPosition.bind(this)}
                                                view_port={this.state.view_port}
                                            />
                                        :
                                            <Actions
                                                character={this.state.character}
                                                character_status={this.state.character_status}
                                                character_position={this.state.position}
                                                celestial_id={this.state.celestial_id}
                                                update_celestial={this.updateCelestial.bind(this)}
                                            />
                                    }
                                </BasicCard>
                            </div>
                            <BasicCard additionalClasses={clsx('hidden lg:block md:mt-0 lg:col-start-3 lg:col-end-3 max-h-[630px] max-w-[555px]', {
                                'max-h-[624px]': this.state.character.is_dead
                            })}>
                                <MapSection
                                    user_id={this.props.userId}
                                    character_id={this.props.characterId}
                                    view_port={this.state.view_port}
                                    currencies={this.state.character_currencies}
                                    is_dead={this.state.character.is_dead}
                                    is_automaton_running={this.state.character.is_automation_running}
                                    automation_completed_at={this.state.character.automation_completed_at}
                                    show_celestial_fight_button={this.updateCelestial.bind(this)}
                                    set_character_position={this.setCharacterPosition.bind(this)}
                                    update_character_quests_plane={this.updateQuestPlane.bind(this)}
                                    disable_bottom_timer={false}
                                />
                            </BasicCard>
                        </div>
                    </TabPanel>
                    <TabPanel key={'character-sheet'}>
                        <CharacterSheet
                            character={this.state.character}
                            finished_loading={this.state.finished_loading}
                            view_port={this.state.view_port}
                            update_disable_tabs={this.updateDisabledTabs.bind(this)}
                        />
                    </TabPanel>
                    <TabPanel key={'quests'}>
                        <BasicCard>
                            <Quests quest_details={this.state.quests} character_id={this.props.characterId} update_quests={this.updateCharacterQuests.bind(this)}/>
                        </BasicCard>
                    </TabPanel>
                    <TabPanel key={'kingdoms'}>
                        <KingdomsList my_kingdoms={this.state.kingdoms} view_port={this.state.view_port} character_gold={removeCommas(this.state.character_currencies.gold)} />
                    </TabPanel>
                </Tabs>

                <GameChat user_id={this.props.userId}
                          character_id={this.state.character.id}
                          is_silenced={this.state.character.is_silenced}
                          can_talk_again_at={this.state.character.can_talk_again_at}
                          is_automation_running={this.state.character.is_automation_running}
                          is_admin={false}
                          view_port={this.state.view_port}
                          update_finished_loading={this.updateFinishedLoading.bind(this)}
                />

                {
                    this.state.character.force_name_change ?
                        <ForceNameChange character_id={this.state.character.id} />
                    : null
                }
            </Fragment>
        );

    }
}
