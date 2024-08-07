import React from "react";
import ComparisonProps from "./types/comparison-props";
import clsx from "clsx";
import { formatNumber } from "../../lib/game/format-number";
import { ItemType } from "../items/enums/item-type";
import { startCase } from "lodash";

const coreAttributes = ["str", "dex", "dur", "int", "chr", "agi", "focus"];

export default class Comparison extends React.Component<ComparisonProps, any> {
    constructor(props: ComparisonProps) {
        super(props);
    }

    isValueBeloZero(value: number): boolean {
        return value < 0;
    }

    isValueAboveZero(value: number): boolean {
        return value > 0;
    }

    mapCoreAttributes(attributeName: string) {
        switch (attributeName) {
            case "str":
                return "Strength";
            case "dex":
                return "Dexterity";
            case "dur":
                return "Durability";
            case "int":
                return "Intelligence";
            case "chr":
                return "Charisma";
            case "agi":
                return "Agility";
            case "focus":
                return "Focus";
            default:
                return "ERROR";
        }
    }

    renderCoreAttributes() {
        return coreAttributes.map((attribute: string) => {
            return (
                <>
                    <dt>{this.mapCoreAttributes(attribute)}</dt>
                    <dd
                        className={clsx({
                            "text-green-700 dark:text-green-500":
                                this.isValueAboveZero(
                                    this.props.comparison[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-red-700 dark:text-red-500":
                                this.isValueBeloZero(
                                    this.props.comparison[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-gray-700 dark:text-white":
                                this.props.comparison[
                                    attribute + "_adjustment"
                                ] === 0,
                        })}
                    >
                        {(
                            this.props.comparison[attribute + "_adjustment"] *
                            100
                        ).toFixed(2) + "%"}
                    </dd>
                </>
            );
        });
    }

    renderAttackChange() {
        return (
            <dl>
                <dt>Attack</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.damage_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.damage_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.damage_adjustment === 0,
                    })}
                >
                    {formatNumber(this.props.comparison.damage_adjustment)}
                </dd>
            </dl>
        );
    }

    renderDefenceChange() {
        return (
            <dl>
                <dt>Defence</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.ac_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ac_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.ac_adjustment === 0,
                    })}
                >
                    {formatNumber(this.props.comparison.ac_adjustment)}
                </dd>
            </dl>
        );
    }

    renderAmbushAndCounterChange() {
        return (
            <dl>
                <dt>Ambush Chance</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.ambush_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ambush_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.ambush_chance_adjustment ===
                            0,
                    })}
                >
                    {(
                        this.props.comparison.ambush_chance_adjustment * 100
                    ).toFixed(2)}
                    %
                </dd>

                <dt>Ambush Resistance</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison
                                    .ambush_resistance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ambush_resistance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison
                                .ambush_resistance_adjustment === 0,
                    })}
                >
                    {(
                        this.props.comparison.ambush_resistance_adjustment * 100
                    ).toFixed(2)}
                    %
                </dd>

                <dt>Counter Chance</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.counter_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.counter_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.counter_chance_adjustment ===
                            0,
                    })}
                >
                    {(
                        this.props.comparison.counter_chance_adjustment * 100
                    ).toFixed(2)}
                    %
                </dd>

                <dt>Counter Resistance</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison
                                    .counter_resistance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.counter_resistance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison
                                .counter_resistance_adjustment === 0,
                    })}
                >
                    {(
                        this.props.comparison.counter_resistance_adjustment *
                        100
                    ).toFixed(2)}
                    %
                </dd>
            </dl>
        );
    }

    renderHealingChange() {
        return (
            <dl>
                <dt>Base Healing</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.healing_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.healing_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.healing_adjustment === 0,
                    })}
                >
                    {formatNumber(this.props.comparison.healing_adjustment)}
                </dd>
                <dt>Base Healing Mod</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.base_healing_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.base_healing_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.base_healing_adjustment === 0,
                    })}
                >
                    {(
                        this.props.comparison.base_healing_adjustment * 100
                    ).toFixed(2)}
                    %
                </dd>
                <dt>Resurrection Chance</dt>
                <dd
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.res_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.res_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.res_chance_adjustment === 0,
                    })}
                >
                    {(
                        this.props.comparison.res_chance_adjustment * 100
                    ).toFixed(2)}
                    %
                </dd>
            </dl>
        );
    }

    renderAttackOrDefenceAdjustment() {
        const damageBased = [
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.STAVE,
            ItemType.FAN,
            ItemType.HAMMER,
            ItemType.GUN,
            ItemType.SPELL_DAMAGE,
            ItemType.SCRATCH_AWL,
            ItemType.RING,
        ];

        if (damageBased.includes(this.props.comparison.type)) {
            return this.renderAttackChange();
        }

        if (this.props.comparison.type === ItemType.SPELL_HEALING) {
            return this.renderHealingChange();
        }

        if (this.props.comparison.type === ItemType.TRINKET) {
            return this.renderAmbushAndCounterChange();
        }

        return this.renderDefenceChange();
    }

    render() {
        return (
            <div>
                <dl>
                    <dt className={"text-orange-500 dark:text-orange-400"}>
                        Equipped Position
                    </dt>
                    <dd>
                        {startCase(
                            this.props.comparison.position.replace("-", " "),
                        )}
                    </dd>
                </dl>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                {this.renderAttackOrDefenceAdjustment()}

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <div>
                    <dl>{this.renderCoreAttributes()}</dl>
                </div>
            </div>
        );
    }
}
