export var craftingGetEndPoints = function (type, characterId) {
    if (type === null) {
        return "";
    }
    switch (type) {
        case "craft":
            return "crafting/" + characterId;
        case "enchant":
            return "enchanting/" + characterId;
        case "alchemy":
            return "alchemy/" + characterId;
        case "trinketry":
            return "trinket-crafting/" + characterId;
        case "workbench":
            return "character/" + characterId + "/inventory/smiths-workbench";
        default:
            return "";
    }
};
export var craftingPostEndPoints = function (type, characterId) {
    if (type === null) {
        return "";
    }
    switch (type) {
        case "craft":
            return "craft/" + characterId;
        case "enchant":
            return "enchant/" + characterId;
        case "alchemy":
            return "transmute/" + characterId;
        case "trinketry":
            return "trinket-craft/" + characterId;
        case "workbench":
            return "character/" + characterId + "/smithy-workbench/apply";
        default:
            return "";
    }
};
//# sourceMappingURL=crafting-type-url.js.map
