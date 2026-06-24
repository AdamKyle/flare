import clsx from "clsx";
import React, { Fragment, lazy, Suspense } from "react";
import { Channel } from "laravel-echo";
import SuccessAlert from "./components/ui/alerts/simple-alerts/success-alert";
import WarningAlert from "./components/ui/alerts/simple-alerts/warning-alert";
import BasicCard from "./components/ui/cards/basic-card";
import ManualProgressBar from "./components/ui/progress-bars/manual-progress-bar";
import TabPanel from "./components/ui/tabs/tab-panel";
import Tabs from "./components/ui/tabs/tabs";
import Ajax from "./lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { serviceContainer } from "./lib/containers/core-container";
import FetchGameData from "./lib/game/ajax/FetchGameData";
import CharacterCurrenciesType from "./lib/game/character/character-currencies-type";
import GameEventListeners from "./lib/game/event-listeners/game-event-listeners";
import { removeCommas } from "./lib/game/format-number";
import GameProps from "./lib/game/types/game-props";
import GameState, {
    ExplorationOutputType,
    GameActionState,
} from "./lib/game/types/game-state";
import QuestType from "./lib/game/types/quests/quest-type";
import MapData from "./sections/map/lib/request-types/MapData";
import MapStateManager from "./sections/map/lib/state/map-state-manager";
import MapState from "./sections/map/types/map-state";
import PositionType from "./sections/map/types/map/position-type";
import ScreenRefresh from "./sections/screen-refresh/screen-refresh";
import KingdomLogDetails from "./components/kingdoms/deffinitions/kingdom-log-details";
import {
    FactionLoyaltyWarningNotice,
    FameTasks,
} from "./components/faction-loyalty/deffinitions/faction-loaylaty";
import IsTabletInPortraitDisplayAlert from "./components/ui/alerts/tablet-portrait-detector/is-tablet-in-portrait-display-alert";
import OrangeButton from "./components/ui/buttons/orange-button";
import TurnOffUserIntroFlag from "./lib/game/ajax/turn-off-user-intro-flag";
import PrimaryButton from "./components/ui/buttons/primary-button";

const SuggestionsAndBugs = lazy(
    () => import("./components/suggestions/suggestions-and-bugs"),
);
const CharacterSheet = lazy(
    () => import("./sections/character-sheet/character-sheet"),
);
const Quests = lazy(() => import("./sections/components/quests/quests"));
const KingdomsList = lazy(() => import("./components/kingdoms/kingdoms-list"));
const GameChat = lazy(() => import("./sections/chat/game-chat"));
const ForceNameChange = lazy(
    () => import("./sections/force-name-change/force-name-change"),
);
const GlobalTimeoutModal = lazy(
    () => import("./sections/game-modals/global-timeout-modal"),
);
const IntroSlides = lazy(
    () => import("./components/intro-section/intro-slides"),
);
const CharacterTopSection = lazy(
    () => import("./sections/character-top-section/character-top-section"),
);
const ActionSection = lazy(
    () => import("./sections/game-actions-section/action-section"),
);
const ActionTabs = lazy(
    () => import("./sections/game-actions-section/action-tabs"),
);
const ActiveBoonsActionSection = lazy(
    () => import("./sections/game-actions-section/active-boons-action-section"),
);
const MapSection = lazy(() => import("./sections/map/map-section"));
const MapTabs = lazy(() => import("./sections/map/map-tabs"));

declare const Echo: {
    private: (channel: string) => Channel;
};

interface ExplorationWarningStateEvent {
    has_warning: boolean;
    warnings: { id: number; type: string; message: string }[];
    warning: { id: number; type: string; message: string } | null;
}

interface ExplorationOutputUpdatedEvent {
    type: "active" | "warning" | null;
    output: Record<string, any> | null;
}

interface FactionLoyaltyAutomationWarningStateEvent {
    has_warning: boolean;
    warning_notices?: FactionLoyaltyWarningNotice[];
    warning_notice?: FactionLoyaltyWarningNotice | null;
}

export default class Game extends React.Component<GameProps, GameState> {
    private gameEventListener?: GameEventListeners;

    private turnOffIntroFlagAjax: TurnOffUserIntroFlag;

    private factionLoyaltyAutomationWarning: Channel;

    private explorationOutputChannel: Channel;

    private explorationWarningChannel: Channel;

