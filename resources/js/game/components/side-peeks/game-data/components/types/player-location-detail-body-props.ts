export default interface PlayerLocationDetailBodyProps {
  location_id: number;
  on_open_map: (gameMapId: number) => void;
}
