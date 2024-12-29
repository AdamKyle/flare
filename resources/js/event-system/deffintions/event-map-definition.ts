export default interface EventMapDefinition {
  // Define an event type as a string with any type of payload.

  [eventType: string]: unknown;
}
