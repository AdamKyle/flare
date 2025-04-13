import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

const UsePaginatedApiHandler = <T>(
  params: ApiParametersDefinitions,
  perPage = 10
): PaginatedApiHandlerDefinition<T> => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<T[]>([]);
  const [error, setError] =
    useState<PaginatedApiHandlerDefinition<T>['error']>(null);
  const [loading, setLoading] = useState(true);
  const [canLoadMore, setCanLoadMore] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [searchText, setSearchText] = useState('');
  const [refresh, setRefresh] = useState(false);

  const fetchPaginatedData = useCallback(async () => {
    if (page > 1) setIsLoadingMore(true);

    try {
      const result = await apiHandler.get<
        PaginatedApiResponseDefinition<T[]>,
        AxiosRequestConfig<PaginatedApiResponseDefinition<T[]>>
      >(url, {
        params: {
          per_page: perPage,
          page,
          search_text: searchText,
        },
      });

      setData((prev) => (page === 1 ? result.data : [...prev, ...result.data]));
      setCanLoadMore(result.meta.can_load_more);
    } catch (error) {
      if (error instanceof AxiosError) {
        setError(error.response?.data || null);
      } else {
        setError(null);
      }
    } finally {
      setLoading(false);
      setIsLoadingMore(false);
    }
  }, [apiHandler, url, page, perPage, refresh]);

  useEffect(() => {
    fetchPaginatedData().catch(console.error);
  }, [fetchPaginatedData]);

  useEffect(() => {
    setPage(1);
    setRefresh((prevValue) => !prevValue);
  }, [searchText]);

  const onEndReached = () => {
    if (!canLoadMore || isLoadingMore) {
      return;
    }

    setPage((prevValue) => prevValue + 1);
  };

  return {
    data,
    error,
    loading,
    canLoadMore,
    isLoadingMore,
    page,
    onEndReached,
    setSearchText,
    setPage,
  };
};

export default UsePaginatedApiHandler;
