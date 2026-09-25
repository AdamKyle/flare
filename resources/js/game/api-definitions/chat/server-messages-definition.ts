export default interface ServerMessagesDefinition {
  id: number | null;
  message: string;
  source: string | null;
  itemId: number | null;
  linkText: string | null;
  timeStamp: string;
}
