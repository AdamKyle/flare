export type ItemSelectedType = {
  mode: 'include' | 'all_except';
  ids?: number[];
  exclude?: number[];
};
