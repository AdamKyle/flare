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

  const previousSearchTextRef = useRef(searchText);
  const previousFiltersRef = useRef<F>(filters);
  const previousAdditionalParamsRef = useRef<Record<string, unknown>>(
    params.additionalParams ?? {}
  );

  const fetchPaginatedData = useCallback(
    async () => {
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
            search_text: searchText,
            filters,
            ...(params.additionalParams ?? {}),
          },
        });

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
        setLoading(false);
        setIsLoadingMore(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url, page, perPage, refresh, enabled]
  );

  useEffect(() => {
    fetchPaginatedData().catch(console.error);
  }, [fetchPaginatedData]);

  useEffect(() => {
    const isSameSearch = previousSearchTextRef.current === searchText;
    const isSameFilters = shallowEqual(previousFiltersRef.current, filters);
    const isSameAdditionalParams = shallowEqual(
      previousAdditionalParamsRef.current,
      params.additionalParams ?? {}
    );

    if (isSameSearch && isSameFilters && isSameAdditionalParams) {
      return;
    }

    previousSearchTextRef.current = searchText;
    previousFiltersRef.current = filters;
    previousAdditionalParamsRef.current = params.additionalParams ?? {};

    setPage(1);
    setRefresh((previousValue) => !previousValue);
  }, [searchText, filters, params.additionalParams]);

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
    onEndReached,
    setSearchText,
    setFilters,
    setPage,
    setRefresh,
  };
};

export default UsePaginatedApiHandler;
