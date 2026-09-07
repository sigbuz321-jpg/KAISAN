import React, { useState, useEffect } from 'react';
import { motion } from 'motion/react';
import { soundEngine } from '../../sound-engine';

export function SoundToggle() {
    const [muted, setMuted] = useState(() => soundEngine.isMuted());

    useEffect(() => {
        const handleMutedChange = (e) => {
            setMuted(e.detail.muted);
        };

        window.addEventListener('kaisan:sound-muted-changed', handleMutedChange);
        return () => {
            window.removeEventListener('kaisan:sound-muted-changed', handleMutedChange);
        };
    }, []);

    const toggle = () => {
        soundEngine.toggleMute();
    };

    return (
        <motion.button
            type="button"
            onClick={toggle}
            whileHover={{ scale: 1.08 }}
            whileTap={{ scale: 0.92 }}
            aria-label={muted ? 'Aktifkan suara' : 'Bisukan suara'}
            title={muted ? 'Suara dimatikan (klik untuk aktifkan)' : 'Suara aktif (klik untuk matikan)'}
            className={`relative inline-flex h-9 w-9 items-center justify-center rounded-md border text-sm transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-accent ${
                muted
                    ? 'border-border bg-surface text-muted hover:bg-surface-muted hover:text-fg'
                    : 'border-accent-soft bg-accent-soft text-accent-text hover:bg-accent-soft/80'
            }`}
        >
            <motion.div
                key={muted ? 'muted' : 'active'}
                initial={{ scale: 0.7, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                transition={{ type: 'spring', stiffness: 400, damping: 20 }}
                className="flex items-center justify-center"
            >
                {muted ? (
                    /* Speaker Muted (Off) SVG */
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.75"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        className="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="M11 5L6 9H2v6h4l5 4V5z" />
                        <line x1="23" y1="9" x2="17" y2="15" />
                        <line x1="17" y1="9" x2="23" y2="15" />
                    </svg>
                ) : (
                    /* Speaker Active (On) SVG */
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.75"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        className="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="M11 5L6 9H2v6h4l5 4V5z" />
                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14" />
                    </svg>
                )}
            </motion.div>
        </motion.button>
    );
}
