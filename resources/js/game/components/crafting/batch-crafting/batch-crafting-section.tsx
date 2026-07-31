import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select, { StylesConfig } from "react-select";
import BatchCraftingStatusPanel from "../../../sections/game-actions-section/components/batch-crafting-status-panel";
import { updateTimers } from "../../../lib/ajax/update-timers";
import { craftingGetEndPoints } from "../general-crafting/helpers/crafting-type-url";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import ItemAffixDetails from "../../../sections/character-sheet/components/modals/components/item-affix-details";
import ItemDetails from "../../../sections/character-sheet/components/modals/components/item-details";
import Dialogue from "../../ui/dialogue/dialogue";
import { MarketBoardLineChart } from "../../ui/charts/line-chart";
import ComponentLoading from "../../ui/loading/component-loading";
import { DateTime } from "luxon";
import {
    BatchCraftingStatus,
    ProgressBar,
} from "./batch-crafting-status-display";
import BatchCraftingSectionProps from "./types/batch-crafting-section-props";
import BatchCraftingSectionState from "./types/batch-crafting-section-state";
import { formatNumber } from "../../../lib/game/format-number";
import {
    AlchemyMode,
    BatchCraftingItemPreviewSnapshot,
    BatchCraftingPreview,
    BatchCraftingStartBlocker,
    BatchType,
    CostBreakdown,
    CraftableItem,
    CraftCategory,
    CraftEnchantSetAvailableItem,
    CraftMode,
    Disposition,
    EnchantMode,
    EnchantmentOption,
    HandSelectionType,
    HolyOilMode,
    HolyOilPreviewItemEntry,
    InventorySetOption,
    OutputDestination,
} from "./types/batch-crafting-types";

const selectAllHolyOilItemsValue = -1;

const batchCraftingSelectStyles: StylesConfig<any, boolean> = {
    menu: (base) => ({
        ...base,
        backgroundColor: "#ffffff",
        color: "#111827",
    }),
    menuPortal: (base) => ({ ...base, zIndex: 9999 }),
    option: (base, state) => ({
        ...base,
        backgroundColor: state.isDisabled
            ? "#ffffff"
            : state.isSelected
              ? "#2684ff"
              : state.isFocused
                ? "#deebff"
                : "#ffffff",
        color: state.isDisabled
            ? "#9ca3af"
            : state.isSelected
              ? "#ffffff"
              : "#111827",
    }),
};

const batchTypes: { value: BatchType; label: string; isDisabled?: boolean }[] =
    [
        { value: "craft", label: "Craft" },
        { value: "craft_and_enchant", label: "Craft and Enchant" },
        { value: "enchant", label: "Enchant For Event" },
        { value: "alchemy", label: "Alchemy" },
        { value: "holy_oils", label: "Holy Oils" },
        { value: "trinketry", label: "Trinketry" },
    ];

const dispositions: { value: Disposition; label: string }[] = [
    { value: "keep", label: "Keep" },
    { value: "sell", label: "Sell" },
    { value: "destroy", label: "Destroy" },
    { value: "list", label: "List" },
    { value: "disenchant", label: "Disenchant" },
    { value: "keep_best_sell_rest", label: "Keep Best and Sell Rest" },
    {
        value: "keep_best_disenchant_rest",
        label: "Keep Best and Disenchant Rest",
    },
    {
        value: "keep_best_destroy_rest",
        label: "Keep Best and Destroy Rest",
    },
    { value: "use_now", label: "Use Now" },
];

const weaponTypeOptions = [
    { value: "dagger", label: "Daggers" },
    { value: "sword", label: "Swords" },
    { value: "claw", label: "Claws" },
    { value: "wand", label: "Wands" },
    { value: "censer", label: "Censers" },
    { value: "stave", label: "Staves" },
    { value: "hammer", label: "Hammers" },
    { value: "bow", label: "Bows" },
    { value: "gun", label: "Guns" },
    { value: "fan", label: "Fans" },
    { value: "mace", label: "Maces" },
    { value: "scratch-awl", label: "Scratch Awls" },
];

const armourTypeOptions = [
    { value: "helmet", label: "Helmet" },
    { value: "body", label: "Body" },
    { value: "sleeves", label: "Sleeves" },
    { value: "gloves", label: "Gloves" },
    { value: "shield", label: "Shields" },
    { value: "leggings", label: "Leggings" },
    { value: "feet", label: "Feet" },
];

const craftCategoryOptions: { value: CraftCategory; label: string }[] = [
    { value: "weapon", label: "Weapon" },
    { value: "armour", label: "Armour" },
    { value: "ring", label: "Ring" },
    { value: "spell", label: "Spell" },
];

type CraftEnchantSetPlanItem = {
    key: string;
    category: "hand" | "armour" | "ring" | "spell";
    label: string;
    optional?: boolean;
};

const craftEnchantSetPlanItems: CraftEnchantSetPlanItem[] = [
    { key: "left_hand", category: "hand", label: "Left Hand", optional: true },
    {
        key: "right_hand",
        category: "hand",
        label: "Right Hand",
        optional: true,
    },
    { key: "body", category: "armour", label: "Body" },
    { key: "leggings", category: "armour", label: "Leggings" },
    { key: "sleeves", category: "armour", label: "Sleeves" },
    { key: "gloves", category: "armour", label: "Gloves" },
    { key: "feet", category: "armour", label: "Feet" },
    { key: "helmet", category: "armour", label: "Helmet" },
    { key: "ring_0", category: "ring" as const, label: "Ring 1" },
    { key: "ring_1", category: "ring" as const, label: "Ring 2" },
    { key: "spell-damage", category: "spell" as const, label: "Damage Spell" },
    {
        key: "spell-healing",
        category: "spell" as const,
        label: "Healing Spell",
    },
];

const handSelectionOptions: {
    value: Exclude<HandSelectionType, null>;
    label: string;
}[] = [
    { value: "single_handed", label: "Single Handed" },
    { value: "shield", label: "Shield" },
    { value: "two_handed", label: "Duel Wield" },
];

function getHandWeaponTypes(
    availableItems: CraftEnchantSetAvailableItem[],
    handedness: "single_handed" | "two_handed",
) {
    return availableItems
        .filter((item) => item.handedness === handedness)
        .reduce<CraftEnchantSetAvailableItem[]>((types, item) => {
            const existing = types.find(
                (candidate) => candidate.type === item.type,
            );

            if (
                !existing ||
                item.skill_level_required < existing.skill_level_required
            ) {
                return [
                    ...types.filter(
                        (candidate) => candidate.type !== item.type,
                    ),
                    item,
                ];
            }

            return types;
        }, [])
        .sort(
            (a, b) =>
                a.skill_level_required - b.skill_level_required ||
                a.type.localeCompare(b.type),
        )
        .map((item) => ({ value: item.type, label: item.type }));
}

function getHandExactItems(
    availableItems: CraftEnchantSetAvailableItem[],
    handSelectionType: HandSelectionType,
    selectedWeaponType: string | null,
) {
    if (
        (handSelectionType === "single_handed" ||
            handSelectionType === "two_handed") &&
        selectedWeaponType === null
    ) {
        return [];
    }

    return availableItems
        .filter(
            (item) =>
                item.handedness === handSelectionType &&
                ((handSelectionType !== "single_handed" &&
                    handSelectionType !== "two_handed") ||
                    item.type === selectedWeaponType),
        )
        .sort(
            (a, b) =>
                a.skill_level_required - b.skill_level_required ||
                a.cost - b.cost ||
                a.id - b.id,
        );
}

function synchronizeHandPlanEntry(
    entry: any,
    availableItems: CraftEnchantSetAvailableItem[],
    clearAffixes: boolean,
) {
    const handSelectionType = entry?.handSelectionType ?? null;
    const selectedWeaponType = entry?.selectedWeaponType ?? null;
    const handSelectionStillAvailable =
        handSelectionType === null ||
        availableItems.some((item) => item.handedness === handSelectionType);
    const weaponTypeStillAvailable =
        (handSelectionType !== "single_handed" &&
            handSelectionType !== "two_handed") ||
        selectedWeaponType === null ||
        availableItems.some(
            (item) =>
                item.handedness === handSelectionType &&
                item.type === selectedWeaponType,
        );
    const selectedItemStillAvailable =
        entry?.selectedItemId === null ||
        entry?.selectedItemId === undefined ||
        availableItems.some((item) => item.id === entry.selectedItemId);

    if (
        handSelectionStillAvailable &&
        weaponTypeStillAvailable &&
        selectedItemStillAvailable
    ) {
        return entry;
    }

    return {
        ...entry,
        selectedItemId: null,
        handSelectionType: handSelectionStillAvailable
            ? handSelectionType
            : null,
        selectedWeaponType:
            handSelectionStillAvailable && weaponTypeStillAvailable
                ? selectedWeaponType
                : null,
        ...(clearAffixes ? { prefixAffixId: null, suffixAffixId: null } : {}),
    };
}

type CraftModeOption = { value: CraftMode; label: string };
type EnchantModeOption = { value: EnchantMode; label: string };
type AlchemyModeOption = { value: AlchemyMode; label: string };

// Mirrors the server-side matrix enforced by
// app/Game/BatchCrafting/Values/BatchCraftingDisposition::isAllowedFor(). Keep
// this in sync with that method whenever the allowed dispositions change.
// holy_oil_mode is not a parameter here because both Holy Oils modes
// (selected gear and inventory set) currently allow the exact same
// disposition set server-side.
function getAllowedDispositions(
    batchType: BatchType,
    craftMode: CraftMode,
    alchemyMode: AlchemyMode,
): Disposition[] {
    if (batchType === "enchant") {
        return ["keep"];
    }

    if (batchType === "craft") {
        if (craftMode === "event") {
            return ["keep"];
        }

        if (craftMode === "experience") {
            return [
                "keep",
                "sell",
                "destroy",
                "keep_best_sell_rest",
                "keep_best_destroy_rest",
            ];
        }

        return ["keep", "sell", "destroy"];
    }

    if (batchType === "craft_and_enchant") {
        if (craftMode === "experience") {
            return [
                "keep",
                "sell",
                "destroy",
                "list",
                "disenchant",
                "keep_best_sell_rest",
                "keep_best_destroy_rest",
                "keep_best_disenchant_rest",
            ];
        }

        return ["keep", "sell", "destroy", "list", "disenchant"];
    }

    if (batchType === "alchemy") {
        if (alchemyMode === "experience") {
            return [
                "keep",
                "destroy",
                "list",
                "keep_best_destroy_rest",
                "use_now",
            ];
        }

        return ["keep", "destroy", "list", "use_now"];
    }

    if (batchType === "trinketry") {
        return ["keep", "destroy", "keep_best_destroy_rest"];
    }

    if (batchType === "holy_oils") {
        return ["keep", "sell", "destroy", "list", "disenchant"];
    }

    return ["keep"];
}

type AffixSelectOption = {
    value: number;
    label: string;
    cost: number;
    intRequired: number;
    skillLevelRequired: number;
    skillName: string | null;
};

function toAffixOption(enchantment: EnchantmentOption): AffixSelectOption {
    return {
        value: enchantment.id,
        label: enchantment.name,
        cost: enchantment.cost,
        intRequired: enchantment.int_required,
        skillLevelRequired: enchantment.skill_level_required,
        skillName: enchantment.skill_name,
    };
}

function formatAffixOptionLabel(option: AffixSelectOption) {
    return (
        <span className="flex flex-wrap items-center gap-1">
            <span>{option.label}</span>
            <span className="text-xs text-gray-700">
                Cost: {formatNumber(option.cost)}
            </span>
            <span className="text-xs text-gray-700">
                INT Required: {formatNumber(option.intRequired)}
            </span>
            {option.skillLevelRequired > 0 ? (
                <span className="text-xs text-gray-700">
                    {option.skillName ?? "Skill"} Level:{" "}
                    {formatNumber(option.skillLevelRequired)}
                </span>
            ) : null}
        </span>
    );
}

export default class BatchCraftingSection extends React.Component<
    BatchCraftingSectionProps,
    BatchCraftingSectionState
