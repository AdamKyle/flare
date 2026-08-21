import clsx from 'clsx';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import React, {
  Attributes,
  createContext,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import UseBindScreenParams from './hooks/definitions/use-bind-screen-params';
import { ScreenNavigation } from './types/navigation-type';
import { ScreenRegistry } from './types/registery-type';
import { ScreenMap } from './types/screen-map-type';
import { ScreenName } from './types/screen-name-type';
import { ScreenPropsOf } from './types/screen-props-of-type';
import { StackEntry } from './types/stack-entry-type';
import { slideVariants } from './variants/animation-variants';

type ScreenAnimationState = 'enter' | 'inactive';

interface ScreenView<TMap extends ScreenMap> {
  entry: StackEntry<TMap>;
  is_top: boolean;
  animation_state: ScreenAnimationState;
}

const buildScreenView = <TMap extends ScreenMap>(
  entry: StackEntry<TMap>,
  topKey: string | null
): ScreenView<TMap> => {
  const is_top = entry.key === topKey;

  return {
    entry,
    is_top,
    animation_state: is_top ? 'enter' : 'inactive',
  };
};

const createScreenManager = <TMap extends ScreenMap>() => {
  const Ctx = createContext<ScreenNavigation<TMap> | null>(null);

  const ScreenManagerProvider = (props: {
    registry: ScreenRegistry<TMap>;
    children?: React.ReactNode;
  }) => {
    const [stack, setStack] = useState<StackEntry<TMap>[]>([]);
    const idRef = useRef(0);

    const nextKey = () => {
      idRef.current = idRef.current + 1;

      return `screen-${idRef.current}`;
    };

    const value = useMemo<
      ScreenNavigation<TMap>
    >((): ScreenNavigation<TMap> => {
      const navigateTo = <K extends ScreenName<TMap>>(
        name: K,
        p: ScreenPropsOf<TMap, K>
      ) => {
        setStack((prev) => {
          const next: StackEntry<TMap> = { name, props: p, key: nextKey() };

          return [...prev, next];
        });
      };

      const replaceWith = <K extends ScreenName<TMap>>(
        name: K,
        p: ScreenPropsOf<TMap, K>
      ) => {
        setStack((prev) => {
          if (prev.length === 0) {
            const next: StackEntry<TMap> = { name, props: p, key: nextKey() };

            return [next];
          }

          const copy = prev.slice(0, prev.length - 1);
          const next: StackEntry<TMap> = { name, props: p, key: nextKey() };

          return [...copy, next];
        });
      };

      const resetTo = <K extends ScreenName<TMap>>(
        name: K,
        p: ScreenPropsOf<TMap, K>
      ) => {
        const next: StackEntry<TMap> = { name, props: p, key: nextKey() };

        setStack([next]);
      };

      const pop = (count: number = 1) => {
        if (count < 1) {
          return;
        }

        setStack((prev) => {
          if (prev.length === 0) {
            return prev;
          }

          return prev.slice(0, Math.max(0, prev.length - count));
        });
      };

      const getTop = () => {
        if (stack.length === 0) {
          return null;
        }

        return stack[stack.length - 1];
      };

      const getHidden = () => {
        if (stack.length <= 1) {
          return [];
        }

        return stack.slice(0, stack.length - 1);
      };

      const resolve = <K extends ScreenName<TMap>>(name: K) => {
        return props.registry[name];
      };

      return {
        navigateTo,
        replaceWith,
        resetTo,
        pop,
        stackDepth: stack.length,
        _getTop: getTop,
        _getHidden: getHidden,
        _resolve: resolve,
      };
    }, [stack, props.registry]);

    const renderChildren = () => {
      if (!props.children) {
        return null;
      }

      return props.children;
    };

    return <Ctx.Provider value={value}>{renderChildren()}</Ctx.Provider>;
  };

  const useScreenNavigation = (): ScreenNavigation<TMap> => {
    const nav = useContext(Ctx);

    if (!nav) {
      throw new Error('ScreenManagerContext not found');
    }

    return nav;
  };

  const useBindScreen = <K extends ScreenName<TMap>>(
    params: UseBindScreenParams<TMap, K>
  ) => {
    const nav = useScreenNavigation();
    const activeRef = useRef(false);

    useEffect(() => {
      if (params.when && !activeRef.current) {
        activeRef.current = true;

        if (params.mode === 'replace') {
          nav.replaceWith(params.to, params.props());
        } else if (params.mode === 'reset') {
          nav.resetTo(params.to, params.props());
        } else {
          nav.navigateTo(params.to, params.props());
        }
      }

      if (!params.when && activeRef.current) {
        nav.pop();
        activeRef.current = false;
      }
    }, [params.when, params.to, nav, params]);
  };

  const ScreenHost = () => {
    const navigation = useScreenNavigation();
    const reduceMotion = useReducedMotion();
    const topRef = useRef<HTMLDivElement>(null);

    const top = useMemo(() => navigation._getTop(), [navigation]);

    const screenViews = useMemo<ScreenView<TMap>[]>(() => {
      const hidden = navigation._getHidden();
      const entries: StackEntry<TMap>[] = top ? [...hidden, top] : hidden;

      return entries.map((entry) => buildScreenView(entry, top?.key ?? null));
    }, [navigation, top]);

    useEffect(() => {
      topRef.current?.focus({ preventScroll: true });
    }, [top?.key]);

    const renderResolved = <K extends ScreenName<TMap>>(
      name: K,
      props: ScreenPropsOf<TMap, K>
    ) => {
      const component = navigation._resolve(
        name
      ) as React.ComponentType<unknown>;

      return React.createElement(component, props as Attributes);
    };

    const renderScreen = (screenView: ScreenView<TMap>) => {
      const { entry, is_top, animation_state } = screenView;

      return (
        <motion.div
          key={entry.key}
          ref={is_top ? topRef : undefined}
          tabIndex={is_top ? -1 : undefined}
          className={clsx('focus:outline-none', {
            'relative z-10': is_top,
            'pointer-events-none absolute inset-0 z-0 opacity-0': !is_top,
          })}
          variants={reduceMotion ? undefined : slideVariants}
          initial={reduceMotion ? false : 'hidden'}
          animate={reduceMotion ? undefined : animation_state}
          exit={reduceMotion ? undefined : 'exit'}
          transition={
            reduceMotion
              ? { duration: 0 }
              : {
                  x: { type: 'tween', duration: 0.25, ease: 'easeOut' },
                  opacity: { duration: 0.15 },
                }
          }
          aria-hidden={!is_top}
          inert={!is_top}
        >
          {renderResolved(entry.name as never, entry.props as never)}
        </motion.div>
      );
    };

    return (
      <div
        className={clsx({
          'pointer-events-none w-full': !top,
          'relative z-20 w-full': top,
        })}
        style={{ willChange: 'transform' }}
      >
        <AnimatePresence initial={false} mode="popLayout">
          {screenViews.map(renderScreen)}
        </AnimatePresence>
      </div>
    );
  };

  return {
    ScreenManagerProvider,
    useScreenNavigation,
    useBindScreen,
    ScreenHost,
  };
};

export default createScreenManager;
