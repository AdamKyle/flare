import SelectOption from "../types/select-option";

export const buildItemTableOptions = (): SelectOption[] => {
    return [
        {
            label: "Please select",
            value: "",
        },
        {
            label: "Crafting",
            value: "crafting",
        },
        {
            label: "Hell Forged",
            value: "hell-forged",
        },
        {
            label: "Purgatory Chains",
            value: "purgatory-chains",
        },
        {
            label: "Labyrinth Cloth",
            value: "labyrinth-cloth",
        },
        {
            label: "Pirate Lord Leather",
            value: "pirate-lord-leather",
        },
        {
            label: "Corrupted Ice",
            value: "corrupted-ice",
        },
        {
            label: "Twisted Earth",
            value: "twisted-earth",
        },
        {
            label: "Delusional Silver",
            value: "delusional-silver",
        },
        {
            label: "Faithless Plate",
            value: "faithless-plate",
        },
    ];
};
