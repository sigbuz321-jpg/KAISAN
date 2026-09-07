import React from 'react';
import { motion } from 'motion/react';

/**
 * A lightweight probe component verifying Motion for React is functioning.
 */
export function MotionProbe({ label = 'Motion Active' }) {
    return (
        <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ type: 'spring', stiffness: 300, damping: 25 }}
            className="inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-xs font-medium text-accent-text"
        >
            <motion.span
                animate={{ rotate: [0, 15, -15, 0] }}
                transition={{ repeat: Infinity, duration: 2, ease: 'easeInOut' }}
            >
                ✨
            </motion.span>
            <span>{label}</span>
        </motion.div>
    );
}
