import React from "react";
import DangerButton from "../../../../../ui/buttons/danger-button";
import { startCase } from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";
import ItemNameColorationText from "../../../../../items/item-name/item-name-coloration-text";

export default class DamageBreakDown extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            is_loading: true,
            details: null,
        };
    }

    componentDidMount(): void {
        this.setState(
            {
                error_message: "",
            },
            () => {
                if (this.props.character === null) {
                    return;
                }

                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            this.props.character_id +
                            "/specific-attribute-break-down",
                    )
                    .setParameters({
                        type: this.props.type,
                        is_voided: this.props.is_voided ? 1 : 0,
                    })
                    .doAjaxCall(
                        "get",
                        (response: AxiosResponse) => {
                            this.setState({
                                is_loading: false,
                                details: response.data.break_down,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ is_loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.mmessage,
                                });
                            }
                        },
                    );
            },
        );
    }

    titelizeType(): string {
        return startCase(this.props.type.replace("-", " "));
    }

    renderItemListEffects() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.attached_affixes.length === 0) {
            return (
                <p className="my-4 text-slate-700 dark:text-slate-400">
                    There is nothing equipped.
                </p>
            );
        }

        return this.state.details.attached_affixes.map((equippedItem: any) => {
            let amount = 0;

            if (typeof equippedItem.base_damage !== "undefined") {
                amount = equippedItem.base_damage;
            } else {
                amount = equippedItem.base_healing;
            }

            return (
                <li>
                    <ItemNameColorationText
                        item={equippedItem.item_details}
                        custom_width={false}
                    />{" "}
                    <span className="text-green-700 dark:text-green-500">
                        (+{amount})
                    </span>
                    {equippedItem.affixes.length > 0 ? (
                        <ul className="ps-5 mt-2 space-y-1 list-disc list-inside">
                            {this.renderAttachedAffixes(equippedItem.affixes)}
                        </ul>
                    ) : null}
                </li>
            );
        });
    }

    renderBoonIncreaseAllStatsEffects() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.boon_details.length <= 0) {
            return;
        }

        if (this.state.details.boon_details.increases_all_stats.length <= 0) {
            return;
        }

        return this.state.details.boon_details.increases_all_stats.map(
            (boonIncreaseAllStats: any) => {
                return (
                    <li>
                        <ItemNameColorationText
                            item={boonIncreaseAllStats.item_details}
                            custom_width={false}
                        />{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                boonIncreaseAllStats.increase_amount * 100
                            ).toFixed(2)}
                            %)
                        </span>
                    </li>
                );
            },
        );
    }

    renderBoonIncreaseSpecificStatEffects() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.boon_details.increases_single_stat.length <= 0) {
            return null;
        }

        return this.state.details.boon_details.increases_single_stat.map(
            (boonIncreaseAllStats: any) => {
                return (
                    <li>
                        <ItemNameColorationText
                            item={boonIncreaseAllStats.item_details}
                            custom_width={false}
                        />{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                boonIncreaseAllStats.increase_amount * 100
                            ).toFixed(2)}
                            %)
                        </span>
                    </li>
                );
            },
        );
    }

    renderAncestralItemSkill() {
        if (this.state.details === null) {
            return;
        }

        return this.state.details.ancestral_item_skill_data.map(
            (ancestralItemSkill: any) => {
                return (
                    <li>
                        <span className="text-orange-600 dark:text-orange-300">
                            {ancestralItemSkill.name}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(ancestralItemSkill.increase_amount * 100).toFixed(
                                2,
                            )}
                            %)
                        </span>
                    </li>
                );
            },
        );
    }

    renderSkillsAffectingDamage() {
        if (this.state.details === null) {
            return;
        }

        return this.state.details.skills_effecting_damage.map(
            (skillAffectingDamage: any) => {
                return (
                    <li>
                        <span className="text-orange-600 dark:text-orange-300">
                            {skillAffectingDamage.name}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{(skillAffectingDamage.amount * 100).toFixed(2)}%)
                        </span>
                    </li>
                );
            },
        );
    }

    renderClassMasteries() {
        if (this.state.details === null) {
            return;
        }

        return this.state.details.masteries.map((mastery: any) => {
            return (
                <li>
                    <span className="text-primary-600 dark:text-primary-300">
                        {startCase(mastery.name.replace("-", " "))} for
                        position:{" "}
                        {startCase(mastery.position.replace("-", " "))}
                    </span>{" "}
                    <span className="text-green-700 dark:text-green-500">
                        (+{(mastery.amount * 100).toFixed(2)}%)
                    </span>
                </li>
            );
        });
    }

    renderClassSpecialtiesStatIncrease() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.class_specialties === null) {
            return null;
        }

        return this.state.details.class_specialties.map(
            (classSpecialty: any) => {
                return (
                    <li>
                        <span className="text-sky-600 dark:text-sky-500">
                            {classSpecialty.name}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{(classSpecialty.amount * 100).toFixed(2)}%)
                        </span>
                    </li>
                );
            },
        );
    }

    renderAttachedAffixes(attachedAffixes: any[]) {
        return attachedAffixes.map((attachedAffix: any) => {
            return (
                <li>
                    <span className="text-slate-700 dark:text-slate-400">
                        {attachedAffix.name}
                    </span>{" "}
                    <span className="text-green-700 dark:text-green-500">
                        (+{(attachedAffix.amount * 100).toFixed(2)}%);
                    </span>
                </li>
            );
        });
    }

    renderUnequippedDamageDetails() {
        return (
            <div>
                <h4>Non Equipped Damage</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Total Damage{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.non_equipped_damage_amount})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Name{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (
                            {startCase(
                                (this.props.is_voided ? "Voided " : "") +
                                    this.state.details.damage_stat_name,
                            )}
                            )
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Amount{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.damage_stat_amount})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Percentage of stat used{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                this.state.details
                                    .non_equipped_percentage_of_stat_used * 100
                            ).toFixed(2)}
                            )
                        </span>
                    </li>
                </ul>
            </div>
        );
    }

    renderWeaponDamageDetails() {
        return (
            <div>
                <h4>How base damage is calculated</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <p className="my-4">
                    Base damage is what we use to determine your over all
                    damage, this is a portion of your stats, usually 5%. Some
                    classes can raise this percent higher.
                </p>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Base Damage{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.base_damage})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Amount of stat used{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                this.state.details.percentage_of_stat_used * 100
                            ).toFixed(2)}
                            %)
                        </span>
                    </li>
                </ul>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <h4>Weapon damage from items</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Total Weapon Damage{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.total_damage_for_type})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Name{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            ({startCase(this.state.details.damage_stat_name)})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Amount{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.damage_stat_amount})
                        </span>
                    </li>
                </ul>
            </div>
        );
    }

    renderSpellDamage() {
        return (
            <div>
                <h4>Spell damage from items</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Name{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            ({startCase(this.state.details.damage_stat_name)})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage Stat Amount{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.damage_stat_amount})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Damage stat amount to use{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {this.state.details.spell_damage_stat_amount_to_use}
                            )
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Percentage of stat used{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                this.state.details.percentage_of_stat_used * 100
                            ).toFixed(2)}
                            %)
                        </span>
                    </li>
                </ul>
            </div>
        );
    }

    renderSpellHealing() {
        return (
            <div>
                <h4>Spell healing from items</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Healing Stat Name{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            ({startCase(this.state.details.damage_stat_name)})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Healing Stat Amount{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.damage_stat_amount})
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Healing stat amount to use{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {this.state.details.spell_damage_stat_amount_to_use}
                            )
                        </span>
                    </li>
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Percentage of stat used{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+
                            {(
                                this.state.details.percentage_of_stat_used * 100
                            ).toFixed(2)}
                            %)
                        </span>
                    </li>
                </ul>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <h4>Spell healing from items</h4>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                    <li>
                        <span className="text-slate-700 dark:text-slate-400">
                            Total healing{" "}
                        </span>{" "}
                        <span className="text-green-700 dark:text-green-500">
                            (+{this.state.details.total_damage_for_type})
                        </span>
                    </li>
                </ul>
            </div>
        );
    }

    render() {
        if (this.state.loading || this.state.details === null) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <div className="flex justify-between">
                    <div className="flex items-center">
                        <h3 className="mr-2">
                            {(this.props.is_voided ? "Voided " : "") +
                                startCase(this.props.type.replace("-", " "))}
                        </h3>
                        {this.state.details.non_equipped_damage_amount === 0 ? (
                            <span className="text-gray-700 dark:text-gray-400">
                                (Base{" "}
                                {this.props.type === "heal_for"
                                    ? "Healing:"
                                    : "Damage:"}{" "}
                                {this.state.details.base_damage})
                            </span>
                        ) : null}
                    </div>
                    <DangerButton
                        button_label={"Close"}
                        on_click={this.props.close_section}
                    />
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                {this.props.is_voided ? (
                    <p className="my-4 text-blue-700 dark:text-blue-500">
                        Voided Weapon Damage means no enchantments from your
                        gear is used. Voided Weapon Damage only comes into play
                        when an enemy voids you in combat.
                    </p>
                ) : null}

                <div className="grid md:grid-cols-2 gap-2">
                    <div>
                        {this.state.details.non_equipped_damage_amount !== 0 ? (
                            <>
                                {this.renderUnequippedDamageDetails()}
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2">
                                    {" "}
                                </div>
                            </>
                        ) : null}

                        {this.props.type === "weapon_damage" &&
                        this.state.details.non_equipped_damage_amount === 0 ? (
                            <>
                                {this.renderWeaponDamageDetails()}
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2">
                                    {" "}
                                </div>
                            </>
                        ) : null}

                        {this.props.type === "spell_damage" &&
                        this.state.details.non_equipped_damage_amount === 0 ? (
                            <>
                                {this.renderSpellDamage()}
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2">
                                    {" "}
                                </div>
                            </>
                        ) : null}

                        {this.props.type === "heal_for" &&
                        this.state.details.non_equipped_damage_amount === 0 ? (
                            <>
                                {this.renderSpellHealing()}
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2">
                                    {" "}
                                </div>
                            </>
                        ) : null}

                        <h4>Equipped Modifiers</h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.attached_affixes !== null ? (
                            <ol className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                                {this.renderItemListEffects()}
                            </ol>
                        ) : (
                            <p>You have nothing equipped.</p>
                        )}
                    </div>

                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden"></div>
                    <div>
                        <h4>Boons that increases all stats</h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.boon_details !== null ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                {this.renderBoonIncreaseAllStatsEffects()}
                            </ul>
                        ) : (
                            <p>
                                There are no boons applied that effect this
                                specific stat.
                            </p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4>Boons that increase: {this.titelizeType()}</h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.boon_details !== null ? (
                            this.state.details.boon_details.hasOwnProperty(
                                "increases_single_stat",
                            ) ? (
                                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                    {this.renderBoonIncreaseSpecificStatEffects()}
                                </ul>
                            ) : (
                                <p>
                                    There are no boons applied that effect this
                                    specific stat.
                                </p>
                            )
                        ) : (
                            <p>
                                There are no boons applied that effect this
                                specific stat.
                            </p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4>
                            {" "}
                            Equipped Class Specials That Raise:{" "}
                            {this.titelizeType()}
                        </h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.class_specialties !== null ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                {this.renderClassSpecialtiesStatIncrease()}
                            </ul>
                        ) : (
                            <p>
                                There are no class specials equipped that effect
                                this stat.
                            </p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4>
                            {" "}
                            Equipped Class Skill That Raise:{" "}
                            {this.titelizeType()}
                        </h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.class_bonus_details !== null ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                <li>
                                    <span className="text-slate-600 dark:text-slate-300">
                                        {
                                            this.state.details
                                                .class_bonus_details.name
                                        }
                                    </span>
                                    <span className="text-green-700 dark:text-green-500">
                                        (+
                                        {(
                                            this.state.details
                                                .class_bonus_details.amount *
                                            100
                                        ).toFixed(2)}
                                        %)
                                    </span>
                                </li>
                            </ul>
                        ) : (
                            <p>
                                You do not have a class skill that effects this
                                stat.
                            </p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4>
                            {" "}
                            Ancestral Item Skills That Raise:{" "}
                            {this.titelizeType()}
                        </h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.ancestral_item_skill_data !==
                        null ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                {this.renderAncestralItemSkill()}
                            </ul>
                        ) : (
                            <p>
                                There are no Ancestral Item Skills that effect
                                this stat.
                            </p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4> Skills That Increase: {this.titelizeType()}</h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.skills_effecting_damage !== null ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                {this.renderSkillsAffectingDamage()}
                            </ul>
                        ) : (
                            <p>No Class Skills that effect your AC.</p>
                        )}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        <h4>
                            {" "}
                            Class Rank Masteries That Increase:{" "}
                            {this.titelizeType()}
                        </h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        {this.state.details.masteries.length > 0 ? (
                            <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                {this.renderClassMasteries()}
                            </ul>
                        ) : (
                            <p>No Class Masteries that effect this stat.</p>
                        )}
                    </div>
                </div>
            </div>
        );
    }
}
