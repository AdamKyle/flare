import React, {ReactNode} from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import CharacterElementalAtonementSection from "./sections/character-elemental-atonement-section";

export default class CharacterElementalAtonement extends React.Component<any, any> {

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
                        <CharacterElementalAtonementSection
                            character={this.props.character}
                            is_open={true}
                            manage_modal={() => {}}
                            title={''}
                            finished_loading={true}
                        />
                    </div>
                    <div id="rightSide" className="mt-10 md:mt-0 pl-4 sm:w-full md:w-1/2 max-h-[400px] overflow-y-scroll">
                        <h1>What is this?</h1>
                        <p className="dark:text-gray-300 mt-4">
                            To the left we can see our <a href="/information/gems" target="_blank">
                            Elemental Atonement's <i className="fas fa-external-link-alt"></i>
                        </a>{' '}. You get these from Gem Crafting (Click link above) which we can start doing at
                            End game when it matters.
                        </p>
                        <p className="dark:text-gray-300 my-4">
                            Players will not need to make use of this feature until they start participating in Raids
                            where enemies have Elemental Attacks of their own.
                        </p>
                        <h3 className="underline">Gems</h3>
                        <p className="dark:text-gray-300 my-4">
                            Players craft what are called gems, there are 4 tiers each giving a specific % that is randomly rolled for that rank
                            which applies to your damage and resistance.
                        </p>
                        <p className="dark:text-gray-300 my-4">
                            Each gem will have a Fire, Water and Ice atonement %. The highest value on that gem is what the gem is atoned to. Each element is weak and strong against
                            the opposite element: Fire is Strong Against Ice who is Strong against Water who is strong against Fire.
                        </p>
                        <h3 className="underline">Sockets</h3>
                        <p className="dark:text-gray-300 my-4">
                            Players who have access to Purgatory can access the <a href="/information/seer-camp" target="_blank">
                            Seer Camp <i className="fas fa-external-link-alt"></i>
                        </a>{' '}which allows you to apply 1-6 sockets on a piece of gear and then socket the gems on to{' '}
                            your gear and even remove gems.
                        </p>
                        <h3 className="underline">Atonement</h3>
                        <p className="dark:text-gray-300 my-4">
                            When you craft and socket your gems your character will become atoned to the element who is the highest across all
                            your gear meaning you will do double damage to those weak to your element and reduce damage from
                            monsters who have the opposite element.
                        </p>
                    </div>
                </div>
            </div>
        )
    }
}
