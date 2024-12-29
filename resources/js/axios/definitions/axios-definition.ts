export default interface AxiosDefinition {
  /**
   * Handles get requests.
   *
   * @param url
   * @param config
   */
  get<T, C>(url: string, config?: Record<string, C>): Promise<T>;

  /**
   * handles post requests.
   *
   * @param url
   * @param data
   * @param config
   */
  post<T, C, D>(url: string, data: D, config?: Record<string, C>): Promise<T>;
}
