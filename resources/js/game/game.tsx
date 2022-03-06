import React from 'react';
import GameProps from './lib/game/types/game-props';
import Tabs from './components/ui/tabs/tabs';
import TabPanel from "./components/ui/tabs/tab-panel";
import BasicCard from "./components/ui/cards/basic-card";
import MapSection from "./sections/map/map-section";
import GameState from "./lib/game/types/game-state";
import WarningAlert from "./components/ui/alerts/simple-alerts/warning-alert";

export default class Game extends React.Component<GameProps, GameState> {

    private tabs: {name: string, key: string}[];

    constructor(props: GameProps) {
        super(props)

        this.tabs = [{
            key: 'game',
            name: 'Game'
        }, {
            key: 'character-sheet',
            name: 'Character Sheet',
        }, {
            key: 'kingdoms',
            name: 'Kingdom'
        }]

        this.state = {
            view_port: 0,
            show_size_message: true,
        }

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
    }

    hideDeviceSizeMessage() {
        this.setState({
            show_size_message: false,
        });
    }

    render() {
        return (
            <div className="md:container">
                { this.state.view_port < 1280 && this.state.show_size_message ?
                    <WarningAlert additional_css={'mb-5'} close_alert={this.hideDeviceSizeMessage.bind(this)}>
                        You are currently on a device size lower then desktop. If you are on a mobile device you cannot click on locations (on the map) to
                        view their details. Instead click on "View Location" which will appear under the directional buttons when you are on a location
                        (blue port icon, pink arches or red/yellow kingdoms)
                    </WarningAlert>
                    : null
                }
                <Tabs tabs={this.tabs}>
                    <TabPanel key={'game'}>
                        <div className="grid lg:grid-cols-3 gap-3">
                            <div className="w-full col-span-3 lg:col-span-2">
                                <BasicCard additionalClasses={'mb-10'}>
                                    <p>Character info</p>
                                </BasicCard>
                                <BasicCard>
                                    <p>Actions</p>
                                </BasicCard>
                            </div>
                            <BasicCard additionalClasses={'col-start-2 col-end-2 mt-5 md:mt-0 lg:col-start-3 lg:col-end-3'}>
                                <MapSection user_id={this.props.userId} character_id={this.props.characterId} view_port={this.state.view_port}/>
                            </BasicCard>
                        </div>
                    </TabPanel>
                    <TabPanel key={'character-sheet'}>
                        <BasicCard>
                            <p>Character Sheet</p>
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