> {
    private statusChannel: any = null;

    public constructor(props: BatchCraftingSectionProps) {
        super(props);

        this.state = {
            status: null,
            statusLoading: true,
            statusLoadError: null,
            batchType: "craft",
            disposition: "keep",
            listingPrice: 1,
            message: "",
            isSaving: false,
            holyOilItems: [],
            holyOilOptions: [],
            selectedItems: [],
            selectedOils: [],
            holyOilsLoading: false,
            holyOilMode: "selected",
            craftMode: "experience",
            alchemyMode: "experience",
            craftCategory: "weapon",
            weaponType: "dagger",
            armourType: "helmet",
            specificItemId: null,
            craftAmount: 1,
            craftableItems: [],
            craftableItemsLoading: false,
            alchemyItems: [],
            alchemyItemsLoading: false,
            selectedAlchemyItemId: null,
            enchantments: [],
            enchantmentsLoading: false,
            selectedPrefixId: null,
            selectedSuffixId: null,
            enchantMode: "event",
            inventorySets: [],
            inventorySetsLoading: false,
            selectedSetId: null,
            outputDestination: "crafted_items_set",
            outputSetId: null,
            craftEnchantSetMode: "build_new",
            craftEnchantSetPlan: {},
            craftEnchantSetBulkPrefixId: null,
            craftEnchantSetBulkSuffixId: null,
            craftEnchantSetAppliedPrefixId: null,
            craftEnchantSetAppliedSuffixId: null,
            craftSetPlan: {},
            preview: null,
            previewLoading: false,
            previewError: null,
            hideMaxedCraftNotice: false,
            affixDetailsModalAffix: null,
            craftEnchantSetItemDetailsModalItem: null,
            listingChartData: [],
            listingChartLoading: false,
            listingChartItemId: null,
        };
    }

    componentDidMount() {
        this.fetchStatus();
        this.listenForStatusUpdates();
        this.syncDependentState();
        this.fetchPreview();

        if (
            localStorage.getItem("hide-batch-crafting-maxed-craft-notice") !==
            null
        ) {
            this.setState({
                hideMaxedCraftNotice: true,
            });
        }
    }

    dismissMaxedCraftNotice() {
        localStorage.setItem("hide-batch-crafting-maxed-craft-notice", "true");

        this.setState({
            hideMaxedCraftNotice: true,
        });
    }

    componentDidUpdate(
        _previousProps: BatchCraftingSectionProps,
        previousState: BatchCraftingSectionState,
    ) {
        if (
            previousState.status !== this.state.status ||
            previousState.batchType !== this.state.batchType
        ) {
            if (this.normalizeModesForAvailability()) {
                return;
            }
        }

        if (
            previousState.batchType !== this.state.batchType ||
            previousState.disposition !== this.state.disposition ||
            previousState.craftMode !== this.state.craftMode ||
            previousState.alchemyMode !== this.state.alchemyMode ||
            previousState.craftCategory !== this.state.craftCategory ||
            previousState.weaponType !== this.state.weaponType ||
            previousState.armourType !== this.state.armourType ||
            previousState.enchantMode !== this.state.enchantMode ||
            previousState.holyOilMode !== this.state.holyOilMode
        ) {
            this.syncDependentState();
        }

        if (
            previousState.batchType !== this.state.batchType ||
            previousState.craftMode !== this.state.craftMode ||
            previousState.alchemyMode !== this.state.alchemyMode ||
            previousState.holyOilMode !== this.state.holyOilMode ||
            previousState.specificItemId !== this.state.specificItemId ||
            previousState.craftAmount !== this.state.craftAmount ||
            previousState.selectedPrefixId !== this.state.selectedPrefixId ||
            previousState.selectedSuffixId !== this.state.selectedSuffixId ||
            previousState.selectedAlchemyItemId !==
                this.state.selectedAlchemyItemId ||
            previousState.selectedSetId !== this.state.selectedSetId ||
            previousState.craftEnchantSetPlan !==
                this.state.craftEnchantSetPlan ||
            previousState.craftSetPlan !== this.state.craftSetPlan ||
            previousState.selectedItems !== this.state.selectedItems ||
            previousState.selectedOils !== this.state.selectedOils
        ) {
            this.fetchPreview();
        }

        if (
            previousState.disposition !== this.state.disposition ||
            this.getListingChartItemId(previousState) !==
                this.getListingChartItemId(this.state)
        ) {
            this.fetchListingChartDataIfNeeded();
        }
    }

    getListingChartItemId(state: BatchCraftingSectionState): number | null {
        const resolvedCraftMode = this.getResolvedCraftMode(
            state.status,
            state.batchType,
            state.craftMode,
        );
        const resolvedAlchemyMode = this.getResolvedAlchemyMode(
            state.status,
            state.alchemyMode,
        );

        if (
            state.batchType === "craft_and_enchant" &&
            resolvedCraftMode === "specific_item"
        ) {
            return state.specificItemId;
        }

        if (state.batchType === "alchemy" && resolvedAlchemyMode === "amount") {
            return state.selectedAlchemyItemId;
        }

        return null;
    }

    fetchListingChartDataIfNeeded() {
        const itemId = this.getListingChartItemId(this.state);

        if (this.state.disposition !== "list" || itemId === null) {
            if (
                this.state.listingChartData.length > 0 ||
                this.state.listingChartItemId !== null
            ) {
                this.setState({
                    listingChartData: [],
                    listingChartLoading: false,
                    listingChartItemId: null,
                });
            }

            return;
        }

        if (itemId === this.state.listingChartItemId) {
            return;
        }

        this.setState({
            listingChartLoading: true,
            listingChartItemId: itemId,
        });

        new Ajax()
            .setRoute("market-board/items")
            .setParameters({ item_id: itemId })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    const data = result.data.items.map(
                        (item: { listed_at: string; listed_price: number }) => {
                            return {
                                date: DateTime.fromISO(item.listed_at)
                                    .toLocaleString({
                                        weekday: "short",
                                        month: "short",
                                        day: "2-digit",
                                        hour: "2-digit",
                                        minute: "2-digit",
                                        second: "2-digit",
                                    })
                                    .toString(),
                                price: item.listed_price,
                            };
                        },
                    );

                    const now = DateTime.now()
                        .toLocaleString({
                            weekday: "short",
                            month: "short",
                            day: "2-digit",
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                        })
                        .toString();

                    if (data.length === 0) {
                        data.push({
                            date: now,
                            price: 0,
                        });
                    }

                    this.setState({
                        listingChartLoading: false,
                        listingChartData: [
                            {
                                label: "Listed for (Gold)",
                                color: "#441414",
                                data,
                            },
                        ],
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        listingChartLoading: false,
                        listingChartData: [],
                    });
                },
            );
    }

    getAvailableBatchTypes(status: BatchCraftingStatus | null) {
        const trinketryMaxed = status?.event_batch?.trinketry_maxed ?? false;
        const canEnchantForEvent =
            status?.event_batch?.can_enchant_for_event ?? false;
        const alchemyLocked = status?.event_batch?.alchemy_locked ?? false;

        return batchTypes
            .filter((option) => {
                if (option.value === "trinketry") {
                    return !trinketryMaxed;
                }

                if (option.value === "enchant") {
                    return canEnchantForEvent;
                }

                return true;
            })
            .map((option) =>
                option.value === "alchemy" || option.value === "holy_oils"
                    ? { ...option, isDisabled: alchemyLocked }
                    : option,
            );
    }

    getCraftExperienceOptions(
        status: BatchCraftingStatus | null,
        batchType: BatchType,
    ) {
        const relevantBatchTypes: BatchType[] =
            batchType === "craft_and_enchant"
                ? ["craft", "craft_and_enchant"]
                : [batchType];

        return (status?.craft_experience_options ?? []).filter((option) =>
            relevantBatchTypes.includes(option.batch_type as BatchType),
        );
    }

    getCraftModeOptions(
        status: BatchCraftingStatus | null,
        batchType: BatchType,
    ): CraftModeOption[] {
        const canCraftForExperience =
            batchType === "craft"
                ? (status?.craft_mode_availability?.can_craft_for_experience ??
                  true)
                : (status?.craft_mode_availability
                      ?.can_craft_and_enchant_for_experience ?? true);
        const eventBatch = status?.event_batch;

        return [
            ...(canCraftForExperience
                ? [
                      {
                          value: "experience" as CraftMode,
                          label:
                              batchType === "craft"
                                  ? "Craft For Experience"
                                  : "Craft and Enchant for Experience",
                      },
                  ]
                : []),
            {
                value: "specific_item" as CraftMode,
                label:
                    batchType === "craft"
                        ? "Craft Amount"
                        : batchType === "craft_and_enchant"
                          ? "Craft and Enchant Amount"
                          : "Specific Item",
            },
            ...(batchType === "craft"
                ? [
                      {
                          value: "craft_set" as CraftMode,
                          label: "Craft Set",
                      },
                  ]
                : []),
            ...(batchType === "craft_and_enchant"
                ? [
                      {
                          value: "craft_enchant_set" as CraftMode,
                          label: "Craft and Enchant Set",
                      },
                  ]
                : []),
            ...(batchType === "craft" && eventBatch?.can_craft_for_event
                ? [
                      {
                          value: "event" as CraftMode,
                          label: "Craft For Event",
                      },
                  ]
                : []),
        ];
    }

    getResolvedCraftMode(
        status: BatchCraftingStatus | null,
        batchType: BatchType,
        craftMode: CraftMode,
    ): CraftMode {
        const options = this.getCraftModeOptions(status, batchType);

        return (
            options.find((option) => option.value === craftMode)?.value ??
            options[0]?.value ??
            "specific_item"
        );
    }

    getEnchantModeOptions(
        status: BatchCraftingStatus | null,
    ): EnchantModeOption[] {
        return [
            ...(status?.event_batch?.can_enchant_for_event
                ? [
                      {
                          value: "event" as EnchantMode,
                          label: "Enchant For Event",
                      },
                  ]
                : []),
        ];
    }

    getResolvedEnchantMode(
        status: BatchCraftingStatus | null,
        enchantMode: EnchantMode,
    ): EnchantMode {
        const options = this.getEnchantModeOptions(status);

        return (
            options.find((option) => option.value === enchantMode)?.value ??
            options[0]?.value ??
            "event"
        );
    }

    getAlchemyModeOptions(
        status: BatchCraftingStatus | null,
    ): AlchemyModeOption[] {
        const alchemyLocked = status?.event_batch?.alchemy_locked ?? false;
        const alchemyMaxed = status?.event_batch?.alchemy_maxed ?? false;

        return [
            ...(alchemyMaxed
                ? []
                : [
                      {
                          value: "experience" as AlchemyMode,
                          label: "For Experience",
                      },
                  ]),
            { value: "amount" as AlchemyMode, label: "Craft Amount" },
        ];
    }

    getResolvedAlchemyMode(
        status: BatchCraftingStatus | null,
        alchemyMode: AlchemyMode,
    ): AlchemyMode {
        const options = this.getAlchemyModeOptions(status);

        return (
            options.find((option) => option.value === alchemyMode)?.value ??
            options[0]?.value ??
            "amount"
        );
    }

    normalizeModesForAvailability(): boolean {
        const { batchType, status } = this.state;

        if (!status) {
            return false;
        }

        const normalizedState: Partial<BatchCraftingSectionState> = {};
        const resolvedBatchType =
            this.getAvailableBatchTypes(status).find(
                (option) => option.value === batchType,
            )?.value ??
            this.getAvailableBatchTypes(status)[0]?.value ??
            "craft";

        if (resolvedBatchType !== batchType) {
            normalizedState.batchType = resolvedBatchType;
        }

        const resolvedCraftMode = this.getResolvedCraftMode(
            status,
            resolvedBatchType,
            this.state.craftMode,
        );

        if (
            (resolvedBatchType === "craft" ||
                resolvedBatchType === "craft_and_enchant") &&
            resolvedCraftMode !== this.state.craftMode
        ) {
            normalizedState.craftMode = resolvedCraftMode;
        }

        const resolvedAlchemyMode = this.getResolvedAlchemyMode(
            status,
            this.state.alchemyMode,
        );

        if (
            resolvedBatchType === "alchemy" &&
            resolvedAlchemyMode !== this.state.alchemyMode
        ) {
            normalizedState.alchemyMode = resolvedAlchemyMode;
        }

        const resolvedEnchantMode = this.getResolvedEnchantMode(
            status,
            this.state.enchantMode,
        );

        if (
            resolvedBatchType === "enchant" &&
            resolvedEnchantMode !== this.state.enchantMode
        ) {
            normalizedState.enchantMode = resolvedEnchantMode;
        }

        if (Object.keys(normalizedState).length === 0) {
            return false;
        }

        this.setState(normalizedState);

        return true;
    }

    componentWillUnmount() {
        this.statusChannel?.stopListening(".batch-crafting.status.updated");
    }

    listenForStatusUpdates() {
        const channelName =
            "batch-crafting-status-updated-" + this.props.user_id;
        this.statusChannel = window.Echo?.private(channelName);

        this.statusChannel?.listen(".batch-crafting.status.updated", () => {
            this.fetchStatus();
        });
    }

    fetchStatus() {
        this.setState({
            statusLoading: true,
            statusLoadError: null,
        });
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/status`)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) =>
                    this.setState({
                        status: response.data,
                        statusLoading: false,
                        statusLoadError: null,
                    }),
                (error: AxiosError) => {
                    const response = error.response as
                        | AxiosResponse
                        | undefined;

                    this.setState({
                        statusLoading: false,
                        statusLoadError:
                            response?.data?.message ??
                            "Batch crafting status could not be loaded.",
                    });
                },
            );
    }

    fetchHolyOilsData() {
        this.setState({
            holyOilsLoading: true,
        });
        new Ajax()
            .setRoute(
                `character/${this.props.character_id}/inventory/smiths-workbench`,
            )
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState({
                        holyOilItems: response.data.items ?? [],
                        holyOilOptions: response.data.alchemy_items ?? [],
                        holyOilsLoading: false,
                    });
                },
                (_error: AxiosError) =>
                    this.setState({
                        holyOilsLoading: false,
                    }),
            );
    }

    fetchInventorySets() {
        this.setState({ inventorySetsLoading: true });
        new Ajax()
            .setRoute(`character/${this.props.character_id}/inventory`)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const sets = response.data.sets ?? {};
                    const options: InventorySetOption[] = Object.keys(sets)
                        .map((name) => ({ name, ...sets[name] }))
                        .filter((set) => !set.is_batch_crafting_set)
                        .map((set) => ({
                            set_id: set.set_id,
                            label:
                                set.max_slots === null ||
                                typeof set.max_slots === "undefined"
                                    ? `${set.name} (${set.current_slots} used / unlimited)`
                                    : `${set.name} (${set.current_slots}/${set.max_slots})`,
                            current_slots: set.current_slots,
                            max_slots: set.max_slots,
                            remaining_slots: set.remaining_slots,
                            equipped: set.equipped,
                            is_batch_crafting_set: set.is_batch_crafting_set,
                        }));

                    const selectedSetId = options.some(
                        (option) => option.set_id === this.state.selectedSetId,
                    )
                        ? this.state.selectedSetId
                        : (options[0]?.set_id ?? null);

                    const eligibleOutputSets = options.filter(
                        (option) =>
                            !option.equipped && option.current_slots === 0,
                    );
                    const outputSetId = eligibleOutputSets.some(
                        (option) => option.set_id === this.state.outputSetId,
                    )
                        ? this.state.outputSetId
                        : (eligibleOutputSets[0]?.set_id ?? null);

                    this.setState({
                        inventorySets: options,
                        inventorySetsLoading: false,
                        selectedSetId,
                        outputSetId,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        inventorySets: [],
                        inventorySetsLoading: false,
                    });
                },
            );
    }

    handleBatchTypeChange(batchType: BatchType) {
        const craftModeOptions = this.getCraftModeOptions(
            this.state.status,
            batchType,
        );
        const alchemyModeOptions = this.getAlchemyModeOptions(
            this.state.status,
        );
        const craftMode: CraftMode = craftModeOptions.some(
            (option) => option.value === "experience",
        )
            ? "experience"
            : "specific_item";
        const alchemyMode: AlchemyMode = alchemyModeOptions.some(
            (option) => option.value === "experience",
        )
            ? "experience"
            : "amount";

        this.setState({
            batchType,
            disposition: "keep",
            listingPrice: 1,
            craftMode,
            alchemyMode,
            holyOilMode: "selected",
            craftCategory: "weapon",
            weaponType: "dagger",
            armourType: "helmet",
            specificItemId: null,
            craftAmount: 1,
            craftableItems: [],
            alchemyItems: [],
            selectedAlchemyItemId: null,
            selectedPrefixId: null,
            selectedSuffixId: null,
            selectedItems: [],
            selectedOils: [],
            selectedSetId: null,
            outputDestination: "crafted_items_set",
            outputSetId: null,
            inventorySets: [],
            craftEnchantSetPlan: {},
            craftEnchantSetBulkPrefixId: null,
            craftEnchantSetBulkSuffixId: null,
            craftEnchantSetAppliedPrefixId: null,
            craftEnchantSetAppliedSuffixId: null,
            craftSetPlan: {},
            preview: null,
            previewError: null,
            previewLoading: false,
        });
    }

    syncDependentState() {
        const { batchType, craftCategory, disposition, holyOilMode, status } =
            this.state;
        const craftMode = this.getResolvedCraftMode(
            status,
            batchType,
            this.state.craftMode,
        );
        const alchemyMode = this.getResolvedAlchemyMode(
            status,
            this.state.alchemyMode,
        );

        const allowedDispositions = getAllowedDispositions(
            batchType,
            craftMode,
            alchemyMode,
        );

        if (!allowedDispositions.includes(disposition)) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (batchType === "holy_oils") {
            this.fetchHolyOilsData();
        } else if (
            this.state.holyOilItems.length > 0 ||
            this.state.holyOilOptions.length > 0 ||
            this.state.selectedItems.length > 0 ||
            this.state.selectedOils.length > 0
        ) {
            this.setState({
                holyOilItems: [],
                holyOilOptions: [],
                selectedItems: [],
                selectedOils: [],
            });
        }

        if (
            (batchType !== "craft" && batchType !== "craft_and_enchant") ||
            craftMode !== "specific_item"
        ) {
            if (
                this.state.craftableItems.length > 0 ||
                this.state.specificItemId !== null
            ) {
                this.setState({
                    craftableItems: [],
                    specificItemId: null,
                });
            }
        } else {
            this.fetchCraftableItems();
        }

        if (batchType !== "alchemy" || alchemyMode !== "amount") {
            if (
                this.state.alchemyItems.length > 0 ||
                this.state.selectedAlchemyItemId !== null
            ) {
                this.setState({
                    alchemyItems: [],
                    selectedAlchemyItemId: null,
                });
            }
        } else {
            this.fetchAlchemyItems();
        }

        const needsEnchantments =
            batchType === "craft_and_enchant" &&
            (craftMode === "specific_item" ||
                craftMode === "craft_enchant_set");

        if (!needsEnchantments) {
            if (
                this.state.enchantments.length > 0 ||
                this.state.selectedPrefixId !== null ||
                this.state.selectedSuffixId !== null
            ) {
                this.setState({
                    enchantments: [],
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                });
            }
        } else {
            this.fetchEnchantments();
        }

        if (
            batchType !== "craft_and_enchant" ||
            craftMode !== "craft_enchant_set"
        ) {
            if (
                Object.keys(this.state.craftEnchantSetPlan).length > 0 ||
                this.state.craftEnchantSetBulkPrefixId !== null ||
                this.state.craftEnchantSetBulkSuffixId !== null ||
                this.state.craftEnchantSetAppliedPrefixId !== null ||
                this.state.craftEnchantSetAppliedSuffixId !== null
            ) {
                this.setState({
                    craftEnchantSetPlan: {},
                    craftEnchantSetBulkPrefixId: null,
                    craftEnchantSetBulkSuffixId: null,
                    craftEnchantSetAppliedPrefixId: null,
                    craftEnchantSetAppliedSuffixId: null,
                });
            }
        } else if (Object.keys(this.state.craftEnchantSetPlan).length === 0) {
            this.setState({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        {
                            prefixAffixId: null,
                            suffixAffixId: null,
                            selectedItemId: null,
                            handSelectionType:
                                item.category === "hand" ? null : undefined,
                            selectedWeaponType:
                                item.category === "hand" ? null : undefined,
                        },
                    ]),
                ),
            });
        }

        if (batchType !== "craft" || craftMode !== "craft_set") {
            if (Object.keys(this.state.craftSetPlan).length > 0) {
                this.setState({
                    craftSetPlan: {},
                });
            }
        } else if (Object.keys(this.state.craftSetPlan).length === 0) {
            this.setState({
                craftSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        {
                            selectedItemId: null,
                            handSelectionType:
                                item.category === "hand" ? null : undefined,
                            selectedWeaponType:
                                item.category === "hand" ? null : undefined,
                        },
                    ]),
                ),
            });
        }

        const needsHolyOilsSet =
            batchType === "holy_oils" && holyOilMode === "set";
        const needsOutputDestinationSelector = this.isFiniteRetainedOutputMode(
            batchType,
            craftMode,
            disposition,
        );
        const needsInventorySet =
            needsHolyOilsSet || needsOutputDestinationSelector;

        if (!needsInventorySet) {
            if (
                this.state.inventorySets.length > 0 ||
                this.state.selectedSetId !== null
            ) {
                this.setState({
                    inventorySets: [],
                    selectedSetId: null,
                });
            }
        } else {
            this.fetchInventorySets();
        }

        if (
            !needsOutputDestinationSelector &&
            this.state.outputSetId !== null
        ) {
            this.setState({
                outputSetId: null,
            });
        }
    }

    isFiniteRetainedOutputMode(
        batchType: BatchType,
        craftMode: CraftMode,
        disposition: Disposition,
    ): boolean {
        if (disposition !== "keep") {
            return false;
        }

        return (
            (batchType === "craft" &&
                (craftMode === "specific_item" || craftMode === "craft_set")) ||
            (batchType === "craft_and_enchant" &&
                (craftMode === "specific_item" ||
                    craftMode === "craft_enchant_set"))
        );
    }

    outputDestinationDescription(): string {
        const { inventorySets, outputDestination, outputSetId } = this.state;

        if (outputDestination === "inventory") {
            return "your Inventory";
        }

        if (outputDestination === "inventory_set") {
            const selectedSet = inventorySets.find(
                (set) => set.set_id === outputSetId,
            );

            return selectedSet
                ? `your set: ${selectedSet.label}`
                : "the selected set";
        }

        return "your Crafted Items Set";
    }

    fetchCraftableItems() {
        const { armourType, craftCategory, weaponType, specificItemId } =
            this.state;
        const craftingType =
            craftCategory === "weapon"
                ? weaponType
                : craftCategory === "armour"
                  ? "armour"
                  : craftCategory;

        this.setState({ craftableItemsLoading: true });

        new Ajax()
            .setRoute(`crafting/${this.props.character_id}`)
            .setParameters({ crafting_type: craftingType })
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items =
                        craftCategory === "armour"
                            ? (response.data.items ?? []).filter(
                                  (item: CraftableItem) =>
                                      item.type === armourType,
                              )
                            : (response.data.items ?? []);
                    // Items come back ordered by skill_level_required ascending, so the
                    // last entry is the highest-level (highest craftable) item.
                    const preservedSelection = items.find(
                        (item: CraftableItem) => item.id === specificItemId,
                    );
                    const defaultItem =
                        preservedSelection ?? items[items.length - 1];

                    this.setState({
                        craftableItems: items,
                        specificItemId: defaultItem?.id ?? null,
                        craftableItemsLoading: false,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        craftableItems: [],
                        specificItemId: null,
                        craftableItemsLoading: false,
                    });
                },
            );
    }

    fetchAlchemyItems() {
        const { selectedAlchemyItemId } = this.state;

        this.setState({ alchemyItemsLoading: true });

        new Ajax()
            .setRoute(craftingGetEndPoints("alchemy", this.props.character_id))
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items = response.data.items ?? [];
                    const preservedSelection = items.find(
                        (item: CraftableItem) =>
                            item.id === selectedAlchemyItemId,
                    );
                    const defaultItem =
                        preservedSelection ?? items[items.length - 1];

                    this.setState({
                        alchemyItems: items,
                        selectedAlchemyItemId: defaultItem?.id ?? null,
                        alchemyItemsLoading: false,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        alchemyItems: [],
                        selectedAlchemyItemId: null,
                        alchemyItemsLoading: false,
                    });
                },
            );
    }

    fetchEnchantments() {
        this.setState({ enchantmentsLoading: true });

        new Ajax().setRoute(`enchanting/${this.props.character_id}`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                const affixes = response.data.affixes?.affixes ?? [];
                this.setState({
                    enchantments: affixes,
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                    enchantmentsLoading: false,
                });
            },
            (_error: AxiosError) => {
                this.setState({
                    enchantments: [],
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                    enchantmentsLoading: false,
                });
            },
        );
    }

    buildBatchParams(
        selectedCraftMode?: CraftMode,
        selectedEnchantMode?: EnchantMode,
    ): Record<string, unknown> {
        const {
            batchType,
            craftAmount,
            craftCategory,
            craftEnchantSetMode,
            craftEnchantSetPlan,
            craftSetPlan,
            disposition,
            holyOilMode,
            listingPrice,
            outputDestination,
            outputSetId,
            selectedAlchemyItemId,
            selectedItems,
            selectedOils,
            selectedPrefixId,
            selectedSuffixId,
            selectedSetId,
            specificItemId,
            weaponType,
            status,
        } = this.state;
        const craftModeForRequest =
            selectedCraftMode ??
            this.getResolvedCraftMode(status, batchType, this.state.craftMode);
        const enchantModeForRequest =
            selectedEnchantMode ??
            this.getResolvedEnchantMode(status, this.state.enchantMode);
        const alchemyModeForRequest = this.getResolvedAlchemyMode(
            status,
            this.state.alchemyMode,
        );

        const progress: Record<string, unknown> = {};

        if (batchType === "craft" || batchType === "craft_and_enchant") {
            progress.craft_mode = craftModeForRequest;

            if (
                this.isFiniteRetainedOutputMode(
                    batchType,
                    craftModeForRequest,
                    disposition,
                )
            ) {
                progress.output_destination = outputDestination;

                if (outputDestination === "inventory_set") {
                    progress.output_set_id = outputSetId;
                }
            }

            if (craftModeForRequest === "specific_item") {
                progress.specific_crafting_type =
                    craftCategory === "weapon"
                        ? weaponType
                        : craftCategory === "armour"
                          ? "armour"
                          : craftCategory;
                progress.specific_item_id = specificItemId;
                progress.craft_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;

                if (batchType === "craft_and_enchant") {
                    progress.enchant_affix_ids = [
                        selectedPrefixId,
                        selectedSuffixId,
                    ].filter((affixId) => affixId !== null);
                }
            } else if (craftModeForRequest === "craft_set") {
                progress.craft_set_plan = Object.fromEntries(
                    Object.entries(craftSetPlan).map(([key, entry]) => [
                        key,
                        {
                            selected_item_id: entry.selectedItemId ?? null,
                        },
                    ]),
                );
            } else if (craftModeForRequest === "craft_enchant_set") {
                progress.craft_enchant_set_mode = craftEnchantSetMode;

                progress.enchant_plan = Object.fromEntries(
                    Object.entries(craftEnchantSetPlan).map(([key, entry]) => [
                        key,
                        {
                            prefix_affix_id: entry.prefixAffixId,
                            suffix_affix_id: entry.suffixAffixId,
                            selected_item_id: entry.selectedItemId ?? null,
                        },
                    ]),
                );
            }
        }

        if (batchType === "enchant") {
            progress.enchant_mode = enchantModeForRequest;
        }

        if (batchType === "alchemy") {
            progress.alchemy_mode = alchemyModeForRequest;

            if (alchemyModeForRequest === "amount") {
                progress.alchemy_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;
                progress.alchemy_item_id = selectedAlchemyItemId;
            }
        }

        if (batchType === "trinketry") {
            progress.trinketry_mode = "experience";
        }

        if (batchType === "holy_oils") {
            progress.holy_oil_mode = holyOilMode;

            if (holyOilMode === "set") {
                progress.selected_set_id = selectedSetId;
            }
        }

        const params: Record<string, unknown> = {
            batch_type: batchType,
            disposition: disposition,
            progress: progress,
        };

        if (disposition === "list") {
            params.listing_price = listingPrice !== "" ? listingPrice : 1;
        }

        if (batchType === "holy_oils") {
            params.selected_items = holyOilMode === "set" ? [] : selectedItems;
            params.selected_oils = selectedOils;
        }

        return params;
    }

    missingRequiredSelections(): boolean {
        const {
            batchType,
            craftEnchantSetPlan,
            craftMode,
            disposition,
            holyOilMode,
            outputDestination,
            outputSetId,
            selectedItems,
            selectedOils,
            selectedSetId,
            specificItemId,
            status,
        } = this.state;
        const resolvedCraftMode = this.getResolvedCraftMode(
            status,
            batchType,
            craftMode,
        );

        return (
            ((batchType === "craft" || batchType === "craft_and_enchant") &&
                resolvedCraftMode === "specific_item" &&
                specificItemId === null) ||
            (batchType === "craft_and_enchant" &&
                resolvedCraftMode === "craft_enchant_set" &&
                Object.keys(craftEnchantSetPlan).length === 0) ||
            (batchType === "holy_oils" &&
                holyOilMode === "selected" &&
                selectedItems.length === 0) ||
            (batchType === "holy_oils" && selectedOils.length === 0) ||
            (batchType === "holy_oils" &&
                holyOilMode === "set" &&
                selectedSetId === null) ||
            (this.isFiniteRetainedOutputMode(
                batchType,
                resolvedCraftMode,
                disposition,
            ) &&
                outputDestination === "inventory_set" &&
                outputSetId === null)
        );
    }

    startDisabledReasons(
        selectedCraftMode: CraftMode,
        selectedAlchemyMode: AlchemyMode,
    ): BatchCraftingStartBlocker[] {
        const {
            batchType,
            craftAmount,
            craftEnchantSetPlan,
            craftSetPlan,
            disposition,
            holyOilMode,
            isSaving,
            listingPrice,
            outputDestination,
            outputSetId,
            preview,
            previewLoading,
            selectedAlchemyItemId,
            selectedItems,
            selectedOils,
            selectedPrefixId,
            selectedSetId,
            selectedSuffixId,
            specificItemId,
            status,
        } = this.state;
        const reasons: BatchCraftingStartBlocker[] = [];
        const addReason = (code: string, message: string) =>
            reasons.push({ code, message, blocking: true });
        const alchemyLocked = status?.event_batch?.alchemy_locked ?? false;
        const alchemyMaxed = status?.event_batch?.alchemy_maxed ?? false;
        const trinketryMaxed = status?.event_batch?.trinketry_maxed ?? false;
        const isAmountMode =
            ((batchType === "craft" || batchType === "craft_and_enchant") &&
                selectedCraftMode === "specific_item") ||
            (batchType === "alchemy" && selectedAlchemyMode === "amount");

        if (isSaving) {
            addReason("start_request_pending", "Starting Batch Crafting...");
        }

        if (
            (batchType === "alchemy" || batchType === "holy_oils") &&
            alchemyLocked
        ) {
            addReason(
                "alchemy_locked",
                "Alchemy has not been unlocked for this character.",
            );
        }

        if (
            batchType === "alchemy" &&
            selectedAlchemyMode === "experience" &&
            alchemyMaxed
        ) {
            addReason(
                "alchemy_experience_maxed",
                "Alchemy Experience cannot start because the Alchemy skill is maxed.",
            );
        }

        if (batchType === "trinketry" && trinketryMaxed) {
            addReason(
                "trinketry_experience_maxed",
                "Trinketry Experience cannot start because the Trinketry skill is maxed.",
            );
        }

        if (
            (batchType === "craft" || batchType === "craft_and_enchant") &&
            selectedCraftMode === "specific_item" &&
            specificItemId === null
        ) {
            addReason(
                "required_item_missing",
                "Select the item required for this Batch Crafting mode.",
            );
        }

        if (
            batchType === "alchemy" &&
            selectedAlchemyMode === "amount" &&
            selectedAlchemyItemId === null
        ) {
            addReason(
                "alchemy_item_missing",
                "Select the Alchemy item to craft.",
            );
        }

        if (
            batchType === "craft" &&
            selectedCraftMode === "craft_set" &&
            Object.keys(craftSetPlan).length === 0
        ) {
            addReason(
                "craft_set_plan_missing",
                "Craft Set has no valid configured plan.",
            );
        }

        if (
            batchType === "craft_and_enchant" &&
            selectedCraftMode === "craft_enchant_set" &&
            Object.keys(craftEnchantSetPlan).length === 0
        ) {
            addReason(
                "craft_enchant_set_plan_missing",
                "Craft-and-Enchant Set has no valid configured plan.",
            );
        }

        if (
            batchType === "craft_and_enchant" &&
            selectedCraftMode === "craft_enchant_set" &&
            craftEnchantSetPlanItems.some((item) => {
                const entry = craftEnchantSetPlan[item.key];

                if (item.optional && (entry?.selectedItemId ?? null) === null) {
                    return false;
                }

                return (
                    (entry?.prefixAffixId === null ||
                        typeof entry?.prefixAffixId === "undefined") &&
                    (entry?.suffixAffixId === null ||
                        typeof entry?.suffixAffixId === "undefined")
                );
            })
        ) {
            addReason(
                "craft_enchant_set_affix_missing",
                "Every Craft-and-Enchant Set row must have a prefix or suffix selected.",
            );
        }

        if (
            batchType === "craft_and_enchant" &&
            selectedCraftMode === "specific_item" &&
            selectedPrefixId === null &&
            selectedSuffixId === null
        ) {
            addReason(
                "craft_enchant_amount_affix_missing",
                "Craft-and-Enchant Amount requires a prefix or suffix selection.",
            );
        }

        if (
            batchType === "holy_oils" &&
            holyOilMode === "selected" &&
            selectedItems.length === 0
        ) {
            addReason(
                "holy_oil_gear_missing",
                "Select at least one gear item for Holy Oils.",
            );
        }

        if (batchType === "holy_oils" && selectedOils.length === 0) {
            addReason("holy_oils_missing", "Select at least one Holy Oil.");
        }

        if (
            batchType === "holy_oils" &&
            holyOilMode === "set" &&
            selectedSetId === null
        ) {
            addReason(
                "holy_oil_set_missing",
                "Select the Inventory Set to receive Holy Oils.",
            );
        }

        if (
            this.isFiniteRetainedOutputMode(
                batchType,
                selectedCraftMode,
                disposition,
            ) &&
            outputDestination === "inventory_set" &&
            outputSetId === null
        ) {
            addReason(
                "output_inventory_set_missing",
                "Select the destination Inventory Set.",
            );
        }

        if (isAmountMode && craftAmount === "") {
            addReason("amount_blank", "Enter an amount to craft.");
        } else if (
            isAmountMode &&
            (typeof craftAmount !== "number" || !Number.isInteger(craftAmount))
        ) {
            addReason("amount_not_whole", "The amount must be a whole number.");
        } else if (isAmountMode && craftAmount < 1) {
            addReason("amount_below_one", "The amount must be at least one.");
        } else if (
            isAmountMode &&
            preview !== null &&
            craftAmount > preview.maximum_request_amount
        ) {
            addReason(
                "amount_above_maximum",
                `The amount may not exceed ${formatNumber(preview.maximum_request_amount)} for the selected destination.`,
            );
        }

        if (
            disposition === "list" &&
            (listingPrice === "" ||
                !Number.isInteger(listingPrice) ||
                listingPrice < 1)
        ) {
            addReason(
                "listing_price_invalid",
                "Enter a valid whole-number listing price of at least one Gold.",
            );
        }

        if (preview !== null && !previewLoading) {
            const effectiveAmountIsZero =
                ((batchType === "craft" || batchType === "craft_and_enchant") &&
                    selectedCraftMode === "specific_item" &&
                    preview.amount_preview?.effective_craftable_amount === 0) ||
                (batchType === "alchemy" &&
                    selectedAlchemyMode === "amount" &&
                    preview.alchemy_amount_preview
                        ?.effective_craftable_amount === 0) ||
                (batchType === "holy_oils" &&
                    holyOilMode === "selected" &&
                    preview.holy_oil_selected_preview
                        ?.max_applications_possible === 0) ||
                (batchType === "holy_oils" &&
                    holyOilMode === "set" &&
                    preview.holy_oil_set_preview?.max_applications_possible ===
                        0);

            if (effectiveAmountIsZero) {
                addReason(
                    "effective_amount_zero",
                    "The current selections, resources, and destination capacity cannot complete an item.",
                );
            }

            if (
                preview.cost_breakdown.can_afford_start === false ||
                preview.cost_breakdown.can_afford_full_plan === false
            ) {
                if (
                    !preview.start_blockers.some((blocker) => blocker.blocking)
                ) {
                    addReason(
                        "cost_breakdown_blocking",
                        preview.cost_breakdown.message ??
                            `You do not have enough ${preview.cost_breakdown.currency_label} to start this complete batch plan. Required: ${formatNumber(preview.cost_breakdown.required_to_start)}, Available: ${formatNumber(preview.cost_breakdown.available_currency_amount)}, Missing: ${formatNumber(preview.cost_breakdown.missing_currency_amount ?? 0)}.`,
                    );
                }
            }

            reasons.push(
                ...preview.start_blockers.filter((blocker) => blocker.blocking),
            );
        }

        return reasons.filter(
            (blocker, index, blockers) =>
                blockers.findIndex(
                    (candidate) => candidate.message === blocker.message,
                ) === index,
        );
    }

    fetchPreview() {
        const { batchType, craftMode } = this.state;
        const resolvedCraftMode = this.getResolvedCraftMode(
            this.state.status,
            batchType,
            craftMode,
        );
        const previewableModes =
            (batchType === "craft" || batchType === "craft_and_enchant") &&
            [
                "experience",
                "specific_item",
                "craft_set",
                "craft_enchant_set",
            ].includes(resolvedCraftMode)
                ? true
                : batchType === "alchemy" &&
                    ["experience", "amount"].includes(
                        this.getResolvedAlchemyMode(
                            this.state.status,
                            this.state.alchemyMode,
                        ),
                    )
                  ? true
                  : batchType === "trinketry"
                    ? true
                    : batchType === "holy_oils"
                      ? true
                      : false;

        if (!previewableModes) {
            if (this.state.preview !== null) {
                this.setState({ preview: null, previewError: null });
            }

            return;
        }

        if (this.missingRequiredSelections()) {
            this.setState({ preview: null, previewError: null });

            return;
        }

        const params = this.buildBatchParams();

        this.setState({ previewLoading: true, previewError: null });

        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/preview`)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (response: AxiosResponse) => {
                    const previewData = response.data as BatchCraftingPreview;
                    const planEntries =
                        previewData?.cost_breakdown?.plan_entries ?? [];

                    this.setState((prevState) => {
                        // Object references are only replaced when an entry actually
                        // changes. componentDidUpdate re-fetches the preview whenever
                        // these plan objects change identity, so returning a fresh
                        // `{...spread}` unconditionally here would refetch forever.
                        let craftEnchantSetPlan = prevState.craftEnchantSetPlan;
                        let craftSetPlan = prevState.craftSetPlan;

                        planEntries.forEach((planEntry) => {
                            const existingEnchantSetEntry =
                                craftEnchantSetPlan[planEntry.key];

                            if (planEntry.optional && existingEnchantSetEntry) {
                                const synchronizedEntry =
                                    synchronizeHandPlanEntry(
                                        existingEnchantSetEntry,
                                        planEntry.available_items,
                                        true,
                                    );

                                if (
                                    synchronizedEntry !==
                                    existingEnchantSetEntry
                                ) {
                                    if (
                                        craftEnchantSetPlan ===
                                        prevState.craftEnchantSetPlan
                                    ) {
                                        craftEnchantSetPlan = {
                                            ...craftEnchantSetPlan,
                                        };
                                    }

                                    craftEnchantSetPlan[planEntry.key] =
                                        synchronizedEntry;
                                }
                            }

                            if (
                                existingEnchantSetEntry !== undefined &&
                                !planEntry.optional &&
                                (existingEnchantSetEntry.selectedItemId ===
                                    null ||
                                    existingEnchantSetEntry.selectedItemId ===
                                        undefined) &&
                                planEntry.included &&
                                planEntry.selected_item_id !== null &&
                                planEntry.selected_item_id !== undefined
                            ) {
                                if (
                                    craftEnchantSetPlan ===
                                    prevState.craftEnchantSetPlan
                                ) {
                                    craftEnchantSetPlan = {
                                        ...craftEnchantSetPlan,
                                    };
                                }

                                craftEnchantSetPlan[planEntry.key] = {
                                    ...existingEnchantSetEntry,
                                    selectedItemId: planEntry.selected_item_id,
                                };
                            }

                            const existingCraftSetEntry =
                                craftSetPlan[planEntry.key];

                            if (planEntry.optional && existingCraftSetEntry) {
                                const synchronizedEntry =
                                    synchronizeHandPlanEntry(
                                        existingCraftSetEntry,
                                        planEntry.available_items,
                                        false,
                                    );

                                if (
                                    synchronizedEntry !== existingCraftSetEntry
                                ) {
                                    if (
                                        craftSetPlan === prevState.craftSetPlan
                                    ) {
                                        craftSetPlan = { ...craftSetPlan };
                                    }

                                    craftSetPlan[planEntry.key] =
                                        synchronizedEntry;
                                }
                            }

                            if (
                                existingCraftSetEntry !== undefined &&
                                !planEntry.optional &&
                                (existingCraftSetEntry.selectedItemId ===
                                    null ||
                                    existingCraftSetEntry.selectedItemId ===
                                        undefined) &&
                                planEntry.included &&
                                planEntry.selected_item_id !== null &&
                                planEntry.selected_item_id !== undefined
                            ) {
                                if (craftSetPlan === prevState.craftSetPlan) {
                                    craftSetPlan = { ...craftSetPlan };
                                }

                                craftSetPlan[planEntry.key] = {
                                    ...existingCraftSetEntry,
                                    selectedItemId: planEntry.selected_item_id,
                                };
                            }
                        });

                        // Prefix/suffix affixes are intentionally never
                        // auto-filled from cost_breakdown.default_prefix_affix_id
                        // / default_suffix_affix_id here. Only the craftable
                        // item preselection above is automatic; the player must
                        // explicitly choose bulk or per-row affixes.
                        return {
                            preview: previewData,
                            previewLoading: false,
                            craftEnchantSetPlan,
                            craftSetPlan,
                        };
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        previewLoading: false,
                        previewError:
                            "Could not load the cost preview. Try again.",
                    });
                },
            );
    }

    startBatch(
        selectedCraftMode?: CraftMode,
        selectedEnchantMode?: EnchantMode,
    ) {
        this.setState({
            isSaving: true,
            message: "",
        });

        const params = this.buildBatchParams(
            selectedCraftMode,
            selectedEnchantMode,
        );

        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/start`)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    this.setState({
                        isSaving: false,
                    });
                    window.dispatchEvent(
                        new CustomEvent("batch-crafting-started"),
                    );
                    updateTimers(this.props.character_id);
                    this.fetchStatus();
                },
                (error: AxiosError) => {
                    const errorData = error.response?.data as {
                        message?: string;
                        errors?: Record<string, string[]>;
                    };
                    const firstError =
                        errorData?.errors !== undefined
                            ? Object.values(errorData.errors)[0]?.[0]
                            : null;

                    this.setState({
                        isSaving: false,
                        message:
                            firstError ??
                            errorData?.message ??
                            "Could not start batch crafting.",
                    });
                },
            );
    }

    cancelBatch() {
        this.setState({
            isSaving: true,
            message: "",
        });
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/cancel`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    this.setState({
                        isSaving: false,
                    });
                    this.fetchStatus();
                },
                (error: AxiosError) => {
                    const response = error.response as
                        | AxiosResponse
                        | undefined;

                    this.setState({
                        isSaving: false,
                        message:
                            response?.data?.message ??
                            "Batch crafting could not be cancelled.",
                    });
                },
            );
    }

    dismissPanel() {
        this.setState({
            message: "",
        });
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => this.fetchStatus(),
                (error: AxiosError) => {
                    const response = error.response as
                        | AxiosResponse
                        | undefined;

                    this.setState({
                        message:
                            response?.data?.message ??
                            "Batch crafting could not be dismissed.",
                    });
                },
            );
    }

    acknowledgeInfo() {
        new Ajax()
            .setRoute(
                `batch-crafting/${this.props.character_id}/info/acknowledge`,
            )
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => this.fetchStatus(),
                (_error: AxiosError) => {},
            );
    }

    renderHandSelectionControls(
        label: string,
        entry: {
            selectedItemId: number | null;
            handSelectionType?: HandSelectionType;
            selectedWeaponType?: string | null;
        },
        availableItems: CraftEnchantSetAvailableItem[],
        loading: boolean,
        disabled: boolean,
        duelWieldUnavailable: boolean,
        setHandSelectionType: (value: HandSelectionType) => void,
        setSelectedWeaponType: (value: string | null) => void,
        setSelectedItemId: (value: number | null) => void,
    ) {
        const handSelectionType = entry.handSelectionType ?? null;
        const selectedWeaponType = entry.selectedWeaponType ?? null;
        const weaponTypeOptions =
            handSelectionType === "single_handed" ||
            handSelectionType === "two_handed"
                ? getHandWeaponTypes(availableItems, handSelectionType)
                : [];
        const exactItemOptions = getHandExactItems(
            availableItems,
            handSelectionType,
            selectedWeaponType,
        ).map((item) => ({ value: item.id, label: item.name }));
        const options = duelWieldUnavailable
            ? handSelectionOptions.filter(
                  (option) => option.value !== "two_handed",
              )
            : handSelectionOptions;

        return (
            <div className="grid gap-2 sm:grid-cols-2">
                <label className="grid gap-1 text-sm font-semibold">
                    {label} option
                    <Select
                        isClearable
                        isSearchable={true}
                        isDisabled={disabled}
                        onChange={(option) =>
                            setHandSelectionType(option?.value ?? null)
                        }
                        options={options}
                        styles={batchCraftingSelectStyles}
                        menuPortalTarget={document.body}
                        value={
                            handSelectionOptions.find(
                                (option) => option.value === handSelectionType,
                            ) ?? null
                        }
                    />
                </label>
                {handSelectionType === "single_handed" ||
                handSelectionType === "two_handed" ? (
                    <label className="grid gap-1 text-sm font-semibold">
                        {label} weapon type
                        <Select
                            isClearable
                            isSearchable={true}
                            isDisabled={disabled}
                            onChange={(option) =>
                                setSelectedWeaponType(option?.value ?? null)
                            }
                            options={weaponTypeOptions}
                            styles={batchCraftingSelectStyles}
                            menuPortalTarget={document.body}
                            value={
                                weaponTypeOptions.find(
                                    (option) =>
                                        option.value === selectedWeaponType,
                                ) ?? null
                            }
                        />
                    </label>
                ) : null}
                {handSelectionType !== null &&
                ((handSelectionType !== "single_handed" &&
                    handSelectionType !== "two_handed") ||
                    selectedWeaponType !== null) ? (
                    <label className="grid gap-1 text-sm font-semibold">
                        {label} item
                        <Select
                            isClearable
                            isSearchable={true}
                            isDisabled={disabled}
                            isLoading={loading}
                            placeholder="No item selected"
                            noOptionsMessage={() =>
                                loading ? "Loading..." : "No options"
                            }
                            onChange={(option) =>
                                setSelectedItemId(option?.value ?? null)
                            }
                            options={exactItemOptions}
                            styles={batchCraftingSelectStyles}
                            menuPortalTarget={document.body}
                            value={
                                exactItemOptions.find(
                                    (option) =>
                                        option.value === entry.selectedItemId,
                                ) ?? null
                            }
                        />
                    </label>
                ) : null}
                {disabled ? (
                    <p className="text-xs font-semibold text-yellow-sea-800 dark:text-yellow-sea-300 sm:col-span-2">
                        The selected Duel Wield item occupies both hands.
                    </p>
                ) : null}
            </div>
        );
    }

    renderCraftEnchantSetPlanner() {
        const {
            craftEnchantSetBulkPrefixId,
            craftEnchantSetBulkSuffixId,
            craftEnchantSetPlan,
            enchantments,
            enchantmentsLoading,
            preview,
            previewLoading,
        } = this.state;

        if (preview === null && previewLoading) {
            return (
                <div className="grid gap-3">
                    <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                        Loading the configurable 10–12 item Craft and Enchant
                        Set planner...
                    </p>
                    <LoadingProgressBar />
                </div>
            );
        }

        const setPrefixAffixId = (key: string, prefixAffixId: number | null) =>
            this.setState((prevState) => ({
                craftEnchantSetPlan: {
                    ...prevState.craftEnchantSetPlan,
                    [key]: {
                        ...prevState.craftEnchantSetPlan[key],
                        prefixAffixId,
                    },
                },
            }));
        const setSuffixAffixId = (key: string, suffixAffixId: number | null) =>
            this.setState((prevState) => ({
                craftEnchantSetPlan: {
                    ...prevState.craftEnchantSetPlan,
                    [key]: {
                        ...prevState.craftEnchantSetPlan[key],
                        suffixAffixId,
                    },
                },
            }));
        const setSelectedItemId = (
            key: string,
            selectedItemId: number | null,
        ) =>
            this.setState((prevState) => {
                const nextPlan = {
                    ...prevState.craftEnchantSetPlan,
                    [key]: {
                        ...prevState.craftEnchantSetPlan[key],
                        selectedItemId,
                        ...(selectedItemId === null
                            ? { prefixAffixId: null, suffixAffixId: null }
                            : {
                                  prefixAffixId:
                                      prevState.craftEnchantSetAppliedPrefixId,
                                  suffixAffixId:
                                      prevState.craftEnchantSetAppliedSuffixId,
                              }),
                    },
                };
                const selected = (
                    prevState.preview?.cost_breakdown?.plan_entries ?? []
                )
                    .find((entry) => entry.key === key)
                    ?.available_items.find(
                        (candidate) => candidate.id === selectedItemId,
                    );

                if (selected?.handedness === "two_handed") {
                    const otherKey =
                        key === "left_hand" ? "right_hand" : "left_hand";
                    nextPlan[otherKey] = {
                        ...nextPlan[otherKey],
                        selectedItemId: null,
                        handSelectionType: null,
                        selectedWeaponType: null,
                        prefixAffixId: null,
                        suffixAffixId: null,
                    };
                }

                return { craftEnchantSetPlan: nextPlan };
            });
        const setBulkPrefixId = (bulkPrefixAffixId: number | null) =>
            this.setState({ craftEnchantSetBulkPrefixId: bulkPrefixAffixId });
        const setBulkSuffixId = (bulkSuffixAffixId: number | null) =>
            this.setState({ craftEnchantSetBulkSuffixId: bulkSuffixAffixId });
        const setHandSelectionType = (
            key: string,
            handSelectionType: HandSelectionType,
        ) =>
            this.setState((prevState) => {
                const availableItems =
                    (
                        prevState.preview?.cost_breakdown?.plan_entries ?? []
                    ).find((entry) => entry.key === key)?.available_items ?? [];
                const bestShield =
                    handSelectionType === "shield"
                        ? (availableItems
                              .filter((item) => item.handedness === "shield")
                              .sort(
                                  (a, b) =>
                                      b.skill_level_required -
                                          a.skill_level_required ||
                                      b.cost - a.cost ||
                                      a.id - b.id,
                              )[0] ?? null)
                        : null;
                const nextPlan = {
                    ...prevState.craftEnchantSetPlan,
                    [key]: {
                        ...prevState.craftEnchantSetPlan[key],
                        selectedItemId: bestShield?.id ?? null,
                        prefixAffixId:
                            bestShield === null
                                ? null
                                : prevState.craftEnchantSetAppliedPrefixId,
                        suffixAffixId:
                            bestShield === null
                                ? null
                                : prevState.craftEnchantSetAppliedSuffixId,
                        handSelectionType,
                        selectedWeaponType: null,
                    },
                };

                if (handSelectionType === "two_handed") {
                    const otherKey =
                        key === "left_hand" ? "right_hand" : "left_hand";
                    nextPlan[otherKey] = {
                        ...nextPlan[otherKey],
                        selectedItemId: null,
                        prefixAffixId: null,
                        suffixAffixId: null,
                        handSelectionType: null,
                        selectedWeaponType: null,
                    };
                }

                return { craftEnchantSetPlan: nextPlan };
            });
        const setSelectedWeaponType = (
            key: string,
            selectedWeaponType: string | null,
        ) =>
            this.setState((prevState) => {
                const availableItems =
                    (
                        prevState.preview?.cost_breakdown?.plan_entries ?? []
                    ).find((entry) => entry.key === key)?.available_items ?? [];
                const handSelectionType =
                    prevState.craftEnchantSetPlan[key]?.handSelectionType ??
                    null;
                const bestItem =
                    selectedWeaponType === null
                        ? null
                        : (availableItems
                              .filter(
                                  (item) =>
                                      item.handedness === handSelectionType &&
                                      item.type === selectedWeaponType,
                              )
                              .sort(
                                  (a, b) =>
                                      b.skill_level_required -
                                          a.skill_level_required ||
                                      b.cost - a.cost ||
                                      a.id - b.id,
                              )[0] ?? null);

                return {
                    craftEnchantSetPlan: {
                        ...prevState.craftEnchantSetPlan,
                        [key]: {
                            ...prevState.craftEnchantSetPlan[key],
                            selectedItemId: bestItem?.id ?? null,
                            prefixAffixId:
                                bestItem === null
                                    ? null
                                    : prevState.craftEnchantSetAppliedPrefixId,
                            suffixAffixId:
                                bestItem === null
                                    ? null
                                    : prevState.craftEnchantSetAppliedSuffixId,
                            selectedWeaponType,
                        },
                    },
                };
            });
        const applyBulkEnchantsToAll = () => {
            if (
                craftEnchantSetBulkPrefixId === null ||
                craftEnchantSetBulkSuffixId === null
            ) {
                return;
            }

            this.setState((prevState) => ({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        item.optional &&
                        (prevState.craftEnchantSetPlan[item.key]
                            ?.selectedItemId ?? null) === null
                            ? {
                                  ...prevState.craftEnchantSetPlan[item.key],
                                  selectedItemId: null,
                                  prefixAffixId: null,
                                  suffixAffixId: null,
                              }
                            : {
                                  ...prevState.craftEnchantSetPlan[item.key],
                                  prefixAffixId:
                                      prevState.craftEnchantSetBulkPrefixId,
                                  suffixAffixId:
                                      prevState.craftEnchantSetBulkSuffixId,
                              },
                    ]),
                ),
                craftEnchantSetAppliedPrefixId:
                    prevState.craftEnchantSetBulkPrefixId,
                craftEnchantSetAppliedSuffixId:
                    prevState.craftEnchantSetBulkSuffixId,
            }));
        };
        const clearAllEnchants = () =>
            this.setState((prevState) => ({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        {
                            ...prevState.craftEnchantSetPlan[item.key],
                            prefixAffixId: null,
                            suffixAffixId: null,
                        },
                    ]),
                ),
                craftEnchantSetBulkPrefixId: null,
                craftEnchantSetBulkSuffixId: null,
                craftEnchantSetAppliedPrefixId: null,
                craftEnchantSetAppliedSuffixId: null,
            }));
        const openAffixDetailsModal = (affix: EnchantmentOption) =>
            this.setState({ affixDetailsModalAffix: affix });
        const openItemDetailsModal = (item: any) =>
            this.setState({ craftEnchantSetItemDetailsModalItem: item });
        const planEntriesByKey = new Map(
            (this.state.preview?.cost_breakdown?.plan_entries ?? []).map(
                (entry) => [entry.key, entry],
            ),
        );
        const isHandDisabled = (key: string) => {
            const otherKey = key === "left_hand" ? "right_hand" : "left_hand";
            return (
                craftEnchantSetPlan[otherKey]?.handSelectionType ===
                "two_handed"
            );
        };
        const isDuelWieldUnavailable = (key: string) => {
            const otherKey = key === "left_hand" ? "right_hand" : "left_hand";
            const otherSelection =
                craftEnchantSetPlan[otherKey]?.handSelectionType ?? null;

            return (
                otherSelection === "single_handed" ||
                otherSelection === "shield"
            );
        };

        const prefixOptions = enchantments
            .filter((enchantment) => enchantment.type === "prefix")
            .map(toAffixOption);
        const suffixOptions = enchantments
            .filter((enchantment) => enchantment.type === "suffix")
            .map(toAffixOption);

        const categories: {
            category: CraftEnchantSetPlanItem["category"];
            label: string;
        }[] = [
            { category: "hand", label: "Hands" },
            { category: "armour", label: "Armour" },
            { category: "ring", label: "Rings" },
            { category: "spell", label: "Spells" },
        ];

        return (
            <div className="grid gap-3">
                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    Craft and Enchant Set creates one equippable set with six
                    armour pieces, two Rings, one Damage Spell, and one Healing
                    Spell. Left Hand and Right Hand are optional; Shields are
                    hand items and two-handed weapons occupy both hands. Your
                    selected affixes are applied to every included item.
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                    <label className="grid gap-1 text-sm font-semibold">
                        Bulk prefix enchant
                        <Select
                            isClearable
                            isSearchable={true}
                            isLoading={enchantmentsLoading}
                            noOptionsMessage={() =>
                                enchantmentsLoading
                                    ? "Loading..."
                                    : "No options"
                            }
                            onChange={(opt) =>
                                setBulkPrefixId(opt?.value ?? null)
                            }
                            options={prefixOptions}
                            formatOptionLabel={formatAffixOptionLabel}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={batchCraftingSelectStyles}
                            menuPortalTarget={document.body}
                            value={
                                prefixOptions.find(
                                    (option) =>
                                        option.value ===
                                        craftEnchantSetBulkPrefixId,
                                ) ?? null
                            }
                        />
                    </label>
                    <label className="grid gap-1 text-sm font-semibold">
                        Bulk suffix enchant
                        <Select
                            isClearable
                            isSearchable={true}
                            isLoading={enchantmentsLoading}
                            noOptionsMessage={() =>
                                enchantmentsLoading
                                    ? "Loading..."
                                    : "No options"
                            }
                            onChange={(opt) =>
                                setBulkSuffixId(opt?.value ?? null)
                            }
                            options={suffixOptions}
                            formatOptionLabel={formatAffixOptionLabel}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={batchCraftingSelectStyles}
                            menuPortalTarget={document.body}
                            value={
                                suffixOptions.find(
                                    (option) =>
                                        option.value ===
                                        craftEnchantSetBulkSuffixId,
                                ) ?? null
                            }
                        />
                    </label>
                </div>
                <PrimaryButton
                    button_label={"Apply Selected Enchants To All"}
                    on_click={applyBulkEnchantsToAll}
                    disabled={
                        craftEnchantSetBulkPrefixId === null ||
                        craftEnchantSetBulkSuffixId === null
                    }
                    additional_css={"w-full"}
                />
                <DangerButton
                    button_label={"Clear All Enchants"}
                    on_click={clearAllEnchants}
                    additional_css={"w-full"}
                />
                {categories.map(({ category, label }) => {
                    const itemsInCategory = craftEnchantSetPlanItems.filter(
                        (item) => item.category === category,
                    );
                    const configuredCount = itemsInCategory.filter((item) => {
                        const entry = craftEnchantSetPlan[item.key];

                        if (category === "hand") {
                            return (entry?.selectedItemId ?? null) !== null;
                        }

                        const hasPrefix =
                            entry?.prefixAffixId !== null &&
                            entry?.prefixAffixId !== undefined;
                        const hasSuffix =
                            entry?.suffixAffixId !== null &&
                            entry?.suffixAffixId !== undefined;

                        return hasPrefix || hasSuffix;
                    }).length;
                    const isCategoryComplete =
                        category === "hand" ||
                        configuredCount === itemsInCategory.length;

                    return (
                        <details
                            key={category}
                            className={
                                isCategoryComplete
                                    ? "group rounded border border-green-300 bg-green-50/40 dark:border-green-700 dark:bg-green-950/20"
                                    : "group rounded border border-orange-300 bg-orange-50/40 dark:border-orange-700 dark:bg-orange-950/20"
                            }
                        >
                            <summary className="flex cursor-pointer list-none flex-nowrap items-center gap-2 rounded p-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 [&::-webkit-details-marker]:hidden">
                                <span
                                    aria-hidden="true"
                                    className="shrink-0 transition-transform group-open:rotate-90"
                                >
                                    ▸
                                </span>
                                <span>{label}</span>
                                <span
                                    className={
                                        isCategoryComplete
                                            ? "ml-auto shrink-0 rounded border border-green-300 bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-100"
                                            : "ml-auto shrink-0 rounded border border-orange-300 bg-orange-100 px-2 py-0.5 text-xs text-orange-800 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100"
                                    }
                                >
                                    {category === "hand"
                                        ? `${configuredCount} of 2 optional hands selected`
                                        : `${configuredCount}/${itemsInCategory.length} configured`}
                                </span>
                            </summary>
                            <div className="grid gap-2 p-2">
                                {itemsInCategory.map((item) => {
                                    const entry = craftEnchantSetPlan[
                                        item.key
                                    ] ?? {
                                        prefixAffixId: null,
                                        suffixAffixId: null,
                                        selectedItemId: null,
                                    };
                                    const planPreviewEntry =
                                        planEntriesByKey.get(item.key) ?? null;
                                    const prefixAffix =
                                        enchantments.find(
                                            (enchantment) =>
                                                enchantment.type === "prefix" &&
                                                enchantment.id ===
                                                    entry.prefixAffixId,
                                        ) ?? null;
                                    const suffixAffix =
                                        enchantments.find(
                                            (enchantment) =>
                                                enchantment.type === "suffix" &&
                                                enchantment.id ===
                                                    entry.suffixAffixId,
                                        ) ?? null;
                                    const prefixLabel =
                                        prefixAffix?.name ??
                                        (entry.prefixAffixId !== null &&
                                        entry.prefixAffixId ===
                                            this.state.preview?.cost_breakdown
                                                ?.default_prefix_affix_id
                                            ? this.state.preview?.cost_breakdown
                                                  ?.default_prefix_affix_name
                                            : null) ??
                                        "None selected";
                                    const suffixLabel =
                                        suffixAffix?.name ??
                                        (entry.suffixAffixId !== null &&
                                        entry.suffixAffixId ===
                                            this.state.preview?.cost_breakdown
                                                ?.default_suffix_affix_id
                                            ? this.state.preview?.cost_breakdown
                                                  ?.default_suffix_affix_name
                                            : null) ??
                                        "None selected";
                                    const isItemConfigured =
                                        (entry.prefixAffixId !== null &&
                                            entry.prefixAffixId !==
                                                undefined) ||
                                        (entry.suffixAffixId !== null &&
                                            entry.suffixAffixId !== undefined);
                                    const availableItems =
                                        planPreviewEntry?.available_items ?? [];
                                    const availableItemOptions = (
                                        item.category === "hand"
                                            ? availableItems
                                            : availableItems
                                                  .slice()
                                                  .sort(
                                                      (a, b) => a.cost - b.cost,
                                                  )
                                    ).map((availableItem) => ({
                                        value: availableItem.id,
                                        label:
                                            item.category === "hand"
                                                ? `${availableItem.name} — ${availableItem.type} — ${availableItem.handedness === "two_handed" ? "Two-handed" : availableItem.handedness === "shield" ? "Shield" : "Single-handed"}`
                                                : availableItem.name,
                                    }));
                                    const selectedItemIdValue =
                                        entry.selectedItemId ??
                                        planPreviewEntry?.selected_item_id ??
                                        null;
                                    const exactItemName =
                                        planPreviewEntry?.selected_item_name ??
                                        item.label;
                                    const itemCraftingCost =
                                        planPreviewEntry?.selected_item_cost ??
                                        0;
                                    const prefixCost =
                                        planPreviewEntry?.prefix_cost ?? 0;
                                    const suffixCost =
                                        planPreviewEntry?.suffix_cost ?? 0;
                                    const affixButtonClass =
                                        "font-semibold text-regent-st-blue-700 hover:underline dark:text-regent-st-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded";
                                    const selectedItemDetails =
                                        planPreviewEntry?.selected_item_details ??
                                        null;
                                    const rowBlockers = (
                                        this.state.preview?.start_blockers ?? []
                                    ).filter(
                                        (blocker) =>
                                            blocker.plan_key === item.key,
                                    );

                                    return (
                                        <div
                                            key={item.key}
                                            className={
                                                isItemConfigured
                                                    ? "rounded border border-green-200 bg-white dark:border-green-800 dark:bg-gray-900"
                                                    : "rounded border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                                            }
                                        >
                                            <div className="p-2 text-sm">
                                                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Target
                                                    </dt>
                                                    <dd>{item.label}</dd>
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Item Name
                                                    </dt>
                                                    <dd>
                                                        {selectedItemDetails ? (
                                                            <button
                                                                type="button"
                                                                className={
                                                                    affixButtonClass
                                                                }
                                                                onClick={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    event.stopPropagation();
                                                                    openItemDetailsModal(
                                                                        selectedItemDetails,
                                                                    );
                                                                }}
                                                            >
                                                                {exactItemName}
                                                            </button>
                                                        ) : (
                                                            <span>
                                                                {exactItemName}
                                                            </span>
                                                        )}
                                                    </dd>
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Item Crafting Cost
                                                    </dt>
                                                    <dd>
                                                        {formatNumber(
                                                            itemCraftingCost,
                                                        )}
                                                    </dd>
                                                    <dt className="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                                        Prefix Selected
                                                    </dt>
                                                    <dd>
                                                        {prefixAffix ? (
                                                            <button
                                                                type="button"
                                                                className={
                                                                    affixButtonClass
                                                                }
                                                                onClick={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    event.stopPropagation();
                                                                    openAffixDetailsModal(
                                                                        prefixAffix,
                                                                    );
                                                                }}
                                                            >
                                                                {prefixLabel}
                                                            </button>
                                                        ) : (
                                                            <span>
                                                                {prefixLabel}
                                                            </span>
                                                        )}
                                                    </dd>
                                                    <dt className="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                                        Prefix Cost
                                                    </dt>
                                                    <dd>
                                                        {formatNumber(
                                                            prefixCost,
                                                        )}
                                                    </dd>
                                                    <dt className="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                                        Suffix Selected
                                                    </dt>
                                                    <dd>
                                                        {suffixAffix ? (
                                                            <button
                                                                type="button"
                                                                className={
                                                                    affixButtonClass
                                                                }
                                                                onClick={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    event.stopPropagation();
                                                                    openAffixDetailsModal(
                                                                        suffixAffix,
                                                                    );
                                                                }}
                                                            >
                                                                {suffixLabel}
                                                            </button>
                                                        ) : (
                                                            <span>
                                                                {suffixLabel}
                                                            </span>
                                                        )}
                                                    </dd>
                                                    <dt className="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                                        Suffix Cost
                                                    </dt>
                                                    <dd>
                                                        {formatNumber(
                                                            suffixCost,
                                                        )}
                                                    </dd>
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Total Enchanting Cost
                                                    </dt>
                                                    <dd>
                                                        {formatNumber(
                                                            prefixCost +
                                                                suffixCost,
                                                        )}
                                                    </dd>
                                                </dl>
                                            </div>
                                            <div className="grid gap-2 p-2 sm:grid-cols-2">
                                                {rowBlockers.length > 0 ? (
                                                    <div className="sm:col-span-2">
                                                        <WarningAlert additional_css="my-2">
                                                            <ul className="grid gap-3">
                                                                {rowBlockers.map(
                                                                    (
                                                                        blocker,
                                                                    ) => (
                                                                        <li
                                                                            key={this.startBlockerKey(
                                                                                blocker,
                                                                            )}
                                                                        >
                                                                            <p>
                                                                                {
                                                                                    blocker.message
                                                                                }
                                                                            </p>
                                                                            {blocker.links &&
                                                                            blocker
                                                                                .links
                                                                                .length >
                                                                                0 ? (
                                                                                <ul className="list-disc space-y-1 pl-5">
                                                                                    {blocker.links.map(
                                                                                        (
                                                                                            link,
                                                                                        ) => (
                                                                                            <li
                                                                                                key={
                                                                                                    link.url
                                                                                                }
                                                                                            >
                                                                                                <a
                                                                                                    href={
                                                                                                        link.url
                                                                                                    }
                                                                                                    target="_blank"
                                                                                                    rel="noopener noreferrer"
                                                                                                    className="underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                                                                >
                                                                                                    {
                                                                                                        link.label
                                                                                                    }
                                                                                                </a>
                                                                                            </li>
                                                                                        ),
                                                                                    )}
                                                                                </ul>
                                                                            ) : null}
                                                                        </li>
                                                                    ),
                                                                )}
                                                            </ul>
                                                        </WarningAlert>
                                                    </div>
                                                ) : null}
                                                {item.category === "hand" ? (
                                                    <div className="sm:col-span-2">
                                                        {this.renderHandSelectionControls(
                                                            item.label,
                                                            entry,
                                                            availableItems,
                                                            previewLoading,
                                                            isHandDisabled(
                                                                item.key,
                                                            ),
                                                            isDuelWieldUnavailable(
                                                                item.key,
                                                            ),
                                                            (value) =>
                                                                setHandSelectionType(
                                                                    item.key,
                                                                    value,
                                                                ),
                                                            (value) =>
                                                                setSelectedWeaponType(
                                                                    item.key,
                                                                    value,
                                                                ),
                                                            (value) =>
                                                                setSelectedItemId(
                                                                    item.key,
                                                                    value,
                                                                ),
                                                        )}
                                                    </div>
                                                ) : (
                                                    <label className="grid gap-1 text-sm font-semibold sm:col-span-2">
                                                        Item to craft
                                                        <Select
                                                            isClearable={false}
                                                            isSearchable={true}
                                                            placeholder="No item selected"
                                                            isLoading={
                                                                previewLoading
                                                            }
                                                            noOptionsMessage={() =>
                                                                previewLoading
                                                                    ? "Loading..."
                                                                    : "No options"
                                                            }
                                                            onChange={(opt) =>
                                                                setSelectedItemId(
                                                                    item.key,
                                                                    opt?.value ??
                                                                        null,
                                                                )
                                                            }
                                                            options={
                                                                availableItemOptions
                                                            }
                                                            menuPosition={
                                                                "absolute"
                                                            }
                                                            menuPlacement={
                                                                "bottom"
                                                            }
                                                            styles={
                                                                batchCraftingSelectStyles
                                                            }
                                                            menuPortalTarget={
                                                                document.body
                                                            }
                                                            value={
                                                                availableItemOptions.find(
                                                                    (option) =>
                                                                        option.value ===
                                                                        selectedItemIdValue,
                                                                ) ?? null
                                                            }
                                                        />
                                                    </label>
                                                )}
                                                {!item.optional ||
                                                selectedItemIdValue !== null ? (
                                                    <>
                                                        <label className="grid gap-1 text-sm font-semibold">
                                                            Prefix enchant
                                                            <Select
                                                                isClearable
                                                                isSearchable={
                                                                    true
                                                                }
                                                                isDisabled={
                                                                    item.optional &&
                                                                    selectedItemIdValue ===
                                                                        null
                                                                }
                                                                isLoading={
                                                                    enchantmentsLoading
                                                                }
                                                                noOptionsMessage={() =>
                                                                    enchantmentsLoading
                                                                        ? "Loading..."
                                                                        : "No options"
                                                                }
                                                                onChange={(
                                                                    opt,
                                                                ) =>
                                                                    setPrefixAffixId(
                                                                        item.key,
                                                                        opt?.value ??
                                                                            null,
                                                                    )
                                                                }
                                                                options={
                                                                    prefixOptions
                                                                }
                                                                formatOptionLabel={
                                                                    formatAffixOptionLabel
                                                                }
                                                                menuPosition={
                                                                    "absolute"
                                                                }
                                                                menuPlacement={
                                                                    "bottom"
                                                                }
                                                                styles={
                                                                    batchCraftingSelectStyles
                                                                }
                                                                menuPortalTarget={
                                                                    document.body
                                                                }
                                                                value={
                                                                    prefixOptions.find(
                                                                        (
                                                                            option,
                                                                        ) =>
                                                                            option.value ===
                                                                            entry.prefixAffixId,
                                                                    ) ?? null
                                                                }
                                                            />
                                                        </label>
                                                        <label className="grid gap-1 text-sm font-semibold">
                                                            Suffix enchant
                                                            <Select
                                                                isClearable
                                                                isSearchable={
                                                                    true
                                                                }
                                                                isDisabled={
                                                                    item.optional &&
                                                                    selectedItemIdValue ===
                                                                        null
                                                                }
                                                                isLoading={
                                                                    enchantmentsLoading
                                                                }
                                                                noOptionsMessage={() =>
                                                                    enchantmentsLoading
                                                                        ? "Loading..."
                                                                        : "No options"
                                                                }
                                                                onChange={(
                                                                    opt,
                                                                ) =>
                                                                    setSuffixAffixId(
                                                                        item.key,
                                                                        opt?.value ??
                                                                            null,
                                                                    )
                                                                }
                                                                options={
                                                                    suffixOptions
                                                                }
                                                                formatOptionLabel={
                                                                    formatAffixOptionLabel
                                                                }
                                                                menuPosition={
                                                                    "absolute"
                                                                }
                                                                menuPlacement={
                                                                    "bottom"
                                                                }
                                                                styles={
                                                                    batchCraftingSelectStyles
                                                                }
                                                                menuPortalTarget={
                                                                    document.body
                                                                }
                                                                value={
                                                                    suffixOptions.find(
                                                                        (
                                                                            option,
                                                                        ) =>
                                                                            option.value ===
                                                                            entry.suffixAffixId,
                                                                    ) ?? null
                                                                }
                                                            />
                                                        </label>
                                                    </>
                                                ) : null}
                                                {item.optional &&
                                                selectedItemIdValue === null ? (
                                                    <p className="text-xs text-gray-600 dark:text-gray-300 sm:col-span-2">
                                                        Optional hand omitted.
                                                        Select a hand item to
                                                        configure its enchants.
                                                    </p>
                                                ) : null}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </details>
                    );
                })}
                {this.state.affixDetailsModalAffix ? (
                    <ItemAffixDetails
                        is_open={true}
                        affix={this.state.affixDetailsModalAffix}
                        manage_modal={() =>
                            this.setState({ affixDetailsModalAffix: null })
                        }
                    />
                ) : null}
            </div>
        );
    }

    renderCraftSetPlanner() {
        const { craftSetPlan, preview, previewLoading } = this.state;

        if (preview === null && previewLoading) {
            return (
                <div className="grid gap-3">
                    <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                        Loading the configurable 10–12 item Craft Set planner...
                    </p>
                    <LoadingProgressBar />
                </div>
            );
        }

        const setSelectedItemId = (
            key: string,
            selectedItemId: number | null,
        ) =>
            this.setState((prevState) => {
                const nextPlan = {
                    ...prevState.craftSetPlan,
                    [key]: {
                        ...prevState.craftSetPlan[key],
                        selectedItemId,
                    },
                };
                const selected = (
                    prevState.preview?.cost_breakdown?.plan_entries ?? []
                )
                    .find((entry) => entry.key === key)
                    ?.available_items.find(
                        (candidate) => candidate.id === selectedItemId,
                    );

                if (selected?.handedness === "two_handed") {
                    const otherKey =
                        key === "left_hand" ? "right_hand" : "left_hand";
                    nextPlan[otherKey] = { selectedItemId: null };
                }

                return { craftSetPlan: nextPlan };
            });
        const openItemDetailsModal = (item: any) =>
            this.setState({ craftEnchantSetItemDetailsModalItem: item });
        const setHandSelectionType = (
            key: string,
            handSelectionType: HandSelectionType,
        ) =>
            this.setState((prevState) => {
                const availableItems =
                    (
                        prevState.preview?.cost_breakdown?.plan_entries ?? []
                    ).find((entry) => entry.key === key)?.available_items ?? [];
                const bestShield =
                    handSelectionType === "shield"
                        ? (availableItems
                              .filter((item) => item.handedness === "shield")
                              .sort(
                                  (a, b) =>
                                      b.skill_level_required -
                                          a.skill_level_required ||
                                      b.cost - a.cost ||
                                      a.id - b.id,
                              )[0] ?? null)
                        : null;
                const nextPlan = {
                    ...prevState.craftSetPlan,
                    [key]: {
                        ...prevState.craftSetPlan[key],
                        selectedItemId: bestShield?.id ?? null,
                        handSelectionType,
                        selectedWeaponType: null,
                    },
                };

                if (handSelectionType === "two_handed") {
                    const otherKey =
                        key === "left_hand" ? "right_hand" : "left_hand";
                    nextPlan[otherKey] = {
                        ...nextPlan[otherKey],
                        selectedItemId: null,
                        handSelectionType: null,
                        selectedWeaponType: null,
                    };
                }

                return { craftSetPlan: nextPlan };
            });
        const setSelectedWeaponType = (
            key: string,
            selectedWeaponType: string | null,
        ) =>
            this.setState((prevState) => {
                const availableItems =
                    (
                        prevState.preview?.cost_breakdown?.plan_entries ?? []
                    ).find((entry) => entry.key === key)?.available_items ?? [];
                const handSelectionType =
                    prevState.craftSetPlan[key]?.handSelectionType ?? null;
                const bestItem =
                    selectedWeaponType === null
                        ? null
                        : (availableItems
                              .filter(
                                  (item) =>
                                      item.handedness === handSelectionType &&
                                      item.type === selectedWeaponType,
                              )
                              .sort(
                                  (a, b) =>
                                      b.skill_level_required -
                                          a.skill_level_required ||
                                      b.cost - a.cost ||
                                      a.id - b.id,
                              )[0] ?? null);

                return {
                    craftSetPlan: {
                        ...prevState.craftSetPlan,
                        [key]: {
                            ...prevState.craftSetPlan[key],
                            selectedItemId: bestItem?.id ?? null,
                            selectedWeaponType,
                        },
                    },
                };
            });

        const planEntriesByKey = new Map(
            (this.state.preview?.cost_breakdown?.plan_entries ?? []).map(
                (entry) => [entry.key, entry],
            ),
        );
        const isHandDisabled = (key: string) => {
            const otherKey = key === "left_hand" ? "right_hand" : "left_hand";
            return craftSetPlan[otherKey]?.handSelectionType === "two_handed";
        };
        const isDuelWieldUnavailable = (key: string) => {
            const otherKey = key === "left_hand" ? "right_hand" : "left_hand";
            const otherSelection =
                craftSetPlan[otherKey]?.handSelectionType ?? null;

            return (
                otherSelection === "single_handed" ||
                otherSelection === "shield"
            );
        };

        const categories: {
            category: CraftEnchantSetPlanItem["category"];
            label: string;
        }[] = [
            { category: "hand", label: "Hands" },
            { category: "armour", label: "Armour" },
            { category: "ring", label: "Rings" },
            { category: "spell", label: "Spells" },
        ];

        return (
            <div className="grid gap-3">
                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    Craft Set creates one equippable set with six armour pieces,
                    two Rings, one Damage Spell, and one Healing Spell. Required
                    rows default to the highest craftable item. Left Hand and
                    Right Hand are optional; Shields are hand items and
                    two-handed weapons occupy both hands. The final set contains
                    10–12 items depending on your hand choices.
                </p>
                {categories.map(({ category, label }) => {
                    const itemsInCategory = craftEnchantSetPlanItems.filter(
                        (item) => item.category === category,
                    );
                    const configuredCount = itemsInCategory.filter((item) => {
                        const entry = craftSetPlan[item.key];
                        const planPreviewEntry =
                            planEntriesByKey.get(item.key) ?? null;

                        return (
                            (entry?.selectedItemId ??
                                planPreviewEntry?.selected_item_id ??
                                null) !== null
                        );
                    }).length;
                    const isCategoryComplete =
                        category === "hand" ||
                        configuredCount === itemsInCategory.length;

                    return (
                        <details
                            key={category}
                            className={
                                isCategoryComplete
                                    ? "group rounded border border-green-300 bg-green-50/40 dark:border-green-700 dark:bg-green-950/20"
                                    : "group rounded border border-orange-300 bg-orange-50/40 dark:border-orange-700 dark:bg-orange-950/20"
                            }
                        >
                            <summary className="flex cursor-pointer list-none flex-nowrap items-center gap-2 rounded p-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 [&::-webkit-details-marker]:hidden">
                                <span
                                    aria-hidden="true"
                                    className="shrink-0 transition-transform group-open:rotate-90"
                                >
                                    ▸
                                </span>
                                <span>{label}</span>
                                <span
                                    className={
                                        isCategoryComplete
                                            ? "ml-auto shrink-0 rounded border border-green-300 bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-100"
                                            : "ml-auto shrink-0 rounded border border-orange-300 bg-orange-100 px-2 py-0.5 text-xs text-orange-800 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100"
                                    }
                                >
                                    {category === "hand"
                                        ? `${configuredCount} of 2 optional hands selected`
                                        : `${configuredCount}/${itemsInCategory.length} resolved`}
                                </span>
                            </summary>
                            <div className="grid gap-2 p-2">
                                {itemsInCategory.map((item) => {
                                    const entry = craftSetPlan[item.key] ?? {
                                        selectedItemId: null,
                                    };
                                    const planPreviewEntry =
                                        planEntriesByKey.get(item.key) ?? null;
                                    const availableItems =
                                        planPreviewEntry?.available_items ?? [];
                                    const availableItemOptions = (
                                        item.category === "hand"
                                            ? availableItems
                                            : availableItems
                                                  .slice()
                                                  .sort(
                                                      (a, b) => a.cost - b.cost,
                                                  )
                                    ).map((availableItem) => ({
                                        value: availableItem.id,
                                        label:
                                            item.category === "hand"
                                                ? `${availableItem.name} — ${availableItem.type} — ${availableItem.handedness === "two_handed" ? "Two-handed" : availableItem.handedness === "shield" ? "Shield" : "Single-handed"}`
                                                : availableItem.name,
                                    }));
                                    const selectedItemIdValue =
                                        entry.selectedItemId ??
                                        planPreviewEntry?.selected_item_id ??
                                        null;
                                    const exactItemName =
                                        planPreviewEntry?.selected_item_name ??
                                        item.label;
                                    const itemCraftingCost =
                                        planPreviewEntry?.selected_item_cost ??
                                        0;
                                    const affixButtonClass =
                                        "font-semibold text-regent-st-blue-700 hover:underline dark:text-regent-st-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded";
                                    const selectedItemDetails =
                                        planPreviewEntry?.selected_item_details ??
                                        null;

                                    return (
                                        <div
                                            key={item.key}
                                            className={
                                                selectedItemIdValue !== null
                                                    ? "rounded border border-green-200 bg-white dark:border-green-800 dark:bg-gray-900"
                                                    : "rounded border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                                            }
                                        >
                                            <div className="p-2 text-sm">
                                                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Target
                                                    </dt>
                                                    <dd>{item.label}</dd>
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Item Name
                                                    </dt>
                                                    <dd>
                                                        {selectedItemDetails ? (
                                                            <button
                                                                type="button"
                                                                className={
                                                                    affixButtonClass
                                                                }
                                                                onClick={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    event.stopPropagation();
                                                                    openItemDetailsModal(
                                                                        selectedItemDetails,
                                                                    );
                                                                }}
                                                            >
                                                                {exactItemName}
                                                            </button>
                                                        ) : (
                                                            <span>
                                                                {exactItemName}
                                                            </span>
                                                        )}
                                                    </dd>
                                                    <dt className="font-semibold sm:whitespace-nowrap">
                                                        Item Crafting Cost
                                                    </dt>
                                                    <dd>
                                                        {formatNumber(
                                                            itemCraftingCost,
                                                        )}
                                                    </dd>
                                                </dl>
                                            </div>
                                            <div className="grid gap-2 p-2">
                                                {item.category === "hand" ? (
                                                    this.renderHandSelectionControls(
                                                        item.label,
                                                        entry,
                                                        availableItems,
                                                        previewLoading,
                                                        isHandDisabled(
                                                            item.key,
                                                        ),
                                                        isDuelWieldUnavailable(
                                                            item.key,
                                                        ),
                                                        (value) =>
                                                            setHandSelectionType(
                                                                item.key,
                                                                value,
                                                            ),
                                                        (value) =>
                                                            setSelectedWeaponType(
                                                                item.key,
                                                                value,
                                                            ),
                                                        (value) =>
                                                            setSelectedItemId(
                                                                item.key,
                                                                value,
                                                            ),
                                                    )
                                                ) : (
                                                    <label className="grid gap-1 text-sm font-semibold">
                                                        Item to craft
                                                        <Select
                                                            isClearable={false}
                                                            isSearchable={true}
                                                            placeholder="No item selected"
                                                            isLoading={
                                                                previewLoading
                                                            }
                                                            noOptionsMessage={() =>
                                                                previewLoading
                                                                    ? "Loading..."
                                                                    : "No options"
                                                            }
                                                            onChange={(opt) =>
                                                                setSelectedItemId(
                                                                    item.key,
                                                                    opt?.value ??
                                                                        null,
                                                                )
                                                            }
                                                            options={
                                                                availableItemOptions
                                                            }
                                                            menuPosition={
                                                                "absolute"
                                                            }
                                                            menuPlacement={
                                                                "bottom"
                                                            }
                                                            styles={
                                                                batchCraftingSelectStyles
                                                            }
                                                            menuPortalTarget={
                                                                document.body
                                                            }
                                                            value={
                                                                availableItemOptions.find(
                                                                    (option) =>
                                                                        option.value ===
                                                                        selectedItemIdValue,
                                                                ) ?? null
                                                            }
                                                        />
                                                    </label>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </details>
                    );
                })}
            </div>
        );
    }

    renderPreviewItem(
        item: BatchCraftingItemPreviewSnapshot | null | undefined,
    ) {
        if (!item || !item.name) {
            return <span>None selected</span>;
        }

        return (
            <button
                type="button"
                className="text-left"
                onClick={() =>
                    this.setState({
                        craftEnchantSetItemDetailsModalItem:
                            item.full_item_details,
                    })
                }
                aria-label={`View details for ${item.name}`}
            >
                <ItemNameColorationText
                    item={{
                        name: item.name,
                        type: item.type ?? "item",
                        affix_count: item.affix_count ?? 0,
                        is_unique: item.is_unique ?? false,
                        is_mythic: item.is_mythic ?? false,
                        is_cosmic: item.is_cosmic ?? false,
                        holy_stacks_applied: item.holy_stacks_applied ?? 0,
                    }}
                    custom_width={false}
                    additional_css={""}
                />
            </button>
        );
    }

    renderPreviewStatus(hasExistingPreview: boolean) {
        const { previewLoading, previewError } = this.state;

        if (previewLoading && !hasExistingPreview) {
            return (
                <p
                    className="text-sm text-gray-500 dark:text-gray-400"
                    role="status"
                    aria-live="polite"
                >
                    Loading cost preview...
                </p>
            );
        }

        if (previewError) {
            return (
                <WarningAlert additional_css="my-2">
                    {previewError}
                </WarningAlert>
            );
        }

        return null;
    }

    renderPreviewLoadingIndicator() {
        if (!this.state.previewLoading) {
            return null;
        }

        return (
            <p
                className="text-xs text-gray-500 dark:text-gray-400"
                role="status"
                aria-live="polite"
            >
                Refreshing cost preview...
            </p>
        );
    }

    startBlockerKey(blocker: BatchCraftingStartBlocker): string {
        return [
            blocker.code,
            blocker.affix_id ?? "",
            blocker.plan_key ?? "",
            blocker.selected_item_id ?? "",
            blocker.target_label ?? "",
            blocker.message,
        ].join(":");
    }

    renderStartBlockers(blockers: BatchCraftingStartBlocker[]) {
        if (blockers.length === 0) {
            return null;
        }

        return (
            <WarningAlert additional_css="my-2">
                <ul className="grid gap-3">
                    {blockers.map((blocker: BatchCraftingStartBlocker) => (
                        <li key={this.startBlockerKey(blocker)}>
                            <p>{blocker.message}</p>
                            {blocker.links && blocker.links.length > 0 ? (
                                <ul className="list-disc space-y-1 pl-5">
                                    {blocker.links.map((link) => (
                                        <li key={link.url}>
                                            <a
                                                href={link.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                {link.label}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                        </li>
                    ))}
                </ul>
            </WarningAlert>
        );
    }

    renderOutputDestinationSelector() {
        const {
            batchType,
            craftMode,
            disposition,
            inventorySets,
            inventorySetsLoading,
            isSaving,
            outputDestination,
            outputSetId,
            status,
        } = this.state;
        const resolvedCraftMode = this.getResolvedCraftMode(
            status,
            batchType,
            craftMode,
        );

        if (
            !this.isFiniteRetainedOutputMode(
                batchType,
                resolvedCraftMode,
                disposition,
            )
        ) {
            return null;
        }

        const outputDestinationOptions: {
            value: OutputDestination;
            label: string;
        }[] = [
            { value: "inventory", label: "Inventory" },
            { value: "inventory_set", label: "Specified Empty Set" },
            { value: "crafted_items_set", label: "Crafted Items Set" },
        ];

        const eligibleOutputSets = inventorySets.filter(
            (set) =>
                !set.equipped &&
                !set.is_batch_crafting_set &&
                set.current_slots === 0,
        );
        const eligibleOutputSetOptions = eligibleOutputSets.map((set) => ({
            value: set.set_id,
            label: set.label,
        }));

        return (
            <div className="grid gap-3">
                <label className="grid gap-1 text-sm font-semibold">
                    Output Destination
                    <Select
                        onChange={(opt) =>
                            this.setState({
                                outputDestination: (opt?.value ??
                                    "crafted_items_set") as OutputDestination,
                            })
                        }
                        options={outputDestinationOptions}
                        isDisabled={isSaving}
                        menuPosition={"absolute"}
                        menuPlacement={"bottom"}
                        styles={batchCraftingSelectStyles}
                        menuPortalTarget={document.body}
                        value={
                            outputDestinationOptions.find(
                                (option) => option.value === outputDestination,
                            ) ?? outputDestinationOptions[2]
                        }
                    />
                </label>
                {outputDestination === "inventory_set" ? (
                    eligibleOutputSetOptions.length === 0 &&
                    !inventorySetsLoading ? (
                        <WarningAlert additional_css="text-sm">
                            You do not have an empty, unequipped set available
                            for Batch Crafting output.
                        </WarningAlert>
                    ) : (
                        <label className="grid gap-1 text-sm font-semibold">
                            Empty Set
                            <Select
                                isLoading={inventorySetsLoading}
                                onChange={(opt) =>
                                    this.setState({
                                        outputSetId: opt?.value ?? null,
                                    })
                                }
                                options={eligibleOutputSetOptions}
                                isDisabled={isSaving}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={batchCraftingSelectStyles}
                                menuPortalTarget={document.body}
                                value={
                                    eligibleOutputSetOptions.find(
                                        (option) =>
                                            option.value === outputSetId,
                                    ) ?? null
                                }
                            />
                        </label>
                    )
                ) : null}
            </div>
        );
    }

    renderDestinationCapacity() {
        const destinationCapacity = this.state.preview?.destination_capacity;

        if (!destinationCapacity) {
            return this.renderPreviewStatus(false);
        }

        const { destination_label, current, max } = destinationCapacity;

        return (
            <div className="grid gap-2">
                {this.renderPreviewLoadingIndicator()}
                <ProgressBar
                    label={`${destination_label} Space`}
                    current={current}
                    max={max}
                    percent={max > 0 ? (current / max) * 100 : 0}
                    barClassName="bg-regent-st-blue-500"
                />
            </div>
        );
    }

    renderCostBreakdown(hideForInvalidSet: boolean) {
        const preview: CostBreakdown | undefined =
            this.state.preview?.cost_breakdown;
        const statusBlock = this.renderPreviewStatus(!!preview);

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return null;
        }

        if (hideForInvalidSet) {
            return null;
        }

        const { batchType, status } = this.state;
        const resolvedCraftMode = this.getResolvedCraftMode(
            status,
            batchType,
            this.state.craftMode,
        );
        const resolvedAlchemyMode = this.getResolvedAlchemyMode(
            status,
            this.state.alchemyMode,
        );
        const isExperienceMode =
            (["craft", "craft_and_enchant"].includes(batchType) &&
                resolvedCraftMode === "experience") ||
            (batchType === "alchemy" && resolvedAlchemyMode === "experience");

        if (isExperienceMode) {
            return null;
        }

        const isCraftEnchantSet =
            batchType === "craft_and_enchant" &&
            resolvedCraftMode === "craft_enchant_set";
        const isAlchemyAmount =
            batchType === "alchemy" && resolvedAlchemyMode === "amount";
        const isHolyOils = batchType === "holy_oils";
        const isTrinketry = batchType === "trinketry";
        const title = isCraftEnchantSet
            ? "Cost To Craft and Enchant"
            : "Cost To Craft";

        const totalCraftingCost =
            preview.craft_cost_total ?? preview.total_required;
        const goldRequired = preview.total_required_gold ?? totalCraftingCost;
        const goldAvailable = preview.available_currency_amount;
        const goldShort =
            !isAlchemyAmount &&
            !isHolyOils &&
            goldRequired !== null &&
            goldRequired > 0 &&
            goldAvailable < goldRequired;
        const goldMissing = goldShort
            ? (preview.missing_currency_amount ??
              Math.max(0, (goldRequired ?? 0) - goldAvailable))
            : 0;

        const goldDustRequired = preview.total_required_gold_dust;
        const goldDustAvailable = preview.available_gold_dust;
        const goldDustShort =
            isAlchemyAmount &&
            typeof goldDustRequired !== "undefined" &&
            typeof goldDustAvailable !== "undefined" &&
            goldDustAvailable < goldDustRequired;

        const shardsRequired = preview.total_required_shards;
        const shardsAvailable = preview.available_shards;
        const shardsShort =
            isAlchemyAmount &&
            typeof shardsRequired !== "undefined" &&
            typeof shardsAvailable !== "undefined" &&
            shardsAvailable < shardsRequired;

        const holyOilGoldDustRequired = preview.total_required;
        const holyOilGoldDustAvailable = preview.available_currency_amount;
        const holyOilGoldDustShort =
            isHolyOils &&
            holyOilGoldDustRequired !== null &&
            holyOilGoldDustAvailable < holyOilGoldDustRequired;

        const redValueClass = "text-red-700 dark:text-red-400 font-semibold";
        const hasCraftCostRow =
            !isAlchemyAmount && !isHolyOils && totalCraftingCost !== null;
        const hasRows =
            hasCraftCostRow || isAlchemyAmount || isHolyOils || isTrinketry;

        if (!hasRows) {
            return null;
        }

        return (
            <div className="rounded border border-regent-st-blue-200 bg-regent-st-blue-50 p-3 text-sm text-regent-st-blue-900 dark:border-regent-st-blue-800 dark:bg-regent-st-blue-900 dark:text-regent-st-blue-100">
                {this.renderPreviewLoadingIndicator()}
                <h5 className="font-semibold">{title}</h5>
                <p className="text-xs">
                    This is the backend-calculated cost and cap preview for
                    starting the selected batch.
                </p>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                {!isAlchemyAmount &&
                !isHolyOils &&
                totalCraftingCost !== null ? (
                    <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Total Crafting Cost
                        </dt>
                        <dd className={goldShort ? redValueClass : ""}>
                            {formatNumber(totalCraftingCost)}
                        </dd>
                        {isCraftEnchantSet ? (
                            <>
                                <dt className="font-semibold sm:whitespace-nowrap">
                                    Total Enchanting Cost
                                </dt>
                                <dd className={goldShort ? redValueClass : ""}>
                                    {formatNumber(
                                        preview.enchant_cost_total ?? 0,
                                    )}
                                </dd>
                                <dt className="font-semibold sm:whitespace-nowrap">
                                    Total Gold Cost
                                </dt>
                                <dd className={goldShort ? redValueClass : ""}>
                                    {formatNumber(
                                        preview.total_required_gold ?? 0,
                                    )}
                                </dd>
                            </>
                        ) : null}
                    </dl>
                ) : null}
                {isAlchemyAmount ? (
                    <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Total Gold Dust Required
                        </dt>
                        <dd className={goldDustShort ? redValueClass : ""}>
                            {formatNumber(goldDustRequired ?? 0)}
                        </dd>
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Total Shards Required
                        </dt>
                        <dd className={shardsShort ? redValueClass : ""}>
                            {formatNumber(shardsRequired ?? 0)}
                        </dd>
                    </dl>
                ) : null}
                {isHolyOils ? (
                    <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Total Gold Dust Required
                        </dt>
                        <dd
                            className={
                                holyOilGoldDustShort ? redValueClass : ""
                            }
                        >
                            {formatNumber(holyOilGoldDustRequired ?? 0)}
                        </dd>
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Available Gold Dust
                        </dt>
                        <dd>{formatNumber(holyOilGoldDustAvailable)}</dd>
                    </dl>
                ) : null}
                {isTrinketry ? (
                    <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                        <dt className="font-semibold sm:whitespace-nowrap">
                            XP-Eligible Trinket
                        </dt>
                        <dd>{preview.trinket?.name ?? "Unavailable"}</dd>
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Gold Dust Required
                        </dt>
                        <dd
                            className={
                                (preview.gold_dust?.missing ?? 0) > 0
                                    ? redValueClass
                                    : ""
                            }
                        >
                            {formatNumber(preview.gold_dust?.required ?? 0)}
                        </dd>
                        <dt className="font-semibold sm:whitespace-nowrap">
                            Copper Coins Required
                        </dt>
                        <dd
                            className={
                                (preview.copper_coins?.missing ?? 0) > 0
                                    ? redValueClass
                                    : ""
                            }
                        >
                            {formatNumber(preview.copper_coins?.required ?? 0)}
                        </dd>
                    </dl>
                ) : null}
                {preview.enchant_has_failure_risk ? (
                    <WarningAlert additional_css="my-2">
                        Enchanting can fail and destroy items in this plan
                        because the relevant Enchanting level is below 400. Gold
                        is still spent even if that happens.
                    </WarningAlert>
                ) : null}
                {preview.message ? (
                    <WarningAlert additional_css="my-2">
                        {preview.message}
                    </WarningAlert>
                ) : null}
                {goldShort ? (
                    <WarningAlert additional_css="my-2">
                        You do not have enough gold to start this batch. You
                        need {formatNumber(goldMissing)} more Gold. Required:{" "}
                        {formatNumber(goldRequired)}, Available:{" "}
                        {formatNumber(goldAvailable)}.
                    </WarningAlert>
                ) : null}
                {preview.source_hint ? (
                    <InfoAlert additional_css="text-sm my-2">
                        {preview.source_hint}
                    </InfoAlert>
                ) : null}
            </div>
        );
    }

    listingItemCount(): number | null {
        const preview = this.state.preview;

        if (preview?.amount_preview) {
            return preview.amount_preview.effective_craftable_amount;
        }

        if (preview?.alchemy_amount_preview) {
            return preview.alchemy_amount_preview.effective_craftable_amount;
        }

        if (
            this.state.batchType === "holy_oils" &&
            this.state.holyOilMode === "selected"
        ) {
            return (
                preview?.holy_oil_selected_preview?.total_eligible_items ?? null
            );
        }

        if (
            this.state.batchType === "holy_oils" &&
            this.state.holyOilMode === "set"
        ) {
            return preview?.holy_oil_set_preview?.total_eligible_items ?? null;
        }

        if (
            this.state.batchType === "craft_and_enchant" &&
            this.state.craftMode === "craft_enchant_set"
        ) {
            return preview?.cost_breakdown?.planned_items ?? null;
        }

        return null;
    }

    listingContext(): string {
        const preview = this.state.preview;

        if (preview?.amount_preview?.selected_item?.name) {
            const affixes = [
                preview.amount_preview.prefix_affix_name,
                preview.amount_preview.suffix_affix_name,
            ].filter(Boolean);

            if (affixes.length > 0) {
                return `${preview.amount_preview.selected_item.name} with ${affixes.join(" and ")}`;
            }

            return preview.amount_preview.selected_item.name;
        }

        if (preview?.alchemy_amount_preview?.selected_item?.name) {
            return preview.alchemy_amount_preview.selected_item.name;
        }

        if (
            this.state.batchType === "holy_oils" &&
            this.state.holyOilMode === "selected"
        ) {
            return "Selected Holy Oils gear that remains eligible after oil application";
        }

        if (
            this.state.batchType === "holy_oils" &&
            this.state.holyOilMode === "set"
        ) {
            const setName = preview?.holy_oil_set_preview?.set_name;

            return setName
                ? `${setName} Holy Oils set items that remain eligible after oil application`
                : "Holy Oils set items that remain eligible after oil application";
        }

        if (
            this.state.batchType === "craft_and_enchant" &&
            this.state.craftMode === "craft_enchant_set"
        ) {
            return "Final enchanted set outputs from the configured set plan";
        }

        return "Eligible output produced by this batch";
    }

    renderListingPricePanel() {
        const { disposition, listingChartData, listingChartLoading } =
            this.state;

        if (disposition !== "list") {
            return null;
        }

        const itemId = this.getListingChartItemId(this.state);
        const isDarkMode =
            typeof window !== "undefined" &&
            window.localStorage.getItem("scheme") === "dark";
        const preview = this.state.preview;
        const itemCountToList = this.listingItemCount();
        const listingContext = this.listingContext();
        const listingPriceNumber =
            typeof this.state.listingPrice === "number"
                ? this.state.listingPrice
                : 0;
        const totalListedValue =
            itemCountToList !== null
                ? itemCountToList * listingPriceNumber
                : null;
        const potentialSellerNet =
            totalListedValue !== null
                ? Math.round(totalListedValue * 0.95)
                : null;
        const setListingPrice = (value: number | "") => {
            if (value === "") {
                this.setState({ listingPrice: "" });
                return;
            }

            let normalizedValue = value;

            if (normalizedValue < 1) {
                normalizedValue = 1;
            }

            if (normalizedValue > 2000000000000000) {
                normalizedValue = 2000000000000000;
            }

            this.setState({ listingPrice: normalizedValue });
        };

        return (
            <div className="rounded border border-regent-st-blue-200 bg-regent-st-blue-50 p-3 text-sm text-regent-st-blue-900 dark:border-regent-st-blue-800 dark:bg-regent-st-blue-900 dark:text-regent-st-blue-100">
                <h5 className="font-semibold">Listing Price</h5>
                <p className="text-xs">
                    Every item this batch lists on the market board will be
                    listed for this single price.
                </p>
                <dl className="mt-3 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                    <dt className="font-semibold sm:whitespace-nowrap">
                        Output
                    </dt>
                    <dd>{listingContext}</dd>
                </dl>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                {itemId !== null ? (
                    listingChartLoading ? (
                        <div className="p-5 mb-2">
                            <ComponentLoading />
                        </div>
                    ) : (
                        <>
                            <MarketBoardLineChart
                                dark_chart={isDarkMode}
                                data={listingChartData}
                                key_for_value={"price"}
                            />
                            <p className="text-xs text-gray-700 dark:text-gray-500 mb-4 mt-2">
                                If the chart above states 0, then this item has
                                never been listed before or there is no current
                                listing for it.
                            </p>
                        </>
                    )
                ) : null}
                <label
                    className="grid gap-1 text-sm font-semibold"
                    htmlFor="batch-listing-price"
                >
                    List For
                    <input
                        id="batch-listing-price"
                        type="number"
                        className="rounded border border-gray-300 bg-white p-2 text-base disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800"
                        min={1}
                        step={1}
                        value={this.state.listingPrice}
                        onChange={(e) =>
                            setListingPrice(
                                e.target.value === ""
                                    ? ""
                                    : parseInt(e.target.value, 10),
                            )
                        }
                    />
                </label>
                <dl className="mt-3 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_minmax(0,1fr)]">
                    <dt className="font-semibold sm:whitespace-nowrap">
                        Items To List
                    </dt>
                    <dd>
                        {itemCountToList !== null
                            ? formatNumber(itemCountToList)
                            : "Determined as the batch produces eligible output"}
                    </dd>
                    <dt className="font-semibold sm:whitespace-nowrap">
                        List Price Per Item
                    </dt>
                    <dd>{formatNumber(listingPriceNumber)} Gold</dd>
                    <dt className="font-semibold sm:whitespace-nowrap">
                        Total Listed Value
                    </dt>
                    <dd>
                        {totalListedValue !== null
                            ? `${formatNumber(totalListedValue)} Gold`
                            : "Depends on final listed output count"}
                    </dd>
                    <dt className="font-semibold sm:whitespace-nowrap">
                        Potential Net After Market Tax
                    </dt>
                    <dd>
                        {potentialSellerNet !== null
                            ? `${formatNumber(potentialSellerNet)} Gold`
                            : "Depends on final listed output count"}
                    </dd>
                </dl>
                <p className="text-xs text-gray-700 dark:text-gray-500 mt-2">
                    Every item sold from this listing is subject to the usual 5%
                    market tax.
                </p>
            </div>
        );
    }

    renderCraftAmountPreview() {
        const preview = this.state.preview?.amount_preview;
        const statusBlock = this.renderPreviewStatus(!!preview);

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return null;
        }

        const hasStartBlockers =
            (this.state.preview?.start_blockers ?? []).length > 0;
        const storesOutput = preview.destination !== null;

        return (
            <div className="grid gap-3">
                {this.renderPreviewLoadingIndicator()}
                {storesOutput ? (
                    <>
                        <InfoAlert additional_css="text-sm my-2">
                            Kept output for this batch will be placed in{" "}
                            {preview.destination_label}.
                        </InfoAlert>
                        <ProgressBar
                            label={`${preview.destination_label} Space`}
                            current={preview.destination_current_slots}
                            max={preview.destination_max_slots}
                            percent={
                                preview.destination_max_slots > 0
                                    ? (preview.destination_current_slots /
                                          preview.destination_max_slots) *
                                      100
                                    : 0
                            }
                            barClassName="bg-regent-st-blue-500"
                        />
                    </>
                ) : null}
                {preview.enchant_has_failure_risk ? (
                    <WarningAlert additional_css="my-2">
                        Enchanting can fail and destroy the item because your
                        Enchanting level is below 400. Gold is still spent even
                        if that happens.
                    </WarningAlert>
                ) : null}
                {preview.effective_craftable_amount === 0 &&
                !hasStartBlockers ? (
                    <WarningAlert additional_css="my-2">
                        This batch cannot complete any items with your current
                        gold
                        {storesOutput
                            ? ` and ${preview.destination_label} space`
                            : ""}
                        .
                    </WarningAlert>
                ) : preview.capped ? (
                    <WarningAlert additional_css="my-2">
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current gold
                        {storesOutput
                            ? ` and ${preview.destination_label} space`
                            : ""}
                        .
                    </WarningAlert>
                ) : null}
                {preview.destination === "inventory" &&
                preview.requested_amount >
                    (this.state.status?.inventory_max ?? Infinity) ? (
                    <WarningAlert additional_css="my-2">
                        You cannot empty all of this into your normal inventory
                        at once. Your normal inventory can only hold{" "}
                        {formatNumber(this.state.status?.inventory_max ?? 0)}{" "}
                        items at a time.
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderAlchemyAmountPreview() {
        const preview = this.state.preview?.alchemy_amount_preview;
        const statusBlock = this.renderPreviewStatus(!!preview);

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return null;
        }

        const hasStartBlockers =
            (this.state.preview?.start_blockers ?? []).length > 0;
        const storesOutput = [
            "keep",
            "keep_best_sell_rest",
            "keep_best_destroy_rest",
        ].includes(this.state.disposition);

        return (
            <div className="grid gap-3">
                {this.renderPreviewLoadingIndicator()}
                {storesOutput ? (
                    <>
                        <InfoAlert additional_css="text-sm my-2">
                            Output for this batch is moved into the Alchemy Bag.
                            This run is capped by Alchemy Bag space, Gold Dust,
                            and Shards, whichever runs out first.
                        </InfoAlert>
                        <ProgressBar
                            label="Alchemy Bag Space"
                            current={preview.bag_current}
                            max={preview.bag_max}
                            percent={
                                preview.bag_max > 0
                                    ? (preview.bag_current / preview.bag_max) *
                                      100
                                    : 0
                            }
                            barClassName="bg-regent-st-blue-500"
                        />
                    </>
                ) : null}
                {preview.effective_craftable_amount === 0 &&
                !hasStartBlockers ? (
                    <>
                        {preview.gold_dust_cost_per_item > 0 &&
                        preview.available_gold_dust <
                            preview.gold_dust_cost_per_item ? (
                            <WarningAlert additional_css="my-2">
                                You do not have enough Gold Dust to start this
                                batch. Required:{" "}
                                {formatNumber(preview.gold_dust_cost_per_item)},
                                Available:{" "}
                                {formatNumber(preview.available_gold_dust)}.
                            </WarningAlert>
                        ) : null}
                        {preview.shards_cost_per_item > 0 &&
                        preview.available_shards <
                            preview.shards_cost_per_item ? (
                            <WarningAlert additional_css="my-2">
                                You do not have enough Shards to start this
                                batch. Required:{" "}
                                {formatNumber(preview.shards_cost_per_item)},
                                Available:{" "}
                                {formatNumber(preview.available_shards)}.
                            </WarningAlert>
                        ) : null}
                        {storesOutput && preview.bag_remaining <= 0 ? (
                            <WarningAlert additional_css="my-2">
                                Your Alchemy Bag is full. Free up space before
                                starting this batch.
                            </WarningAlert>
                        ) : null}
                    </>
                ) : preview.capped ? (
                    <WarningAlert additional_css="my-2">
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current currency
                        {storesOutput ? " and alchemy bag space" : ""}.
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderHolyOilPreviewItemsList(items: HolyOilPreviewItemEntry[]) {
        if (items.length === 0) {
            return null;
        }

        return (
            <ul className="grid gap-2">
                {items.map((entry, index) => (
                    <li
                        key={entry.target_slot_id}
                        className="rounded border border-gray-300 p-3 dark:border-gray-600"
                    >
                        <dl className="grid grid-cols-2 gap-1 text-xs">
                            <dt className="font-semibold">Item</dt>
                            <dd>
                                {this.renderPreviewItem(
                                    entry.item
                                        ? {
                                              ...entry.item,
                                              full_item_details:
                                                  entry.full_item_details,
                                          }
                                        : null,
                                )}
                            </dd>
                            <dt className="font-semibold">
                                Applications Planned
                            </dt>
                            <dd>{formatNumber(entry.planned_applications)}</dd>
                            <dt className="font-semibold">Result</dt>
                            <dd>
                                {formatNumber(entry.current_stacks)} /{" "}
                                {formatNumber(entry.maximum_stacks)} →{" "}
                                {formatNumber(entry.resulting_stacks)} /{" "}
                                {formatNumber(entry.maximum_stacks)}
                            </dd>
                            <dt className="font-semibold">
                                Exact Gold Dust Cost
                            </dt>
                            <dd>{formatNumber(entry.exact_gold_dust_cost)}</dd>
                        </dl>
                        <div className="mt-3">
                            <ProgressBar
                                label="Holy Oil Stacks"
                                current={entry.resulting_stacks}
                                max={entry.maximum_stacks}
                                percent={
                                    entry.maximum_stacks > 0
                                        ? (entry.resulting_stacks /
                                              entry.maximum_stacks) *
                                          100
                                        : 0
                                }
                            />
                        </div>
                    </li>
                ))}
            </ul>
        );
    }

    renderHolyOilsSelectedPreview() {
        const preview = this.state.preview?.holy_oil_selected_preview;
        const statusBlock = this.renderPreviewStatus(!!preview);

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                {this.renderPreviewLoadingIndicator()}
                <h5 className="font-semibold">Holy Oils - Selected Items</h5>
                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    Selected gear is updated in place in your normal inventory.
                    It is never moved to the Crafted Items Set.
                </p>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                {this.renderHolyOilPreviewItemsList(preview.items)}
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Applications Planned</dt>
                    <dd>{formatNumber(preview.applications_planned)}</dd>
                    <dt className="font-semibold">Items Affected</dt>
                    <dd>{formatNumber(preview.items_affected)}</dd>
                    <dt className="font-semibold">Gold Dust Required</dt>
                    <dd>{formatNumber(preview.exact_gold_dust_required)}</dd>
                </dl>
                {preview.capped ? (
                    <WarningAlert additional_css="my-2">
                        {formatNumber(preview.oils_not_applicable)} selected
                        Holy Oils cannot be used. {preview.unapplied_reason}
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderHolyOilsSetPreview() {
        const preview = this.state.preview?.holy_oil_set_preview;
        const statusBlock = this.renderPreviewStatus(!!preview);

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                {this.renderPreviewLoadingIndicator()}
                <h5 className="font-semibold">Holy Oils - Inventory Set</h5>
                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    Selected set items are updated in place within that set.
                    They are never moved to your normal inventory or the Crafted
                    Items Set.
                </p>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Set</dt>
                    <dd>{preview.set_name}</dd>
                    <dt className="font-semibold">Items Affected</dt>
                    <dd>{formatNumber(preview.items_affected)}</dd>
                </dl>
                {this.renderHolyOilPreviewItemsList(preview.items)}
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Applications Planned</dt>
                    <dd>{formatNumber(preview.applications_planned)}</dd>
                    <dt className="font-semibold">Gold Dust Required</dt>
                    <dd>{formatNumber(preview.exact_gold_dust_required)}</dd>
                </dl>
                {preview.capped ? (
                    <WarningAlert additional_css="my-2">
                        {formatNumber(preview.oils_not_applicable)} selected
                        Holy Oils cannot be used. {preview.unapplied_reason}
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    render() {
        const { character_id, remove_crafting } = this.props;
        const {
            alchemyItems,
            alchemyItemsLoading,
            alchemyMode,
            armourType,
            batchType,
            craftAmount,
            craftCategory,
            craftEnchantSetMode,
            craftEnchantSetPlan,
            craftMode,
            craftableItems,
            craftableItemsLoading,
            disposition,
            enchantMode,
            enchantments,
            enchantmentsLoading,
            hideMaxedCraftNotice,
            holyOilItems,
            holyOilMode,
            holyOilOptions,
            holyOilsLoading,
            inventorySets,
            inventorySetsLoading,
            isSaving,
            message,
            preview,
            previewError,
            previewLoading,
            selectedAlchemyItemId,
            selectedItems,
            selectedOils,
            selectedPrefixId,
            selectedSetId,
            selectedSuffixId,
            specificItemId,
            status,
            statusLoadError,
            statusLoading,
            weaponType,
        } = this.state;

        const alchemyLocked = status?.event_batch?.alchemy_locked ?? false;
        const alchemyMaxed = status?.event_batch?.alchemy_maxed ?? false;
        const trinketryMaxed = status?.event_batch?.trinketry_maxed ?? false;

        const setBatchType = (batchType: BatchType) =>
            this.handleBatchTypeChange(batchType);
        const setDisposition = (disposition: Disposition) => {
            if (disposition === "list") {
                let defaultListingPrice = 1;

                if (this.state.batchType === "craft_and_enchant") {
                    const amountCraftEnchantCost =
                        this.state.preview?.amount_preview
                            ?.total_per_item_cost ?? 0;
                    const setCraftEnchantCost =
                        (this.state.preview?.cost_breakdown?.craft_cost_total ??
                            0) +
                        (this.state.preview?.cost_breakdown
                            ?.enchant_cost_total ?? 0);
                    const craftEnchantCost =
                        amountCraftEnchantCost > 0
                            ? amountCraftEnchantCost
                            : setCraftEnchantCost;

                    if (craftEnchantCost > 0) {
                        defaultListingPrice = craftEnchantCost;
                    }
                }

                this.setState({
                    disposition,
                    listingPrice: defaultListingPrice,
                });

                return;
            }

            this.setState({ disposition });
        };
        const setCraftMode = (craftMode: CraftMode) =>
            this.setState({ craftMode });
        const setAlchemyMode = (alchemyMode: AlchemyMode) =>
            this.setState({ alchemyMode });
        const setCraftCategory = (craftCategory: CraftCategory) =>
            this.setState({ craftCategory });
        const setWeaponType = (weaponType: string) =>
            this.setState({ weaponType });
        const setArmourType = (armourType: string) =>
            this.setState({ armourType });
        const setSpecificItemId = (specificItemId: number | null) =>
            this.setState({ specificItemId });
        const setCraftAmount = (craftAmount: number | "") =>
            this.setState({ craftAmount });
        const setSelectedAlchemyItemId = (
            selectedAlchemyItemId: number | null,
        ) => this.setState({ selectedAlchemyItemId });
        const setSelectedPrefixId = (selectedPrefixId: number | null) =>
            this.setState({ selectedPrefixId });
        const setSelectedSuffixId = (selectedSuffixId: number | null) =>
            this.setState({ selectedSuffixId });
        const setSelectedItems = (selectedItems: number[]) =>
            this.setState({ selectedItems });
        const setSelectedOils = (selectedOils: number[]) =>
            this.setState({ selectedOils });
        const setHolyOilMode = (holyOilMode: HolyOilMode) =>
            this.setState({ holyOilMode });
        const setSelectedSetId = (selectedSetId: number | null) =>
            this.setState({ selectedSetId });
        const startBatch = () =>
            this.startBatch(
                selectedCraftMode,
                selectedEnchantModeOption?.value as EnchantMode,
            );
        const cancelBatch = () => this.cancelBatch();
        const dismissPanel = () => this.dismissPanel();
        const acknowledgeInfo = () => this.acknowledgeInfo();

        const eventBatch = status?.event_batch;
        const availableBatchTypes = this.getAvailableBatchTypes(status);
        const craftExperienceOptions = this.getCraftExperienceOptions(
            status,
            batchType,
        );
        const canCraftForExperience =
            batchType === "craft"
                ? (status?.craft_mode_availability?.can_craft_for_experience ??
                  true)
                : (status?.craft_mode_availability
                      ?.can_craft_and_enchant_for_experience ?? true);
        const craftModeOptions = this.getCraftModeOptions(status, batchType);
        const enchantModeOptions = this.getEnchantModeOptions(status);
        const selectedEnchantModeOption =
            enchantModeOptions.find((option) => option.value === enchantMode) ??
            enchantModeOptions[0];
        const holyOilModeOptions: { value: HolyOilMode; label: string }[] = [
            { value: "selected", label: "Selected Gear" },
            { value: "set", label: "Inventory Set" },
        ];
        const selectedHolyOilModeOption =
            holyOilModeOptions.find((option) => option.value === holyOilMode) ??
            holyOilModeOptions[0];
        const alchemyModeOptions = this.getAlchemyModeOptions(status);
        const selectedAlchemyModeOption =
            alchemyModeOptions.find((option) => option.value === alchemyMode) ??
            alchemyModeOptions[0];
        const craftingSkillsMaxed =
            status?.event_batch?.crafting_skills_maxed ?? false;
        const enchantingMaxed = status?.event_batch?.enchanting_maxed ?? false;
        const selectedInventorySetOption =
            inventorySets.find((set) => set.set_id === selectedSetId) ?? null;
        const selectedCraftModeOption =
            craftModeOptions.find((option) => option.value === craftMode) ??
            craftModeOptions[0];
        const selectedCraftMode = (selectedCraftModeOption?.value ??
            "specific_item") as CraftMode;
        const allowedDispositionValues = getAllowedDispositions(
            batchType,
            selectedCraftMode,
            (selectedAlchemyModeOption?.value ?? "amount") as AlchemyMode,
        );
        const availableDispositions = dispositions.filter((option) =>
            allowedDispositionValues.includes(option.value),
        );
        const selectedBatchType = availableBatchTypes.find(
            (option) => option.value === batchType,
        );
        const selectedDisposition = availableDispositions.find(
            (option) => option.value === disposition,
        );
        const isActive = status?.active ?? false;
        const isCompleted = status?.completed ?? false;
        const needsEmptySelectedSet = false;
        const selectedSetNotEmpty = false;
        const hasBlockingTargetSetBlocker = (
            preview?.start_blockers ?? []
        ).some(
            (blocker) =>
                blocker.blocking && blocker.code.startsWith("target_set_"),
        );
        const disabledReasons = this.startDisabledReasons(
            selectedCraftMode,
            (selectedAlchemyModeOption?.value ?? "amount") as AlchemyMode,
        );
        const startDisabled = disabledReasons.length > 0;

        if (statusLoadError) {
            return (
                <section className="mt-2">
                    <h3 className="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Batch Crafting
                    </h3>

                    <DangerAlert additional_css={"my-4"}>
                        {statusLoadError}
                    </DangerAlert>

                    <div className="mt-4 flex flex-col items-center justify-center gap-2 md:flex-row">
                        <PrimaryButton
                            button_label={"Retry"}
                            on_click={() => this.fetchStatus()}
                            disabled={statusLoading}
                            additional_css={"w-full md:w-auto"}
                        />
                        <DangerButton
                            button_label={"Close"}
                            on_click={remove_crafting}
                            additional_css={"w-full md:w-auto"}
                            disabled={statusLoading}
                        />
                    </div>
                </section>
            );
        }

        if (status === null) {
            return (
                <section className="mt-2">
                    <h3 className="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Batch Crafting
                    </h3>

                    <div className="mt-3">
                        <LoadingProgressBar />
                    </div>
                </section>
            );
        }

        if ((isActive || isCompleted) && status?.is_visible !== false) {
            return (
                <section className="mt-2">
                    <h3 className="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Batch Crafting
                    </h3>

                    <BatchCraftingStatusPanel
                        character_id={character_id}
                        user_id={this.props.user_id}
                        onDismissed={() => this.fetchStatus()}
                    />

                    {isActive ? (
                        <div className="mt-4 flex flex-col items-center justify-center gap-2 md:flex-row">
                            <DangerButton
                                button_label={"Close"}
                                on_click={remove_crafting}
                                additional_css={"w-full md:w-auto"}
                                disabled={isSaving}
                            />
                        </div>
                    ) : null}
                </section>
            );
        }

        return (
            <section className="mt-2">
                <h3 className="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Batch Crafting
                </h3>

                {status?.show_info ? (
                    <InfoAlert additional_css={"my-4"}>
                        <p className="mb-2">
                            Batch crafting allows you to keep exploring,
                            delving, and manually fighting while crafting,
                            enchanting, working alchemy items, applying holy
                            oils, and making trinkets.
                        </p>
                        <div className="flex flex-col gap-2 md:flex-row md:justify-center">
                            <a
                                href="/information/batch-crafting"
                                target="_blank"
                                rel="noreferrer"
                                className="text-center"
                            >
                                Help{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            <button
                                type="button"
                                className="font-semibold text-blue-700 dark:text-blue-400"
                                onClick={acknowledgeInfo}
                            >
                                Acknowledge
                            </button>
                        </div>
                    </InfoAlert>
                ) : null}

                {!isActive && !isCompleted ? (
                    <div className="mt-4 grid gap-3">
                        <label className="grid gap-1 text-sm font-semibold">
                            Crafting type
                            <Select
                                onChange={(selectedOption) => {
                                    setBatchType(
                                        selectedOption?.value ?? "craft",
                                    );
                                }}
                                options={availableBatchTypes}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={batchCraftingSelectStyles}
                                menuPortalTarget={document.body}
                                value={selectedBatchType}
                            />
                        </label>

                        {alchemyLocked ? (
                            <WarningAlert additional_css="text-sm my-2">
                                You need to unlock Alchemy before you can batch
                                craft Alchemy items or use Holy Oils.
                            </WarningAlert>
                        ) : null}

                        {availableDispositions.length > 1 ? (
                            <label className="grid gap-1 text-sm font-semibold">
                                Disposition
                                <Select
                                    onChange={(selectedOption) => {
                                        setDisposition(
                                            selectedOption?.value ?? "keep",
                                        );
                                    }}
                                    options={availableDispositions}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={batchCraftingSelectStyles}
                                    menuPortalTarget={document.body}
                                    value={selectedDisposition}
                                />
                            </label>
                        ) : null}

                        {(batchType === "craft" ||
                            batchType === "craft_and_enchant") &&
                        selectedCraftMode !== "event" ? (
                            <label className="grid gap-1 text-sm font-semibold">
                                Craft mode
                                <Select
                                    onChange={(opt) =>
                                        setCraftMode(
                                            (opt?.value ??
                                                (canCraftForExperience
                                                    ? "experience"
                                                    : "specific_item")) as CraftMode,
                                        )
                                    }
                                    options={craftModeOptions}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={batchCraftingSelectStyles}
                                    menuPortalTarget={document.body}
                                    value={selectedCraftModeOption}
                                />
                            </label>
                        ) : null}

                        {batchType === "craft" &&
                        craftingSkillsMaxed &&
                        selectedCraftMode !== "event" &&
                        !hideMaxedCraftNotice ? (
                            <InfoAlert additional_css="text-sm my-2">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <span>
                                        Your Weapon Crafting, Armour Crafting,
                                        Ring Crafting, and Spell Crafting are
                                        all maxed. You cannot craft for
                                        experience. You can craft an amount or a
                                        set if you would like.
                                    </span>
                                    <button
                                        type="button"
                                        className="font-semibold text-blue-700 dark:text-blue-400"
                                        onClick={this.dismissMaxedCraftNotice.bind(
                                            this,
                                        )}
                                    >
                                        Dismiss
                                    </button>
                                </div>
                            </InfoAlert>
                        ) : null}

                        {batchType === "craft_and_enchant" &&
                        craftingSkillsMaxed &&
                        enchantingMaxed ? (
                            <InfoAlert additional_css="text-sm my-2">
                                Weapon Crafting, Armour Crafting, Ring Crafting,
                                Spell Crafting, and Enchanting are all maxed, so
                                this character cannot batch craft and enchant
                                for experience.
                            </InfoAlert>
                        ) : null}

                        {batchType === "alchemy" &&
                        selectedAlchemyModeOption?.value === "experience" &&
                        alchemyMaxed ? (
                            <InfoAlert additional_css="text-sm my-2">
                                Alchemy is maxed, so this character cannot batch
                                craft alchemy items for experience.
                            </InfoAlert>
                        ) : null}

                        {batchType === "alchemy" &&
                        disposition === "use_now" ? (
                            <InfoAlert additional_css="text-sm my-2">
                                We will only use usable items on you. The rest
                                will be placed into your Alchemy Bag. You may
                                only have 10 boons at a time, so extras will be
                                placed into your Alchemy Bag.
                            </InfoAlert>
                        ) : null}

                        {batchType === "alchemy" &&
                        selectedAlchemyModeOption?.value === "experience" &&
                        !alchemyMaxed ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Output for this batch is created in the
                                    Alchemy Bag, not your normal inventory.
                                </p>
                                {this.renderDestinationCapacity()}
                                <div className="grid gap-2">
                                    {craftExperienceOptions.map((option) => (
                                        <CraftingXp
                                            key={option.value}
                                            skill_xp={{
                                                skill_name: option.skill_name,
                                                level: option.current_level,
                                                current_xp: option.current_xp,
                                                next_level_xp:
                                                    option.required_xp,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {batchType === "craft_and_enchant" &&
                        selectedCraftMode === "experience" ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Batch Crafting will craft and enchant items
                                    for non-maxed applicable skills. Kept output
                                    is created in the Crafted Items Set when
                                    that disposition is used.
                                </p>
                                {this.renderDestinationCapacity()}
                                <div className="grid gap-2">
                                    {craftExperienceOptions.map((option) => (
                                        <CraftingXp
                                            key={option.value}
                                            skill_xp={{
                                                skill_name: option.skill_name,
                                                level: option.current_level,
                                                current_xp: option.current_xp,
                                                next_level_xp:
                                                    option.required_xp,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {batchType === "craft" &&
                        selectedCraftMode === "experience" ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Batch Crafting will only craft items for
                                    non-maxed crafting skills. Kept output is
                                    created in the Crafted Items Set when that
                                    disposition is used.
                                </p>
                                {this.renderDestinationCapacity()}
                                <div className="grid gap-2">
                                    {craftExperienceOptions.map((option) => (
                                        <CraftingXp
                                            key={option.value}
                                            skill_xp={{
                                                skill_name: option.skill_name,
                                                level: option.current_level,
                                                current_xp: option.current_xp,
                                                next_level_xp:
                                                    option.required_xp,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {batchType === "craft" &&
                        selectedCraftMode === "event" ? (
                            <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                Event:{" "}
                                {eventBatch?.event_name ?? "Current event"}.
                                Current step:{" "}
                                {eventBatch?.current_step ?? "crafting"}.
                                Actions per tick:{" "}
                                {eventBatch?.actions_per_tick ?? 6}. Tick rate:
                                1 minute. Max runtime:{" "}
                                {eventBatch?.max_runtime_hours ?? 8} hours. Goal
                                remaining: {eventBatch?.goal_remaining ?? 0}.
                            </p>
                        ) : null}

                        {batchType === "craft" &&
                        selectedCraftMode === "craft_set" ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Craft Set creates one equippable set with
                                    six armour pieces, two Rings, one Damage
                                    Spell, and one Healing Spell. Left Hand and
                                    Right Hand are optional; Shields are hand
                                    items and two-handed weapons occupy both
                                    hands. The resulting 10–12 items are placed
                                    into {this.outputDestinationDescription()}.
                                </p>
                                {this.renderOutputDestinationSelector()}
                                {this.renderDestinationCapacity()}
                                {this.renderCraftSetPlanner()}
                            </div>
                        ) : null}

                        {batchType === "craft_and_enchant" &&
                        selectedCraftMode === "craft_enchant_set" ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Craft and Enchant Set creates the same
                                    configurable equippable 10–12 item set and
                                    applies the selected affixes to every
                                    included item. Left Hand and Right Hand are
                                    optional; Shields are hand items and
                                    two-handed weapons occupy both hands.
                                    Completed items are placed into{" "}
                                    {this.outputDestinationDescription()}.
                                </p>
                                {this.renderOutputDestinationSelector()}
                                {this.renderDestinationCapacity()}
                                {this.renderCraftEnchantSetPlanner()}
                            </div>
                        ) : null}

                        {batchType === "enchant" &&
                        selectedEnchantModeOption?.value === "event" ? (
                            <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                Event:{" "}
                                {eventBatch?.event_name ?? "Current event"}.
                                Current step:{" "}
                                {eventBatch?.current_step ?? "enchanting"}.
                                Actions per tick:{" "}
                                {eventBatch?.actions_per_tick ?? 6}. Tick rate:
                                1 minute. Max runtime:{" "}
                                {eventBatch?.max_runtime_hours ?? 8} hours. Goal
                                remaining: {eventBatch?.goal_remaining ?? 0}.
                            </p>
                        ) : null}

                        {(batchType === "craft" ||
                            batchType === "craft_and_enchant") &&
                        selectedCraftMode === "specific_item" ? (
                            <label className="grid gap-1 text-sm font-semibold">
                                Category
                                <Select
                                    onChange={(opt) =>
                                        setCraftCategory(
                                            (opt?.value ??
                                                "weapon") as CraftCategory,
                                        )
                                    }
                                    options={craftCategoryOptions}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={batchCraftingSelectStyles}
                                    menuPortalTarget={document.body}
                                    value={{
                                        value: craftCategory,
                                        label:
                                            craftCategoryOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    craftCategory,
                                            )?.label ?? "Weapon",
                                    }}
                                />
                            </label>
                        ) : null}

                        {(batchType === "craft" ||
                            batchType === "craft_and_enchant") &&
                        selectedCraftMode === "specific_item" ? (
                            <>
                                {craftCategory === "weapon" ? (
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Weapon type
                                        <Select
                                            isSearchable={true}
                                            onChange={(opt) =>
                                                setWeaponType(
                                                    opt?.value ?? "dagger",
                                                )
                                            }
                                            options={weaponTypeOptions}
                                            menuPosition={"absolute"}
                                            menuPlacement={"bottom"}
                                            styles={batchCraftingSelectStyles}
                                            menuPortalTarget={document.body}
                                            value={
                                                weaponTypeOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        weaponType,
                                                ) ?? weaponTypeOptions[0]
                                            }
                                        />
                                    </label>
                                ) : null}
                                {craftCategory === "armour" ? (
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Armour type
                                        <Select
                                            isSearchable={true}
                                            onChange={(opt) =>
                                                setArmourType(
                                                    opt?.value ?? "helmet",
                                                )
                                            }
                                            options={armourTypeOptions}
                                            menuPosition={"absolute"}
                                            menuPlacement={"bottom"}
                                            styles={batchCraftingSelectStyles}
                                            menuPortalTarget={document.body}
                                            value={
                                                armourTypeOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        armourType,
                                                ) ?? armourTypeOptions[0]
                                            }
                                        />
                                    </label>
                                ) : null}
                                <label className="grid gap-1 text-sm font-semibold">
                                    Item
                                    <Select
                                        isSearchable={true}
                                        isLoading={craftableItemsLoading}
                                        noOptionsMessage={() =>
                                            craftableItemsLoading
                                                ? "Loading..."
                                                : "No options"
                                        }
                                        onChange={(opt) =>
                                            setSpecificItemId(
                                                opt?.value ?? null,
                                            )
                                        }
                                        options={craftableItems.map((item) => ({
                                            value: item.id,
                                            label: item.name,
                                            itemType: item.type,
                                            cost: item.cost,
                                        }))}
                                        formatOptionLabel={(option: {
                                            label: string;
                                            itemType?: string;
                                            cost?: number;
                                        }) => (
                                            <span className="flex flex-wrap items-center gap-1">
                                                <span>{option.label}</span>
                                                {option.cost ? (
                                                    <span className="text-xs text-gray-700">
                                                        Gold Cost: {option.cost}
                                                    </span>
                                                ) : null}
                                            </span>
                                        )}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={batchCraftingSelectStyles}
                                        menuPortalTarget={document.body}
                                        value={
                                            specificItemId === null
                                                ? null
                                                : craftableItems
                                                      .map((item) => ({
                                                          value: item.id,
                                                          label: item.name,
                                                          itemType: item.type,
                                                          cost: item.cost,
                                                      }))
                                                      .find(
                                                          (item) =>
                                                              item.value ===
                                                              specificItemId,
                                                      )
                                        }
                                    />
                                </label>
                                {batchType === "craft_and_enchant" ? (
                                    <>
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Prefix enchant
                                            <Select
                                                isClearable
                                                isSearchable={true}
                                                isLoading={enchantmentsLoading}
                                                noOptionsMessage={() =>
                                                    enchantmentsLoading
                                                        ? "Loading..."
                                                        : "No options"
                                                }
                                                onChange={(opt) =>
                                                    setSelectedPrefixId(
                                                        opt?.value ?? null,
                                                    )
                                                }
                                                options={enchantments
                                                    .filter(
                                                        (enchantment) =>
                                                            enchantment.type ===
                                                            "prefix",
                                                    )
                                                    .map(toAffixOption)}
                                                formatOptionLabel={
                                                    formatAffixOptionLabel
                                                }
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={
                                                    batchCraftingSelectStyles
                                                }
                                                menuPortalTarget={document.body}
                                                value={
                                                    enchantments
                                                        .filter(
                                                            (enchantment) =>
                                                                enchantment.type ===
                                                                    "prefix" &&
                                                                enchantment.id ===
                                                                    selectedPrefixId,
                                                        )
                                                        .map(toAffixOption)[0]
                                                }
                                            />
                                        </label>
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Suffix enchant
                                            <Select
                                                isClearable
                                                isSearchable={true}
                                                isLoading={enchantmentsLoading}
                                                noOptionsMessage={() =>
                                                    enchantmentsLoading
                                                        ? "Loading..."
                                                        : "No options"
                                                }
                                                onChange={(opt) =>
                                                    setSelectedSuffixId(
                                                        opt?.value ?? null,
                                                    )
                                                }
                                                options={enchantments
                                                    .filter(
                                                        (enchantment) =>
                                                            enchantment.type ===
                                                            "suffix",
                                                    )
                                                    .map(toAffixOption)}
                                                formatOptionLabel={
                                                    formatAffixOptionLabel
                                                }
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={
                                                    batchCraftingSelectStyles
                                                }
                                                menuPortalTarget={document.body}
                                                value={
                                                    enchantments
                                                        .filter(
                                                            (enchantment) =>
                                                                enchantment.type ===
                                                                    "suffix" &&
                                                                enchantment.id ===
                                                                    selectedSuffixId,
                                                        )
                                                        .map(toAffixOption)[0]
                                                }
                                            />
                                        </label>
                                    </>
                                ) : null}
                                <label className="grid gap-1 text-sm font-semibold">
                                    Amount to craft
                                    <input
                                        id="batch-craft-amount"
                                        className="rounded border border-gray-300 bg-white p-2 text-base disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800"
                                        type="number"
                                        inputMode="numeric"
                                        min={1}
                                        max={
                                            this.state.preview
                                                ?.maximum_request_amount ?? 2000
                                        }
                                        step={1}
                                        disabled={isSaving}
                                        value={craftAmount}
                                        onChange={(e) =>
                                            setCraftAmount(
                                                e.target.value === ""
                                                    ? ""
                                                    : parseInt(
                                                          e.target.value,
                                                          10,
                                                      ),
                                            )
                                        }
                                    />
                                    <span className="text-xs font-normal text-gray-600 dark:text-gray-400">
                                        Maximum for this destination:{" "}
                                        {this.state.preview
                                            ?.maximum_request_amount ?? 2000}
                                    </span>
                                </label>
                                {this.renderOutputDestinationSelector()}
                                {this.renderCraftAmountPreview()}
                            </>
                        ) : null}

                        {batchType === "alchemy" ? (
                            <label className="grid gap-1 text-sm font-semibold">
                                Alchemy mode
                                <Select
                                    onChange={(opt) =>
                                        setAlchemyMode(
                                            (opt?.value ??
                                                "experience") as AlchemyMode,
                                        )
                                    }
                                    options={alchemyModeOptions}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={batchCraftingSelectStyles}
                                    menuPortalTarget={document.body}
                                    value={selectedAlchemyModeOption}
                                />
                            </label>
                        ) : null}

                        {batchType === "alchemy" &&
                        selectedAlchemyModeOption?.value === "amount" ? (
                            <>
                                <label className="grid gap-1 text-sm font-semibold">
                                    Alchemy item
                                    <Select
                                        isSearchable={true}
                                        isLoading={alchemyItemsLoading}
                                        noOptionsMessage={() =>
                                            alchemyItemsLoading
                                                ? "Loading..."
                                                : "No options"
                                        }
                                        onChange={(opt) =>
                                            setSelectedAlchemyItemId(
                                                opt?.value ?? null,
                                            )
                                        }
                                        options={alchemyItems.map((item) => ({
                                            value: item.id,
                                            label: item.name,
                                            itemType: item.type,
                                            goldDustCost: item.gold_dust_cost,
                                            shardsCost: item.shards_cost,
                                        }))}
                                        formatOptionLabel={(option: {
                                            label: string;
                                            itemType?: string;
                                            goldDustCost?: number;
                                            shardsCost?: number;
                                        }) => (
                                            <span className="flex flex-wrap items-center gap-1">
                                                <span>{option.label}</span>
                                                {option.goldDustCost ? (
                                                    <span className="text-xs text-gray-700">
                                                        Gold Dust Cost:{" "}
                                                        {option.goldDustCost}
                                                    </span>
                                                ) : null}
                                                {option.shardsCost ? (
                                                    <span className="text-xs text-gray-700">
                                                        Shards Cost:{" "}
                                                        {option.shardsCost}
                                                    </span>
                                                ) : null}
                                            </span>
                                        )}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={batchCraftingSelectStyles}
                                        menuPortalTarget={document.body}
                                        value={
                                            selectedAlchemyItemId === null
                                                ? null
                                                : alchemyItems
                                                      .map((item) => ({
                                                          value: item.id,
                                                          label: item.name,
                                                          itemType: item.type,
                                                          goldDustCost:
                                                              item.gold_dust_cost,
                                                          shardsCost:
                                                              item.shards_cost,
                                                      }))
                                                      .find(
                                                          (item) =>
                                                              item.value ===
                                                              selectedAlchemyItemId,
                                                      )
                                        }
                                    />
                                </label>
                                <label className="grid gap-1 text-sm font-semibold">
                                    Amount to craft
                                    <input
                                        id="batch-alchemy-amount"
                                        className="rounded border border-gray-300 bg-white p-2 text-base disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800"
                                        type="number"
                                        inputMode="numeric"
                                        min={1}
                                        max={
                                            this.state.preview
                                                ?.maximum_request_amount ?? 2000
                                        }
                                        step={1}
                                        disabled={isSaving}
                                        value={craftAmount}
                                        onChange={(e) =>
                                            setCraftAmount(
                                                e.target.value === ""
                                                    ? ""
                                                    : parseInt(
                                                          e.target.value,
                                                          10,
                                                      ),
                                            )
                                        }
                                    />
                                    <span className="text-xs font-normal text-gray-600 dark:text-gray-400">
                                        Maximum for this destination:{" "}
                                        {this.state.preview
                                            ?.maximum_request_amount ?? 2000}
                                    </span>
                                </label>
                                {this.renderAlchemyAmountPreview()}
                            </>
                        ) : null}

                        {batchType === "trinketry" && !trinketryMaxed ? (
                            <div className="grid gap-3">
                                <InfoAlert additional_css="text-sm my-2">
                                    Trinketry batch crafting is only for gaining
                                    Trinketry experience. You cannot batch craft
                                    a chosen amount of trinkets. Once Trinketry
                                    is maxed, manually craft the trinkets you
                                    need. Kept output is moved to the Crafted
                                    Items Set, not your normal inventory.
                                </InfoAlert>
                                {this.renderDestinationCapacity()}
                                <div className="grid gap-2">
                                    {craftExperienceOptions.map((option) => (
                                        <CraftingXp
                                            key={option.value}
                                            skill_xp={{
                                                skill_name: option.skill_name,
                                                level: option.current_level,
                                                current_xp: option.current_xp,
                                                next_level_xp:
                                                    option.required_xp,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {batchType === "trinketry" && trinketryMaxed ? (
                            <InfoAlert additional_css="text-sm my-2">
                                Trinketry is maxed, so this character cannot
                                batch craft trinket items for experience.
                            </InfoAlert>
                        ) : null}

                        {batchType === "holy_oils" ? (
                            <div className="grid gap-3">
                                <label className="grid gap-1 text-sm font-semibold">
                                    Apply oils to
                                    <Select
                                        onChange={(opt) =>
                                            setHolyOilMode(
                                                (opt?.value ??
                                                    "selected") as HolyOilMode,
                                            )
                                        }
                                        options={holyOilModeOptions}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={batchCraftingSelectStyles}
                                        menuPortalTarget={document.body}
                                        value={selectedHolyOilModeOption}
                                    />
                                </label>

                                {holyOilMode === "set" ? (
                                    <div className="grid gap-3">
                                        <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                            All eligible items in the selected
                                            set will have the chosen holy oils
                                            applied until every eligible stack
                                            is filled or the selected oils run
                                            out.
                                        </p>
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Target set
                                            <Select
                                                isSearchable={true}
                                                onChange={(opt) =>
                                                    setSelectedSetId(
                                                        opt?.set_id ?? null,
                                                    )
                                                }
                                                isLoading={inventorySetsLoading}
                                                noOptionsMessage={() =>
                                                    inventorySetsLoading
                                                        ? "Loading..."
                                                        : "No options"
                                                }
                                                getOptionValue={(option) =>
                                                    String(option.set_id)
                                                }
                                                getOptionLabel={(option) =>
                                                    option.label
                                                }
                                                options={inventorySets}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={
                                                    batchCraftingSelectStyles
                                                }
                                                menuPortalTarget={document.body}
                                                value={
                                                    selectedInventorySetOption
                                                }
                                            />
                                        </label>
                                    </div>
                                ) : null}

                                <div>
                                    {holyOilMode ===
                                    "set" ? null : holyOilsLoading ? (
                                        <p
                                            className="text-sm text-gray-500"
                                            role="status"
                                            aria-live="polite"
                                        >
                                            Loading...
                                        </p>
                                    ) : holyOilItems.length === 0 ? (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            No items available for holy oils.
                                        </p>
                                    ) : (
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Items to apply holy oils to
                                            <Select
                                                isMulti
                                                isSearchable={true}
                                                onChange={(options) => {
                                                    const values = options.map(
                                                        (option) =>
                                                            option.value,
                                                    );

                                                    if (
                                                        values.includes(
                                                            selectAllHolyOilItemsValue,
                                                        )
                                                    ) {
                                                        setSelectedItems(
                                                            holyOilItems.map(
                                                                (slot) =>
                                                                    slot.id,
                                                            ),
                                                        );

                                                        return;
                                                    }

                                                    setSelectedItems(values);
                                                }}
                                                options={[
                                                    {
                                                        value: selectAllHolyOilItemsValue,
                                                        label: "Select all",
                                                    },
                                                    ...holyOilItems.map(
                                                        (slot) => ({
                                                            value: slot.id,
                                                            label: `${slot.item.name} (${slot.item.holy_stacks_applied}/${slot.item.holy_stacks} stacks)`,
                                                        }),
                                                    ),
                                                ]}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={
                                                    batchCraftingSelectStyles
                                                }
                                                menuPortalTarget={document.body}
                                                value={holyOilItems
                                                    .filter((slot) =>
                                                        selectedItems.includes(
                                                            slot.id,
                                                        ),
                                                    )
                                                    .map((slot) => ({
                                                        value: slot.id,
                                                        label: `${slot.item.name} (${slot.item.holy_stacks_applied}/${slot.item.holy_stacks} stacks)`,
                                                    }))}
                                            />
                                        </label>
                                    )}
                                </div>

                                <div>
                                    {holyOilsLoading ? (
                                        <p
                                            className="text-sm text-gray-500"
                                            role="status"
                                            aria-live="polite"
                                        >
                                            Loading...
                                        </p>
                                    ) : holyOilOptions.length === 0 ? (
                                        <WarningAlert additional_css="my-2">
                                            No holy oils available.
                                        </WarningAlert>
                                    ) : (
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Holy oils to use
                                            <Select
                                                isMulti
                                                isSearchable={true}
                                                onChange={(options) =>
                                                    setSelectedOils(
                                                        options.map(
                                                            (option) =>
                                                                option.value,
                                                        ),
                                                    )
                                                }
                                                options={holyOilOptions.map(
                                                    (slot) => ({
                                                        value: slot.id,
                                                        label: `${slot.item.name} (x${slot.amount})`,
                                                    }),
                                                )}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={
                                                    batchCraftingSelectStyles
                                                }
                                                menuPortalTarget={document.body}
                                                value={holyOilOptions
                                                    .filter((slot) =>
                                                        selectedOils.includes(
                                                            slot.id,
                                                        ),
                                                    )
                                                    .map((slot) => ({
                                                        value: slot.id,
                                                        label: `${slot.item.name} (x${slot.amount})`,
                                                    }))}
                                            />
                                        </label>
                                    )}
                                </div>

                                {holyOilMode === "selected"
                                    ? this.renderHolyOilsSelectedPreview()
                                    : this.renderHolyOilsSetPreview()}
                            </div>
                        ) : null}

                        {this.renderCostBreakdown(
                            selectedSetNotEmpty || hasBlockingTargetSetBlocker,
                        )}

                        {this.renderListingPricePanel()}

                        {message !== "" ? (
                            <p className="text-sm text-red-700 dark:text-red-300">
                                {message}
                            </p>
                        ) : null}

                        {isSaving ? (
                            <InfoAlert additional_css="my-2">
                                Starting Batch Crafting...
                            </InfoAlert>
                        ) : null}

                        {this.renderStartBlockers(
                            disabledReasons.filter(
                                (blocker) =>
                                    blocker.code !== "start_request_pending",
                            ),
                        )}

                        <div className="flex flex-col items-center justify-center gap-2 md:flex-row">
                            <PrimaryButton
                                button_label={"Start Batch"}
                                on_click={startBatch}
                                disabled={startDisabled}
                                additional_css={"w-full md:w-auto"}
                            />
                            <DangerButton
                                button_label={"Close"}
                                on_click={remove_crafting}
                                additional_css={"w-full md:w-auto"}
                                disabled={isSaving}
                            />
                            <a
                                href="/information/batch-crafting"
                                target="_blank"
                                rel="noreferrer"
                                className="block text-center md:ml-2"
                            >
                                Help{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                ) : null}
                {this.state.craftEnchantSetItemDetailsModalItem ? (
                    <Dialogue
                        is_open={true}
                        handle_close={() =>
                            this.setState({
                                craftEnchantSetItemDetailsModalItem: null,
                            })
                        }
                        title={
                            <ItemNameColorationText
                                custom_width={false}
                                item={
                                    this.state
                                        .craftEnchantSetItemDetailsModalItem
                                }
                            />
                        }
                        large_modal={true}
                    >
                        <ItemDetails
                            item={
                                this.state.craftEnchantSetItemDetailsModalItem
                            }
                            character_id={this.props.character_id}
                        />
                    </Dialogue>
                ) : null}
            </section>
        );
    }
}