    constructor(props: GameProps) {
        super(props);

        this.gameEventListener = serviceContainer().fetch(GameEventListeners);

        this.gameEventListener.initialize(this, this.props.userId);

        this.gameEventListener.registerEvents();

        this.state = {
            view_port: 0,
            character_status: null,
            loading: true,
            finished_loading: false,
            character_currencies: null,
            secondary_loading_title: "Fetching character sheet ...",
            percentage_loaded: 0,
            celestial_id: 0,
            character: null,
            kingdoms: [],
            kingdom_logs: [],
            quests: null,
            position: null,
            disable_tabs: false,
            show_global_timeout: false,
            action_data: null,
            map_data: null,
            fame_action_tasks: null,
            has_faction_loyalty_warning: false,
            faction_loyalty_warning_notices: [],
            show_guide_quest_completed: false,
            hide_donation_alert: false,
            show_map: true,
            show_suggestions_and_bugs: false,
            is_showing_active_boons: false,
            show_intro_page: false,
            exploration_output_loading: false,
            exploration_output: null,
            tabs: [
                {
                    key: "game",
                    name: "Game",
                },
                {
                    key: "character-sheet",
                    name: "Character Sheet",
                },
                {
                    key: "quests",
                    name: "Quests",
                },
                {
                    key: "kingdoms",
                    name: "Kingdoms",
                    has_logs: false,
                },
            ],
        };

        this.turnOffIntroFlagAjax =
            serviceContainer().fetch(TurnOffUserIntroFlag);

        this.factionLoyaltyAutomationWarning = Echo.private(
            "faction-loyalty-automation-warning-" + this.props.userId,
        );

        this.explorationOutputChannel = Echo.private(
            "exploration-output-" + this.props.userId,
        );

        this.explorationWarningChannel = Echo.private(
            "exploration-warning-" + this.props.userId,
        );
    }

    componentDidMount() {
        this.setState({
            view_port:
                window.innerWidth || document.documentElement.clientWidth,
            show_intro_page: this.props.showIntroPage,
        });

        window.addEventListener("resize", () => {
            this.setState({
                view_port:
                    window.innerWidth || document.documentElement.clientWidth,
            });
        });

        new FetchGameData(this)
            .setUrls([
                {
                    url: "character-sheet/" + this.props.characterId,
                    name: "character-sheet",
                },
                { url: "quests/" + this.props.characterId, name: "quests" },
                {
                    url: "map-actions/" + this.props.characterId,
                    name: "actions",
                },
                {
                    url: "player-kingdoms/" + this.props.characterId,
                    name: "kingdoms",
                },
                {
                    url: "map/" + this.props.characterId,
                    name: "game-map",
                },
            ])
            .doAjaxCalls();

        if (this.gameEventListener) {
            this.gameEventListener.listenToEvents();
        }

        this.listenForFactionLoyaltyAutomationWarnings();

        this.fetchExplorationOutput();

        this.listenForExplorationUpdates();

        if (localStorage.getItem("hide-dontainion") !== null) {
            this.setState({
                hide_donation_alert: true,
            });
        }
    }

    listenForFactionLoyaltyAutomationWarnings(): void {
        this.factionLoyaltyAutomationWarning.listen(
            "Game.Factions.FactionLoyalty.Events.FactionLoyaltyAutomationWarningState",
            (event: FactionLoyaltyAutomationWarningStateEvent) => {
                this.updateFactionLoyaltyWarning(
                    event.has_warning,
                    event.warning_notices ??
                        (event.warning_notice ? [event.warning_notice] : []),
                );
            },
        );
    }

