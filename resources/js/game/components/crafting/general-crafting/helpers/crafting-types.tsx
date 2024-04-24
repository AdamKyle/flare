export const getCraftingType = (type: string): string => {
    if (
        [
            "body",
            "shield",
            "leggings",
            "feet",
            "sleeves",
            "helmet",
            "gloves",
        ].includes(type)
    ) {
        return "armour";
    }

    if (type === "spell-damage" || type === "spell-healing") {
        return "spell";
    }

    return type;
};
