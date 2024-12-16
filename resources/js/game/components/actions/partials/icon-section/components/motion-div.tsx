import { motion } from 'framer-motion';
import React, { ReactNode } from 'react';

import { motionLeftPositionHelper } from '../lib/motion-position-helper';
import MotionDevProps from './types/motion-dev-props';

export const MotionDiv = (props: MotionDevProps): ReactNode => {
  return (
    <motion.div
      initial={{
        x: window.innerWidth <= 900 ? 0 : -100,
        y: window.innerWidth <= 900 ? -50 : 0,
        opacity: 0,
      }}
      animate={{
        x: 0,
        y: 0,
        opacity: 1,
      }}
      exit={{
        x: window.innerWidth <= 900 ? 0 : -100,
        y: window.innerWidth <= 900 ? 0 : 0,
        opacity: 0,
      }}
      transition={{ duration: 0.5 }}
      style={{
        position: 'absolute',
        top: window.innerWidth <= 900 ? '5rem' : '0',
        left: motionLeftPositionHelper(),
        zIndex: 10,
      }}
    >
      {props.children}
    </motion.div>
  );
};