    fetchExplorationOutput(): void {
        this.setState({
            exploration_output_loading: true,
            exploration_output: this.state.exploration_output ?? {
                type: null,
                output: null,
                loading: true,
            },
        });

        new Ajax()
            .setRoute("exploration/" + this.props.characterId + "/output")
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        exploration_output:
                            result.data as ExplorationOutputType,
                        exploration_output_loading: false,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        exploration_output_loading: false,
                        exploration_output: this.state.exploration_output
                            ? {
                                  ...this.state.exploration_output,
                                  loading: false,
                              }
                            : null,
                    });
                    console.error(error);
                },
            );
    }

    listenForExplorationUpdates(): void {
        this.explorationWarningChannel.listen(
            "Game.Automation.Events.ExplorationWarningState",
            (_event: ExplorationWarningStateEvent) => {
                this.fetchExplorationOutput();
            },
        );

        this.explorationOutputChannel.listen(
            "Game.Automation.Events.ExplorationOutputUpdated",
            (event: ExplorationOutputUpdatedEvent) => {
                this.setState({
                    exploration_output_loading: false,
                    exploration_output: {
                        type: event.type,
                        output: event.output,
                    },
                });
            },
        );
    }

    updateFactionLoyaltyWarning(
        hasWarning: boolean,
        warningNotices?: FactionLoyaltyWarningNotice[],
    ): void {
        this.setState({
            has_faction_loyalty_warning: hasWarning,
            faction_loyalty_warning_notices:
                warningNotices ?? this.state.faction_loyalty_warning_notices,
        });
    }

    resetShowIntroPage() {
        this.setState(
            {
                show_intro_page: false,
            },
            () => {
                this.turnOffIntroFlagAjax.turnOffIntro(
                    this,
                    this.props.characterId,
                );
            },
        );
    }

    setStateFromData(data: MapData) {
        const state = MapStateManager.buildChangeState(data, this);

        this.setState({
            map_data: state,
        });
    }

    updateLogIcon() {
        const tabs = JSON.parse(JSON.stringify(this.state.tabs));

        if (this.state.kingdom_logs.length > 0) {
            const hasLogs = this.state.kingdom_logs.filter(
                (log: KingdomLogDetails) => !log.opened,
            );

            tabs[tabs.length - 1].has_logs = hasLogs.length > 0;
        } else {
            tabs[tabs.length - 1].has_logs = false;
        }

        this.setState({
            tabs: tabs,
        });
    }

    updateDisabledTabs() {
        this.setState({
            disable_tabs: !this.state.disable_tabs,
        });
    }

    updateCharacterStatus(characterStatus: any): void {
        this.setState({ character_status: characterStatus });
    }

    updateCharacterCurrencies(currencies: CharacterCurrenciesType): void {
        this.setState({ character_currencies: currencies });
    }

    setCharacterPosition(position: PositionType) {
        const character = JSON.parse(JSON.stringify(this.state.character));

        character.base_position = position;

        this.setState({
            position: position,
            character: character,
        });
    }

    updateCharacterQuests(quests: QuestType) {
        this.setState({
            quests: quests,
        });
    }

    updateQuestPlane(plane: string) {
        if (this.state.quests !== null) {
            const quests: QuestType = JSON.parse(
                JSON.stringify(this.state.quests),
            );

            quests.player_plane = plane;

            this.setState({
                quests: quests,
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
        });
    }

    setActionState(stateData: GameActionState): void {
        this.setState({
            action_data: { ...this.state.action_data, ...stateData },
        });
    }

    setMapState(mapData: Partial<MapState>): void {
        this.setState({
            map_data: mapData,
        });
    }

    setCanSeeFactionLoyaltyTab(canSee: boolean, factionId?: number) {
        const character = JSON.parse(JSON.stringify(this.state.character));

        character.can_see_pledge_tab = canSee;
        character.pledged_to_faction_id = factionId;

        this.setState({
            character: character,
        });
    }

    updateFactionActionTasks(fameTasks: FameTasks[] | null) {
        this.setState({
            fame_action_tasks: fameTasks,
        });
    }

    updateMapVisibility(showMap: boolean) {
        this.setState({
            show_map: showMap,
        });
    }

    renderLoading() {
        return (
            <div className="flex h-screen justify-center items-center max-w-md m-auto mt-[-150px]">
                <div className="w-full">
                    <ManualProgressBar
                        label={"Loading game ..."}
                        secondary_label={this.state.secondary_loading_title}
                        percentage_left={this.state.percentage_loaded}
                        show_loading_icon={true}
                    />
                </div>
            </div>
        );
    }

    closeDonationAlert() {
        localStorage.setItem("hide-dontainion", "yes");

        this.setState({
            hide_donation_alert: true,
        });
    }

    manageBugsAndSuggestions() {
        this.setState({
            show_suggestions_and_bugs: !this.state.show_suggestions_and_bugs,
        });
    }

    updateIsShowingActiveBoons(isShowing: boolean) {
        this.setState({
            is_showing_active_boons: isShowing,
        });
    }

    render() {
        if (this.state.show_intro_page) {
            return (
                <Suspense fallback={<ManualProgressBar label="Loading..." />}>
                    <IntroSlides
                        view_port={this.state.view_port}
                        reset_show_intro={this.resetShowIntroPage.bind(this)}
                    />
                </Suspense>
            );
        }

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

        if (this.state.map_data === null) {
            return this.renderLoading();
        }

        if (this.state.show_suggestions_and_bugs) {
            return (
                <Suspense fallback={<ManualProgressBar label="Loading..." />}>
                    <SuggestionsAndBugs
                        manage_suggestions_and_bugs={this.manageBugsAndSuggestions.bind(
                            this,
                        )}
                        character_id={this.props.characterId}
                    />
                </Suspense>
            );
        }

        const gameMap = this.state.map_data;
        let gameMapId = null;

        if (gameMap !== null) {
            gameMapId = gameMap.game_map_id;
        }

        return (
            <div>
                <ScreenRefresh user_id={this.state.character.user_id} />

                <IsTabletInPortraitDisplayAlert />

                <Tabs
                    tabs={this.state.tabs}
                    disabled={this.state.disable_tabs}
                    additonal_css={clsx("flex justify-center", {
                        "ml-[40px]": this.state.view_port >= 1024,
                    })}
                    icon_key={"has_logs"}
                >
                    <TabPanel key={"game"}>
                        <Suspense
                            fallback={
                                <ManualProgressBar label="Loading game sections..." />
                            }
                        >
                            <div
                                className={clsx(
                                    "grid grid-cols-1 lg:grid-cols-3 gap-4",
                                    {
                                        "md:grid-cols-2":
                                            this.state.view_port > 1024,
                                    },
                                )}
                            >
                                <div className="md:col-span-1 lg:col-span-2">
                                    <BasicCard additionalClasses="w-full">
                                        <CharacterTopSection
                                            character={this.state.character}
                                            view_port={this.state.view_port}
                                            update_character_status={this.updateCharacterStatus.bind(
                                                this,
                                            )}
                                            update_character_currencies={this.updateCharacterCurrencies.bind(
                                                this,
                                            )}
                                        />
                                    </BasicCard>
                                    {!this.state.hide_donation_alert ? (
                                        <WarningAlert
                                            additional_css={"mb-4 mt-[15px]"}
                                            close_alert={this.closeDonationAlert.bind(
                                                this,
                                            )}
                                        >
                                            <p className={"my-2"}>
                                                <strong>
                                                    Tlessa needs your help,
                                                </strong>
                                            </p>

                                            <p className="my-2">
                                                Tlessa needs your help to
                                                survive. Please, consider
                                                donating to support the
                                                continued development of Planes
                                                of Tlessa.
                                            </p>

                                            <p className="my-2">
                                                <a
                                                    href="/tlessa-donations"
                                                    target="_blank"
                                                >
                                                    Learn more here{" "}
                                                    <i className="fas fa-external-link-alt"></i>
                                                </a>
                                                . Tlessa and I deeply appreciate
                                                your contribution in keeping
                                                this amazing game alive!
                                            </p>
                                        </WarningAlert>
                                    ) : null}

                                    {this.state.show_guide_quest_completed ? (
                                        <SuccessAlert
                                            additional_css={"mb-4 mt-[15px]"}
                                        >
                                            You have completed a guide quest.
                                            Click the button in the top right to
                                            collect your rewards and move on to
                                            the next!
                                        </SuccessAlert>
                                    ) : null}
                                    <div className="flex w-full min-w-0 flex-wrap justify-center items-center gap-4">
                                        <div
                                            className={clsx("w-full min-w-0", {
                                                hidden:
                                                    this.state.view_port > 932,
                                            })}
                                        >
                                            <ActiveBoonsActionSection
                                                character_id={
                                                    this.props.characterId
                                                }
                                                user_id={this.props.userId}
                                                update_is_showing_boons={this.updateIsShowingActiveBoons.bind(
                                                    this,
                                                )}
                                            />
                                        </div>
                                        {!this.state.is_showing_active_boons ? (
                                            <OrangeButton
                                                button_label={
                                                    "Submit Bug/Suggestions"
                                                }
                                                on_click={this.manageBugsAndSuggestions.bind(
                                                    this,
                                                )}
                                                additional_css={clsx({
                                                    "relative top-[10px]":
                                                        this.state.view_port >
                                                        932,
                                                    "mt-[5px]":
                                                        this.state.view_port <=
                                                        932,
                                                })}
                                            />
                                        ) : null}
                                    </div>
                                    <BasicCard additionalClasses="min-h-60 mt-4">
                                        <ActionTabs
                                            use_tabs={
                                                this.state.character
                                                    .can_see_pledge_tab
                                            }
                                            view_port={this.state.view_port}
                                            user_id={this.props.userId}
                                            character_id={
                                                this.props.characterId
                                            }
                                            can_attack={
                                                this.state.character.can_attack
                                            }
                                            can_craft={
                                                this.state.character.can_craft
                                            }
                                            update_faction_action_tasks={this.updateFactionActionTasks.bind(
                                                this,
                                            )}
                                            character_map_id={gameMapId}
                                            is_automation_running={
                                                this.state.character
                                                    .is_automation_running
                                            }
                                            is_faction_loyalty_automation_running={
                                                this.state.character
                                                    .is_faction_loyalty_automation_running
                                            }
                                            is_delve_running={
                                                this.state.character
                                                    .is_delve_running
                                            }
                                            has_faction_loyalty_warning={
                                                this.state
                                                    .has_faction_loyalty_warning
                                            }
                                            has_exploration_warning={
                                                this.state.exploration_output
                                                    ?.type === "warning"
                                            }
                                            faction_loyalty_warning_notices={
                                                this.state
                                                    .faction_loyalty_warning_notices
                                            }
                                            update_faction_loyalty_warning={this.updateFactionLoyaltyWarning.bind(
                                                this,
                                            )}
                                        >
                                            <ActionSection
                                                character={this.state.character}
                                                character_status={
                                                    this.state.character_status
                                                }
                                                character_position={
                                                    this.state.position
                                                }
                                                character_currencies={
                                                    this.state
                                                        .character_currencies
                                                }
                                                celestial_id={
                                                    this.state.celestial_id
                                                }
                                                update_celestial={this.updateCelestial.bind(
                                                    this,
                                                )}
                                                update_plane_quests={this.updateQuestPlane.bind(
                                                    this,
                                                )}
                                                update_character_position={this.setCharacterPosition.bind(
                                                    this,
                                                )}
                                                view_port={this.state.view_port}
                                                can_engage_celestial={
                                                    this.state.character
                                                        .can_engage_celestials
                                                }
                                                action_data={
                                                    this.state.action_data
                                                }
                                                map_data={this.state.map_data}
                                                update_parent_state={this.setActionState.bind(
                                                    this,
                                                )}
                                                set_map_data={this.setMapState.bind(
                                                    this,
                                                )}
                                                fame_tasks={
                                                    this.state.fame_action_tasks
                                                }
                                                update_show_map_mobile={this.updateMapVisibility.bind(
                                                    this,
                                                )}
                                                can_access_hell_forged_shop={
                                                    this.state.map_data
                                                        .can_access_hell_forged_shop
                                                }
                                                can_access_purgatory_chains_shop={
                                                    this.state.map_data
                                                        .can_access_purgatory_chains_shop
                                                }
                                                can_access_twisted_earth_shop={
                                                    this.state.map_data
                                                        .can_access_twisted_earth_shop
                                                }
                                                exploration_output={
                                                    this.state
                                                        .exploration_output
                                                }
                                            />
                                        </ActionTabs>
                                    </BasicCard>
                                </div>
                                <div className="md:col-span-1">
                                    <BasicCard
                                        additionalClasses={clsx(
                                            "lg:max-h-[630px] max-w-[555px]",
                                            {
                                                "lg:max-h-[695px]":
                                                    this.state.character
                                                        .can_use_event_goals_button,
                                            },
                                            {
                                                hidden: !this.state.show_map,
                                            },
                                        )}
                                    >
                                        <MapTabs
                                            use_tabs={
                                                this.state.character
                                                    .can_use_event_goals_button
                                            }
                                            character_id={
                                                this.state.character.id
                                            }
                                            user_id={
                                                this.state.character.user_id
                                            }
                                        >
                                            <MapSection
                                                can_move={
                                                    this.state.character_status
                                                        .can_move
                                                }
                                                user_id={this.props.userId}
                                                character_id={
                                                    this.props.characterId
                                                }
                                                view_port={this.state.view_port}
                                                currencies={
                                                    this.state
                                                        .character_currencies
                                                }
                                                is_dead={
                                                    this.state.character.is_dead
                                                }
                                                is_automaton_running={
                                                    this.state.character
                                                        .is_automation_running
                                                }
                                                is_faction_loyalty_automation_running={
                                                    this.state.character
                                                        .is_faction_loyalty_automation_running
                                                }
                                                is_delve_running={
                                                    this.state.character
                                                        .is_delve_running
                                                }
                                                can_engage_celestial={
                                                    this.state.character
                                                        .can_engage_celestials
                                                }
                                                automation_completed_at={
                                                    this.state.character
                                                        .automation_completed_at
                                                }
                                                active_automation={
                                                    this.state.character
                                                        .active_automation
                                                }
                                                can_engage_celestials_again_at={
                                                    this.state.character
                                                        .can_engage_celestials_again_at
                                                }
                                                show_celestial_fight_button={this.updateCelestial.bind(
                                                    this,
                                                )}
                                                set_character_position={this.setCharacterPosition.bind(
                                                    this,
                                                )}
                                                update_character_quests_plane={this.updateQuestPlane.bind(
                                                    this,
                                                )}
                                                disable_bottom_timer={false}
                                                map_data={this.state.map_data}
                                                set_map_data={this.setMapState.bind(
                                                    this,
                                                )}
                                            />
                                        </MapTabs>
                                    </BasicCard>
                                </div>
                            </div>
                        </Suspense>
                    </TabPanel>
                    <TabPanel key={"character-sheet"}>
                        <Suspense
                            fallback={<ManualProgressBar label="Loading..." />}
                        >
                            <CharacterSheet
                                character={this.state.character}
                                finished_loading={this.state.finished_loading}
                                view_port={this.state.view_port}
                                update_disable_tabs={this.updateDisabledTabs.bind(
                                    this,
                                )}
                                update_pledge_tab={this.setCanSeeFactionLoyaltyTab.bind(
                                    this,
                                )}
                                update_faction_action_tasks={this.updateFactionActionTasks.bind(
                                    this,
                                )}
                            />
                        </Suspense>
                    </TabPanel>
                    <TabPanel key={"quests"}>
                        <BasicCard>
                            <Suspense
                                fallback={
                                    <ManualProgressBar label="Loading..." />
                                }
                            >
                                <Quests
                                    quest_details={this.state.quests}
                                    character_id={this.props.characterId}
                                    update_quests={this.updateCharacterQuests.bind(
                                        this,
                                    )}
                                    is_automation_running={
                                        this.state.character
                                            .is_automation_running
                                    }
                                    is_faction_loyalty_automation_running={
                                        this.state.character
                                            .is_faction_loyalty_automation_running
                                    }
                                    is_delve_running={
                                        this.state.character.is_delve_running
                                    }
                                />
                            </Suspense>
                        </BasicCard>
                    </TabPanel>
                    <TabPanel key={"kingdoms"}>
                        <Suspense
                            fallback={<ManualProgressBar label="Loading..." />}
                        >
                            <KingdomsList
                                is_dead={this.state.character_status.is_dead}
                                my_kingdoms={this.state.kingdoms}
                                logs={this.state.kingdom_logs}
                                view_port={this.state.view_port}
                                character_gold={removeCommas(
                                    this.state.character.gold,
                                )}
                                user_id={this.state.character.user_id}
                                character_id={this.state.character.id}
                                is_automation_running={
                                    this.state.character.is_automation_running
                                }
                                is_faction_loyalty_automation_running={
                                    this.state.character
                                        .is_faction_loyalty_automation_running
                                }
                                is_delve_running={
                                    this.state.character.is_delve_running
                                }
                            />
                        </Suspense>
                    </TabPanel>
                </Tabs>

                <Suspense fallback={null}>
                    <GameChat
                        user_id={this.props.userId}
                        character_id={this.state.character.id}
                        is_silenced={this.state.character.is_silenced}
                        can_talk_again_at={
                            this.state.character.can_talk_again_at
                        }
                        is_automation_running={
                            this.state.character.is_automation_running
                        }
                        is_admin={false}
                        view_port={this.state.view_port}
                        update_finished_loading={this.updateFinishedLoading.bind(
                            this,
                        )}
                    />
                </Suspense>

                {this.state.character.force_name_change ? (
                    <Suspense fallback={null}>
                        <ForceNameChange
                            character_id={this.state.character.id}
                        />
                    </Suspense>
                ) : null}

                {this.state.show_global_timeout ? (
                    <Suspense fallback={null}>
                        <GlobalTimeoutModal />
                    </Suspense>
                ) : null}
            </div>
        );
    }
}
