import { AppScreenName, AppScreenPropsMap } from './screen-manager-props';

type IntentMap = Partial<{ [K in AppScreenName]: AppScreenPropsMap[K] }>;

let intents: IntentMap = {};

export function setScreenIntent<K extends AppScreenName>(
  name: K,
  props: AppScreenPropsMap[K]
): void {
  intents[name] = props;
}

export function consumeScreenIntent<K extends AppScreenName>(
  name: K
): AppScreenPropsMap[K] | undefined {
  const payload = intents[name] as AppScreenPropsMap[K] | undefined;
  if (payload) {
    delete intents[name];
  }
  return payload;
}
