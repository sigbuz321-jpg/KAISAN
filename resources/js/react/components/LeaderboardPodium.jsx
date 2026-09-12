import React from 'react';
import { motion } from 'motion/react';

/**
 * The top three of a season, drawn as a podium.
 *
 * Nothing is drawn below three entries. A podium built for one student is a
 * ceremony for a race with one runner: it crowned the only participant and
 * then repeated their name in the row directly underneath. The list on its own
 * says everything true at that point.
 *
 * Colours come from the leaderboard tokens in resources/css/app.css, which were
 * written for this component ("rank 1 borrows the accent, 2 and 3 recede on
 * purpose") but had been bypassed with raw slate/amber utilities.
 */
const MINIMUM_ENTRIES = 3;

export function LeaderboardPodium({ entries = [] }) {
    if (!entries || entries.length < MINIMUM_ENTRIES) {
        return null;
    }

    const rank1 = entries.find((e) => e.rank === 1);
    const rank2 = entries.find((e) => e.rank === 2);
    const rank3 = entries.find((e) => e.rank === 3);

    if (!rank1 || !rank2 || !rank3) {
        return null;
    }

    return (
        <div className="my-6 flex items-end justify-center gap-2 px-2 pt-6 sm:gap-4">
            <Place entry={rank2} place={2} />
            <Place entry={rank1} place={1} />
            <Place entry={rank3} place={3} />
        </div>
    );
}

/** Per-place styling, kept in one table so the three columns cannot drift apart. */
const STYLES = {
    1: {
        column: 'max-w-[125px] z-10',
        avatar: 'h-12 w-12 border-master bg-master-soft text-master-text ring-2 ring-master/20 text-base',
        name: 'text-sm font-bold',
        points: 'text-master-text font-semibold',
        block: 'h-28 border-t-2 border-x border-master bg-master-soft',
        number: 'text-2xl font-black text-master-text',
        spring: { stiffness: 320, damping: 18, delay: 0.3 },
        rise: 50,
    },
    2: {
        column: 'max-w-[110px]',
        avatar: 'h-10 w-10 border-rank-2 bg-surface text-fg text-sm',
        name: 'text-xs font-semibold',
        points: 'text-muted',
        block: 'h-20 border-t border-x border-rank-2 bg-rank-2-soft',
        number: 'text-lg font-bold text-fg',
        spring: { stiffness: 280, damping: 22, delay: 0.15 },
        rise: 35,
    },
    3: {
        column: 'max-w-[110px]',
        avatar: 'h-10 w-10 border-rank-3 bg-surface text-fg text-sm',
        name: 'text-xs font-semibold',
        points: 'text-muted',
        block: 'h-14 border-t border-x border-rank-3 bg-rank-3-soft',
        number: 'text-base font-bold text-fg',
        spring: { stiffness: 260, damping: 24, delay: 0.05 },
        rise: 30,
    },
};

function Place({ entry, place }) {
    const s = STYLES[place];

    return (
        <motion.div
            initial={{ opacity: 0, y: s.rise, scale: 0.85 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ type: 'spring', ...s.spring }}
            className={`flex flex-1 flex-col items-center text-center ${s.column}`}
        >
            <div
                className={`mb-2 flex items-center justify-center rounded-full border-2 font-bold shadow-xs ${s.avatar}`}
            >
                {entry.name.charAt(0).toUpperCase()}
            </div>
            <span className={`w-full truncate text-fg ${s.name}`}>{entry.name}</span>
            <span className={`font-mono text-xs ${s.points}`}>{entry.points} poin</span>
            <div
                className={`mt-2 flex w-full flex-col items-center justify-center rounded-t-lg shadow-xs ${s.block}`}
            >
                <span className={`font-mono ${s.number}`}>{place}</span>
            </div>
        </motion.div>
    );
}
