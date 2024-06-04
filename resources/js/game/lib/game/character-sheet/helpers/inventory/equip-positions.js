var EquipPositions = (function () {
    function EquipPositions() {}
    EquipPositions.getAllowedPositions = function (type) {
        switch (type) {
            case "stave":
            case "hammer":
            case "bow":
            case "weapon":
            case "shield":
            case "gun":
            case "fan":
            case "mace":
            case "scratch-awl":
                return ["left-hand", "right-hand"];
            case "ring":
                return ["ring-one", "ring-two"];
            case "spell-damage":
            case "spell-healing":
                return ["spell-one", "spell-two"];
            case "trinket":
            case "armour":
            case "artifact":
            default:
                return null;
        }
    };
    EquipPositions.isTwoHanded = function (type) {
        return ["bow", "stave", "hammer"].includes(type);
    };
    EquipPositions.isArmour = function (type) {
        return [
            "body",
            "leggings",
            "feet",
            "sleeves",
            "helmet",
            "gloves",
        ].includes(type);
    };
    EquipPositions.isArtifact = function (type) {
        return type === "artifact";
    };
    EquipPositions.isTrinket = function (type) {
        return type === "trinket";
    };
    return EquipPositions;
})();
export { EquipPositions };
//# sourceMappingURL=equip-positions.js.map
