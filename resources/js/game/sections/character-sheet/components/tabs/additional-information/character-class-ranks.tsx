import React, {ReactNode} from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";

export default class CharacterClassRanks extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            character_class_rank_tab_text: 'class-ranks'
        }
    }

    changeAdditionalInfoTextWhenTabChanges(index: number, tabs: {key: string, name: string}[]): void {

        const tab = tabs[index];

        this.setState({
            character_class_rank_tab_text: tab.key,
        })
    }

    buildHelpSectionContentBasedOnTab(): ReactNode | null {
        switch (this.state.character_class_rank_tab_text) {
            case 'class-ranks':
                return this.classRanksHelpText();
            case 'class-masteries':
                return this.classSpecialHelpText();
            default:
                return null;
        }
    }

    classRanksHelpText(): ReactNode {
        return <div className='max-h-[400px] overflow-y-scroll'>
            <h1>What is this?</h1>
            <p className="dark:text-gray-300 mt-4">
                To the left we can manage our <a href="/information/class-ranks" target="_blank">
                Class Ranks <i className="fas fa-external-link-alt"></i>
            </a>{' '}. We can think of these in a similar vien of Final Fantasy Job system (or similar games) where
                players cna switch classes, level special abilities and mix and match those abilities to make their own build.
            </p>
            <p className="dark:text-gray-300 my-4">
                Characters level their class ranks by being that class and killing creatures. Over time, your server message
                section will tell you when your class rank, class special or class weapon mastery levels up.
            </p>
            <h3 className="underline">When To Switch Classes</h3>
            <p className="dark:text-gray-300 my-4">
                Players can switch classes at anytime they want. Players will notice the
                Orange skill under their trainable skills will swap out for that classes skill.
            </p>
            <h3 className="underline">Weapon Masteries</h3>
            <p className="dark:text-gray-300 my-4">
                Each class has a list of weapon masteries, which while that weapon type is equipped,
                as you kill creatures this will level - allowing you do an additional % of damage while that
                weapon type is equipped. You can see this by clicking on the class rank name.
            </p>
            <h3 className="underline">Some Classes Require Other Classes</h3>
            <p className="dark:text-gray-300 my-4">
                Some classes like Prisoner or Alcoholic require players to level other class ranks
                to specific levels before being able to switch to those classes. These classes cannot be
                selected at registration.
            </p>
            <h3 className="underline">Some Classes Require Other Classes</h3>
            <p className="dark:text-gray-300 my-4">
                Some classes like Prisoner or Alcoholic require players to level other class ranks
                to specific levels before being able to switch to those classes. These classes cannot be
                selected at registration.
            </p>
        </div>
    }

    classSpecialHelpText(): ReactNode {
        return <>
            <h1>What is this?</h1>
            <p className="dark:text-gray-300 my-4">
                To the left we can manage our <a href="/information/class-ranks" target="_blank">
                Class Specialties <i className="fas fa-external-link-alt"></i>
            </a>{' '}. We can think of these as special abilities that you can equip and level up by killing
                creatures.
            </p>
            <h3 className="underline">Mixing and Matching</h3>
            <p className="dark:text-gray-300 my-4">
                Players who switch classes and level their class rank via fighting monsters,
                will unlock specials that can the be equipped. Some of them give boosts to Attack, Ac, Healing and so on.
                While others will allow you to deal damage, but you can only have one damage ability equipped at a time.
            </p>
            <h3 className="underline">Making Your Own Build</h3>
            <p className="dark:text-gray-300 my-4">
               Players who level each class and unlock each of the classes special abilities as well as level them up will
                find that they have a play ground of their own making where they can create their own build that make their character
                not just feel unique but all their own.
            </p>
        </>
    }

    render() {
        return (
            <div className="relative">
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
                            when_tab_changes={this.changeAdditionalInfoTextWhenTabChanges.bind(this)}
                        />
                    </div>
                    <div id="rightSide" className="mt-10 md:mt-0 pl-4 sm:w-full md:w-1/2">
                        {this.buildHelpSectionContentBasedOnTab()}
                    </div>
                </div>
            </div>
        )
    }
}
