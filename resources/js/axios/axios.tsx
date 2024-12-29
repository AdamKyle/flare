import axios, { AxiosRequestConfig } from 'axios';

import AxiosDefinition from './definitions/axios-definition';

export default class Axios implements AxiosDefinition {
  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await axios.get<T>(url, config);
    return response.data;
  }

  async post<T, D>(
    url: string,
    data: D,
    config?: AxiosRequestConfig
  ): Promise<T> {
    const response = await axios.post<T>(url, data, config);
    return response.data;
  }
}
