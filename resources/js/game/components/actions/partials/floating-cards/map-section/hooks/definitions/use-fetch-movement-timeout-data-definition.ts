import MapMovementEventTimeoutDefinition from '../../../../../../map-section/websockets/event-data-definitions/map-movement-event-timeout-definition';

export default interface UseFetchMovementTimeoutDataDefinition {
  showTimerBar: boolean;
  canMove: boolean;
  lengthOfTime: number;
  handleEventData: (data: MapMovementEventTimeoutDefinition) => void;
}
