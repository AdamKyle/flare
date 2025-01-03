import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';

import AxiosDefinition from './definitions/axios-definition';

export default class ApiHandler implements AxiosDefinition {
  async get<T>(
    url: string,
    config: AxiosRequestConfig & { params?: object } = {}
  ): Promise<T> {
    this.setCsrfToken(config);
    const modifiedUrl = this.addApiPrefix(url);
    const response: AxiosResponse<T> = await axios.get<T>(modifiedUrl, config);
    return response.data;
  }

  async post<T, C, D>(
    url: string,
    data: D,
    config: AxiosRequestConfig & { params?: C } = {}
  ): Promise<T> {
    this.setCsrfToken(config);
    const modifiedUrl = this.addApiPrefix(url);
    const response: AxiosResponse<T> = await axios.post<T>(
      modifiedUrl,
      data,
      config
    );
    return response.data;
  }

  /**
   * Add `/api` prefix to the URL if it's not already there.
   *
   * @param url
   * @private
   */
  private addApiPrefix(url: string): string {
    if (!url.startsWith('/api')) {
      return `/api${url}`;
    }
    return url;
  }

  /**
   * Get the token from the meta tag,
   *
   * @private
   */
  private getCsrfToken() {
    const token = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');
    return token || '';
  }

  /**
   * Set additional config and the csrf token.
   *
   * @param config
   * @private
   */
  private setCsrfToken(config?: AxiosRequestConfig) {
    const csrfToken = this.getCsrfToken();
    if (csrfToken) {
      if (!config) {
        config = {};
      }
      config.headers = {
        ...config.headers,
        Accept: 'application/json',
        'X-CSRF-Token': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      };
    }
  }
}
