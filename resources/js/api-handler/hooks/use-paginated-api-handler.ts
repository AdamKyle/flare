import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { shallowEqual } from 'api-handler/utils/shallow-equal';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

const EMPTY_ADDITIONAL_PARAMS: Record<string, unknown> = {};

const UsePaginatedApiHandler = <
  T,
  F extends Record<string, unknown> = Record<string, unknown>,
  R extends PaginatedApiResponseDefinition<T[]> =
    PaginatedApiResponseDefinition<T[]>,
>(
  params: ApiParametersDefinitions<F>,
  perPage = 10
): PaginatedApiHandlerDefinition<T, F, R> => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const enabled = params.enabled !== false;
  const additionalParams = params.additionalParams ?? EMPTY_ADDITIONAL_PARAMS;
  const paginationMode = params.paginationMode ?? 'append';

  const [data, setData] = useState<T[]>([]);
  const [error, setError] =
    useState<PaginatedApiHandlerDefinition<T, F>['error']>(null);
  const [loading, setLoading] = useState(enabled);
  const [canLoadMore, setCanLoadMore] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [searchText, setSearchText] = useState(params.initialSearchText ?? '');
  const [filters, setFilters] = useState<Partial<F>>(
    params.initialFilters ?? {}
  );
  const [refresh, setRefresh] = useState(false);
  const [response, setResponse] = useState<R | null>(null);

  const [trackedSearchText, setTrackedSearchText] = useState(searchText);
  const [trackedFilters, setTrackedFilters] = useState<Partial<F>>(filters);
  const [trackedAdditionalParams, setTrackedAdditionalParams] =
    useState<Record<string, unknown>>(additionalParams);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const querySignatureChanged =
    trackedSearchText !== searchText ||
    !shallowEqual(trackedFilters, filters) ||
    !shallowEqual(trackedAdditionalParams, additionalParams);

  useEffect(() => {
    if (!querySignatureChanged) {
      return;
    }

    abortControllerRef.current?.abort();
    requestGenerationRef.current += 1;

    setData([]);
    setError(null);
    setResponse(null);
    setCanLoadMore(false);
    setPage(1);
    setTrackedSearchText(searchText);
    setTrackedFilters(filters);
    setTrackedAdditionalParams(additionalParams);
  }, [querySignatureChanged, searchText, filters, additionalParams]);

  useEffect(() => {
    if (enabled) {
      return;
    }

    abortControllerRef.current?.abort();
    requestGenerationRef.current += 1;

    setData([]);
    setError(null);
    setResponse(null);
    setCanLoadMore(false);
    setIsLoadingMore(false);
    setLoading(false);
    setPage(1);
  }, [enabled]);

  const fetchPaginatedData = useCallback(async () => {
    if (!enabled) {
      return;
    }

    void refresh;

    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setError(null);

    if (page > 1) {
      setIsLoadingMore(true);
    } else {
      setLoading(true);
    }

    try {
      const result = await apiHandler.get<R, AxiosRequestConfig<R>>(url, {
        signal: controller.signal,
        params: {
          per_page: perPage,
          page,
          search_text: trackedSearchText,
          filters: trackedFilters,
          ...trackedAdditionalParams,
        },
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setData((previousData) => {
        if (paginationMode === 'replace') {
          return result.data;
        }

        return page === 1 ? result.data : [...previousData, ...result.data];
      });
      setCanLoadMore(result.meta.can_load_more);
      setResponse(result);
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance)) {
        return;
      }

      if (axios.isAxiosError<AxiosErrorDefinition>(errorInstance)) {
        const axiosResponse = errorInstance.response;

        if (!axiosResponse) {
          setError({ message: errorInstance.message });

          return;
        }

        if (axiosResponse.status === 401) {
          window.location.reload();

          return;
        }

        if (typeof axiosResponse.data?.message === 'string') {
          setError({ message: axiosResponse.data.message });
        } else {
          setError({ message: errorInstance.message });
        }

        return;
      }

      if (errorInstance instanceof Error) {
        setError({ message: errorInstance.message });

        return;
      }

      setError({ message: 'Unable to load data.' });
    } finally {
      if (
        requestGenerationRef.current === requestGeneration &&
        abortControllerRef.current === controller
      ) {
        abortControllerRef.current = null;
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
    paginationMode,
  ]);

  useEffect(() => {
    if (!querySignatureChanged) {
      void fetchPaginatedData();
    }

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchPaginatedData, querySignatureChanged]);

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
