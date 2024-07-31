import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import CoreCharacterStatsSection from "./sections/core-character-stats-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterReincarnationSection from "./sections/character-reincarnation-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import CharacterElementalAtonementSection from "./sections/character-elemental-atonement-section";
import ResurrectionChance from "./sections/resurrection-chance";

export default class AdditionalStatSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <div className="grid md:grid-cols-2 gap-2">
                    <BasicCard>
                        <h3>Character Stats</h3>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <CoreCharacterStatsSection
                            view_port={0}
                            character={this.props.character}
                            is_open={true}
                            manage_modal={() => {}}
                            title={""}
                            finished_loading={true}
                            when_tab_changes={() => {}}
                        />
                    </BasicCard>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden"></div>
                    <BasicCard>
                        <h3>Class Ranks</h3>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <CharacterClassRanksSection
                            view_port={0}
                            character={this.props.character}
                            is_open={true}
                            manage_modal={() => {}}
                            title={""}
                            finished_loading={true}
                            when_tab_changes={() => {}}
                        />
                        <p className="my-4">
                            Learn more about:{" "}
                            <a href="/information/class-ranks" target="_blank">
                                Class Ranks{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                    </BasicCard>
                </div>

                <div className="grid md:grid-cols-2 gap-2 mt-4">
                    <BasicCard>
                        <div className="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3>Resistances</h3>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                                <p className={"my-3"}>
                                    These resistances come from the rings you
                                    have equipped.{" "}
                                    <strong>Spell Evasion</strong> will give you
                                    a chance at evading the enemies spells,
                                    while{" "}
                                    <strong>Affix Damage Reduction</strong> will
                                    reduce incoming damage from enemy
                                    enchantments. Finally,{" "}
                                    <strong>Enemy healing Reduction</strong>,
                                    will reduce the amount the enemy heals by.
                                </p>
                                <div className="mt-3">
                                    <ResistanceInfoSection
                                        view_port={0}
                                        character={this.props.character}
                                        is_open={true}
                                        manage_modal={() => {}}
                                        title={""}
                                        finished_loading={true}
                                    />
                                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                    <h3>Resurrection Chance</h3>
                                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                                    <p className={"my-3"}>
                                        Resurrection chance is calculated by
                                        equipping Healing Spells. Having two
                                        healing spells increases the chance for
                                        a character to be resurrected when they
                                        die in battle, with the exception of
                                        PVP.
                                    </p>
                                    <ResurrectionChance
                                        character={this.props.character}
                                    />
                                </div>
                            </div>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden"></div>
                            <div>
                                <h3>Elemental Atonement</h3>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                                <CharacterElementalAtonementSection
                                    character={this.props.character}
                                    is_open={true}
                                    manage_modal={() => {}}
                                    title={""}
                                    finished_loading={true}
                                />
                            </div>
                        </div>
                    </BasicCard>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden"></div>
                    <BasicCard>
                        <h3>Reincarnation</h3>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <p className="my-3">
                            This shows you, how many times you have
                            reincarnated, the stats we apply to your character
                            when they reincarnate back to level one. The damage
                            stat modifier and the base damage stat modifier as
                            well as the xp penalty for reincarnating.
                        </p>
                        <p className="mb-6">
                            <a
                                href="/information/reincarnation"
                                target="_blank"
                            >
                                Reincarnation{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            must be unlocked via completing a quest in Hell
                            called: "Unlock the secrets of reincarnation" and
                            require the use of an end game currency called:{" "}
                            <a href="/information/currencies" target="_blank">
                                Copper Coins{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                        <CharacterReincarnationSection
                            view_port={0}
                            character={this.props.character}
                            is_open={true}
                            manage_modal={() => {}}
                            title={""}
                            finished_loading={true}
                        />
                    </BasicCard>
                </div>
            </div>
        );
    }
}
