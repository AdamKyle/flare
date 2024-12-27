import { EquipmentTypes } from "../enums/equipment-types";
import { match } from "ts-pattern";

export const borderStyles = (equipmentType: EquipmentTypes): string => {
    return match(equipmentType)
        .with(EquipmentTypes.NORMAL, () => "")
        .with(EquipmentTypes.ONE_ENCHANT, () => "border-blue-500")
        .with(
            EquipmentTypes.TWO_ENCHANTS,
            () => "border-fuchsia-800 dark:border-fuchsia-300",
        )
        .with(EquipmentTypes.HOLY, () => "border-sky-700 dark:border-sky-300")
        .with(
            EquipmentTypes.UNIQUE,
            () => "border-green-700 dark:border-green-600",
        )
        .with(
            EquipmentTypes.MYTHICAL,
            () => "border-amber-600 dark:border-amber-500",
        )
        .with(
            EquipmentTypes.COSMIC,
            () => "border-cosmic-colors-700 dark:border-cosmic-colors-60",
        )
        .otherwise(() => "");
};
