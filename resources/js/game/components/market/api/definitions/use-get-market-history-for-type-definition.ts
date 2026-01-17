import {AxiosErrorDefinition} from "api-handler/definitions/axios-error-definition";
import {StateSetter} from "../../../../../types/state-setter-type";
import UseGetMarketHistoryForTypeRequestParams from "./use-get-market-history-for-type-request-params";
import MarketHistoryForTypeResponseDefinition from "./use-get-market-history-for-type-response-definition";

export default interface UseGetMarketHistoryForTypeDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  data: MarketHistoryForTypeResponseDefinition[] | [];
  setRequestParams: StateSetter<UseGetMarketHistoryForTypeRequestParams>;
}