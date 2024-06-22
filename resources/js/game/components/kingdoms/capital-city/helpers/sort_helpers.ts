import { CompressedData } from "../deffinitions/kingdom_building_data";

export const sortByKingdomName = (
    kingdomData: CompressedData[],
    order = "asc",
): CompressedData[] => {
    return kingdomData.sort((a, b) => {
        if (order === "asc") {
            return a.kingdom_name.localeCompare(b.kingdom_name);
        } else {
            return b.kingdom_name.localeCompare(a.kingdom_name);
        }
    });
};

export const sortByBuildingName = (
    kingdomData: CompressedData[],
    order = "asc",
): CompressedData[] => {
    return kingdomData.sort((a, b) => {
        if (order === "asc") {
            return a.building_name.localeCompare(b.building_name);
        } else {
            return b.building_name.localeCompare(a.building_name);
        }
    });
};

export const sortByBuildingLevel = (
    kingdomData: CompressedData[],
    order = "asc",
): CompressedData[] => {
    return kingdomData.sort((a, b) => {
        if (order === "asc") {
            return a.level - b.level;
        } else {
            return b.level - a.level;
        }
    });
};
