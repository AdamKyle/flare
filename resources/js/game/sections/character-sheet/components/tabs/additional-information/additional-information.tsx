import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";

export default class AdditionalInformation extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            additional_info_tab_text: 'stats',
        }
    }

    changeAdditionalInfoTextWhenTabChanges(index: number, tabs: {key: string, name: string}[]): void {

        const tab = tabs[index];

        this.setState({
            additional_info_tab_text: tab.key,
        })
    }

    renderTextForTabChange(): JSX.Element | null {
        switch(this.state.additional_info_tab_text) {
            case 'stats':
                return <>
                    <h3 className="underline">Stats</h3>
                    <p className="dark:text-gray-300 my-4">
                        Stats represent two aspects of your character: Gear on (Modified) or naked (Raw). In some
                        instances, your character can become{' '}
                        <a href="/information/voidance" target="_blank">
                            voided <i className="fas fa-external-link-alt"></i>
                        </a>{' '}
                        and thus your raw stats come into play.
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Under stats, we have your basic damage information based on what's equipped, what{' '}
                        <a href="/information/enchanting" target="_blank">
                            enchantments <i className="fas fa-external-link-alt"></i>
                        </a>{' '}
                        are applied and so on. There are a lot of aspects that go into gear progression that one
                        encounters as they progress in the game.
                    </p>
                </>
            case 'holy':
                return <>
                    <h3 className="underline">Holy</h3>
                    <p className="dark:text-gray-300 my-4">
                        Holy is a special kind of property that can be applied to items. This is also know as{' '}
                        <a href="/information/holy-items" target="_blank">
                            Holy Oils <i className="fas fa-external-link-alt"></i>
                        </a>{' '}which are crafted through a system called: <a href="/information/alchemy" target="_blank">
                        Alchemy <i className="fas fa-external-link-alt"></i></a>. These Oils can be applied to items only
                        while in <a href="/information/planes" target="_blank">
                        Purgatory <i className="fas fa-external-link-alt"></i>
                    </a>
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Hoily oils apply two things to characters: Stats (random based on the level of the oil) and{' '}
                        <a href="/information/voidance" target="_blank">
                            Devouring Light/Darkness <i className="fas fa-external-link-alt"></i>
                        </a>{' '}
                        to the item. These can stack on the item in question giving late game players a huge power boost
                        for the challenges to come!
                    </p>
                </>
            case 'voidance':
                return <>
                    <h3 className="underline">Voidance</h3>
                    <p className="dark:text-gray-300 my-4">
                        <a href="/information/voidance" target="_blank">
                            Voidance <i className="fas fa-external-link-alt"></i>
                        </a>{' '} refers to the concept of Devouring Darkness and Devouring Light.
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Devouring Light and Darkness refer to the ability to devoid as well as void the enemy.
                        Voiding (Devouring Light) means you stop the enemy from using enchantments and reducing their power.
                        Where as devoiding (Devouring Darkness) the enemy refers to stopping them from being able to void you.
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Players can gain this via quest items like:{' '}<a href="/information/quest-items?table[search]=Dead+King%27s+Crown" target="_blank">
                        Dead Kings Crown <i className="fas fa-external-link-alt"></i></a>{' '}quest item.
                    </p>
                </>
            case 'ambush-counter':
                return <>
                    <h3 className="underline">Ambush and Counter</h3>
                    <p className="dark:text-gray-300 my-4">
                        <a href="/information/ambush-and-counter" target="_blank">
                            Ambush and Counter <i className="fas fa-external-link-alt"></i>
                        </a>{' '} Refer to the ability to ambush and enemy or counter an enemies attack.
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Ambushing and Countering chance as well as the resistance to each, are onlky obtainable by characters who craft:{' '}
                        <a href="/information/trinketry" target="_blank">
                            Trinkets <i className="fas fa-external-link-alt"></i>
                        </a>{' '} which require access to the end game plane:{' '}
                        <a href="/information/planes" target="_blank">
                            Purgatory <i className="fas fa-external-link-alt"></i>
                        </a>{' '}where players can then obtain{' '}
                        <a href="/information/currencies" target="_blank">
                            Copper Coins <i className="fas fa-external-link-alt"></i>
                        </a>{' '}to then craft them.
                    </p>
                    <p className="dark:text-gray-300 my-4">
                        Fortunately for new players, this is not required until the start of the end game
                        where creatures will start to have small amounts of Ambush and Counter chance and resistance, which will
                        grow over time as you move down the list.
                    </p>
                </>
            default:
                return null
        }
    }

    render() {
        return (
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
                            when_tab_changes={this.changeAdditionalInfoTextWhenTabChanges.bind(this)}
                        />
                    </div>
                    <div id="rightSide" className="mt-10 md:mt-0 pl-4 sm:w-full md:w-1/2">
                        <h1>What is this?</h1>
                        <p className="dark:text-gray-300 my-4">
                            To the left we can see a set of stats, each tab represents an aspect of your character,
                            with specific types of gear assigned or not.
                        </p>
                        {this.renderTextForTabChange()}
                    </div>
                </div>
            </div>
        )
    }
}
