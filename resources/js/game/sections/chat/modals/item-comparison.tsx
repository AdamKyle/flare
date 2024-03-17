import React, { Fragment } from "react";
import ItemNameColorationText from "../../../components/items/item-name/item-name-coloration-text";
import { capitalize } from "lodash";
import InventoryComparisonAdjustment from "../../components/item-details/comparison/definitions/inventory-comparison-adjustment";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import ComparisonSection from "../../components/item-details/comparison/comparison-section";
import { watchForChatDarkModeComparisonChange } from "../../../lib/game/dark-mode-watcher";
import UsableItemSection from "../../character-sheet/components/modals/components/usable-item-section";
import InventoryQuestItemDetails from "../../character-sheet/components/modals/components/inventory-quest-item-details";
import AlchemyItemHoly from "../../../components/modals/chat-item-comparison/item-views/alchemy-item-holy";
import GemDetails from "../../character-sheet/components/modals/components/gem-details";

export default class ItemComparison extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            comparison_details: null,
            usable_sets: [],
            action_loading: false,
            loading: true,
            dark_charts: false,
            error_message: null,
        };
    }

    componentDidMount() {
        watchForChatDarkModeComparisonChange(this);

        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/comparison-from-chat"
            )
            .setParameters({
                id: this.props.slot_id,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        comparison_details: result.data.comparison_data,
                        usable_sets: result.data.usable_sets,
                    });
                },
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response = error.response;

                        if (response.status === 404) {
                            this.setState({
                                error_message:
                                    "Item no longer exists in your inventory...",
                                loading: false,
                            });
                        }
                    }
                }
            );
    }

    setStatusToLoading() {
        this.setState({
            action_loading: !this.state.action_loading,
        });
    }

    getTheName() {
        const item = this.state.comparison_details.itemToEquip;

        if (typeof item.affix_name === "undefined") {
            return item.name;
        }

        return item.affix_name;
    }

    buildTitle() {
        if (this.state.error_message !== null) {
            return "Um ... ERROR!";
        }

        if (this.state.comparison_details === null) {
            return "Loading comparison data ...";
        }

        return (
            <div className="grid grid-cols-2 gap-2">
                {this.state.comparison_details.itemToEquip.type === "gem" ? (
                    <span className="text-lime-600 dark:text-lime-500">
                        {this.state.comparison_details.itemToEquip.item.gem.name}
                    </span>
                ) : (
                    <ItemNameColorationText
                        custom_width={false}
                        item={{
                            name: this.getTheName(),
                            type: this.state.comparison_details.itemToEquip
                                .type,
                            affix_count:
                                this.state.comparison_details.itemToEquip
                                    .affix_count,
                            is_unique:
                                this.state.comparison_details.itemToEquip
                                    .is_unique,
                            is_mythic:
                                this.state.comparison_details.itemToEquip
                                    .is_mythic,
                            holy_stacks_applied:
                                this.state.comparison_details.itemToEquip
                                    .holy_stacks_applied,
                        }}
                    />
                )}

                <div className="absolute right-[-30px] md:right-0">
                    <span className="pl-3 text-right mr-[70px]">
                        (Type:{" "}
                        {capitalize(
                            this.state.comparison_details.itemToEquip.type
                        )
                            .split("-")
                            .join(" ")}
                        )
                    </span>
                </div>
            </div>
        );
    }

    isGridSize(
        size: number,
        itemToEquip: InventoryComparisonAdjustment
    ): boolean {
        switch (size) {
            case 5:
                return (
                    itemToEquip.affix_count === 0 &&
                    itemToEquip.holy_stacks_applied === 0 &&
                    !itemToEquip.is_unique
                );
            case 7:
                return (
                    itemToEquip.affix_count > 0 ||
                    itemToEquip.holy_stacks_applied > 0 ||
                    itemToEquip.is_unique
                );
            default:
                return false;
        }
    }

    renderViewForType(type: string, holy_number?: number): JSX.Element {
        if (type === "alchemy") {
            if (typeof holy_number !== "undefined" && holy_number !== null) {
                return (
                    <AlchemyItemHoly
                        item={this.state.comparison_details.itemToEquip}
                    />
                );
            }

            return (
                <UsableItemSection
                    item={this.state.comparison_details.itemToEquip}
                />
            );
        }

        if (type === "quest") {
            return (
                <InventoryQuestItemDetails
                    item={this.state.comparison_details.itemToEquip}
                />
            );
        }

        if (type === "gem") {
            return (
                <GemDetails
                    gem={this.state.comparison_details.itemToEquip.item.gem}
                />
            );
        }

        return (
            <ComparisonSection
                is_large_modal={true}
                is_grid_size={this.isGridSize.bind(this)}
                comparison_details={this.state.comparison_details}
                set_action_loading={this.setStatusToLoading.bind(this)}
                is_action_loading={this.state.action_loading}
                manage_modal={this.props.manage_modal}
                character_id={this.props.character_id}
                dark_charts={this.state.dark_charts}
                usable_sets={this.state.usable_sets}
                slot_id={this.props.slot_id}
                is_automation_running={this.props.is_automation_running}
            />
        );
    }

    render() {
        if (this.props.is_dead) {
            return (
                <Dialogue
                    is_open={this.props.is_open}
                    handle_close={this.props.manage_modal}
                    title={"You are dead"}
                    large_modal={true}
                    primary_button_disabled={false}
                >
                    <p className="text-red-700 dark:text-red-400">
                        And you thought dead people could manage their
                        inventory. Go to the game tab, click revive and live
                        again.
                    </p>
                </Dialogue>
            );
        }

        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.buildTitle()}
                large_modal={
                    true
                }
                primary_button_disabled={this.state.action_loading}
            >
                {this.state.loading ? (
                    <div className="p-5 mb-2">
                        <ComponentLoading />
                    </div>
                ) : (
                    <Fragment>
                        {this.state.error_message !== null ? (
                            <div className="mx-4 text-red-500 dark:text-red-400 text-center text-lg">
                                {this.state.error_message}
                            </div>
                        ) : (
                            this.renderViewForType(
                                this.state.comparison_details.itemToEquip.type,
                                this.state.comparison_details.itemToEquip
                                    .holy_level
                            )
                        )}
                    </Fragment>
                )}
            </Dialogue>
        );
    }
}
