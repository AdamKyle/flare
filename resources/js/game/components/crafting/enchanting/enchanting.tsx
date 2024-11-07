import React, { Fragment } from "react";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import {
    craftingGetEndPoints,
    craftingPostEndPoints,
} from "../general-crafting/helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import DangerLinkButton from "../../ui/buttons/danger-link-button";
import {
    EnchantingState,
    Enchantment,
    ItemToEnchant,
} from "./types/enchanting-state";
import EnchantingProps from "./types/enchanting-props";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
import OrangeButton from "../../ui/buttons/orange-button";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
import InventoryCount from "../base-components/inventory-count/inventory_count";

export default class Enchanting extends React.Component<
    EnchantingProps,
    EnchantingState
> {
    private characterStatus: any;

    constructor(props: EnchantingProps) {
        super(props);

        this.state = {
            loading: true,
            selected_item: null,
            selected_prefix: null,
            selected_suffix: null,
            selected_type: null,
            enchantable_items: [],
            event_items: [],
            show_enchanting_for_event: false,
            enchantments: [],
            info_message: null,
            skill_xp: {
                current_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
            inventory_count: {
                current_count: 0,
                max_inventory: 75,
            },
            hide_enchanting_help: false,
        };

        // @ts-ignore
        this.characterStatus = Echo.private(
            "update-character-status-" + this.props.user_id,
        );
    }

    componentDidMount() {
        if (localStorage.getItem("hide-enchanting-help") !== null) {
            this.setState({
                hide_enchanting_help: true,
            });
        }

        const url = craftingGetEndPoints("enchant", this.props.character_id);

        new Ajax().setRoute(url).doAjaxCall("get", (result: AxiosResponse) => {
            this.setState({
                loading: false,
                enchantable_items: result.data.affixes.character_inventory,
                event_items: result.data.affixes.items_for_event,
                show_enchanting_for_event:
                    result.data.affixes.show_enchanting_for_event,
                enchantments: result.data.affixes.affixes,
                skill_xp: result.data.skill_xp,
                inventory_count: result.data.inventory_count,
            });
        });

        this.characterStatus.listen(
            "Game.Battle.Events.UpdateCharacterStatus",
            (event: any) => {
                this.setState({
                    show_enchanting_for_event:
                        event.characterStatuses.show_enchanting_for_event,
                });
            },
        );
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    enchant(enchantForEvent: boolean) {
        this.setState(
            {
                loading: true,
                info_message: null,
            },
            () => {
                const url = craftingPostEndPoints(
                    "enchant",
                    this.props.character_id,
                );

                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        slot_id: this.state.selected_item,
                        affix_ids: [
                            this.state.selected_prefix,
                            this.state.selected_suffix,
                        ],
                        enchant_for_event: enchantForEvent,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            const oldEnchantments = JSON.parse(
                                JSON.stringify(this.state.enchantments),
                            );

                            this.setState(
                                {
                                    loading: false,
                                    enchantable_items:
                                        result.data.affixes.character_inventory,
                                    event_items:
                                        result.data.affixes.items_for_event,
                                    show_enchanting_for_event:
                                        result.data.affixes
                                            .show_enchanting_for_event,
                                    enchantments: result.data.affixes.affixes,
                                    skill_xp: result.data.skill_xp,
                                    inventory_count:
                                        result.data.inventory_count,
                                },
                                () => {
                                    if (
                                        !isEqual(
                                            oldEnchantments,
                                            result.data.affixes.affixes,
                                        )
                                    ) {
                                        generateServerMessage(
                                            "new_items",
                                            "You have new enchantments. Check the list(s)!",
                                        );
                                    }

                                    if (
                                        result.data.affixes.items_for_event
                                            .length > 0 &&
                                        this.state.selected_type === "event"
                                    ) {
                                        this.setState({
                                            selected_item:
                                                result.data.affixes
                                                    .items_for_event[0].slot_id,
                                        });
                                    } else if (
                                        result.data.affixes.character_inventory
                                            .length > 0
                                    ) {
                                        this.setState({
                                            selected_item:
                                                result.data.affixes
                                                    .character_inventory[0]
                                                    .slot_id,
                                        });
                                    }

                                    if (
                                        result.data.affixes.items_for_event
                                            .length <= 0
                                    ) {
                                        this.setState({
                                            selected_type: "regular",
                                        });
                                    }
                                },
                            );
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    setSelectedItem(data: any) {
        this.setState({
            selected_item: parseInt(data.value),
        });
    }

    setTypeOfEnchanting(data: any) {
        this.setState({
            selected_type: data.value,
        });
    }

    setPrefix(data: any) {
        this.setState({
            selected_prefix: parseInt(data.value),
        });
    }

    setSuffix(data: any) {
        this.setState({
            selected_suffix: parseInt(data.value),
        });
    }

    renderItemsToEnchantSelection() {
        if (this.state.selected_type === null) {
            return this.state.enchantable_items.map((item: any) => {
                return {
                    label: item.item_name,
                    value: item.slot_id,
                };
            });
        }

        if (this.state.selected_type !== "regular") {
            return this.state.event_items.map((item: any) => {
                return {
                    label: item.item_name,
                    value: item.slot_id,
                };
            });
        }

        return this.state.enchantable_items.map((item: any) => {
            return {
                label: item.item_name,
                value: item.slot_id,
            };
        });
    }

    renderEnchantmentTypes() {
        return [
            {
                label: "Event",
                value: "event",
            },
            {
                label: "Regular",
                value: "regular",
            },
        ];
    }

    resetPrefixes() {
        this.setState({
            selected_prefix: null,
        });
    }

    resetSuffixes() {
        this.setState({
            selected_suffix: null,
        });
    }

    renderEnchantmentOptions(type: "prefix" | "suffix") {
        const enchantments = this.state.enchantments
            .filter((enchantment: any) => {
                return enchantment.type === type;
            })
            .sort((a: any, b: any) => a.cost - b.cost);

        return enchantments.map((enchantment: any) => {
            return {
                label:
                    enchantment.name +
                    " [Cost: " +
                    formatNumber(enchantment.cost) +
                    ", INT REQ: " +
                    formatNumber(enchantment.int_required) +
                    "]",
                value: enchantment.id,
            };
        });
    }

    selectedItemToEnchant() {
        if (this.state.selected_item !== null) {
            // @ts-ignore
            let foundItem: ItemToEnchant[] =
                this.state.enchantable_items.filter((item: any) => {
                    return item.slot_id === this.state.selected_item;
                });

            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item_name,
                    value: this.state.selected_item,
                };
            }

            foundItem = this.state.event_items.filter((item: any) => {
                return item.slot_id === this.state.selected_item;
            });

            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item_name,
                    value: this.state.selected_item,
                };
            }
        }

        return {
            label: "Please select item.",
            value: 0,
        };
    }

    selectedEnchantment(type: "prefix" | "suffix") {
        // @ts-ignore
        const selectedType: number | null = this.state["selected_" + type];

        if (selectedType !== null) {
            // @ts-ignore
            const foundEnchantment: Enchantment[] =
                this.state.enchantments.filter((item: any) => {
                    return item.id === selectedType;
                });

            if (foundEnchantment.length > 0) {
                return {
                    label:
                        foundEnchantment[0].name +
                        " Cost: " +
                        formatNumber(foundEnchantment[0].cost),
                    value: selectedType,
                };
            }
        }

        return {
            label: "Please select " + type + " enchantment.",
            value: 0,
        };
    }

    selectedTypeOfEnchantment() {
        if (this.state.selected_type !== null) {
            return {
                label:
                    this.state.selected_type === "regular"
                        ? "Regular"
                        : "Event",
                value: this.state.selected_type,
            };
        }

        return {
            label: "Please select which type of enchanting.",
            value: null,
        };
    }

    cannotCraft() {
        return (
            this.state.loading ||
            this.state.selected_item === null ||
            this.props.cannot_craft ||
            (this.state.selected_prefix === null &&
                this.state.selected_suffix === null)
        );
    }

    hideEnchantingHelp() {
        localStorage.setItem("hide-enchanting-help", "true");

        this.setState({
            hide_enchanting_help: true,
        });
    }

    render() {
        return (
            <Fragment>
                <div className="mt-2 grid lg:grid-cols-3 gap-2 lg:ml-[120px]">
                    {this.state.show_enchanting_for_event &&
                    this.state.event_items.length > 0 ? (
                        <div className="col-start-1 col-span-2">
                            <Select
                                onChange={this.setTypeOfEnchanting.bind(this)}
                                options={this.renderEnchantmentTypes()}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base: any) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={this.selectedTypeOfEnchantment()}
                            />
                        </div>
                    ) : null}
                    <div className="col-start-1 col-span-2">
                        <Select
                            onChange={this.setSelectedItem.bind(this)}
                            options={this.renderItemsToEnchantSelection()}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base: any) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.selectedItemToEnchant()}
                            isDisabled={
                                this.state.show_enchanting_for_event &&
                                this.state.event_items.length > 0 &&
                                this.state.selected_type === null
                            }
                        />
                    </div>
                    <div className="col-start-1 col-span-2">
                        <div className="lg:hidden grid grid-cols-3">
                            <div className="col-start-1 col-span-2">
                                <Select
                                    onChange={this.setPrefix.bind(this)}
                                    options={this.renderEnchantmentOptions(
                                        "prefix",
                                    )}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.selectedEnchantment("prefix")}
                                    isDisabled={
                                        (this.state.show_enchanting_for_event &&
                                            this.state.event_items.length > 0 &&
                                            this.state.selected_type ===
                                                null) ||
                                        this.state.selected_item === null
                                    }
                                />
                            </div>
                            <div className="cols-start-3 cols-end-3 mt-2 ml-4">
                                <DangerLinkButton
                                    button_label={"Clear"}
                                    on_click={this.resetPrefixes.bind(this)}
                                />
                            </div>
                        </div>
                        <div className="hidden lg:block">
                            <Select
                                onChange={this.setPrefix.bind(this)}
                                options={this.renderEnchantmentOptions(
                                    "prefix",
                                )}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base: any) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={this.selectedEnchantment("prefix")}
                                isDisabled={
                                    (this.state.show_enchanting_for_event &&
                                        this.state.event_items.length > 0 &&
                                        this.state.selected_type === null) ||
                                    this.state.selected_item === null
                                }
                            />
                        </div>
                    </div>
                    <div className="hidden lg:block cols-start-3 cols-end-3 mt-2">
                        <DangerLinkButton
                            button_label={"Clear"}
                            on_click={this.resetPrefixes.bind(this)}
                        />
                    </div>
                    <div className="col-start-1 col-span-2">
                        <div className="lg:hidden grid grid-cols-3">
                            <div className="col-start-1 col-span-2">
                                <Select
                                    onChange={this.setSuffix.bind(this)}
                                    options={this.renderEnchantmentOptions(
                                        "suffix",
                                    )}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.selectedEnchantment("suffix")}
                                    isDisabled={
                                        (this.state.show_enchanting_for_event &&
                                            this.state.event_items.length > 0 &&
                                            this.state.selected_type ===
                                                null) ||
                                        this.state.selected_item === null
                                    }
                                />
                            </div>
                            <div className="cols-start-3 cols-end-3 mt-2 ml-4">
                                <DangerLinkButton
                                    button_label={"Clear"}
                                    on_click={this.resetSuffixes.bind(this)}
                                />
                            </div>
                        </div>
                        <div className="hidden lg:block">
                            <Select
                                onChange={this.setSuffix.bind(this)}
                                options={this.renderEnchantmentOptions(
                                    "suffix",
                                )}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base: any) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={this.selectedEnchantment("suffix")}
                                isDisabled={
                                    (this.state.show_enchanting_for_event &&
                                        this.state.event_items.length > 0 &&
                                        this.state.selected_type === null) ||
                                    this.state.selected_item === null
                                }
                            />
                        </div>
                    </div>
                    <div className="hidden lg:block cols-start-3 cols-end-3 mt-2">
                        <DangerLinkButton
                            button_label={"Clear"}
                            on_click={this.resetSuffixes.bind(this)}
                        />
                    </div>
                </div>
                <div className="m-auto lg:w-1/2 relative lg:left-[-60px]">
                    <InfoAlert
                        additional_css={clsx("my-4", {
                            hidden: this.state.hide_enchanting_help,
                        })}
                        close_alert={this.hideEnchantingHelp.bind(this)}
                    >
                        <p className="my-2">
                            <strong className="my-2">
                                Pay attention to your Server Message chat tab.
                            </strong>
                        </p>
                        <p className="mb-2">
                            Enchanting requires you to raise your character INT
                            and your Enchanting skill. Players will run into an
                            issue where they unlock new enchants but cannot
                            craft them because their INT is too low. You can
                            raise this, regardless of class, by equipping
                            staves, damage spells or utilizing the{" "}
                            <a
                                href="/information/class-ranks"
                                target="_blank"
                                className="ml-2"
                            >
                                Class Ranks{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            or equipping Stat Modifier enchants or Spell
                            Crafting enchants will also raise your INT.
                        </p>
                        <p className="mb-2">
                            Sometimes, you just need to level your character as
                            well. Never underestimate a bit of grind.
                        </p>
                        <p>Click Help below for more info.</p>
                    </InfoAlert>
                </div>
                <div className="m-auto lg:w-1/2 relative lg:left-[-60px]">
                    {this.state.loading ? <LoadingProgressBar /> : null}

                    {this.state.enchantments.length > 0 ? (
                        <div className="ml-[25px] lg:ml-0 mb-2 lg:mb-0">
                            <CraftingXp skill_xp={this.state.skill_xp} />
                            <InventoryCount
                                inventory_count={this.state.inventory_count}
                            />
                        </div>
                    ) : null}
                </div>
                {this.state.event_items.length <= 0 &&
                this.state.show_enchanting_for_event ? (
                    <InfoAlert
                        additional_css={
                            "my-4 m-auto lg:w-1/2 relative lg:left-[-60px]"
                        }
                    >
                        You have no event crafted items. You can craft your own
                        items and either enchant them for your self or enchant
                        for event and participate in the event for a Legendary
                        item.
                    </InfoAlert>
                ) : null}
                <div className={"text-center md:ml-[-100px] mt-3 mb-3"}>
                    <div className="flex flex-col md:flex-row justify-center items-center gap-2">
                        <PrimaryButton
                            button_label={"Enchant"}
                            on_click={() => this.enchant(false)}
                            disabled={this.cannotCraft()}
                            additional_css="w-full md:w-auto"
                        />
                        {this.state.show_enchanting_for_event ? (
                            <OrangeButton
                                button_label={"Enchant for event"}
                                on_click={() => this.enchant(true)}
                                disabled={this.cannotCraft()}
                                additional_css={"w-full md:w-auto md:ml-2"}
                            />
                        ) : null}

                        <DangerButton
                            button_label={"Close"}
                            on_click={this.clearCrafting.bind(this)}
                            additional_css={"w-full md:w-auto md:ml-2"}
                            disabled={
                                this.state.loading || this.props.cannot_craft
                            }
                        />
                    </div>
                    <a
                        href="/information/enchanting"
                        target="_blank"
                        className="block mt-2 md:ml-2"
                    >
                        Help <i className="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </Fragment>
        );
    }
}
