import { AllowedFilters } from "../deffinitions/allowed-filter-types";
import { CharacterOnlineData } from "../deffinitions/character-online-data";

export interface CharactersOnlineListState {
    characters_online_data: CharacterOnlineData[];
    loading: boolean;
    filter_type: AllowedFilters;
    error_message: string;
  }
