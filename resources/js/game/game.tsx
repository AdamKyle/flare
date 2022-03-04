import React from 'react';
import { Tab } from '@headlessui/react'
import {classNames} from './lib/ui/css-class-helper';
import GameProps from './lib/game/types/GameProps';
import Tabs from  './components/ui/tabs/tabs';
import TabPanel from "./components/ui/tabs/tab-panel";
import BasicCard from "./components/ui/cards/basic-card";

export default class Game extends React.Component<GameProps, {}> {

    private tabs: {name: string, key: string}[];

    constructor(props: GameProps | Readonly<GameProps>) {
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
    }


    render() {
        return (
            <div className="md:container">
                <Tabs tabs={this.tabs}>
                    <TabPanel key={'game'}>
                        <div className="grid lg:grid-cols-3 gap-3">
                            <div className="w-full col-span-2">
                                <BasicCard additionalClasses={'mb-10'}>
                                    <p>Character info</p>
                                </BasicCard>
                                <BasicCard>
                                    <p>Actions</p>
                                </BasicCard>
                            </div>
                            <BasicCard additionalClasses={'col-start-1 col-end-3 mt-5 md:mt-0 md:col-start-3 md:col-end-3'}>
                                <p>Map Area</p>
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

                <BasicCard additionalClasses={'mt-10'}>
                    <p>Chat Section</p>
                </BasicCard>
            </div>
        );

    }
}
