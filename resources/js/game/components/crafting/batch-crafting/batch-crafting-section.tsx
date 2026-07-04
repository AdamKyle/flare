import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select from "react-select";
import { craftingGetEndPoints } from "../general-crafting/helpers/crafting-type-url";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import { BatchCraftingStatus } from "./batch-crafting-status-display";
import BatchCraftingSectionProps from "./types/batch-crafting-section-props";
import BatchCraftingSectionState from "./types/batch-crafting-section-state";
import { formatNumber } from "../../../lib/game/format-number";
import {
    AlchemyMode,
    BatchCraftingItemPreviewSnapshot,
    BatchType,
    CraftableItem,
    CraftCategory,
    CraftMode,
    Disposition,
    EnchantMode,
    HolyOilMode,
    HolyOilPreviewItemEntry,
    InventorySetOption,
} from "./types/batch-crafting-types";

const batchTypes: { value: BatchType; label: string }[] = [
    { value: "craft", label: "Craft" },
    { value: "craft_and_enchant", label: "Craft and Enchant" },
    { value: "enchant", label: "Enchant For Event" },
    { value: "alchemy", label: "Alchemy" },
    { value: "holy_oils", label: "Holy Oils" },
    { value: "trinketry", label: "Trinketry" },
];

const dispositions: { value: Disposition; label: string }[] = [
    { value: "keep", label: "Keep" },
    { value: "keep_highest", label: "Keep Highest Level Crafted At The End" },
    { value: "sell", label: "Sell" },
    { value: "destroy", label: "Destroy" },
    { value: "list", label: "List" },
    { value: "disenchant", label: "Disenchant" },
    { value: "keep_best_sell_rest", label: "Keep Best and Sell Rest" },
    {
        value: "keep_best_disenchant_rest",
        label: "Keep Best and Disenchant Rest",
    },
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
    category: "weapon" | "armour" | "ring" | "spell";
    label: string;
};

const craftEnchantSetPlanItems: CraftEnchantSetPlanItem[] = [
    ...weaponTypeOptions.map((option) => ({
        key: option.value,
        category: "weapon" as const,
        label: option.label,
    })),
    ...armourTypeOptions.map((option) => ({
        key: option.value,
        category: "armour" as const,
        label: option.label,
    })),
    { key: "ring_0", category: "ring" as const, label: "Ring 1" },
    { key: "ring_1", category: "ring" as const, label: "Ring 2" },
    { key: "spell-damage", category: "spell" as const, label: "Spell Damage" },
    {
        key: "spell-healing",
        category: "spell" as const,
        label: "Spell Healing",
    },
];

type CraftModeOption = { value: CraftMode; label: string };
type EnchantModeOption = { value: EnchantMode; label: string };
type AlchemyModeOption = { value: AlchemyMode; label: string };

function canList(batchType: BatchType): boolean {
    return ["craft_and_enchant", "alchemy"].includes(batchType);
}

function canDisenchant(batchType: BatchType): boolean {
    return batchType === "craft_and_enchant";
}

function canKeepBestRest(batchType: BatchType): boolean {
    return batchType === "craft_and_enchant";
}

export default class BatchCraftingSection extends React.Component<
    BatchCraftingSectionProps,
    BatchCraftingSectionState
