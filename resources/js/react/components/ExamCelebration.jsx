import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { soundEngine } from '../../sound-engine';

function createConfetti(count = 32) {
    const colors = ['#D97706', '#F59E0B', '#16A34A', '#22C55E', '#FBBF24', '#38BDF8', '#EC4899'];
    return Array.from({ length: count }, (_, i) => {
        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.4;
        const distance = 90 + Math.random() * 150;
        return {
            id: `ec-${i}-${Date.now()}`,
            x: Math.cos(angle) * distance,
            y: Math.sin(angle) * distance - 25,
            size: 6 + Math.random() * 8,
            color: colors[i % colors.length],
            rotation: (Math.random() - 0.5) * 720,
            duration: 0.9 + Math.random() * 0.5,
            delay: Math.random() * 0.1,
            shape: i % 2 === 0 ? 'rect' : 'circle',
        };
    });
}

export function ExamCelebration({ score = null }) {
    const targetScore = typeof score === 'number' ? score : parseFloat(score) || 0;
    const [displayScore, setDisplayScore] = useState(0);
    const [confetti, setConfetti] = useState([]);

    useEffect(() => {
        soundEngine.playExamSubmit();
        setConfetti(createConfetti(36));
        const timer = setTimeout(() => setConfetti([]), 2000);

        // Counter roll-up animation
        const duration = 850;
        const startTime = performance.now();

        const animateScore = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const currentVal = easeProgress * targetScore;

            setDisplayScore(currentVal);

            if (progress < 1) {
                requestAnimationFrame(animateScore);
            } else {
                setDisplayScore(targetScore);
            }
        };

        requestAnimationFrame(animateScore);

        return () => clearTimeout(timer);
    }, [targetScore]);

    const formattedScore = Number.isInteger(targetScore)
        ? Math.round(displayScore).toString()
        : displayScore.toFixed(1);

    const message =
        targetScore >= 85
            ? 'Hasil yang luar biasa! Pertahankan prestasimu 🌟'
            : targetScore >= 70
              ? 'Kerja bagus! Terus tingkatkan pemahamanmu 👍'
              : 'Tetap semangat! Jadikan latihan untuk persiapan lebih baik 💪';

    return (
        <div className="relative my-4 flex flex-col items-center">
            {/* Confetti container */}
            <div className="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                <AnimatePresence>
                    {confetti.map((p) => (
                        <motion.div
                            key={p.id}
                            initial={{ x: 0, y: 0, scale: 0, opacity: 1, rotate: 0 }}
                            animate={{
                                x: p.x,
                                y: p.y,
                                scale: [0, 1.3, 0.9, 0],
                                opacity: [1, 1, 0.7, 0],
                                rotate: p.rotation,
                            }}
                            exit={{ opacity: 0 }}
                            transition={{
                                duration: p.duration,
                                delay: p.delay,
                                ease: [0.15, 0.85, 0.35, 1],
                            }}
                            style={{
                                position: 'absolute',
                                width: p.shape === 'rect' ? p.size * 1.5 : p.size,
                                height: p.size,
                                backgroundColor: p.color,
                                borderRadius: p.shape === 'circle' ? '9999px' : '2px',
                            }}
                        />
                    ))}
                </AnimatePresence>
            </div>

            {/* Animated Score Box */}
            <motion.div
                initial={{ scale: 0.6, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                transition={{ type: 'spring', stiffness: 320, damping: 20 }}
                className="my-3 flex flex-col items-center justify-center rounded-2xl border-2 border-accent/30 bg-accent-soft px-8 py-5 shadow-xs"
            >
                <span className="text-xs font-semibold uppercase tracking-widest text-accent-text">
                    Nilai Akhir
                </span>
                <span className="mt-1 font-mono text-5xl font-black tracking-tight tabular-nums text-accent-text sm:text-6xl">
                    {formattedScore}
                </span>
            </motion.div>

            <motion.p
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
                className="mt-2 text-sm font-medium text-fg"
            >
                {message}
            </motion.p>
        </div>
    );
}
