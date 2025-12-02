export default interface UseManageAnnouncementDetailsVisibilityDefinition {
  announcementId: number | null;
  openAnnouncementDetails: (announcementId: number | null) => void;
  closeAnnouncementDetails: () => void;
}
