import React from 'react';
import GameProps from './lib/game/types/game-props';
import Tabs from './components/ui/tabs/tabs';
import TabPanel from "./components/ui/tabs/tab-panel";
import BasicCard from "./components/ui/cards/basic-card";
import MapSection from "./sections/map/map-section";
import GameState from "./lib/game/types/game-state";
import WarningAlert from "./components/ui/alerts/simple-alerts/warning-alert";
import CharacterTopSection from "./sections/character-top-section/character-top-section";
import Quests from "./sections/components/quests/quests";
import Actions from "./sections/game-actions-section/actions";
import ManualProgressBar from "./components/ui/progress-bars/manual-progress-bar";
import FetchGameData from "./lib/game/ajax/FetchGameData";
import CharacterSheet from "./sections/character-sheet/character-sheet";

export default class Game extends React.Component<GameProps, GameState> {

    private tabs: {name: string, key: string}[];

    private characterTopBar: any;

    private characterAttacks: any;

    private characterStatus: any;

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
            name: 'Kingdom'
        }]

        this.state = {
            view_port: 0,
            show_size_message: true,
            character_status: null,
            loading: true,
            character_currencies: undefined,
            secondary_loading_title: 'Fetching character sheet ...',
            percentage_loaded: 0,
            character: null,
            kingdoms: [],
            quests: null,
        }


        // @ts-ignore
        this.characterTopBar = Echo.private('update-top-bar-' + this.props.userId);

        // @ts-ignore
        this.characterAttacks = Echo.private('update-character-attacks-' + this.props.userId);

        // @ts-ignore
        this.characterStatus = Echo.private('update-character-status-' + this.props.userId);
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
                character: {...this.state.character, ...event.characterStatuses}
            });
        });
    }

    hideDeviceSizeMessage(): void {
        this.setState({
            show_size_message: false,
        });
    }

    updateCharacterStatus(characterStatus: {is_dead: boolean, can_adventure: boolean}): void {
        this.setState({character_status: characterStatus});
    }

    updateCharacterCurrencies(currencies: {gold: number, shards: number, gold_dust: number, copper_coins: number}): void {
        this.setState({character_currencies: currencies});
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

        return (
            <div className="md:container">
                { this.state.view_port < 1600 && this.state.show_size_message ?
                    <WarningAlert additional_css={'mb-5'} close_alert={this.hideDeviceSizeMessage.bind(this)}>
                        Your devices screen size is too small to properly display the map.
                    </WarningAlert>
                    : null
                }
                <Tabs tabs={this.tabs}>
                    <TabPanel key={'game'}>
                        <div className="grid lg:grid-cols-3 gap-3">
                            <div className="w-full col-span-3 lg:col-span-2">
                                <BasicCard additionalClasses={'mb-10'}>
                                    <CharacterTopSection character={this.state.character}
                                                         view_port={this.state.view_port}
                                                         update_character_status={this.updateCharacterStatus.bind(this)}
                                                         update_character_currencies={this.updateCharacterCurrencies.bind(this)}
                                    />
                                </BasicCard>
                                <BasicCard additionalClasses={'min-h-60'}>
                                    <Actions character_id={this.props.characterId} character={this.state.character} />
                                </BasicCard>
                            </div>
                            <BasicCard additionalClasses={'hidden lg:block md:mt-0 lg:col-start-3 lg:col-end-3 max-h-[575px]'}>
                                <MapSection
                                    user_id={this.props.userId}
                                    character_id={this.props.characterId}
                                    view_port={this.state.view_port}
                                    currencies={this.state.character_currencies}
                                    is_dead={this.state.character?.is_dead}
                                />
                            </BasicCard>
                        </div>
                    </TabPanel>
                    <TabPanel key={'character-sheet'}>
                        <CharacterSheet character={this.state.character} />
                    </TabPanel>
                    <TabPanel key={'quests'}>
                        <BasicCard>
                            <Quests quest_details={this.state.quests} character_id={this.props.characterId} />
                        </BasicCard>
                    </TabPanel>
                    <TabPanel key={'kingdoms'}>
                        <BasicCard>
                            <p>Kingdoms</p>
                        </BasicCard>
                    </TabPanel>
                </Tabs>

                <BasicCard additionalClasses={'mt-10 mb-5'}>
                    <p>Chat Section</p>
                </BasicCard>
            </div>
        );

    }
}
