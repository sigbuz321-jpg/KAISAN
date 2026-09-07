import React from 'react';
import { motion } from 'motion/react';

const iconClass = 'h-5 w-5';

const ICONS = {
    book: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className={iconClass}>
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
        </svg>
    ),
    clipboard: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className={iconClass}>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
        </svg>
    ),
    user: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className={iconClass}>
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </svg>
    ),
    chevron: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5 shrink-0 text-muted">
            <polyline points="9 18 15 12 9 6" />
        </svg>
    ),
};

const TONE_BG = {
    accent: 'bg-accent-soft text-accent-text',
    info: 'bg-info-soft text-info-text',
    neutral: 'bg-surface-muted text-muted',
};

export function HomePillars({ items = [] }) {
    if (!items || items.length === 0) {
        return null;
    }

    return (
        <ul className="mt-3 space-y-3">
            {items.map((item, index) => (
                <motion.li
                    key={item.href}
                    initial={{ opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                        type: 'spring',
                        stiffness: 280,
                        damping: 24,
                        delay: 0.08 + index * 0.08,
                    }}
                >
                    <motion.a
                        href={item.href}
                        whileHover={{ y: -3 }}
                        whileTap={{ scale: 0.985 }}
                        className="flex items-center gap-3 rounded-lg border border-border bg-surface p-4 transition-shadow duration-150 hover:shadow-elevated focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        onClick={() => window.soundEngine?.playTap()}
                    >
                        <span
                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-md ${
                                TONE_BG[item.tone] || TONE_BG.neutral
                            }`}
                        >
                            {ICONS[item.icon] || null}
                        </span>

                        <span className="min-w-0 flex-1">
                            <span className="block text-base font-semibold text-fg">{item.label}</span>
                            <span className="mt-0.5 block text-xs leading-relaxed text-muted">{item.meta}</span>
                        </span>

                        {ICONS.chevron}
                    </motion.a>
                </motion.li>
            ))}
        </ul>
    );
}
