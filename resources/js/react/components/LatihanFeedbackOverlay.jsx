import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { soundEngine } from '../../sound-engine';

/**
 * Generate randomized confetti particles for celebratory bursts
 */
function createConfettiParticles(count = 28) {
    const colors = ['#D97706', '#F59E0B', '#16A34A', '#22C55E', '#FBBF24', '#38BDF8', '#818CF8'];
    return Array.from({ length: count }, (_, i) => {
        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.5;
        const distance = 80 + Math.random() * 140;
        return {
            id: `p-${i}-${Date.now()}`,
            x: Math.cos(angle) * distance,
            y: Math.sin(angle) * distance - 20,
            size: 6 + Math.random() * 8,
            color: colors[i % colors.length],
            rotation: (Math.random() - 0.5) * 720,
            duration: 0.8 + Math.random() * 0.5,
            delay: Math.random() * 0.08,
            shape: i % 3 === 0 ? 'circle' : 'rect',
        };
    });
}

export function LatihanFeedbackOverlay() {
    const [confetti, setConfetti] = useState([]);
    const [levelUpModal, setLevelUpModal] = useState(null);

    useEffect(() => {
        const handleFeedback = (e) => {
            const data = e.detail?.[0] || e.detail || {};
            const { benar, naikLevel, level } = data;

            if (benar) {
                soundEngine.playCorrect();
                setConfetti(createConfettiParticles(28));
                setTimeout(() => setConfetti([]), 1600);
            } else {
                soundEngine.playWrong();
            }

            if (naikLevel) {
                setTimeout(() => {
                    soundEngine.playLevelUp();
                    setLevelUpModal({ level });
                }, 400);
            }
        };

        const handleSelesai = () => {
            soundEngine.playExamSubmit();
            setConfetti(createConfettiParticles(36));
            setTimeout(() => setConfetti([]), 2000);
        };

        window.addEventListener('latihan-feedback', handleFeedback);
        window.addEventListener('latihan-selesai', handleSelesai);

        return () => {
            window.removeEventListener('latihan-feedback', handleFeedback);
            window.removeEventListener('latihan-selesai', handleSelesai);
        };
    }, []);

    return (
        <div className="pointer-events-none relative z-50">
            {/* Confetti Particle Burst Container */}
            <div className="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                <AnimatePresence>
                    {confetti.map((p) => (
                        <motion.div
                            key={p.id}
                            initial={{ x: 0, y: 0, scale: 0, opacity: 1, rotate: 0 }}
                            animate={{
                                x: p.x,
                                y: p.y,
                                scale: [0, 1.2, 0.9, 0],
                                opacity: [1, 1, 0.8, 0],
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

            {/* Level Up Celebratory Modal */}
            <AnimatePresence>
                {levelUpModal && (
                    <div className="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                        <motion.div
                            initial={{ scale: 0.7, opacity: 0, y: 30 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.8, opacity: 0, y: 20 }}
                            transition={{ type: 'spring', stiffness: 350, damping: 22 }}
                            className="w-full max-w-sm rounded-xl border border-accent/30 bg-surface p-6 text-center shadow-modal"
                        >
                            <motion.div
                                animate={{
                                    scale: [1, 1.18, 1],
                                    rotate: [0, -8, 8, 0],
                                }}
                                transition={{ duration: 0.7, delay: 0.15 }}
                                className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-accent-soft text-3xl"
                            >
                                🏆
                            </motion.div>

                            <motion.span
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2 }}
                                className="inline-block rounded-full bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-wider text-accent-text"
                            >
                                Naik Level!
                            </motion.span>

                            <motion.h3
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.25 }}
                                className="mt-3 text-xl font-bold tracking-tight text-fg"
                            >
                                Level {levelUpModal.level}
                            </motion.h3>

                            <motion.p
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ delay: 0.3 }}
                                className="mt-2 text-sm leading-relaxed text-muted"
                            >
                                Hebat! Kemampuanmu meningkat seiring ketepatan jawabanmu dalam latihan ini.
                            </motion.p>

                            <motion.button
                                whileHover={{ scale: 1.02 }}
                                whileTap={{ scale: 0.98 }}
                                onClick={() => setLevelUpModal(null)}
                                className="mt-6 w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-accent-fg shadow-sm transition-colors hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-accent"
                            >
                                Lanjutkan Latihan
                            </motion.button>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>
        </div>
    );
}
