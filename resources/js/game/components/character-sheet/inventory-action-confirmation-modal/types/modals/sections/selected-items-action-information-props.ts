import SetDetails from "../../../../../../lib/game/character-sheet/types/inventory/set-details";

export interface SelectedItemsActionInformationProps {
    item_names: string[] | [];

    usable_sets?: SetDetails[] | [];

    update_api_params?: (params: any) => void;
}
