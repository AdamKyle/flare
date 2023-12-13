import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";

export default class CharacterResistances extends React.Component<any, any> {

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
                        <ResistanceInfoSection
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
                            To the left we can see a set of resistances.
                            These effect your character in one way: Resisting the damage
                            of spells or enchantments (also known as Affix Damage) that's the enemy can do
                            to you.
                        </p>
                        <h3 className="underline">Spell Evasion</h3>
                        <p className="dark:text-gray-300 my-4">
                           Helps you evade the enemies spells. This can be obtained from rings.
                        </p>
                        <h3 className="underline">Affix Damage Reduction</h3>
                        <p className="dark:text-gray-300 my-4">
                            Helps you reduce the incoming Affix Damage. This can be obtained from rings.
                        </p>
                    </div>
                </div>
            </div>
        )
    }
}
