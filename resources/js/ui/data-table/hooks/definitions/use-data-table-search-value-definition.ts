import { Dispatch, SetStateAction } from 'react';

export default interface UseDataTableSearchValueDefinition {
  search_input_value: string;
  set_search_input_value: Dispatch<SetStateAction<string>>;
}
