import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AdditionalInfoSection from "./additional-info-section";

export default class CharacterAdditionalStats extends React.Component<any, any> {

    private tabs: {key: string, name: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'core-info',
            name: 'Core Information'
        },{
            key: 'active-boons',
            name: 'Active Boons',
        }, {
            key: 'factions',
            name: 'Factions'
        }, {
            key: 'mercenaries',
            name: 'Mercenaries'
        }];
    }

    render() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'core-info'}>
                    <div className="relative">
                        {/* Horizontal line on small screens */}
                        <div className="absolute top-1/2 left-0 right-0 bg-gray-300 h-px transform -translate-y-1/2 sm:hidden"></div>

                        {/* Vertical line on large screens */}
                        <div className="hidden md:block absolute top-1/4 bottom-1/4 left-1/2 bg-gray-300 w-px transform -translate-x-1/2"></div>

                        <div className="flex flex-wrap md:flex-nowrap items-center">

                            <div id="leftSide" className="pr-4 sm:w-full md:w-1/2">
                                <AdditionalInfoSection
                                    character={this.props.character}
                                    is_open={true}
                                    manage_modal={() => {}}
                                    title={''}
                                    finished_loading={true}
                                />
                            </div>
                            <div id="rightSide" className="mt-10 md:mt-0 pl-4 sm:w-full md:w-1/2">
                                <h1>What is this?</h1>
                                <p className="text-gray-300 my-4">
                                    To the left we can see a set of stats, each tab represents an aspect of your character,
                                    with specific types of gear assigned or not.
                                </p>
                                <h3 className="underline">Stats</h3>
                                <p className="text-gray-300 my-4">
                                    Stats represent two aspects of your character: Gear on (Modified) or naked (Raw). In some
                                    instances, your character can become{' '}
                                    <a href="/information/voidance" target="_blank">
                                        voided <i className="fas fa-external-link-alt"></i>
                                    </a>{' '}
                                    and thus your raw stats come into play.
                                </p>
                                <p className="text-gray-300 my-4">
                                    Under stats, we have your basic damage information based on what's equipped, what{' '}
                                    <a href="/information/enchanting" target="_blank">
                                        enchantments <i className="fas fa-external-link-alt"></i>
                                    </a>{' '}
                                    are applied and so on. There are a lot of aspects that go into gear progression that one
                                    encounters as they progress in the game.
                                </p>
                            </div>
                        </div>
                    </div>

                </TabPanel>
            </Tabs>
        );
    }
}
