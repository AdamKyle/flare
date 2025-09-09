export default interface AffixesSectionProps {
  prefix?: { id: number; name: string } | null;
  suffix?: { id: number; name: string } | null;
  onOpenAffix: (affixId: number) => void;
}
