import React, { Fragment } from "react";
import ItemHolyEffects from "../values/item-holy-effects";
import { serviceContainer } from "../../../../lib/containers/core-container";
import AlchemyItemHolyProps from "../types/alchemy-item-holy-props";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";

export default class AlchemyItemHoly extends React.Component<
    AlchemyItemHolyProps,
    {}
> {
    private itemHolyEffects: ItemHolyEffects;

    constructor(props: AlchemyItemHolyProps) {
        super(props);

        this.itemHolyEffects = serviceContainer().fetch(ItemHolyEffects);
    }

    render() {
        if (this.props.item.holy_level === null) {
            return (
                <DangerAlert additional_css={"my-4"}>
                    <strong>Error</strong>: Holy Oil does not seem to have a
                    level associated with it.
                </DangerAlert>
            );
        }

        const effects = this.itemHolyEffects.determineItemHolyEffects(
            this.props.item.holy_level,
        );

        return (
            <div className="mr-auto ml-auto w-3/5">
                <p className="mt-4 mb-4">{this.props.item.description}</p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <p className="my-4 text-sky-700 dark:text-sky-600">
                    These items can only be used at the Purgatory Smiths
                    Workbench in Purgatory on items you wish to enhance. Each
                    oil will stack with the other. The amount of oils one can
                    apply to their weapons and armour is dependant on the craft
                    level of that item. Set items, always have the max holy
                    stacks that can applied.
                </p>
                <p className="my-4 text-sky-700 dark:text-sky-600">
                    As you can see below, the oil has a Holy Level, the higher
                    the level (max 5) the better the stats applied to the item.
                </p>
                <dl>
                    <dt>Holy Level</dt>
                    <dt>{this.props.item.holy_level}</dt>
                    <dt>Stat Increase Per Item used</dt>
                    <dd>{effects.stat_increase}%</dd>
                    <dt>Devouring Resistance Increase Per Item used</dt>
                    <dd>{effects.devouring_adjustment}%</dd>
                </dl>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <p className="my-4">
                    Read more about Holy Items in the{" "}
                    <a href="/information/holy-items" target="_blank">
                        Help Docs <i className="fas fa-external-link-alt"></i>
                    </a>
                </p>
            </div>
        );
    }
}