> {
    public constructor(props: BatchCraftingSectionProps) {
        super(props);

        this.state = {
            status: null,
            batchType: "craft",
            disposition: "keep",
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
            alchemyItems: [],
            selectedAlchemyItemId: null,
            enchantments: [],
            selectedPrefixId: null,
            selectedSuffixId: null,
            enchantMode: "event",
            inventorySets: [],
            inventorySetsLoading: false,
            selectedSetId: null,
            craftEnchantSetPlan: {},
            craftEnchantSetBulkPrefixId: null,
            craftEnchantSetBulkSuffixId: null,
            preview: null,
            previewLoading: false,
            previewError: null,
        };
    }

    componentDidMount() {
        this.fetchStatus();
        this.listenForStatusUpdates();
        this.syncDependentState();
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
            previousState.selectedItems !== this.state.selectedItems ||
            previousState.selectedOils !== this.state.selectedOils
        ) {
            this.fetchPreview();
        }
    }

    getAvailableBatchTypes(status: BatchCraftingStatus | null) {
        const trinketryMaxed = status?.event_batch?.trinketry_maxed ?? false;
        const canEnchantForEvent =
            status?.event_batch?.can_enchant_for_event ?? false;

        return batchTypes.filter((option) => {
            if (option.value === "trinketry") {
                return !trinketryMaxed;
            }

            if (option.value === "enchant") {
                return canEnchantForEvent;
            }

            return true;
        });
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
            this.getCraftExperienceOptions(status, batchType).length > 0;
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
        window.Echo?.leave(
            "batch-crafting-status-updated-" + this.props.user_id,
        );
    }

    listenForStatusUpdates() {
        const channelName =
            "batch-crafting-status-updated-" + this.props.user_id;
        const channel = window.Echo?.private(channelName);

        channel?.listen(".batch-crafting.status.updated", () => {
            this.fetchStatus();
        });
    }

    fetchStatus() {
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/status`)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) =>
                    this.setState({
                        status: response.data,
                    }),
                (_error: AxiosError) =>
                    this.setState({
                        message: "Could not load batch crafting status.",
                    }),
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
                        }));

                    const selectedSetId = options.some(
                        (option) => option.set_id === this.state.selectedSetId,
                    )
                        ? this.state.selectedSetId
                        : (options[0]?.set_id ?? null);

                    this.setState({
                        inventorySets: options,
                        inventorySetsLoading: false,
                        selectedSetId,
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

        if (disposition === "list" && !canList(batchType)) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (disposition === "disenchant" && !canDisenchant(batchType)) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (
            ["keep_best_sell_rest", "keep_best_disenchant_rest"].includes(
                disposition,
            ) &&
            !canKeepBestRest(batchType)
        ) {
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
                this.state.craftEnchantSetBulkSuffixId !== null
            ) {
                this.setState({
                    craftEnchantSetPlan: {},
                    craftEnchantSetBulkPrefixId: null,
                    craftEnchantSetBulkSuffixId: null,
                });
            }
        } else if (Object.keys(this.state.craftEnchantSetPlan).length === 0) {
            this.setState({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        { prefixAffixId: null, suffixAffixId: null },
                    ]),
                ),
            });
        }

        const needsCraftSet =
            batchType === "craft" && craftMode === "craft_set";
        const needsCraftEnchantSet =
            batchType === "craft_and_enchant" &&
            craftMode === "craft_enchant_set";
        const needsHolyOilsSet =
            batchType === "holy_oils" && holyOilMode === "set";
        const needsInventorySet =
            needsCraftSet || needsCraftEnchantSet || needsHolyOilsSet;

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
    }

    fetchCraftableItems() {
        const { armourType, craftCategory, weaponType } = this.state;
        const craftingType =
            craftCategory === "weapon"
                ? weaponType
                : craftCategory === "armour"
                  ? "armour"
                  : craftCategory;

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
                    this.setState({
                        craftableItems: items,
                        specificItemId: items[0]?.id ?? null,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        craftableItems: [],
                        specificItemId: null,
                    });
                },
            );
    }

    fetchAlchemyItems() {
        new Ajax()
            .setRoute(craftingGetEndPoints("alchemy", this.props.character_id))
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items = response.data.items ?? [];
                    this.setState({
                        alchemyItems: items,
                        selectedAlchemyItemId: items[0]?.id ?? null,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        alchemyItems: [],
                        selectedAlchemyItemId: null,
                    });
                },
            );
    }

    fetchEnchantments() {
        new Ajax().setRoute(`enchanting/${this.props.character_id}`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                const affixes = response.data.affixes?.affixes ?? [];
                this.setState({
                    enchantments: affixes,
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                });
            },
            (_error: AxiosError) => {
                this.setState({
                    enchantments: [],
                    selectedPrefixId: null,
                    selectedSuffixId: null,
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
            craftEnchantSetPlan,
            disposition,
            holyOilMode,
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
                progress.selected_set_id = selectedSetId;
            } else if (craftModeForRequest === "craft_enchant_set") {
                progress.selected_set_id = selectedSetId;
                progress.enchant_plan = Object.fromEntries(
                    Object.entries(craftEnchantSetPlan).map(([key, entry]) => [
                        key,
                        {
                            prefix_affix_id: entry.prefixAffixId,
                            suffix_affix_id: entry.suffixAffixId,
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

        if (batchType === "holy_oils") {
            params.selected_items = holyOilMode === "set" ? [] : selectedItems;
            params.selected_oils = selectedOils;
        }

        return params;
    }

    fetchPreview() {
        const { batchType, craftMode, holyOilMode } = this.state;
        const previewableModes =
            (batchType === "craft" || batchType === "craft_and_enchant") &&
            craftMode === "specific_item"
                ? true
                : batchType === "alchemy" &&
                    this.getResolvedAlchemyMode(
                        this.state.status,
                        this.state.alchemyMode,
                    ) === "amount"
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

        if (
            (batchType === "holy_oils" &&
                holyOilMode === "selected" &&
                this.state.selectedItems.length === 0) ||
            (batchType === "holy_oils" &&
                this.state.selectedOils.length === 0) ||
            (batchType === "holy_oils" &&
                holyOilMode === "set" &&
                this.state.selectedSetId === null)
        ) {
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
                    this.setState({
                        preview: response.data,
                        previewLoading: false,
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
                    this.fetchStatus();
                    this.props.remove_crafting();
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
                (_error: AxiosError) =>
                    this.setState({
                        isSaving: false,
                    }),
            );
    }

    dismissPanel() {
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => this.fetchStatus(),
                (_error: AxiosError) => {},
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

    renderCraftEnchantSetPlanner() {
        const {
            craftEnchantSetBulkPrefixId,
            craftEnchantSetBulkSuffixId,
            craftEnchantSetPlan,
            enchantments,
        } = this.state;

        const setPrefixAffixId = (key: string, prefixAffixId: number | null) =>
            this.setState({
                craftEnchantSetPlan: {
                    ...this.state.craftEnchantSetPlan,
                    [key]: {
                        ...this.state.craftEnchantSetPlan[key],
                        prefixAffixId,
                    },
                },
            });
        const setSuffixAffixId = (key: string, suffixAffixId: number | null) =>
            this.setState({
                craftEnchantSetPlan: {
                    ...this.state.craftEnchantSetPlan,
                    [key]: {
                        ...this.state.craftEnchantSetPlan[key],
                        suffixAffixId,
                    },
                },
            });
        const setBulkPrefixId = (bulkPrefixAffixId: number | null) =>
            this.setState({ craftEnchantSetBulkPrefixId: bulkPrefixAffixId });
        const setBulkSuffixId = (bulkSuffixAffixId: number | null) =>
            this.setState({ craftEnchantSetBulkSuffixId: bulkSuffixAffixId });
        const applyBulkPrefixToAll = () => {
            if (craftEnchantSetBulkPrefixId === null) {
                return;
            }

            this.setState({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        {
                            ...this.state.craftEnchantSetPlan[item.key],
                            prefixAffixId: craftEnchantSetBulkPrefixId,
                        },
                    ]),
                ),
            });
        };
        const applyBulkSuffixToAll = () => {
            if (craftEnchantSetBulkSuffixId === null) {
                return;
            }

            this.setState({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        {
                            ...this.state.craftEnchantSetPlan[item.key],
                            suffixAffixId: craftEnchantSetBulkSuffixId,
                        },
                    ]),
                ),
            });
        };
        const clearAllEnchants = () =>
            this.setState({
                craftEnchantSetPlan: Object.fromEntries(
                    craftEnchantSetPlanItems.map((item) => [
                        item.key,
                        { prefixAffixId: null, suffixAffixId: null },
                    ]),
                ),
            });

        const prefixOptions = enchantments
            .filter((enchantment) => enchantment.type === "prefix")
            .map((enchantment) => ({
                value: enchantment.id,
                label: enchantment.name,
            }));
        const suffixOptions = enchantments
            .filter((enchantment) => enchantment.type === "suffix")
            .map((enchantment) => ({
                value: enchantment.id,
                label: enchantment.name,
            }));

        const categories: {
            category: CraftEnchantSetPlanItem["category"];
            label: string;
        }[] = [
            { category: "weapon", label: "Weapons" },
            { category: "armour", label: "Armour" },
            { category: "ring", label: "Rings" },
            { category: "spell", label: "Spells" },
        ];

        return (
            <div className="grid gap-3">
                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    Craft and Enchant Set crafts the highest craftable version
                    of a full set, then applies the prefix and suffix you choose
                    for each item, placing each completed piece directly into
                    the inventory set you choose below.
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                    <label className="grid gap-1 text-sm font-semibold">
                        Bulk prefix enchant
                        <Select
                            isClearable
                            onChange={(opt) =>
                                setBulkPrefixId(opt?.value ?? null)
                            }
                            options={prefixOptions}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
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
                    <PrimaryButton
                        button_label={"Apply Selected Prefix To All"}
                        on_click={applyBulkPrefixToAll}
                        disabled={craftEnchantSetBulkPrefixId === null}
                        additional_css={"w-full self-end"}
                    />
                    <label className="grid gap-1 text-sm font-semibold">
                        Bulk suffix enchant
                        <Select
                            isClearable
                            onChange={(opt) =>
                                setBulkSuffixId(opt?.value ?? null)
                            }
                            options={suffixOptions}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
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
                    <PrimaryButton
                        button_label={"Apply Selected Suffix To All"}
                        on_click={applyBulkSuffixToAll}
                        disabled={craftEnchantSetBulkSuffixId === null}
                        additional_css={"w-full self-end"}
                    />
                </div>
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

                        return (
                            entry?.prefixAffixId !== null &&
                            entry?.prefixAffixId !== undefined &&
                            entry?.suffixAffixId !== null &&
                            entry?.suffixAffixId !== undefined
                        );
                    }).length;

                    return (
                        <details
                            key={category}
                            className="rounded border border-gray-300 dark:border-gray-600"
                            open={category === "weapon"}
                        >
                            <summary className="cursor-pointer p-2 text-sm font-semibold">
                                {label} ({configuredCount}/
                                {itemsInCategory.length} configured)
                            </summary>
                            <div className="grid gap-2 p-2">
                                {itemsInCategory.map((item) => {
                                    const entry = craftEnchantSetPlan[
                                        item.key
                                    ] ?? {
                                        prefixAffixId: null,
                                        suffixAffixId: null,
                                    };
                                    const prefixLabel =
                                        prefixOptions.find(
                                            (option) =>
                                                option.value ===
                                                entry.prefixAffixId,
                                        )?.label ?? "None selected";
                                    const suffixLabel =
                                        suffixOptions.find(
                                            (option) =>
                                                option.value ===
                                                entry.suffixAffixId,
                                        )?.label ?? "None selected";
                                    const planStatus =
                                        entry.prefixAffixId !== null &&
                                        entry.suffixAffixId !== null
                                            ? "Double enchant selected"
                                            : "Missing enchants";

                                    return (
                                        <details
                                            key={item.key}
                                            className="rounded border border-gray-200 dark:border-gray-700"
                                        >
                                            <summary className="cursor-pointer p-2 text-sm">
                                                <dl className="grid gap-x-3 gap-y-1 sm:grid-cols-4 sm:items-center">
                                                    <div>
                                                        <dt className="sr-only">
                                                            Item
                                                        </dt>
                                                        <dd className="font-semibold">
                                                            {item.label}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt className="text-xs text-gray-500 dark:text-gray-400">
                                                            Prefix
                                                        </dt>
                                                        <dd>{prefixLabel}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="text-xs text-gray-500 dark:text-gray-400">
                                                            Suffix
                                                        </dt>
                                                        <dd>{suffixLabel}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="text-xs text-gray-500 dark:text-gray-400">
                                                            Status
                                                        </dt>
                                                        <dd>{planStatus}</dd>
                                                    </div>
                                                </dl>
                                            </summary>
                                            <div className="grid gap-2 p-2 sm:grid-cols-2">
                                                <label className="grid gap-1 text-sm font-semibold">
                                                    Prefix enchant
                                                    <Select
                                                        isClearable
                                                        onChange={(opt) =>
                                                            setPrefixAffixId(
                                                                item.key,
                                                                opt?.value ??
                                                                    null,
                                                            )
                                                        }
                                                        options={prefixOptions}
                                                        menuPosition={
                                                            "absolute"
                                                        }
                                                        menuPlacement={"bottom"}
                                                        styles={{
                                                            menuPortal: (
                                                                base,
                                                            ) => ({
                                                                ...base,
                                                                zIndex: 9999,
                                                                color: "#000000",
                                                            }),
                                                        }}
                                                        menuPortalTarget={
                                                            document.body
                                                        }
                                                        value={
                                                            prefixOptions.find(
                                                                (option) =>
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
                                                        onChange={(opt) =>
                                                            setSuffixAffixId(
                                                                item.key,
                                                                opt?.value ??
                                                                    null,
                                                            )
                                                        }
                                                        options={suffixOptions}
                                                        menuPosition={
                                                            "absolute"
                                                        }
                                                        menuPlacement={"bottom"}
                                                        styles={{
                                                            menuPortal: (
                                                                base,
                                                            ) => ({
                                                                ...base,
                                                                zIndex: 9999,
                                                                color: "#000000",
                                                            }),
                                                        }}
                                                        menuPortalTarget={
                                                            document.body
                                                        }
                                                        value={
                                                            suffixOptions.find(
                                                                (option) =>
                                                                    option.value ===
                                                                    entry.suffixAffixId,
                                                            ) ?? null
                                                        }
                                                    />
                                                </label>
                                            </div>
                                        </details>
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
        );
    }

    renderPreviewStatus() {
        const { previewLoading, previewError } = this.state;

        if (previewLoading) {
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
            return <WarningAlert>{previewError}</WarningAlert>;
        }

        return null;
    }

    renderCraftAmountPreview() {
        const preview = this.state.preview?.amount_preview;
        const statusBlock = this.renderPreviewStatus();

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return (
                <InfoAlert additional_css="text-sm">
                    Select an item and an amount to see cost and destination
                    details before starting.
                </InfoAlert>
            );
        }

        return (
            <div className="grid gap-3">
                <InfoAlert additional_css="text-sm">
                    Kept output for this batch is moved into the Crafted Items
                    Set, not your normal inventory. You can sell or disenchant
                    items out of that set later.
                </InfoAlert>
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Selected Item</dt>
                        <dd>{this.renderPreviewItem(preview.selected_item)}</dd>
                    </div>
                    {preview.prefix_affix_name ? (
                        <div>
                            <dt className="font-semibold">Prefix</dt>
                            <dd>{preview.prefix_affix_name}</dd>
                        </div>
                    ) : null}
                    {preview.suffix_affix_name ? (
                        <div>
                            <dt className="font-semibold">Suffix</dt>
                            <dd>{preview.suffix_affix_name}</dd>
                        </div>
                    ) : null}
                    <div>
                        <dt className="font-semibold">Requested Amount</dt>
                        <dd>{formatNumber(preview.requested_amount)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Destination</dt>
                        <dd>Crafted Items Set</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Per Item Cost</dt>
                        <dd>{formatNumber(preview.total_per_item_cost)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Requested Cost</dt>
                        <dd>{formatNumber(preview.total_cost)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Available Gold</dt>
                        <dd>{formatNumber(preview.available_gold)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Crafted Items Set Space
                        </dt>
                        <dd>
                            {formatNumber(preview.destination_current_slots)} /{" "}
                            {formatNumber(preview.destination_max_slots)} (
                            {formatNumber(preview.destination_remaining_slots)}{" "}
                            remaining)
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Effective Craftable Amount
                        </dt>
                        <dd>
                            {formatNumber(preview.effective_craftable_amount)}{" "}
                            of{" "}
                            {formatNumber(preview.remaining_requested_amount)}
                        </dd>
                    </div>
                    {preview.enchant_can_destroy_item ? (
                        <div>
                            <dt className="font-semibold">Enchanting Risk</dt>
                            <dd>
                                Enchanting can fail and destroy the item. Gold
                                is still spent even if that happens.
                            </dd>
                        </div>
                    ) : null}
                </dl>
                {preview.effective_craftable_amount === 0 ? (
                    <WarningAlert>
                        This batch cannot complete any items with your current
                        gold and Crafted Items Set space.
                    </WarningAlert>
                ) : preview.capped ? (
                    <WarningAlert>
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current gold and Crafted Items Set space.
                    </WarningAlert>
                ) : null}
                {preview.requested_amount >
                (this.state.status?.inventory_max ?? Infinity) ? (
                    <WarningAlert>
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
        const statusBlock = this.renderPreviewStatus();

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return (
                <InfoAlert additional_css="text-sm">
                    Select an alchemy item and an amount to see cost and
                    capacity details before starting.
                </InfoAlert>
            );
        }

        return (
            <div className="grid gap-3">
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Selected Item</dt>
                        <dd>{this.renderPreviewItem(preview.selected_item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Requested Amount</dt>
                        <dd>{formatNumber(preview.requested_amount)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Destination</dt>
                        <dd>Alchemy Bag</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Alchemy Bag</dt>
                        <dd>
                            {formatNumber(preview.bag_current)} /{" "}
                            {formatNumber(preview.bag_max)} (
                            {formatNumber(preview.bag_remaining)} remaining)
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Per Item Cost</dt>
                        <dd>
                            {formatNumber(preview.gold_dust_cost_per_item)} gold
                            dust
                            {preview.shards_cost_per_item > 0
                                ? `, ${formatNumber(preview.shards_cost_per_item)} shards`
                                : ""}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Requested Cost</dt>
                        <dd>
                            {formatNumber(preview.total_gold_dust_cost)} gold
                            dust
                            {preview.total_shards_cost > 0
                                ? `, ${formatNumber(preview.total_shards_cost)} shards`
                                : ""}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Available Currency</dt>
                        <dd>
                            {formatNumber(preview.available_gold_dust)} gold
                            dust, {formatNumber(preview.available_shards)}{" "}
                            shards
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Effective Craftable Amount
                        </dt>
                        <dd>
                            {formatNumber(preview.effective_craftable_amount)}{" "}
                            of{" "}
                            {formatNumber(preview.remaining_requested_amount)}
                        </dd>
                    </div>
                </dl>
                {preview.effective_craftable_amount === 0 ? (
                    <WarningAlert>
                        This batch cannot complete any items with your current
                        currency and alchemy bag space.
                    </WarningAlert>
                ) : preview.capped ? (
                    <WarningAlert>
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current currency and alchemy bag space.
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
                    <li key={index}>
                        <dl className="grid grid-cols-2 gap-1 text-xs sm:grid-cols-4">
                            <div>
                                <dt className="font-semibold">Item</dt>
                                <dd>{this.renderPreviewItem(entry.item)}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Stacks</dt>
                                <dd>
                                    {formatNumber(entry.current_stacks)} /{" "}
                                    {formatNumber(entry.max_stacks)}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Remaining</dt>
                                <dd>
                                    {formatNumber(entry.remaining_capacity)}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">
                                    Gold Dust / Application
                                </dt>
                                <dd>
                                    {formatNumber(
                                        entry.gold_dust_cost_per_application,
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </li>
                ))}
            </ul>
        );
    }

    renderHolyOilsSelectedPreview() {
        const preview = this.state.preview?.holy_oil_selected_preview;
        const statusBlock = this.renderPreviewStatus();

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return (
                <InfoAlert additional_css="text-sm">
                    Select at least one item and one oil to see application
                    details before starting.
                </InfoAlert>
            );
        }

        return (
            <div className="grid gap-3">
                {this.renderHolyOilPreviewItemsList(preview.items)}
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">
                            Total Remaining Applications
                        </dt>
                        <dd>
                            {formatNumber(preview.total_remaining_applications)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Selected Oils Available
                        </dt>
                        <dd>{formatNumber(preview.selected_oils_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Available</dt>
                        <dd>{formatNumber(preview.gold_dust_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Max Applications Possible
                        </dt>
                        <dd>
                            {formatNumber(preview.max_applications_possible)}
                        </dd>
                    </div>
                </dl>
                {preview.max_applications_possible === 0 ? (
                    <WarningAlert>
                        There are no valid Holy Oil applications with the
                        current selection.
                    </WarningAlert>
                ) : preview.capped ? (
                    <WarningAlert>
                        This can apply{" "}
                        {formatNumber(preview.max_applications_possible)} of{" "}
                        {formatNumber(preview.total_remaining_applications)}{" "}
                        remaining Holy Oil stacks with your current oils and
                        gold dust.
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderHolyOilsSetPreview() {
        const preview = this.state.preview?.holy_oil_set_preview;
        const statusBlock = this.renderPreviewStatus();

        if (statusBlock) {
            return statusBlock;
        }

        if (!preview) {
            return (
                <InfoAlert additional_css="text-sm">
                    Select a set and at least one oil to see application details
                    before starting.
                </InfoAlert>
            );
        }

        return (
            <div className="grid gap-3">
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Selected Set</dt>
                        <dd>{preview.set_name}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Eligible Items</dt>
                        <dd>{formatNumber(preview.total_eligible_items)}</dd>
                    </div>
                </dl>
                {this.renderHolyOilPreviewItemsList(preview.items)}
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">
                            Total Remaining Applications
                        </dt>
                        <dd>
                            {formatNumber(preview.total_remaining_applications)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Selected Oils Available
                        </dt>
                        <dd>{formatNumber(preview.selected_oils_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Available</dt>
                        <dd>{formatNumber(preview.gold_dust_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Max Applications Possible
                        </dt>
                        <dd>
                            {formatNumber(preview.max_applications_possible)}
                        </dd>
                    </div>
                </dl>
                {preview.max_applications_possible === 0 ? (
                    <WarningAlert>
                        There are no valid Holy Oil applications with the
                        current selection.
                    </WarningAlert>
                ) : preview.capped ? (
                    <WarningAlert>
                        This can apply{" "}
                        {formatNumber(preview.max_applications_possible)} of{" "}
                        {formatNumber(preview.total_remaining_applications)}{" "}
                        remaining Holy Oil stacks with your current oils and
                        gold dust.
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    render() {
        const { character_id, remove_crafting } = this.props;
        const {
            alchemyItems,
            alchemyMode,
            armourType,
            batchType,
            craftAmount,
            craftCategory,
            craftEnchantSetPlan,
            craftMode,
            craftableItems,
            disposition,
            enchantMode,
            enchantments,
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
            weaponType,
        } = this.state;

        const alchemyMaxed = status?.event_batch?.alchemy_maxed ?? false;
        const trinketryMaxed = status?.event_batch?.trinketry_maxed ?? false;

        const setBatchType = (batchType: BatchType) =>
            this.setState({ batchType });
        const setDisposition = (disposition: Disposition) =>
            this.setState({ disposition });
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
        const setEnchantMode = (enchantMode: EnchantMode) =>
            this.setState({ enchantMode });
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
        const canCraftForExperience = craftExperienceOptions.length > 0;
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
        const selectedInventorySetOption =
            inventorySets.find((set) => set.set_id === selectedSetId) ?? null;
        const selectedCraftModeOption =
            craftModeOptions.find((option) => option.value === craftMode) ??
            craftModeOptions[0];
        const selectedCraftMode = (selectedCraftModeOption?.value ??
            "specific_item") as CraftMode;
        const availableDispositions = dispositions.filter(
            (option) =>
                batchType !== "enchant" &&
                (option.value !== "list" || canList(batchType)) &&
                (option.value !== "disenchant" || canDisenchant(batchType)) &&
                (!["keep_best_sell_rest", "keep_best_disenchant_rest"].includes(
                    option.value,
                ) ||
                    canKeepBestRest(batchType)),
        );
        const selectedBatchType = availableBatchTypes.find(
            (option) => option.value === batchType,
        );
        const selectedDisposition = availableDispositions.find(
            (option) => option.value === disposition,
        );
        const isActive = status?.active ?? false;
        const isCompleted = status?.completed ?? false;
        const needsSelectedSet =
            (batchType === "craft" && selectedCraftMode === "craft_set") ||
            (batchType === "craft_and_enchant" &&
                selectedCraftMode === "craft_enchant_set") ||
            (batchType === "holy_oils" && holyOilMode === "set");
        const needsEmptySelectedSet =
            (batchType === "craft" && selectedCraftMode === "craft_set") ||
            (batchType === "craft_and_enchant" &&
                selectedCraftMode === "craft_enchant_set");
        const selectedSetNotEmpty =
            needsEmptySelectedSet &&
            (selectedInventorySetOption?.current_slots ?? 0) > 0;
        const craftEnchantSetPlanIncomplete =
            batchType === "craft_and_enchant" &&
            selectedCraftMode === "craft_enchant_set" &&
            craftEnchantSetPlanItems.some(
                (item) =>
                    craftEnchantSetPlan[item.key]?.prefixAffixId === null ||
                    craftEnchantSetPlan[item.key]?.suffixAffixId === null ||
                    typeof craftEnchantSetPlan[item.key]?.prefixAffixId ===
                        "undefined" ||
                    typeof craftEnchantSetPlan[item.key]?.suffixAffixId ===
                        "undefined",
            );
        const previewBlocksStart =
            preview !== null &&
            !previewLoading &&
            (((batchType === "craft" || batchType === "craft_and_enchant") &&
            selectedCraftMode === "specific_item"
                ? (preview.amount_preview?.effective_craftable_amount ?? 1) ===
                  0
                : false) ||
                (batchType === "alchemy" &&
                selectedAlchemyModeOption?.value === "amount"
                    ? (preview.alchemy_amount_preview
                          ?.effective_craftable_amount ?? 1) === 0
                    : false) ||
                (batchType === "holy_oils" && holyOilMode === "selected"
                    ? (preview.holy_oil_selected_preview
                          ?.max_applications_possible ?? 1) === 0
                    : false) ||
                (batchType === "holy_oils" && holyOilMode === "set"
                    ? (preview.holy_oil_set_preview
                          ?.max_applications_possible ?? 1) === 0
                    : false));
        const startDisabled =
            isSaving ||
            (batchType === "alchemy" &&
                selectedAlchemyModeOption?.value === "experience" &&
                alchemyMaxed) ||
            (batchType === "trinketry" && trinketryMaxed) ||
            (needsSelectedSet && selectedSetId === null) ||
            selectedSetNotEmpty ||
            craftEnchantSetPlanIncomplete ||
            previewBlocksStart;

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

                {isActive || isCompleted ? (
                    <div className="mt-4 flex flex-col items-center justify-center gap-2 md:flex-row">
                        <DangerButton
                            button_label={"Close"}
                            on_click={remove_crafting}
                            additional_css={"w-full md:w-auto"}
                            disabled={isSaving}
                        />
                    </div>
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
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={selectedBatchType}
                            />
                        </label>

                        {batchType !== "holy_oils" &&
                        batchType !== "enchant" ? (
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
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={selectedDisposition}
                                />
                            </label>
                        ) : null}

                        {batchType === "craft" ||
                        batchType === "craft_and_enchant" ? (
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
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={selectedCraftModeOption}
                                />
                            </label>
                        ) : null}

                        {batchType === "craft" && craftingSkillsMaxed ? (
                            <p
                                role="alert"
                                className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100"
                            >
                                Weapon Crafting, Armour Crafting, Ring Crafting,
                                and Spell Crafting are all maxed, so this
                                character cannot batch craft for experience.
                            </p>
                        ) : null}

                        {batchType === "alchemy" &&
                        selectedAlchemyModeOption?.value === "experience" &&
                        alchemyMaxed ? (
                            <p
                                role="alert"
                                className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100"
                            >
                                Alchemy is maxed, so this character cannot batch
                                craft alchemy items for experience.
                            </p>
                        ) : null}

                        {batchType === "craft_and_enchant" &&
                        selectedCraftMode === "experience" ? (
                            <div className="grid gap-3">
                                <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                                    Batch Crafting will craft and enchant items
                                    for non-maxed applicable skills. Kept output
                                    is moved to the Crafted Items Set when that
                                    disposition is used.
                                </p>
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
                                    moved to the Crafted Items Set when that
                                    disposition is used.
                                </p>
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
                                    Craft Set crafts the highest craftable
                                    version of one of each weapon type, a full
                                    armour set including a shield, two rings, a
                                    spell damage item, and a spell healing item,
                                    placing each completed piece directly into
                                    the inventory set you choose below.
                                </p>
                                <label className="grid gap-1 text-sm font-semibold">
                                    Target set
                                    <Select
                                        onChange={(opt) =>
                                            setSelectedSetId(
                                                opt?.set_id ?? null,
                                            )
                                        }
                                        isLoading={inventorySetsLoading}
                                        getOptionValue={(option) =>
                                            String(option.set_id)
                                        }
                                        getOptionLabel={(option) =>
                                            option.label
                                        }
                                        options={inventorySets}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
                                        menuPortalTarget={document.body}
                                        value={selectedInventorySetOption}
                                    />
                                </label>
                                {selectedSetNotEmpty ? (
                                    <WarningAlert>
                                        The selected set must be empty before
                                        starting Craft Set. Choose an empty set
                                        or remove the items from this one first.
                                    </WarningAlert>
                                ) : null}
                            </div>
                        ) : null}

                        {batchType === "craft_and_enchant" &&
                        selectedCraftMode === "craft_enchant_set" ? (
                            <div className="grid gap-3">
                                <label className="grid gap-1 text-sm font-semibold">
                                    Target set
                                    <Select
                                        onChange={(opt) =>
                                            setSelectedSetId(
                                                opt?.set_id ?? null,
                                            )
                                        }
                                        isLoading={inventorySetsLoading}
                                        getOptionValue={(option) =>
                                            String(option.set_id)
                                        }
                                        getOptionLabel={(option) =>
                                            option.label
                                        }
                                        options={inventorySets}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
                                        menuPortalTarget={document.body}
                                        value={selectedInventorySetOption}
                                    />
                                </label>
                                {selectedSetNotEmpty ? (
                                    <WarningAlert>
                                        The selected set must be empty before
                                        starting Craft and Enchant Set. Choose
                                        an empty set or remove the items from
                                        this one first.
                                    </WarningAlert>
                                ) : null}
                                {this.renderCraftEnchantSetPlanner()}
                            </div>
                        ) : null}

                        {batchType === "enchant" ? (
                            <label className="grid gap-1 text-sm font-semibold">
                                Enchant mode
                                <Select
                                    onChange={(opt) =>
                                        setEnchantMode(
                                            (opt?.value ??
                                                "event") as EnchantMode,
                                        )
                                    }
                                    options={enchantModeOptions}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={selectedEnchantModeOption}
                                />
                            </label>
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
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
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
                                            onChange={(opt) =>
                                                setWeaponType(
                                                    opt?.value ?? "dagger",
                                                )
                                            }
                                            options={weaponTypeOptions}
                                            menuPosition={"absolute"}
                                            menuPlacement={"bottom"}
                                            styles={{
                                                menuPortal: (base) => ({
                                                    ...base,
                                                    zIndex: 9999,
                                                    color: "#000000",
                                                }),
                                            }}
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
                                            onChange={(opt) =>
                                                setArmourType(
                                                    opt?.value ?? "helmet",
                                                )
                                            }
                                            options={armourTypeOptions}
                                            menuPosition={"absolute"}
                                            menuPlacement={"bottom"}
                                            styles={{
                                                menuPortal: (base) => ({
                                                    ...base,
                                                    zIndex: 9999,
                                                    color: "#000000",
                                                }),
                                            }}
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
                                                <ItemNameColorationText
                                                    item={{
                                                        name: option.label,
                                                        type:
                                                            option.itemType ??
                                                            "item",
                                                        affix_count: 0,
                                                        is_unique: false,
                                                        is_mythic: false,
                                                        is_cosmic: false,
                                                        holy_stacks_applied: 0,
                                                    }}
                                                    custom_width={false}
                                                    additional_css={""}
                                                />
                                                {option.cost ? (
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        Gold Cost: {option.cost}
                                                    </span>
                                                ) : null}
                                            </span>
                                        )}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
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
                                                    .map((enchantment) => ({
                                                        value: enchantment.id,
                                                        label: enchantment.name,
                                                    }))}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={{
                                                    menuPortal: (base) => ({
                                                        ...base,
                                                        zIndex: 9999,
                                                        color: "#000000",
                                                    }),
                                                }}
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
                                                        .map((enchantment) => ({
                                                            value: enchantment.id,
                                                            label: enchantment.name,
                                                        }))[0]
                                                }
                                            />
                                        </label>
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Suffix enchant
                                            <Select
                                                isClearable
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
                                                    .map((enchantment) => ({
                                                        value: enchantment.id,
                                                        label: enchantment.name,
                                                    }))}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={{
                                                    menuPortal: (base) => ({
                                                        ...base,
                                                        zIndex: 9999,
                                                        color: "#000000",
                                                    }),
                                                }}
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
                                                        .map((enchantment) => ({
                                                            value: enchantment.id,
                                                            label: enchantment.name,
                                                        }))[0]
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
                                </label>
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
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
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
                                                <ItemNameColorationText
                                                    item={{
                                                        name: option.label,
                                                        type:
                                                            option.itemType ??
                                                            "alchemy",
                                                        affix_count: 0,
                                                        is_unique: false,
                                                        is_mythic: false,
                                                        is_cosmic: false,
                                                        holy_stacks_applied: 0,
                                                    }}
                                                    custom_width={false}
                                                    additional_css={""}
                                                />
                                                {option.goldDustCost ? (
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        Gold Dust Cost:{" "}
                                                        {option.goldDustCost}
                                                    </span>
                                                ) : null}
                                                {option.shardsCost ? (
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        Shards Cost:{" "}
                                                        {option.shardsCost}
                                                    </span>
                                                ) : null}
                                            </span>
                                        )}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
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
                                </label>
                                {this.renderAlchemyAmountPreview()}
                            </>
                        ) : null}

                        {batchType === "trinketry" && trinketryMaxed ? (
                            <p
                                role="alert"
                                className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100"
                            >
                                Trinketry is maxed, so this character cannot
                                batch craft trinket items for experience.
                            </p>
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
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
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
                                                onChange={(opt) =>
                                                    setSelectedSetId(
                                                        opt?.set_id ?? null,
                                                    )
                                                }
                                                isLoading={inventorySetsLoading}
                                                getOptionValue={(option) =>
                                                    String(option.set_id)
                                                }
                                                getOptionLabel={(option) =>
                                                    option.label
                                                }
                                                options={inventorySets}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={{
                                                    menuPortal: (base) => ({
                                                        ...base,
                                                        zIndex: 9999,
                                                        color: "#000000",
                                                    }),
                                                }}
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
                                                onChange={(options) =>
                                                    setSelectedItems(
                                                        options.map(
                                                            (option) =>
                                                                option.value,
                                                        ),
                                                    )
                                                }
                                                options={holyOilItems.map(
                                                    (slot) => ({
                                                        value: slot.item_id,
                                                        label: `${slot.item.name} (${slot.item.holy_stacks_applied}/${slot.item.holy_stacks} stacks)`,
                                                    }),
                                                )}
                                                menuPosition={"absolute"}
                                                menuPlacement={"bottom"}
                                                styles={{
                                                    menuPortal: (base) => ({
                                                        ...base,
                                                        zIndex: 9999,
                                                        color: "#000000",
                                                    }),
                                                }}
                                                menuPortalTarget={document.body}
                                                value={holyOilItems
                                                    .filter((slot) =>
                                                        selectedItems.includes(
                                                            slot.item_id,
                                                        ),
                                                    )
                                                    .map((slot) => ({
                                                        value: slot.item_id,
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
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            No holy oils available.
                                        </p>
                                    ) : (
                                        <label className="grid gap-1 text-sm font-semibold">
                                            Holy oils to use
                                            <Select
                                                isMulti
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
                                                styles={{
                                                    menuPortal: (base) => ({
                                                        ...base,
                                                        zIndex: 9999,
                                                        color: "#000000",
                                                    }),
                                                }}
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

                        {message !== "" ? (
                            <p className="text-sm text-red-700 dark:text-red-300">
                                {message}
                            </p>
                        ) : null}

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
            </section>
        );
    }
}
