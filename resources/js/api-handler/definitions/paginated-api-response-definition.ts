export interface PaginatedApiResponseDefinition<T> {
  data: T;
  meta: {
    can_load_more: boolean;
    pagination: {
      count: number;
      current_page: number;
      links: Record<string, string>;
      per_page: number;
      total: number;
      total_pages: number;
    };
  };
}
