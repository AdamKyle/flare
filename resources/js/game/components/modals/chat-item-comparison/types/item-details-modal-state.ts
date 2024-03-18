import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";

export interface UsableSets {
    index: number;
    id: number;
    name: string;
    equipped: boolean;
}

export interface ChatItemComparisonDialogueSecondaryActions {
    secondary_button_disabled: boolean;
    secondary_button_label: string;
    handle_action: (args: any) => void;
}

export interface ItemDetailsModalState {
    comparison_details: ComparisonData | null;
    usable_sets: UsableSets[] | [];
    action_loading: boolean;
    loading: boolean;
    dark_charts: boolean;
    error_message: string | null;
    is_showing_expanded_details: boolean;
    secondary_actions: ChatItemComparisonDialogueSecondaryActions | null
}
