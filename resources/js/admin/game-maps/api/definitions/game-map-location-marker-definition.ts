export default interface GameMapLocationMarkerDefinition {
  id: number;
  name: string;
  x: number;
  y: number;
  is_port: boolean;
  is_corrupted: boolean;
  pin_css_class: string | null;
}
