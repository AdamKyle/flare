export default interface EventMapDefinition {
  [eventType: string]: unknown | [unknown, unknown?];
}
