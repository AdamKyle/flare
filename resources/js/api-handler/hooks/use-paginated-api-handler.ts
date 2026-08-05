import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { shallowEqual } from 'api-handler/utils/shallow-equal';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

const UsePaginatedApiHandler = <
  T,
  F extends Record<string, unknown> = Record<string, unknown>,
  R = PaginatedApiResponseDefinition<T[]>,
>(
  params: ApiParametersDefinitions,
  perPage = 10
): PaginatedApiHandlerDefinition<T, F, R> => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const enabled = params.enabled !== false;
  const additionalParams = params.additionalParams ?? {};

  const [data, setData] = useState<T[]>([]);
  const [error, setError] =
    useState<PaginatedApiHandlerDefinition<T, F>['error']>(null);
  const [loading, setLoading] = useState(enabled);
  const [canLoadMore, setCanLoadMore] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState<F>({} as F);
  const [refresh, setRefresh] = useState(false);
  const [response, setResponse] = useState<R | null>(null);

  // Stable mirrors of `searchText` / `filters` / the caller-supplied `additionalParams`.
  // `additionalParams` in particular is frequently a fresh object literal on every render, so it
  // cannot be used directly as a dependency without recreating the fetch callback (and therefore
  // refetching) on every render. These mirrors are only updated - via the React-recommended
  // "adjust state while rendering" pattern - when their real content actually changes, which
  // gives the fetch callback stable, honest dependencies and lets page-one resets happen in the
  // same render pass as the value change, before any effect (and therefore any fetch) runs.
  const [trackedSearchText, setTrackedSearchText] = useState(searchText);
  const [trackedFilters, setTrackedFilters] = useState<F>(filters);
  const [trackedAdditionalParams, setTrackedAdditionalParams] =
    useState<Record<string, unknown>>(additionalParams);

  const requestGenerationRef = useRef(0);

  const querySignatureChanged =
    trackedSearchText !== searchText ||
    !shallowEqual(trackedFilters, filters) ||
    !shallowEqual(trackedAdditionalParams, additionalParams);

  if (querySignatureChanged) {
    setTrackedSearchText(searchText);
    setTrackedFilters(filters);
    setTrackedAdditionalParams(additionalParams);
    setData([]);
    requestGenerationRef.current += 1;

    if (page !== 1) {
      setPage(1);
    }
  }

  const fetchPaginatedData = useCallback(async () => {
    if (!enabled) {
      setData([]);
      setError(null);
      setLoading(false);
      setCanLoadMore(false);
      setIsLoadingMore(false);
      setPage(1);
      setResponse(null);

      return;
    }

    // `refresh` carries no value of its own; toggling it via `setRefresh` is how callers force a
    // refetch of the current page/search/filters without changing any of them. Referencing it
    // here keeps it an honest dependency instead of an unused one.
    void refresh;

    const requestGeneration = requestGenerationRef.current;

    if (page > 1) {
      setIsLoadingMore(true);
    } else {
      setLoading(true);
    }

    try {
      const result = await apiHandler.get<
        PaginatedApiResponseDefinition<T[]>,
        AxiosRequestConfig<PaginatedApiResponseDefinition<T[]>>
      >(url, {
        params: {
          per_page: perPage,
          page,
          search_text: trackedSearchText,
          filters: trackedFilters,
          ...trackedAdditionalParams,
        },
      });

      if (requestGenerationRef.current !== requestGeneration) {
        // The search text, filters, or additional params changed while this request was in
        // flight. Its results belong to a stale query and must not be applied.
        return;
      }

      setData((previousData) =>
        page === 1 ? result.data : [...previousData, ...result.data]
      );
      setCanLoadMore(result.meta.can_load_more);
      setResponse(result as unknown as R);
    } catch (errorInstance) {
      if (errorInstance instanceof AxiosError) {
        const axiosResponse = errorInstance.response;

        if (!axiosResponse) {
          return;
        }

        /**
         * If we are not logged in, reload to put them back on the login screen.
         */
        if (axiosResponse.status === 401) {
          window.location.reload();
        }

        setError(errorInstance.response?.data || null);
      } else {
        setError(null);
      }
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
        setIsLoadingMore(false);
      }
    }
  }, [
    apiHandler,
    url,
    page,
    perPage,
    refresh,
    enabled,
    trackedSearchText,
    trackedFilters,
    trackedAdditionalParams,
  ]);

  useEffect(() => {
    fetchPaginatedData().catch(console.error);
  }, [fetchPaginatedData]);

  const onEndReached = () => {
    if (!canLoadMore || isLoadingMore) {
      return;
    }

    setPage((previousValue) => previousValue + 1);
  };

  return {
    data,
    error,
    loading,
    canLoadMore,
    isLoadingMore,
    page,
    response,
    searchText,
    onEndReached,
    setSearchText,
    setFilters,
    setPage,
    setRefresh,
  };
};

export default UsePaginatedApiHandler;
