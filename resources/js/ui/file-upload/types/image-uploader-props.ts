export default interface ImageUploaderProps {
  onFileChange?: (file: File | null) => void;
  onDelete?: () => void;
  initialImageUrl?: string | null;
  className?: string;
  deletable?: boolean;
}
