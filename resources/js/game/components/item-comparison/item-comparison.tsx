import React, { ReactNode } from "react";
import ItemComparisonProps from "./types/item-comparison-props";
import Comparison from "./comparison";
import ItemNameColorationText from "../items/item-name/item-name-coloration-text";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import ComparisonDetails from "./deffinitions/comparison-details";
import ExpandedComparison from "./expanded-comparison";
import { ItemType } from "../items/enums/item-type";
import WarningAlert from "../ui/alerts/simple-alerts/warning-alert";
import { startCase } from "lodash";
import { formatNumber } from "../../lib/game/format-number";
import clsx from "clsx";
import ItemComparisonState from "./types/item-comparison-state";
import Item from "../items/item";

const twoHandedWeapons = [ItemType.STAVE, ItemType.BOW, ItemType.HAMMER];

export default class ItemComparison extends React.Component<
    ItemComparisonProps,
    ItemComparisonState
> {
    constructor(props: ItemComparisonProps) {
        super(props);

        this.state = {
            expanded_comparison_details: null,
            view_port: 0,
        };
    }

    componentDidMount() {
        this.setState({
            view_port:
                window.innerWidth || document.documentElement.clientWidth,
        });

        window.addEventListener("resize", () => {
            this.setState({
                view_port:
                    window.innerWidth || document.documentElement.clientWidth,
            });
        });
    }

    componentDidUpdate(prevProps: Readonly<ItemComparisonProps>) {
        if (
            prevProps.is_showing_expanded_comparison &&
            !this.props.is_showing_expanded_comparison
        ) {
            this.setState({
                expanded_comparison_details: null,
            });
        }
    }

    renderEquipButtons(
        isInline: boolean,
        comparisonItemType?: ItemType,
    ): ReactNode {
        const singleHandedItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
        ];

        if (comparisonItemType) {
            if (twoHandedWeapons.includes(comparisonItemType)) {
                return;
            }
        }

        const itemType = this.props.comparison_info.itemToEquip
            .type as ItemType;

        if (singleHandedItems.includes(itemType)) {
            return (
                <div className="flex justify-center">
                    <PrimaryOutlineButton
                        button_label={"Left Hand"}
                        on_click={() => this.handleSecondaryAction("left-hand")}
                    />
                    <PrimaryOutlineButton
                        button_label={"Right Hand"}
                        on_click={() =>
                            this.handleSecondaryAction("right-hand")
                        }
                        additional_css={"ml-4"}
                    />
                </div>
            );
        }

        if (twoHandedWeapons.includes(itemType)) {
            return (
                <>
                    {isInline ? (
                        <WarningAlert additional_css="my-4">
                            This is a two handed weapon, it will replace both
                            hands when equipped.
                        </WarningAlert>
                    ) : null}

                    <div className="flex justify-center">
                        <PrimaryOutlineButton
                            button_label={"Left Hand"}
                            on_click={() =>
                                this.handleSecondaryAction("left-hand")
                            }
                        />
                        <PrimaryOutlineButton
                            button_label={"Right Hand"}
                            on_click={() =>
                                this.handleSecondaryAction("right-hand")
                            }
                            additional_css={"ml-4"}
                        />
                    </div>
                </>
            );
        }

        if (ItemType.RING === itemType) {
            return (
                <div className="flex justify-center">
                    <PrimaryOutlineButton
                        button_label={"Ring One"}
                        on_click={() => this.handleSecondaryAction("ring-one")}
                    />
                    <PrimaryOutlineButton
                        button_label={"Ring Two"}
                        on_click={() => this.handleSecondaryAction("ring-two")}
                        additional_css={"ml-4"}
                    />
                </div>
            );
        }

        if (
            [ItemType.SPELL_DAMAGE, ItemType.SPELL_HEALING].includes(itemType)
        ) {
            return (
                <div className="flex justify-center">
                    <PrimaryOutlineButton
                        button_label={"Spell One"}
                        on_click={() => this.handleSecondaryAction("spell-one")}
                    />
                    <PrimaryOutlineButton
                        button_label={"Spell Two"}
                        on_click={() => this.handleSecondaryAction("spell-two")}
                        additional_css={"ml-4"}
                    />
                </div>
            );
        }

        return (
            <div className="flex justify-center">
                <PrimaryOutlineButton
                    button_label={"Equip"}
                    on_click={() =>
                        this.handleSecondaryAction(
                            this.props.comparison_info.itemToEquip.type,
                        )
                    }
                />
                <div className="ml-4 mt-2">
                    This item has a default position of{" "}
                    <strong>
                        {startCase(
                            this.props.comparison_info.itemToEquip
                                .default_position,
                        )}
                    </strong>{" "}
                    selected for you.
                </div>
            </div>
        );
    }

    showExpandedComparison(comparison: ComparisonDetails) {
        this.setState(
            {
                expanded_comparison_details: comparison,
            },
            () => {
                this.props.manage_show_expanded_comparison();
            },
        );
    }

    renderExpandedComparison() {
        if (this.state.expanded_comparison_details == null) {
            return;
        }

        return (
            <ExpandedComparison
                comparison_details={this.state.expanded_comparison_details}
                mobile_data={{
                    view_port: this.state.view_port,
                    mobile_height_restriction:
                        this.props.mobile_height_restriction,
                }}
            />
        );
    }

    handleSecondaryAction(position: string) {
        if (typeof this.props.handle_replace_action == "undefined") {
            return;
        }

        this.props.handle_replace_action(position);
    }

    renderSecondaryActionButton() {
        if (!this.props.replace_button_text) {
            return;
        }

        return (
            <div>
                <SuccessOutlineButton
                    button_label={this.props.replace_button_text}
                    on_click={() =>
                        this.handleSecondaryAction(
                            this.props.comparison_info.details[0].position,
                        )
                    }
                />
            </div>
        );
    }

    shouldRenderPositionButtons() {
        return typeof this.props.handle_replace_action !== "undefined";
    }

    shouldUseMobileHeightRestrictions(customWidth: number) {
        return (
            this.state.view_port < customWidth &&
            this.props.mobile_height_restriction
        );
    }

    renderColumns() {
        return (
            <div
                className={clsx({
                    "max-h-[375px] overflow-y-scroll":
                        this.shouldUseMobileHeightRestrictions(1500),
                    "max-h-[200px] overflow-y-scroll":
                        this.shouldUseMobileHeightRestrictions(800),
                })}
            >
                <div
                    className={clsx("my-4", {
                        hidden: this.props.mobile_height_restriction,
                    })}
                >
                    Looking to purchase:{" "}
                    <strong>
                        {this.props.comparison_info.itemToEquip.affix_name}
                    </strong>
                    , below is your comparison data, if you were to equip this
                    item in the equipped items slot. This fabulous item will
                    only cost you:{" "}
                    {formatNumber(this.props.comparison_info.itemToEquip.cost)}{" "}
                    gold!
                </div>
                <div className="grid md:grid-cols-2 gap-4">
                    <div>
                        <div className={"flex justify-between"}>
                            <div>
                                <ItemNameColorationText
                                    item={this.props.comparison_info.details[0]}
                                    custom_width={true}
                                    additional_css={"mt-4"}
                                />
                            </div>
                            <div>
                                <span
                                    className={
                                        "text-gray-600 dark:text-gray-200"
                                    }
                                >
                                    (
                                    {startCase(
                                        this.props.comparison_info.details[0].type.replace(
                                            "-",
                                            " ",
                                        ),
                                    )}
                                    )
                                </span>
                            </div>
                        </div>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <Comparison
                            comparison={this.props.comparison_info.details[0]}
                        />
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <div className="flex items-center">
                            <div className="mr-2">
                                <PrimaryOutlineButton
                                    button_label={"See Expanded Details"}
                                    on_click={() =>
                                        this.showExpandedComparison(
                                            this.props.comparison_info
                                                .details[0],
                                        )
                                    }
                                />
                            </div>
                            {this.renderSecondaryActionButton()}
                        </div>
                    </div>
                    <div>
                        <div className={"flex justify-between"}>
                            <div>
                                <ItemNameColorationText
                                    item={this.props.comparison_info.details[1]}
                                    custom_width={true}
                                    additional_css={"mt-4"}
                                />
                            </div>
                            <div>
                                <span
                                    className={
                                        "text-gray-600 dark:text-gray-200"
                                    }
                                >
                                    (
                                    {startCase(
                                        this.props.comparison_info.details[1].type.replace(
                                            "-",
                                            " ",
                                        ),
                                    )}
                                    )
                                </span>
                            </div>
                        </div>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <Comparison
                            comparison={this.props.comparison_info.details[1]}
                        />
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <div className="flex items-center">
                            <div className="mr-2">
                                <PrimaryOutlineButton
                                    button_label={"See Expanded Details"}
                                    on_click={() =>
                                        this.showExpandedComparison(
                                            this.props.comparison_info
                                                .details[1],
                                        )
                                    }
                                />
                            </div>
                            {this.renderSecondaryActionButton()}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    renderSingleComparison() {
        const nonArmourItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
            ItemType.STAVE,
            ItemType.HAMMER,
            ItemType.BOW,
            ItemType.RING,
            ItemType.SPELL_HEALING,
            ItemType.SPELL_DAMAGE,
        ];

        return (
            <div className="mr-auto ml-auto w-full md:w-3/5">
                <div
                    className={clsx("my-4", {
                        hidden: this.props.mobile_height_restriction,
                    })}
                >
                    Looking to purchase:{" "}
                    <strong>
                        {this.props.comparison_info.itemToEquip.affix_name}
                    </strong>
                    , below is your comparison data, if you were to equip this
                    item in the equipped items slot. This fabulous item will
                    only cost you:{" "}
                    {formatNumber(this.props.comparison_info.itemToEquip.cost)}{" "}
                    gold!
                </div>

                <h3>
                    <ItemNameColorationText
                        item={this.props.comparison_info.details[0]}
                        custom_width={true}
                    />
                </h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <Comparison
                    comparison={this.props.comparison_info.details[0]}
                />
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                <div className="flex items-center">
                    <div className="mr-2">
                        <PrimaryOutlineButton
                            button_label={"See Expanded Details"}
                            on_click={() =>
                                this.showExpandedComparison(
                                    this.props.comparison_info.details[0],
                                )
                            }
                        />
                    </div>
                    {this.renderSecondaryActionButton()}
                    {nonArmourItems.includes(
                        this.props.comparison_info.itemToEquip.type as ItemType,
                    ) && this.shouldRenderPositionButtons() ? (
                        <>
                            <div
                                className={clsx("px-5", {
                                    hidden: twoHandedWeapons.includes(
                                        this.props.comparison_info.details[0]
                                            .type,
                                    ),
                                })}
                            >
                                Or (Select position)
                            </div>

                            {this.renderEquipButtons(
                                false,
                                this.props.comparison_info.details[0].type,
                            )}
                        </>
                    ) : null}
                </div>
            </div>
        );
    }

    renderComparison() {
        if (this.props.comparison_info.details.length === 0) {
            if (this.props.mobile_height_restriction) {
                return (
                    <div>
                        <p className="my-4 italic text-center">
                            You have nothing equipped. Anything is better then
                            nothing.
                        </p>
                        <Item item={this.props.comparison_info.itemToEquip} />
                    </div>
                );
            }
            return (
                <div className="w-full md:max-w-3/5 md:mr-auto md:ml-auto">
                    <p className="my-4 text-center">
                        You don't have anything equipped in this slot. Why not
                        buy and equip the{" "}
                        <strong>
                            {this.props.comparison_info.itemToEquip.affix_name}
                        </strong>{" "}
                        for the low, low price of:{" "}
                        {formatNumber(
                            this.props.comparison_info.itemToEquip.cost,
                        )}{" "}
                        gold?
                    </p>
                    <Item item={this.props.comparison_info.itemToEquip} />
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 w-1/3 ml-auto mr-auto"></div>
                    {this.renderEquipButtons(true)}
                </div>
            );
        }

        if (this.props.comparison_info.details.length > 1) {
            return this.renderColumns();
        }

        return this.renderSingleComparison();
    }

    render() {
        return (
            <>
                {this.state.expanded_comparison_details !== null
                    ? this.renderExpandedComparison()
                    : this.renderComparison()}
            </>
        );
    }
}
