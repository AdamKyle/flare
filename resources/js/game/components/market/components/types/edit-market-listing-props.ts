import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketListingDefinition from '../../api/definitions/market-listing-definition';

export default interface EditMarketListingProps {
  listing: MarketListingDefinition;
  is_processing: boolean;
  error: AxiosErrorDefinition | null;
  on_save: (listedPrice: number) => void;
  on_delist: () => void;
  on_close: () => void;
}
