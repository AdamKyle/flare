export default interface ApiErrorAlertProps {
  apiError: string;
  on_close?: () => void;
  closable?: boolean;
}
