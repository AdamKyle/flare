export default interface EventMapDeffinition {
  // Define an event type as a string with any type of payload.

  [eventType: string]: unknown;
}
