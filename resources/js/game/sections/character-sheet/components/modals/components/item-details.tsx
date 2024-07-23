import React, { Fragment } from "react";
import { formatNumber } from "../../../../../lib/game/format-number";
import ItemAffixDetails from "./item-affix-details";
import ItemHolyDetails from "./item-holy-details";
import OrangeButton from "../../../../../components/ui/buttons/orange-button";
import InventoryItemAttachedGems from "../inventory-item-attached-gems";
import clsx from "clsx";
import ItemDetailsProps from "./types/item-details-props";
import ItemDetailsState from "./types/item-details-state";
import { ItemDefinition } from "./deffinitions/item-definition";
import AffixDefinition from "./deffinitions/affix-definition";
import { AppliedStack } from "./deffinitions/holy-definition";

type ItemDetailsElements = React.JSX.Element | null;

export default class ItemDetails extends React.Component<
    ItemDetailsProps,
    ItemDetailsState
> {
    constructor(props: ItemDetailsProps) {
        super(props);

        this.state = {
            affix: null,
            view_affix: false,
            holy_stacks: null,
            view_stacks: false,
            view_sockets: false,
        };
    }

    manageAffixModal(affix?: AffixDefinition | null) {
        if (affix === null) {
            return;
        }

        this.setState({
            affix: typeof affix !== "undefined" ? affix : null,
            view_affix: !this.state.view_affix,
        });
    }

    manageHolyStacksDetails(holyStacks?: AppliedStack[]) {
        this.setState({
            holy_stacks: typeof holyStacks !== "undefined" ? holyStacks : null,
            view_stacks: !this.state.view_stacks,
        });
    }

    viewSockets() {
        this.setState({
            view_sockets: !this.state.view_sockets,
        });
    }

    renderAtonementAmounts(): ItemDetailsElements[] | [] {
        const atonements = this.props.item.item_atonements.atonements;
        const atonementData = [];

        for (const key in atonements) {
            atonementData.push(
                <Fragment>
                    <dt>{key}</dt>
                    <dd>{(atonements[key] * 100).toFixed(2)}%</dd>
                </Fragment>,
            );
        }

        return atonementData;
    }

    getStatModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            { label: "Str Modifier", value: item.str_modifier },
            { label: "Dex Modifier", value: item.dex_modifier },
            { label: "Agi Modifier", value: item.agi_modifier },
            { label: "Chr Modifier", value: item.chr_modifier },
            { label: "Dur Modifier", value: item.dur_modifier },
            { label: "Int Modifier", value: item.int_modifier },
            { label: "Focus Modifier", value: item.focus_modifier },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(modifier.value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    getBaseModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            {
                label: "Base Damage",
                value: item.base_damage,
                is_percentage: false,
            },
            { label: "Base Ac", value: item.base_ac, is_percentage: false },
            {
                label: "Base Healing",
                value: item.base_healing,
                is_percentage: false,
            },
            {
                label: "Base Damage Mod",
                value: item.base_damage_mod,
                is_percentage: true,
            },
            {
                label: "Base Ac Mod",
                value: item.base_ac_mod,
                is_percentage: true,
            },
            {
                label: "Base Healing Mod",
                value: item.base_healing_mod,
                is_percentage: true,
            },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>
                            {modifier.value > 0
                                ? modifier.is_percentage
                                    ? (modifier.value * 100).toFixed(2)
                                    : formatNumber(modifier.value)
                                : 0}
                            {modifier.is_percentage ? "%" : null}
                        </dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    getSkillModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            {
                label: "Effects Skill",
                value: item.skill_name !== null ? item.skill_name : "N/A",
            },
            { label: "Skill Bonus", value: item.skill_bonus },
            { label: "Skill XP Bonus", value: item.skill_training_bonus },
        ]
            .map((modifier) => {
                const value =
                    typeof modifier.value === "number" ? modifier.value : 0;

                return value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null;
            })
            .filter((value: React.JSX.Element | null) => value !== null);
    }

    getEvasionModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            { label: "Spell Evasion", value: item.spell_evasion },
            { label: "Healing Reduction", value: item.healing_reduction },
            {
                label: "Affix Dmg. Reduction",
                value: item.affix_damage_reduction,
            },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(modifier.value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    getDevouringModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            { label: "Devouring Light", value: item.devouring_light },
            { label: "Devouring Darkness", value: item.devouring_darkness },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(modifier.value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    getAmbushModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            { label: "Chance", value: item.ambush_chance },
            { label: "Resistance", value: item.ambush_resistance },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(modifier.value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    getCounterModifiers(item: ItemDefinition): ItemDetailsElements[] | [] {
        return [
            { label: "Chance", value: item.counter_chance },
            { label: "Resistance", value: item.counter_resistance },
        ]
            .map((modifier) =>
                modifier.value > 0 ? (
                    <Fragment key={modifier.label}>
                        <dt>{modifier.label}</dt>
                        <dd>{(modifier.value * 100).toFixed(2)}%</dd>
                    </Fragment>
                ) : null,
            )
            .filter((value: React.JSX.Element | null) => {
                return value !== null;
            });
    }

    render() {
        const statModifiers: ItemDetailsElements[] | [] = this.getStatModifiers(
            this.props.item,
        );
        const baseModifiers: ItemDetailsElements[] | [] = this.getBaseModifiers(
            this.props.item,
        );
        const skillModifiers: ItemDetailsElements[] | [] =
            this.getSkillModifiers(this.props.item);
        const evasionModifiers: ItemDetailsElements[] | [] =
            this.getEvasionModifiers(this.props.item);
        const devouringModifiers: ItemDetailsElements[] | [] =
            this.getDevouringModifiers(this.props.item);
        const ambushModifiers: ItemDetailsElements[] | [] =
            this.getAmbushModifiers(this.props.item);
        const counterModifiers: ItemDetailsElements[] | [] =
            this.getCounterModifiers(this.props.item);

        return (
            <div className="max-h-[400px] md:max-h-[600px] overflow-y-auto">
                <div
                    className="mb-4 mt-4 text-sky-700 dark:text-sky-500"
                    dangerouslySetInnerHTML={{
                        __html: this.props.item.description,
                    }}
                />

                <div
                    className={clsx("grid gap-3 mb-4", {
                        "md:grid-cols-2":
                            (baseModifiers.length > 0 ||
                                skillModifiers.length > 0 ||
                                this.props.item.affix_count > 0) &&
                            (baseModifiers.length <= 0 ||
                                skillModifiers.length <= 0 ||
                                this.props.item.affix_count <= 0),
                        "md:grid-cols-3":
                            [
                                baseModifiers.length,
                                skillModifiers.length,
                                this.props.item.affix_count,
                            ].filter((val) => val > 0).length >= 2,
                    })}
                >
                    <div>
                        <h4 className="text-sky-600 dark:text-sky-500">
                            Stats
                        </h4>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        <dl>{statModifiers}</dl>
                    </div>
                    {baseModifiers.length > 0 || skillModifiers.length > 0 ? (
                        <div>
                            <div
                                className={clsx({
                                    hidden: baseModifiers.length <= 0,
                                })}
                            >
                                <h4 className="text-sky-600 dark:text-sky-500">
                                    Modifiers
                                </h4>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <dl>{baseModifiers}</dl>
                            </div>
                            <div
                                className={clsx(
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                    {
                                        hidden: skillModifiers.length <= 0,
                                    },
                                )}
                            ></div>
                            <div
                                className={clsx({
                                    hidden: skillModifiers.length <= 0,
                                })}
                            >
                                <h4 className="text-sky-600 dark:text-sky-500">
                                    Skill Modifiers
                                </h4>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <dl>{skillModifiers}</dl>
                            </div>
                        </div>
                    ) : null}

                    {this.props.item.affix_count > 0 ? (
                        <div>
                            {evasionModifiers.length > 0 ? (
                                <div>
                                    <h4 className="text-sky-600 dark:text-sky-500">
                                        Evasion and Reductions
                                    </h4>
                                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                    <dl>{evasionModifiers}</dl>
                                </div>
                            ) : null}

                            {this.props.item.affix_count > 0 ? (
                                <Fragment>
                                    <div
                                        className={clsx(
                                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                            {
                                                hidden:
                                                    evasionModifiers.length <=
                                                    0,
                                            },
                                        )}
                                    ></div>
                                    <h4 className="text-sky-600 dark:text-sky-500">
                                        Attached Affixes
                                    </h4>
                                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                    <div className="mt-4">
                                        <div className={"mb-4"}>
                                            {this.props.item.item_prefix !==
                                            null ? (
                                                <OrangeButton
                                                    button_label={
                                                        this.props.item
                                                            .item_prefix.name
                                                    }
                                                    on_click={() =>
                                                        this.manageAffixModal(
                                                            this.props.item
                                                                .item_prefix,
                                                        )
                                                    }
                                                    additional_css={"w-1/2"}
                                                />
                                            ) : null}
                                        </div>
                                        <div>
                                            {this.props.item.item_suffix !==
                                            null ? (
                                                <OrangeButton
                                                    button_label={
                                                        this.props.item
                                                            .item_suffix.name
                                                    }
                                                    on_click={() =>
                                                        this.manageAffixModal(
                                                            this.props.item
                                                                .item_suffix,
                                                        )
                                                    }
                                                    additional_css={"w-1/2"}
                                                />
                                            ) : null}
                                        </div>
                                    </div>
                                </Fragment>
                            ) : null}
                        </div>
                    ) : null}
                </div>
                <div
                    className={clsx(
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        {
                            hidden: this.props.item.socket_amount <= 0,
                        },
                    )}
                ></div>
                {this.props.item.socket_amount > 0 ? (
                    <div className="grid md:grid-cols-3 gap-2">
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Sockets
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <dl>
                                <dt>Sockets Available</dt>
                                <dd>{this.props.item.socket_amount}</dd>
                            </dl>
                            <OrangeButton
                                button_label={"View Attached Gems"}
                                on_click={this.viewSockets.bind(this)}
                                additional_css={"my-4"}
                            />
                        </div>
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Elemental Atonement
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <dl>{this.renderAtonementAmounts()}</dl>
                        </div>
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Primary Elemental Attack
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <dl>
                                <dt>Primary Element</dt>
                                <dd>
                                    {
                                        this.props.item.item_atonements
                                            .elemental_damage.name
                                    }
                                </dd>
                                <dt>Damage</dt>
                                <dd>
                                    {(
                                        this.props.item.item_atonements
                                            .elemental_damage.amount * 100
                                    ).toFixed(2)}
                                    %
                                </dd>
                            </dl>
                        </div>
                    </div>
                ) : null}

                <div
                    className={clsx(
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        {
                            hidden: this.props.item.holy_stacks <= 0,
                        },
                    )}
                ></div>
                <div
                    className={clsx("grid gap-3 mb-4", {
                        "grid-cols-3":
                            [
                                devouringModifiers.length,
                                this.props.item.resurrection_chance,
                                this.props.item.holy_stacks,
                            ].filter((val) => val > 0).length >= 2,
                        "grid-cols-2":
                            (devouringModifiers.length > 0 ||
                                this.props.item.resurrection_chance > 0 ||
                                this.props.item.holy_stacks > 0) &&
                            [
                                devouringModifiers.length,
                                this.props.item.resurrection_chance,
                                this.props.item.holy_stacks,
                            ].filter((val) => val > 0).length < 2,
                    })}
                >
                    {devouringModifiers.length > 0 ? (
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Devouring Chance
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <dl>{devouringModifiers}</dl>
                        </div>
                    ) : null}

                    {this.props.item.resurrection_chance > 0 ? (
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Resurrection Chance
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <dl>
                                <dt>Chance</dt>
                                <dd>
                                    {(
                                        this.props.item.resurrection_chance *
                                        100
                                    ).toFixed(2)}
                                    %
                                </dd>
                            </dl>
                        </div>
                    ) : null}

                    {this.props.item.holy_stacks > 0 ? (
                        <div>
                            <h4 className="text-sky-600 dark:text-sky-500">
                                Holy Info
                            </h4>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <p className="mb-4">
                                Indicates how many can be applied to the item,
                                via the{" "}
                                <a
                                    href="/information/holy-items"
                                    target="_blank"
                                >
                                    <i className="fas fa-external-link-alt"></i>
                                    Purgatory Smith Work Bench.
                                </a>
                            </p>
                            <dl>
                                <dt>Holy Stacks</dt>
                                <dd>{this.props.item.holy_stacks}</dd>
                                <dt>Holy Stacks Applied</dt>
                                <dd>{this.props.item.holy_stacks_applied}</dd>
                                {this.props.item.holy_stacks_applied > 0 ? (
                                    <Fragment>
                                        <dt>Holy Stack Bonus</dt>
                                        <dd>
                                            {(
                                                this.props.item
                                                    .holy_stack_stat_bonus * 100
                                            ).toFixed(2)}
                                            %
                                        </dd>
                                        <dt>Holy Stack Stat Bonus</dt>
                                        <dd>
                                            {(
                                                this.props.item
                                                    .holy_stack_stat_bonus * 100
                                            ).toFixed(2)}
                                            %
                                        </dd>
                                        <dt>Holy Stack Break Down</dt>
                                        <dd>
                                            <button
                                                type="button"
                                                className="text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400"
                                                onClick={() =>
                                                    this.manageHolyStacksDetails(
                                                        this.props.item
                                                            .applied_stacks,
                                                    )
                                                }
                                            >
                                                View Details
                                            </button>
                                        </dd>
                                    </Fragment>
                                ) : null}
                            </dl>
                        </div>
                    ) : null}
                </div>
                {ambushModifiers.length > 0 || counterModifiers.length > 0 ? (
                    <div
                        className={clsx("grid md:grid-cols-2 gap-3 mb-4", {
                            "md:grid-cols-2":
                                ambushModifiers.length > 0 &&
                                counterModifiers.length > 0,
                        })}
                    >
                        {ambushModifiers.length > 0 ? (
                            <div>
                                <h4 className="text-sky-600 dark:text-sky-500">
                                    Ambush Info
                                </h4>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <dl>{ambushModifiers}</dl>
                            </div>
                        ) : null}
                        {counterModifiers.length > 0 ? (
                            <div>
                                <h4 className="text-sky-600 dark:text-sky-500">
                                    Counter
                                </h4>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <dl>{counterModifiers}</dl>
                            </div>
                        ) : null}
                    </div>
                ) : null}

                {this.state.view_affix && this.state.affix !== null ? (
                    <ItemAffixDetails
                        is_open={this.state.view_affix}
                        affix={this.state.affix}
                        manage_modal={this.manageAffixModal.bind(this)}
                    />
                ) : null}

                {this.state.view_stacks && this.state.holy_stacks !== null ? (
                    <ItemHolyDetails
                        is_open={this.state.view_stacks}
                        holy_stacks={this.state.holy_stacks}
                        manage_modal={this.manageHolyStacksDetails.bind(this)}
                    />
                ) : null}

                {this.state.view_sockets ? (
                    <InventoryItemAttachedGems
                        is_open={this.state.view_sockets}
                        character_id={this.props.character_id}
                        item_id={this.props.item.id}
                        manage_modal={this.viewSockets.bind(this)}
                    />
                ) : null}
            </div>
        );
    }
}
