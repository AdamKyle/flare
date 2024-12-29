import AxiosDefinition from './axios-definition';

export default interface ApiHandleContextDefinition {
  apiHandler: AxiosDefinition;
  getUrl: (urlEnum: string, params?: Record<string, number>) => string;
}
