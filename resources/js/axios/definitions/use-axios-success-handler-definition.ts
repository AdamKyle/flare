export default interface UseAxiosSuccessHandlerDefinition<T> {
  onSuccess: (data: T) => void;
}
