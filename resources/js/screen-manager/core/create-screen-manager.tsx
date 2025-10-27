import { AnimatePresence, motion } from 'framer-motion';
import React, {
  Attributes,
  createContext,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import {
  ScreenMap,
  ScreenName,
  ScreenPropsOf,
  StackEntry,
  ScreenRegistry,
  ScreenNavigation,
} from './types';
import UseBindScreenParams from '../hooks/definitions/use-bind-screen-params';

const slideVariants = {
  hidden: { x: '100%', opacity: 0 },
  enter: {
    x: 0,
    opacity: 1,
    transition: {
      x: { type: 'tween', ease: 'easeOut', duration: 0.45 },
      opacity: { duration: 0.2 },
    },
  },
  exit: {
    x: 0,
    opacity: 0,
    transition: {
      opacity: { duration: 0.25, ease: 'easeIn' },
    },
  },
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

          const next = prev.slice(0, Math.max(0, prev.length - count));

          return next;
        });
      };

      const getTop = () => {
        if (stack.length === 0) {
          return null;
        }

        const top = stack[stack.length - 1];

        return top;
      };

      const getHidden = () => {
        if (stack.length <= 1) {
          return [];
        }

        const hidden = stack.slice(0, stack.length - 1);

        return hidden;
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

    const top = useMemo(() => {
      const t = navigation._getTop();

      return t;
    }, [navigation]);

    const wrapperClasses = () => {
      if (!top) {
        return 'w-full pointer-events-none';
      }

      return 'w-full relative z-20';
    };

    const renderResolved = <K extends ScreenName<TMap>>(
      name: K,
      props: ScreenPropsOf<TMap, K>
    ) => {
      const C = navigation._resolve(name) as React.ComponentType<unknown>;

      return React.createElement(C, props as Attributes);
    };

    const renderHiddenScreens = () => {
      const hidden = navigation._getHidden();

      if (hidden.length === 0) {
        return null;
      }

      return hidden.map((entry: StackEntry<TMap>) => {
        return (
          <div
            key={entry.key}
            className="absolute inset-0 opacity-0 pointer-events-none z-0"
            aria-hidden
          >
            {renderResolved(entry.name as never, entry.props as never)}
          </div>
        );
      });
    };

    const renderTopScreen = () => {
      if (!top) {
        return null;
      }

      return (
        <motion.div
          key={top.key}
          className="relative z-10"
          variants={slideVariants}
          initial="hidden"
          animate="enter"
          exit="exit"
        >
          {renderResolved(top.name as never, top.props as never)}
        </motion.div>
      );
    };

    return (
      <div className={wrapperClasses()} style={{ willChange: 'transform' }}>
        {renderHiddenScreens()}
        <AnimatePresence>{renderTopScreen()}</AnimatePresence>
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
