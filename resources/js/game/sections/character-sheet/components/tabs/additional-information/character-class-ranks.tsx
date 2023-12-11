import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";

export default class CharacterClassRanks extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
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
                        <CharacterClassRanksSection
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
                            To the left we can manage our <a href="/information/class-ranks" target="_blank">
                            Class Ranks <i className="fas fa-external-link-alt"></i>
                            </a>{' '}. We can think of these in a similar vien of Final Fantasy Job System where
                            players cna switch classes, level special abilities and mix and match those abilities to make their own build.
                        </p>
                        <h3 className="underline">When To Switch Classes</h3>
                        <p className="dark:text-gray-300 my-4">
                            Players can switch classes at anytime they want. Player swill notice the
                            Orange skill under their trainable skills will swap out for that classes skill.
                        </p>
                        <h3 className="underline">Managing Special Abilities</h3>
                        <p className="dark:text-gray-300 my-4">
                            Players can click the button below to open the Special Abilities of their or other classes.
                            A player can have three specials equipped and only one of those may deal damage. Other then that you are free to mix and match
                            and train them up by equipping them and killing monsters. The Server Messages tab will update when you level of your abilities.
                        </p>
                        <div className='text-center'>
                            <PrimaryButton button_label={'Manage Specials'} on_click={() => {}} />
                        </div>
                    </div>
                </div>
            </div>
        )
    }
}
