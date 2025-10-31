import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { TraversalApiUrls } from '../enums/traversal-api-urls';
import UseTraverseMapsApiDefinition from './deffinitions/use-traverse-maps-api-definition';
import UseTraverseMapsApiParamsDefinition from './deffinitions/use-traverse-maps-api-params-definition';
import UseTraverseMapsApiResponse from './deffinitions/use-traverse-maps-api-response';
import UseTraverseMapsRequestParamsDefinition from './deffinitions/use-traverse-maps-request-params-definition';
import { useCloseSidePeekEmitter } from '../../../../base/hooks/use-close-side-peek-emitter';
import { useEmitMapRefresh } from '../../hooks/use-emit-map-refresh';

const UseTraverseMapsApi = ({
  character_id,
}: UseTraverseMapsApiParamsDefinition): UseTraverseMapsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { emitShouldRefreshMap } = useEmitMapRefresh();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const [data, setData] = useState<UseTraverseMapsApiResponse | null>(null);
  const [error, setError] =
    useState<UseTraverseMapsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UseTraverseMapsRequestParamsDefinition>({
      map_id: 0,
    });

  const url = getUrl(TraversalApiUrls.TRAVERSE, { character: character_id });

  const traversePlayer = useCallback(async () => {
    if (requestParams.map_id === 0) {
      return;
    }

    setLoading(true);

    try {
      const result = await apiHandler.post<
        UseTraverseMapsApiResponse,
        AxiosRequestConfig<UseTraverseMapsApiResponse>,
        UseTraverseMapsRequestParamsDefinition
      >(url, {
        map_id: requestParams.map_id,
      });

      setData(result);

      emitShouldRefreshMap(true);

      closeSidePeek();
    } catch (error) {
      if (error instanceof AxiosError) {
        setError(error.response?.data);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url, requestParams]);

  useEffect(() => {
    traversePlayer().catch(() => {});
  }, [traversePlayer, url, requestParams]);

  return {
    data,
    loading,
    error,
    setRequestParams,
  };
};
export default UseTraverseMapsApi;
