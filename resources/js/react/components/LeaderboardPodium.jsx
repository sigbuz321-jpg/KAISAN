import React from 'react';
import { motion } from 'motion/react';

export function LeaderboardPodium({ entries = [] }) {
    if (!entries || entries.length === 0) {
        return null;
    }

    const rank1 = entries.find((e) => e.rank === 1);
    const rank2 = entries.find((e) => e.rank === 2);
    const rank3 = entries.find((e) => e.rank === 3);

    return (
        <div className="my-6 flex items-end justify-center gap-2 sm:gap-4 pt-6 px-2">
            {/* Juara 2 (Kiri) */}
            {rank2 && (
                <motion.div
                    initial={{ opacity: 0, y: 35, scale: 0.85 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    transition={{ type: 'spring', stiffness: 280, damping: 22, delay: 0.15 }}
                    className="flex flex-1 max-w-[110px] flex-col items-center text-center"
                >
                    <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-full border-2 border-slate-300 bg-surface text-sm font-bold text-fg shadow-xs">
                        {rank2.name.charAt(0).toUpperCase()}
                    </div>
                    <span className="w-full truncate text-xs font-semibold text-fg">{rank2.name}</span>
                    <span className="font-mono text-xs text-muted">{rank2.points} pt</span>
                    <div className="mt-2 flex h-20 w-full flex-col items-center justify-center rounded-t-lg border-t border-x border-slate-300 bg-gradient-to-b from-slate-100 to-slate-200/80 shadow-xs">
                        <span className="font-mono text-lg font-bold text-slate-700">2</span>
                    </div>
                </motion.div>
            )}

            {/* Juara 1 (Tengah - Tertinggi) */}
            {rank1 && (
                <motion.div
                    initial={{ opacity: 0, y: 50, scale: 0.75 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    transition={{ type: 'spring', stiffness: 320, damping: 18, delay: 0.3 }}
                    className="flex flex-1 max-w-[125px] flex-col items-center text-center z-10"
                >
                    <motion.span
                        animate={{ rotate: [-4, 4, -4], y: [0, -3, 0] }}
                        transition={{ repeat: Infinity, duration: 2.2, ease: 'easeInOut' }}
                        className="text-2xl"
                    >
                        👑
                    </motion.span>
                    <div className="mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 border-accent bg-accent-soft text-base font-bold text-accent-text shadow-sm ring-2 ring-accent/20">
                        {rank1.name.charAt(0).toUpperCase()}
                    </div>
                    <span className="w-full truncate text-sm font-bold text-fg">{rank1.name}</span>
                    <span className="font-mono text-xs font-semibold text-accent-text">{rank1.points} pt</span>
                    <div className="mt-2 flex h-28 w-full flex-col items-center justify-center rounded-t-lg border-t-2 border-x border-accent bg-gradient-to-b from-accent-soft via-amber-100/90 to-amber-200/70 shadow-sm">
                        <span className="font-mono text-2xl font-black text-accent-text">1</span>
                    </div>
                </motion.div>
            )}

            {/* Juara 3 (Kanan) */}
            {rank3 && (
                <motion.div
                    initial={{ opacity: 0, y: 30, scale: 0.9 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    transition={{ type: 'spring', stiffness: 260, damping: 24, delay: 0.05 }}
                    className="flex flex-1 max-w-[110px] flex-col items-center text-center"
                >
                    <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-full border-2 border-amber-600/40 bg-surface text-sm font-bold text-fg shadow-xs">
                        {rank3.name.charAt(0).toUpperCase()}
                    </div>
                    <span className="w-full truncate text-xs font-semibold text-fg">{rank3.name}</span>
                    <span className="font-mono text-xs text-muted">{rank3.points} pt</span>
                    <div className="mt-2 flex h-14 w-full flex-col items-center justify-center rounded-t-lg border-t border-x border-amber-300 bg-gradient-to-b from-amber-50 to-amber-100/80 shadow-xs">
                        <span className="font-mono text-base font-bold text-amber-900">3</span>
                    </div>
                </motion.div>
            )}
        </div>
    );
}
