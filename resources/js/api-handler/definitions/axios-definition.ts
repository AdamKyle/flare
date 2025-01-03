import { AxiosRequestConfig } from 'axios';

export default interface AxiosDefinition {
  /**
   * Handles GET requests.
   *
   * @param url - The endpoint to send the GET request to.
   * @param config - Optional Axios configuration, including query parameters.
   */
  get<T, C>(
    url: string,
    config?: AxiosRequestConfig & { params?: C }
  ): Promise<T>;

  /**
   * Handles POST requests.
   *
   * @param url - The endpoint to send the POST request to.
   * @param data - The data payload to include in the request body.
   * @param config - Optional Axios configuration, including query parameters.
   */
  post<T, C, D>(
    url: string,
    data: D,
    config?: AxiosRequestConfig & { params?: C }
  ): Promise<T>;
}
