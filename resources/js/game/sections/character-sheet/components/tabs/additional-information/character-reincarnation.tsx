import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterReincarnationSection from "./sections/character-reincarnation-section";

export default class CharacterReincarnation extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="relative">

                {/* Vertical line on large screens */}
                <div className="hidden md:block absolute top-1/4 bottom-1/4 left-1/2 bg-gray-300 w-px transform -translate-x-1/2"></div>

                <div className="flex flex-wrap md:flex-nowrap items-center">

                    <div id="leftSide" className="pr-4 sm:w-full md:w-1/2">
                        <CharacterReincarnationSection
                            character={this.props.character}
                            is_open={true}
                            manage_modal={() => {}}
                            title={''}
                            finished_loading={true}
                        />
                    </div>
                    <div id="rightSide" className="mt-10 md:mt-0 pl-4 sm:w-full md:w-1/2">
                        <h1>What is this?</h1>
                        <p className="dark:text-gray-300 my-4">
                            To the left we can see some basic information about how many times our character{' '}
                            can <a href="/information/reincarnation" target="_blank">
                            reincarnated <i className="fas fa-external-link-alt"></i>
                            </a>{' '}as well as the effects that has on your character.
                        </p>
                        <h3 className="underline">Reincarnation</h3>
                        <p className="dark:text-gray-300 my-4">
                            Characters who reach the max level: 5,000 and have access to end game currency: <a href="/information/currencies" target="_blank">
                                Copper Coins <i className="fas fa-external-link-alt"></i>
                            </a>{' '}Have the ability to Reincarnate.
                        </p>
                        <p className="dark:text-gray-300 my-4">
                            Essentially, when you reincarnate you set your character level to level 1. You keep a portion of your raw stats at the level you choose to reincarnate
                            and then you level from 1 to, ideally, 5000 again and then repeat another 98 times. Why? Power. Every time you do this your raw stats
                            will grow and your character will become much more powerful - especially when combined with your gear.
                        </p>
                    </div>
                </div>
            </div>
        )
    }
}
